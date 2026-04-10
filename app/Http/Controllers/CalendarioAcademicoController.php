<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CalendarioAcademicoController extends Controller
{
    /**
     * Devuelve las visibilidades permitidas según el perfil del usuario interno.
     */
    private function visibilidadesPorPerfil(string $profile): array
    {
        if ($profile === 'SuperAd') {
            return ['todos', 'interno', 'docentes', 'directivas', 'padres'];
        }

        if (str_starts_with($profile, 'DOC')) {
            return ['todos', 'interno', 'docentes'];
        }

        // ConvCor*, Ori, Sec*, Admin, Contab y cualquier otro interno
        return ['todos', 'interno', 'directivas'];
    }

    /**
     * Calcula ciclo y periodo actuales a partir de los días del calendario.
     * Ciclo = cuántos "día 1" han aparecido hasta hoy inclusive.
     * Periodo = ceil(ciclo / 7). Ciclo en periodo = ((ciclo-1) % 7) + 1.
     */
    private function infoCicloHoy(int $anio, string $hoy): array
    {
        $cicloActual = DB::table('calendario_academico')
            ->where('anio', $anio)
            ->where('fecha', '<=', $hoy)
            ->where('dia_ciclo', 1)
            ->count();

        if ($cicloActual === 0) {
            return ['ciclo' => null, 'cicloEnPeriodo' => null, 'periodo' => null];
        }

        return [
            'ciclo'          => $cicloActual,
            'cicloEnPeriodo' => (($cicloActual - 1) % 7) + 1,
            'periodo'        => (int) ceil($cicloActual / 7),
        ];
    }

    /**
     * Vista para usuarios internos autenticados.
     */
    public function index(Request $request)
    {
        $profile    = auth()->user()->PROFILE;
        $visibles   = $this->visibilidadesPorPerfil($profile);

        $mes  = (int) $request->input('mes',  now()->month);
        $anio = (int) $request->input('anio', now()->year);

        $inicio = Carbon::create($anio, $mes, 1)->startOfMonth();
        $fin    = $inicio->copy()->endOfMonth();

        // Todos los días del mes para la grilla (dia_ciclo siempre visible,
        // el evento solo se mostrará si su visibilidad está permitida)
        $diasMes = DB::table('calendario_academico')
            ->whereBetween('fecha', [$inicio->toDateString(), $fin->toDateString()])
            ->orderBy('fecha')
            ->get()
            ->map(function ($row) use ($visibles) {
                // Ocultar el texto del evento si no tiene permiso
                if ($row->evento && ! in_array($row->visibilidad, $visibles)) {
                    $row->evento      = null;
                    $row->visibilidad = null;
                }
                return $row;
            })
            ->keyBy('fecha');

        // Eventos próximos (30 días) visibles para este perfil
        $proximosEventos = DB::table('calendario_academico')
            ->where('fecha', '>=', now()->toDateString())
            ->where('fecha', '<=', now()->addDays(30)->toDateString())
            ->whereNotNull('evento')
            ->whereIn('visibilidad', $visibles)
            ->orderBy('fecha')
            ->get();

        $hoyStr = now()->toDateString();

        // Día académico de hoy
        $hoy = DB::table('calendario_academico')
            ->where('fecha', $hoyStr)
            ->first();

        // Próximo día académico (mañana o el siguiente hábil)
        $manana = DB::table('calendario_academico')
            ->where('fecha', '>', $hoyStr)
            ->where('dia_ciclo', '>', 0)
            ->orderBy('fecha')
            ->first();

        // Ciclo y periodo actuales
        $infoCiclo = $this->infoCicloHoy($anio, $hoyStr);

        return view('calendario.index', compact(
            'diasMes', 'proximosEventos', 'hoy', 'manana', 'infoCiclo',
            'mes', 'anio', 'inicio', 'visibles', 'profile'
        ));
    }

    /**
     * Guarda o actualiza el evento y visibilidad de una fecha.
     * Solo SuperAd / Admin.
     */
    public function guardarEvento(Request $request, string $fecha)
    {
        $request->validate([
            'evento'      => 'nullable|string|max:200',
            'visibilidad' => 'required|in:todos,interno,docentes,directivas,padres',
        ]);

        DB::table('calendario_academico')
            ->where('fecha', $fecha)
            ->update([
                'evento'      => $request->input('evento') ?: null,
                'visibilidad' => $request->input('visibilidad'),
            ]);

        return response()->json(['ok' => true]);
    }

    /**
     * Elimina el evento de una fecha (pone evento = null).
     * Solo SuperAd / Admin.
     */
    public function eliminarEvento(string $fecha)
    {
        DB::table('calendario_academico')
            ->where('fecha', $fecha)
            ->update(['evento' => null, 'visibilidad' => 'interno']);

        return response()->json(['ok' => true]);
    }

    /**
     * Vista de solo lectura para docentes.
     */
    public function docente(Request $request)
    {
        $visibles = ['todos', 'interno', 'docentes'];

        $mes  = (int) $request->input('mes',  now()->month);
        $anio = (int) $request->input('anio', now()->year);

        $inicio = Carbon::create($anio, $mes, 1)->startOfMonth();
        $fin    = $inicio->copy()->endOfMonth();

        $diasMes = DB::table('calendario_academico')
            ->whereBetween('fecha', [$inicio->toDateString(), $fin->toDateString()])
            ->orderBy('fecha')
            ->get()
            ->map(function ($row) use ($visibles) {
                if ($row->evento && ! in_array($row->visibilidad, $visibles)) {
                    $row->evento      = null;
                    $row->visibilidad = null;
                }
                return $row;
            })
            ->keyBy('fecha');

        $proximosEventos = DB::table('calendario_academico')
            ->where('fecha', '>=', now()->toDateString())
            ->where('fecha', '<=', now()->addDays(30)->toDateString())
            ->whereNotNull('evento')
            ->whereIn('visibilidad', $visibles)
            ->orderBy('fecha')
            ->get();

        $hoyStr = now()->toDateString();

        $hoy = DB::table('calendario_academico')
            ->where('fecha', $hoyStr)
            ->first();

        $manana = DB::table('calendario_academico')
            ->where('fecha', '>', $hoyStr)
            ->where('dia_ciclo', '>', 0)
            ->orderBy('fecha')
            ->first();

        $infoCiclo = $this->infoCicloHoy($anio, $hoyStr);

        return view('calendario.docente', compact(
            'diasMes', 'proximosEventos', 'hoy', 'manana', 'infoCiclo',
            'mes', 'anio', 'inicio'
        ));
    }

    /**
     * Vista para padres (autenticados por sesión).
     */
    public function padres(Request $request)
    {
        $visibles = ['todos', 'padres'];

        $mes  = (int) $request->input('mes',  now()->month);
        $anio = (int) $request->input('anio', now()->year);

        $inicio = Carbon::create($anio, $mes, 1)->startOfMonth();
        $fin    = $inicio->copy()->endOfMonth();

        $diasMes = DB::table('calendario_academico')
            ->whereBetween('fecha', [$inicio->toDateString(), $fin->toDateString()])
            ->orderBy('fecha')
            ->get()
            ->keyBy('fecha');

        $proximosEventos = DB::table('calendario_academico')
            ->where('fecha', '>=', now()->toDateString())
            ->where('fecha', '<=', now()->addDays(30)->toDateString())
            ->whereNotNull('evento')
            ->whereIn('visibilidad', $visibles)
            ->orderBy('fecha')
            ->get();

        $hoy = DB::table('calendario_academico')
            ->where('fecha', now()->toDateString())
            ->first();

        return view('calendario.padres', compact(
            'diasMes', 'proximosEventos', 'hoy', 'mes', 'anio', 'inicio'
        ));
    }
}
