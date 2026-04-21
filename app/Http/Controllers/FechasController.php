<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FechasController extends Controller
{
    // Horario fijo para la ventana de sustentación de recuperaciones.
    const RECUP_HORA_INICIO = '06:30:00';
    const RECUP_HORA_FIN    = '16:30:00';

    // Grupos de códigos con sus etiquetas
    public static function grupos(): array
    {
        return [
            'N' => ['label' => 'Ingreso de Notas por Docentes',    'icon' => '📝'],
            'O' => ['label' => 'Ingreso de Observaciones (Dir. Grupo)', 'icon' => '🗒️'],
            'V' => ['label' => 'Subir Salvavidas (Docentes)',      'icon' => '⬆️'],
            'D' => ['label' => 'Consulta de Derroteros',           'icon' => '🗺️'],
            'S' => ['label' => 'Consulta de Salvavidas',           'icon' => '🏊'],
            'B' => ['label' => 'Consulta de Boletines',            'icon' => '📋'],
            'F' => ['label' => 'Consulta de Notas Finales',        'icon' => '🎓'],
        ];
    }

    /**
     * Fecha (YYYY-MM-DD) de la sustentación de recuperaciones del período dado,
     * tomada del calendario académico. Se busca por evento que contenga
     * "SUSTENTACIÓN ... RECUPERACIONES" o "SUSTENTACIÓN ... DERROTERO" y se asigna
     * por orden cronológico al período correspondiente.
     */
    public static function fechaRecuperacion(int $periodo, ?int $anio = null): ?string
    {
        $anio ??= (int) date('Y');

        $fechas = DB::table('calendario_eventos')
            ->whereYear('fecha', $anio)
            ->where(function ($q) {
                $q->where('evento', 'like', '%SUSTENTACI%RECUPERACI%')
                  ->orWhere('evento', 'like', '%SUSTENTACI%DERROTERO%');
            })
            ->orderBy('fecha')
            ->pluck('fecha')
            ->unique()
            ->values();

        return $fechas[$periodo - 1] ?? null;
    }

    /**
     * Ventana activa de sustentación (06:30–16:30 del día del evento).
     */
    public static function recuperacionAbierta(int $periodo, ?int $anio = null): bool
    {
        $fecha = self::fechaRecuperacion($periodo, $anio);
        if (!$fecha) return false;

        $inicio = Carbon::parse($fecha . ' ' . self::RECUP_HORA_INICIO);
        $fin    = Carbon::parse($fecha . ' ' . self::RECUP_HORA_FIN);
        $now    = now();

        return $now >= $inicio && $now <= $fin;
    }

    // Verifica si un código está activo ahora mismo
    public static function estaActivo(string $codigo): bool
    {
        $now = now();
        return DB::table('FECHAS')
            ->where('CODIGO_FECHA', $codigo)
            ->where('INICIO', '<=', $now)
            ->where('FIN',    '>=', $now)
            ->exists();
    }

    // Retorna todos los códigos activos ahora mismo
    public static function codigosActivos(): array
    {
        $now = now();
        return DB::table('FECHAS')
            ->where('INICIO', '<=', $now)
            ->where('FIN',    '>=', $now)
            ->pluck('CODIGO_FECHA')
            ->toArray();
    }

    // Verifica si algún código del prefijo está activo (ej: 'D' → D1, D2, D3, D4)
    public static function prefixActivo(string $prefix): bool
    {
        $now = now();
        return DB::table('FECHAS')
            ->where('CODIGO_FECHA', 'like', $prefix . '%')
            ->where('INICIO', '<=', $now)
            ->where('FIN',    '>=', $now)
            ->exists();
    }

    public function index()
    {
        $fechas = DB::table('FECHAS')
            ->orderBy('CODIGO_FECHA')
            ->get()
            ->keyBy('CODIGO_FECHA');

        $grupos = self::grupos();

        return view('admin.fechas', compact('fechas', 'grupos'));
    }

    public function upsert(Request $request)
    {
        $request->validate([
            'CODIGO_FECHA' => 'required|max:10',
            'INICIO'       => 'required|date',
            'FIN'          => 'required|date|after_or_equal:INICIO',
        ], [
            'FIN.after_or_equal' => 'La fecha de fin debe ser igual o posterior al inicio.',
        ]);

        $codigo = strtoupper(trim($request->CODIGO_FECHA));
        $inicio = $request->INICIO;
        $fin    = $request->FIN;

        $existe = DB::table('FECHAS')->where('CODIGO_FECHA', $codigo)->exists();

        if ($existe) {
            DB::table('FECHAS')->where('CODIGO_FECHA', $codigo)
                ->update(['INICIO' => $inicio, 'FIN' => $fin]);
        } else {
            DB::table('FECHAS')->insert([
                'CODIGO_FECHA' => $codigo,
                'INICIO'       => $inicio,
                'FIN'          => $fin,
            ]);
        }

        return back()->with('success_fechas', "Fecha «{$codigo}» guardada correctamente.");
    }

    public function destroy(string $codigo)
    {
        DB::table('FECHAS')->where('CODIGO_FECHA', $codigo)->delete();
        return back()->with('success_fechas', "Fecha «{$codigo}» eliminada.");
    }
}
