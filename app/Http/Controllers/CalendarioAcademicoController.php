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

        if (str_starts_with($profile, 'DOC') || str_starts_with($profile, 'COR')) {
            return ['todos', 'interno', 'docentes'];
        }

        // ConvCor*, Ori, Sec*, Admin, Contab y cualquier otro interno
        return ['todos', 'interno', 'directivas'];
    }

    /**
     * Calcula ciclo y periodo actuales a partir de los días del calendario.
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
     * Carga los eventos del mes desde la tabla calendario_eventos,
     * filtrados por visibilidad del perfil, agrupados por fecha.
     */
    private function eventosPorFecha(string $desde, string $hasta, array $visibles): \Illuminate\Support\Collection
    {
        return DB::table('calendario_eventos')
            ->whereBetween('fecha', [$desde, $hasta])
            ->whereIn('visibilidad', $visibles)
            ->orderBy('fecha')
            ->orderBy('id')
            ->get()
            ->groupBy('fecha');
    }

    /**
     * Carga próximos eventos (30 días) con dia_ciclo desde join.
     */
    private function proximosEventos(array $visibles): \Illuminate\Support\Collection
    {
        return DB::table('calendario_eventos as ce')
            ->leftJoin('calendario_academico as ca', 'ca.fecha', '=', 'ce.fecha')
            ->where('ce.fecha', '>=', now()->toDateString())
            ->where('ce.fecha', '<=', now()->addDays(30)->toDateString())
            ->whereIn('ce.visibilidad', $visibles)
            ->orderBy('ce.fecha')
            ->orderBy('ce.id')
            ->select('ce.*', 'ca.dia_ciclo')
            ->get();
    }

    /**
     * Vista para usuarios internos autenticados.
     */
    public function index(Request $request)
    {
        $profile  = auth()->user()->PROFILE;
        $visibles = $this->visibilidadesPorPerfil($profile);

        $mes  = (int) $request->input('mes',  now()->month);
        $anio = (int) $request->input('anio', now()->year);

        $inicio = Carbon::create($anio, $mes, 1)->startOfMonth();
        $fin    = $inicio->copy()->endOfMonth();

        // Estructura del calendario (sin filtrado de eventos)
        $diasMes = DB::table('calendario_academico')
            ->whereBetween('fecha', [$inicio->toDateString(), $fin->toDateString()])
            ->orderBy('fecha')
            ->get()
            ->keyBy('fecha');

        // Eventos del mes filtrados por visibilidad, agrupados por fecha
        $eventosPorFecha = $this->eventosPorFecha(
            $inicio->toDateString(), $fin->toDateString(), $visibles
        );

        $proximosEventos = $this->proximosEventos($visibles);

        $hoyStr = now()->toDateString();

        $hoy = DB::table('calendario_academico')
            ->where('fecha', $hoyStr)
            ->first();

        $eventosHoy = DB::table('calendario_eventos')
            ->where('fecha', $hoyStr)
            ->whereIn('visibilidad', $visibles)
            ->orderBy('id')
            ->get();

        $manana = DB::table('calendario_academico')
            ->where('fecha', '>', $hoyStr)
            ->where('dia_ciclo', '>', 0)
            ->orderBy('fecha')
            ->first();

        $infoCiclo = $this->infoCicloHoy($anio, $hoyStr);
        $puedeEditar = in_array($profile, ['SuperAd', 'Admin']);

        return view('calendario.index', compact(
            'diasMes', 'eventosPorFecha', 'proximosEventos',
            'hoy', 'eventosHoy', 'manana', 'infoCiclo',
            'mes', 'anio', 'inicio', 'visibles', 'profile', 'puedeEditar'
        ));
    }

    /**
     * Crea un nuevo evento para una fecha.
     * Solo SuperAd / Admin.
     */
    public function crearEvento(Request $request, string $fecha)
    {
        $request->validate([
            'evento'      => 'required|string|max:200',
            'visibilidad' => 'required|in:todos,interno,docentes,directivas,padres',
        ]);

        // Asegurar que la fecha tenga registro en calendario_academico
        if (!DB::table('calendario_academico')->where('fecha', $fecha)->exists()) {
            DB::table('calendario_academico')->insert([
                'fecha'     => $fecha,
                'anio'      => (int) substr($fecha, 0, 4),
                'dia_ciclo' => 0,
            ]);
        }

        $id = DB::table('calendario_eventos')->insertGetId([
            'fecha'       => $fecha,
            'evento'      => $request->input('evento'),
            'visibilidad' => $request->input('visibilidad'),
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        return response()->json(['ok' => true, 'id' => $id]);
    }

    /**
     * Actualiza un evento existente por ID.
     * Solo SuperAd / Admin.
     */
    public function actualizarEvento(Request $request, int $id)
    {
        $request->validate([
            'evento'      => 'required|string|max:200',
            'visibilidad' => 'required|in:todos,interno,docentes,directivas,padres',
        ]);

        DB::table('calendario_eventos')
            ->where('id', $id)
            ->update([
                'evento'      => $request->input('evento'),
                'visibilidad' => $request->input('visibilidad'),
                'updated_at'  => now(),
            ]);

        return response()->json(['ok' => true]);
    }

    /**
     * Elimina un evento por ID.
     * Solo SuperAd / Admin.
     */
    public function eliminarEvento(int $id)
    {
        DB::table('calendario_eventos')->where('id', $id)->delete();
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
            ->keyBy('fecha');

        $eventosPorFecha = $this->eventosPorFecha(
            $inicio->toDateString(), $fin->toDateString(), $visibles
        );

        $proximosEventos = $this->proximosEventos($visibles);

        $hoyStr = now()->toDateString();

        $hoy = DB::table('calendario_academico')
            ->where('fecha', $hoyStr)
            ->first();

        $eventosHoy = DB::table('calendario_eventos')
            ->where('fecha', $hoyStr)
            ->whereIn('visibilidad', $visibles)
            ->orderBy('id')
            ->get();

        $manana = DB::table('calendario_academico')
            ->where('fecha', '>', $hoyStr)
            ->where('dia_ciclo', '>', 0)
            ->orderBy('fecha')
            ->first();

        $infoCiclo = $this->infoCicloHoy($anio, $hoyStr);

        return view('calendario.docente', compact(
            'diasMes', 'eventosPorFecha', 'proximosEventos',
            'hoy', 'eventosHoy', 'manana', 'infoCiclo',
            'mes', 'anio', 'inicio'
        ));
    }

    /**
     * Vista para padres (autenticados por sesión).
     */
    public function padres(Request $request)
    {
        $estudiante = session('padre_estudiante');
        if (!$estudiante) return redirect()->route('padres.portal');

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

        $eventosPorFecha = $this->eventosPorFecha(
            $inicio->toDateString(), $fin->toDateString(), $visibles
        );

        $proximosEventos = $this->proximosEventos($visibles);

        $hoy = DB::table('calendario_academico')
            ->where('fecha', now()->toDateString())
            ->first();

        $eventosHoy = DB::table('calendario_eventos')
            ->where('fecha', now()->toDateString())
            ->whereIn('visibilidad', $visibles)
            ->orderBy('id')
            ->get();

        return view('calendario.padres', compact(
            'diasMes', 'eventosPorFecha', 'proximosEventos',
            'hoy', 'eventosHoy', 'mes', 'anio', 'inicio'
        ));
    }
}
