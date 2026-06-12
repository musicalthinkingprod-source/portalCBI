<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InformeAnualController extends Controller
{
    private const ANIOS_DISPONIBLES = [2024, 2025];

    // Igual que el certificado de notas: Acompañamiento de Padres (32, 50, 51),
    // Conducta (33) y Disciplina (34) no aparecen en el informe.
    private const MAT_EXCLUIDAS = [32, 33, 34, 50, 51];

    public const APROBADO_MIN = 7.0;

    public function buscar(Request $request)
    {
        $anios   = self::ANIOS_DISPONIBLES;
        $defecto = $anios[count($anios) - 1];
        $anio    = (int) $request->input('anio', $defecto);
        if (!in_array($anio, $anios, true)) {
            $anio = $defecto;
        }

        $q           = trim((string) $request->input('q', ''));
        $estudiantes = collect();

        if (strlen($q) >= 2) {
            $cursoCol = "CURSO_{$anio}";

            $estudiantes = DB::table('ESTUDIANTES as e')
                ->leftJoin('ESTUDIANTES_CURSOS_ANUAL as eca', 'eca.CODIGO_ALUM', '=', 'e.CODIGO')
                ->where(function ($w) use ($q) {
                    $w->where('e.NOMBRE1',   'like', "%{$q}%")
                      ->orWhere('e.NOMBRE2',   'like', "%{$q}%")
                      ->orWhere('e.APELLIDO1', 'like', "%{$q}%")
                      ->orWhere('e.APELLIDO2', 'like', "%{$q}%")
                      ->orWhere('e.CODIGO',    'like', "%{$q}%");
                })
                ->select(
                    'e.CODIGO', 'e.NOMBRE1', 'e.NOMBRE2',
                    'e.APELLIDO1', 'e.APELLIDO2',
                    "eca.{$cursoCol} as CURSO_ANIO"
                )
                ->orderBy('e.APELLIDO1')->orderBy('e.APELLIDO2')->orderBy('e.NOMBRE1')
                ->get()
                ->filter(fn($e) => !empty($e->CURSO_ANIO))
                ->values();
        }

        $aniosDisponibles = self::ANIOS_DISPONIBLES;
        return view('informes-anuales.buscar', compact('estudiantes', 'anio', 'q', 'aniosDisponibles'));
    }

    public function ver(int $codigo, Request $request)
    {
        $datos = $this->datosInforme(
            $codigo,
            (int) $request->input('anio', 0),
            $request->input('director'),
            $request->input('obs')
        );
        if (empty($datos)) abort(404);

        return view('informes-anuales.ver', $datos);
    }

    private function datosInforme(int $codigo, int $anio, ?string $directorInput, ?string $obsInput): array
    {
        if (!in_array($anio, self::ANIOS_DISPONIBLES, true)) return [];

        $estudiante = DB::table('ESTUDIANTES')->where('CODIGO', $codigo)->first();
        if (!$estudiante) return [];

        $eca       = DB::table('ESTUDIANTES_CURSOS_ANUAL')->where('CODIGO_ALUM', $codigo)->first();
        $cursoAnio = $eca?->{"CURSO_{$anio}"} ?? null;
        if (!$cursoAnio) return [];

        $notasRaw = DB::table("NOTAS_{$anio} as n")
            ->join('CODIGOSMAT as m', 'm.CODIGO_MAT', '=', 'n.CODIGO_MAT')
            ->leftJoin('CODIGOSAREA as a', 'a.CODIGO_AREA', '=', 'm.AREA_MAT')
            ->where('n.CODIGO_ALUM', $codigo)
            ->whereNotIn('n.CODIGO_MAT', self::MAT_EXCLUIDAS)
            ->whereIn('n.TIPODENOTA', ['N', 'R'])
            ->select('n.CODIGO_MAT', 'm.NOMBRE_MAT', 'm.AREA_MAT', 'a.NOMBRE_AREA', 'n.PERIODO', 'n.NOTA', 'n.TIPODENOTA')
            ->orderBy('n.CODIGO_MAT')->orderBy('n.PERIODO')
            ->get();

        // Las notas se llevan a precisión simple (FLOAT de MySQL) antes de
        // promediar, para reproducir los mismos redondeos del constructor
        // antiguo (ej. 7.5+9.7+9.7+9.3 → 9.04999995 → 9.0, no 9.05 → 9.1).
        $f32 = static fn(float $v): float => unpack('f', pack('f', $v))[1];

        // Materias en orden de CODIGO_MAT. Si un periodo tiene nota normal y de
        // recuperación, la 'R' es la definitiva del periodo.
        $materias = [];
        foreach ($notasRaw as $r) {
            $mk = $r->CODIGO_MAT;
            if (!isset($materias[$mk])) {
                $materias[$mk] = [
                    'nombre'     => $r->NOMBRE_MAT,
                    'area'       => $r->AREA_MAT,
                    'nombreArea' => $r->NOMBRE_AREA,
                    'periodos'   => [],
                ];
            }
            $p = (int) $r->PERIODO;
            if (!isset($materias[$mk]['periodos'][$p]) || $r->TIPODENOTA === 'R') {
                $materias[$mk]['periodos'][$p] = $f32((float) $r->NOTA);
            }
        }
        if (empty($materias)) return [];

        foreach ($materias as &$mat) {
            $mat['acumRaw']   = array_sum($mat['periodos']) / count($mat['periodos']);
            $mat['acumulado'] = round($mat['acumRaw'], 1);
        }
        unset($mat);

        // Promedio por área: promedio de los acumulados (sin redondear) de sus
        // materias, en orden de CODIGO_AREA.
        $porArea = [];
        foreach ($materias as $mat) {
            if ($mat['area'] === null) continue;
            $ak = (int) $mat['area'];
            if (!isset($porArea[$ak])) {
                $porArea[$ak] = ['nombre' => $mat['nombreArea'] ?: "Área {$ak}", 'acums' => []];
            }
            $porArea[$ak]['acums'][] = $mat['acumRaw'];
        }
        ksort($porArea);

        $areas = [];
        foreach ($porArea as $a) {
            $areas[] = [
                'nombre'   => $a['nombre'],
                'promedio' => round(array_sum($a['acums']) / count($a['acums']), 1),
            ];
        }

        // El director de grupo histórico no está registrado: se prellena con el
        // docente que hoy tiene ese curso como dirección y queda editable.
        $directorDefault = DB::table('CODIGOS_DOC')->where('DIR_GRUPO', $cursoAnio)->value('NOMBRE_DOC');
        $director        = $directorInput !== null ? trim($directorInput) : (string) ($directorDefault ?? '');
        $observaciones   = trim((string) $obsInput);

        $nombreCompleto = trim(implode(' ', array_filter([
            $estudiante->NOMBRE1 ?? null,
            $estudiante->NOMBRE2 ?? null,
            $estudiante->APELLIDO1 ?? null,
            $estudiante->APELLIDO2 ?? null,
        ])));

        return compact(
            'estudiante', 'nombreCompleto', 'cursoAnio', 'anio',
            'materias', 'areas', 'director', 'observaciones', 'codigo'
        );
    }
}
