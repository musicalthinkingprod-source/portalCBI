<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ControlFechasController extends Controller
{
    // ── Definición canónica de etapas ────────────────────────────────────────
    public static function etapas(): array
    {
        return [
            'caract'      => ['label' => 'Caracterizaciones',  'desc' => 'Docentes diligencian la caracterización por materia y el director de grupo la suya.', 'rol' => 'Docentes'],
            'ajustes'     => ['label' => 'Ajustes razonables', 'desc' => 'Docentes completan los ajustes razonables y evaluación por período.', 'rol' => 'Docentes'],
            'plan_casero' => ['label' => 'Plan Casero',        'desc' => 'Docentes registran las estrategias y frecuencia del plan de trabajo en casa.', 'rol' => 'Docentes'],
            'evaluacion'  => ['label' => 'Evaluación',         'desc' => 'Orientadores registran la evaluación del PIAR al cierre de cada período.', 'rol' => 'Orientadores'],
        ];
    }

    // ── Estados posibles ─────────────────────────────────────────────────────
    public static function estados(): array
    {
        return [
            'cerrado'    => ['label' => 'Cerrado',     'color' => 'gray'],
            'abierto'    => ['label' => 'Abierto',     'color' => 'green'],
            'revision'   => ['label' => 'En revisión', 'color' => 'blue'],
            'finalizado' => ['label' => 'Finalizado',  'color' => 'purple'],
        ];
    }

    // ── Recupera estados globales para el año dado ───────────────────────────
    // Retorna array indexado: 'etapa_key' => estado
    public static function etapasDelAnio(int $anio): array
    {
        return DB::table('piar_etapas_control')
            ->where('anio', $anio)
            ->where('periodo', 0)
            ->get(['etapa_key', 'estado'])
            ->mapWithKeys(fn($r) => [$r->etapa_key => $r->estado])
            ->toArray();
    }

    // ── Estado de una etapa ──────────────────────────────────────────────────
    public static function estadoEtapa(string $etapaKey): string
    {
        return self::etapasDelAnio((int) date('Y'))[$etapaKey] ?? 'cerrado';
    }

    // ── Período activo (1-4) para los ajustes ────────────────────────────────
    public static function periodoActivo(): int
    {
        $val = DB::table('piar_etapas_control')
            ->where('anio', (int) date('Y'))
            ->where('periodo', 0)
            ->where('etapa_key', 'periodo_activo')
            ->value('estado');
        return in_array((int) $val, [1, 2, 3, 4]) ? (int) $val : 1;
    }

    // Compatibilidad: devuelve array vacío (ya no se usan fechas límite)
    public static function fechasDelAnio(int $anio): array
    {
        return [];
    }

    // ── Panel de control ─────────────────────────────────────────────────────
    public function index()
    {
        $anio         = (int) date('Y');
        $etapas       = self::etapas();
        $grid         = self::etapasDelAnio($anio);
        $periodoActivo = self::periodoActivo();

        return view('control.piar-etapas', compact('etapas', 'grid', 'anio', 'periodoActivo'));
    }

    // ── Guardar estado de todas las etapas ────────────────────────────────────
    public function guardar(Request $request)
    {
        $anio    = (int) date('Y');
        $etapas  = self::etapas();
        $validos = array_keys(self::estados());

        foreach ($etapas as $key => $info) {
            $input  = $request->input($key, 'cerrado');
            $estado = in_array($input, $validos) ? $input : 'cerrado';

            DB::table('piar_etapas_control')->updateOrInsert(
                ['anio' => $anio, 'periodo' => 0, 'etapa_key' => $key],
                ['estado' => $estado, 'updated_at' => now(), 'created_at' => now()]
            );
        }

        // Guardar período activo
        $pActivo = in_array((int) $request->input('periodo_activo'), [1,2,3,4])
            ? (int) $request->input('periodo_activo') : 1;
        DB::table('piar_etapas_control')->updateOrInsert(
            ['anio' => $anio, 'periodo' => 0, 'etapa_key' => 'periodo_activo'],
            ['estado' => (string) $pActivo, 'updated_at' => now(), 'created_at' => now()]
        );

        return back()->with('saved', 'Control de etapas PIAR guardado correctamente.');
    }
}
