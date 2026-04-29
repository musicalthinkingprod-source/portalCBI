<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\FechasController;

class NotasController extends Controller
{
    private function tablaNotas(): string
    {
        return 'NOTAS_' . date('Y');
    }

    public function reporte()
    {
        $tabla = $this->tablaNotas();

        // Todos los docentes con asignaciones calificables (activos e inactivos)
        $asignaciones = DB::table('ASIGNACION_PCM as a')
            ->join('CODIGOSMAT as m', 'a.CODIGO_MAT', '=', 'm.CODIGO_MAT')
            ->leftJoin('CODIGOS_DOC as d', 'a.CODIGO_EMP', '=', 'd.CODIGO_EMP')
            ->where('a.calificable', 1)
            ->select('a.CODIGO_EMP', 'a.CODIGO_MAT', 'a.CURSO', 'm.NOMBRE_MAT',
                     'd.NOMBRE_DOC', 'd.ESTADO as ESTADO_DOC')
            ->orderByRaw("CASE WHEN d.ESTADO = 'ACTIVO' OR d.ESTADO IS NULL THEN 0 ELSE 1 END")
            ->orderBy('d.NOMBRE_DOC')
            ->orderBy('a.CURSO')
            ->get();

        // Estudiantes MATRICULADOS por curso (normales)
        $estPorCurso = DB::table('ESTUDIANTES')
            ->where('ESTADO', 'MATRICULADO')
            ->select('CURSO', DB::raw('COUNT(*) as total'))
            ->groupBy('CURSO')
            ->pluck('total', 'CURSO');

        // Conteo por grupo en LISTADOS_ESPECIALES (GP*, sufijos -1/-2)
        $conteosLE = DB::table('LISTADOS_ESPECIALES as le')
            ->join('ESTUDIANTES as e', 'le.CODIGO_ALUM', '=', 'e.CODIGO')
            ->where('e.ESTADO', 'MATRICULADO')
            ->select('le.GRUPO', DB::raw('COUNT(*) as total'))
            ->groupBy('le.GRUPO')
            ->pluck('total', 'GRUPO');
        $estPorCurso = $estPorCurso->union($conteosLE);

        // Notas ingresadas para materias normales (ASIGNACION.CURSO = ESTUDIANTE.CURSO)
        $notasConteo = [];
        try {
            $rows = DB::table($tabla . ' as n')
                ->join('ESTUDIANTES as e', function ($join) {
                    $join->on('e.CODIGO', '=', 'n.CODIGO_ALUM')
                         ->where('e.ESTADO', '=', 'MATRICULADO');
                })
                ->join('ASIGNACION_PCM as a', function ($join) {
                    $join->on('a.CODIGO_EMP', '=', 'n.CODIGO_EMP')
                         ->on('a.CODIGO_MAT', '=', 'n.CODIGO_MAT')
                         ->on('a.CURSO',      '=', 'e.CURSO');
                })
                ->where('a.calificable', 1)
                ->whereNotIn('n.CODIGO_MAT', [25, 26, 31])
                ->select('n.CODIGO_EMP', 'n.CODIGO_MAT', 'a.CURSO', 'n.PERIODO',
                         DB::raw('COUNT(DISTINCT n.CODIGO_ALUM) as total'))
                ->groupBy('n.CODIGO_EMP', 'n.CODIGO_MAT', 'a.CURSO', 'n.PERIODO')
                ->get();

            foreach ($rows as $r) {
                $notasConteo[$r->CODIGO_EMP][$r->CODIGO_MAT][$r->CURSO][$r->PERIODO] = $r->total;
            }

            // Notas de Proyectos (31): join vía LISTADOS_ESPECIALES → a.CURSO = le.GRUPO
            $rowsProy = DB::table($tabla . ' as n')
                ->join('ESTUDIANTES as e', fn($j) =>
                    $j->on('e.CODIGO', '=', 'n.CODIGO_ALUM')->where('e.ESTADO', 'MATRICULADO'))
                ->join('LISTADOS_ESPECIALES as le', 'le.CODIGO_ALUM', '=', 'n.CODIGO_ALUM')
                ->join('ASIGNACION_PCM as a', fn($j) =>
                    $j->on('a.CODIGO_EMP', '=', 'n.CODIGO_EMP')
                      ->on('a.CODIGO_MAT', '=', 'n.CODIGO_MAT')
                      ->on('a.CURSO',      '=', 'le.GRUPO'))
                ->where('a.calificable', 1)
                ->where('n.CODIGO_MAT', 31)
                ->select('n.CODIGO_EMP', 'n.CODIGO_MAT', 'a.CURSO', 'n.PERIODO',
                         DB::raw('COUNT(DISTINCT n.CODIGO_ALUM) as total'))
                ->groupBy('n.CODIGO_EMP', 'n.CODIGO_MAT', 'a.CURSO', 'n.PERIODO')
                ->get();

            foreach ($rowsProy as $r) {
                $notasConteo[$r->CODIGO_EMP][$r->CODIGO_MAT][$r->CURSO][$r->PERIODO] = $r->total;
            }

            // Música (26) y Artes (25) con listado especial: a.CURSO ya trae sufijo -1/-2
            // y coincide directamente con le.GRUPO (ej. 7A-1, 11B-2)
            $rowsMusArt = DB::table($tabla . ' as n')
                ->join('ESTUDIANTES as e', fn($j) =>
                    $j->on('e.CODIGO', '=', 'n.CODIGO_ALUM')->where('e.ESTADO', 'MATRICULADO'))
                ->join('LISTADOS_ESPECIALES as le', 'le.CODIGO_ALUM', '=', 'n.CODIGO_ALUM')
                ->join('ASIGNACION_PCM as a', fn($j) =>
                    $j->on('a.CODIGO_EMP', '=', 'n.CODIGO_EMP')
                      ->on('a.CODIGO_MAT', '=', 'n.CODIGO_MAT')
                      ->on('a.CURSO',      '=', 'le.GRUPO'))
                ->where('a.calificable', 1)
                ->whereIn('n.CODIGO_MAT', [25, 26])
                ->whereRaw("a.CURSO REGEXP '-[12]$'")
                ->select('n.CODIGO_EMP', 'n.CODIGO_MAT', 'a.CURSO', 'n.PERIODO',
                         DB::raw('COUNT(DISTINCT n.CODIGO_ALUM) as total'))
                ->groupBy('n.CODIGO_EMP', 'n.CODIGO_MAT', 'a.CURSO', 'n.PERIODO')
                ->get();

            foreach ($rowsMusArt as $r) {
                $notasConteo[$r->CODIGO_EMP][$r->CODIGO_MAT][$r->CURSO][$r->PERIODO] = $r->total;
            }

            // Música/Artes en cursos base sin dividir (grados 1-5 y 6° cuando no se divide)
            $rowsMusArtBajos = DB::table($tabla . ' as n')
                ->join('ESTUDIANTES as e', fn($j) =>
                    $j->on('e.CODIGO', '=', 'n.CODIGO_ALUM')->where('e.ESTADO', 'MATRICULADO'))
                ->join('ASIGNACION_PCM as a', fn($j) =>
                    $j->on('a.CODIGO_EMP', '=', 'n.CODIGO_EMP')
                      ->on('a.CODIGO_MAT', '=', 'n.CODIGO_MAT')
                      ->on('a.CURSO',      '=', 'e.CURSO'))
                ->where('a.calificable', 1)
                ->whereIn('n.CODIGO_MAT', [25, 26])
                ->whereRaw("a.CURSO NOT REGEXP '-[12]$'")
                ->select('n.CODIGO_EMP', 'n.CODIGO_MAT', 'a.CURSO', 'n.PERIODO',
                         DB::raw('COUNT(DISTINCT n.CODIGO_ALUM) as total'))
                ->groupBy('n.CODIGO_EMP', 'n.CODIGO_MAT', 'a.CURSO', 'n.PERIODO')
                ->get();

            foreach ($rowsMusArtBajos as $r) {
                $notasConteo[$r->CODIGO_EMP][$r->CODIGO_MAT][$r->CURSO][$r->PERIODO] = $r->total;
            }
        } catch (\Exception $e) {
            // tabla del año podría no existir
        }

        // Construir reporte por docente
        $docentes = [];
        foreach ($asignaciones as $a) {
            $doc = $a->CODIGO_EMP;

            if (!isset($docentes[$doc])) {
                $docentes[$doc] = [
                    'codigo'       => $doc,
                    'nombre'       => $a->NOMBRE_DOC ?? $doc,
                    'estado'       => $a->ESTADO_DOC ?? 'ACTIVO',
                    'periodos'     => array_fill(1, 4, ['esperadas' => 0, 'ingresadas' => 0]),
                    'asignaciones' => [],
                ];
            }

            $grupoKey = $this->grupoListado((int) $a->CODIGO_MAT, $a->CURSO) ?? $a->CURSO;
            $totalEst = $estPorCurso[$grupoKey] ?? 0;
            $detalle  = ['materia' => $a->NOMBRE_MAT, 'curso' => $a->CURSO, 'estudiantes' => $totalEst, 'periodos' => []];

            for ($p = 1; $p <= 4; $p++) {
                $ing = $notasConteo[$doc][$a->CODIGO_MAT][$a->CURSO][$p] ?? 0;
                $docentes[$doc]['periodos'][$p]['esperadas']  += $totalEst;
                $docentes[$doc]['periodos'][$p]['ingresadas'] += $ing;
                $detalle['periodos'][$p] = ['esperadas' => $totalEst, 'ingresadas' => $ing];
            }

            $docentes[$doc]['asignaciones'][] = $detalle;
        }

        // ── Observaciones 2026 por director de grupo ─────────────────────────
        $obsConteo = [];
        try {
            $rowsObs = DB::table('OBSERVACIONES_2026 as o')
                ->join('ESTUDIANTES as e', 'e.CODIGO', '=', 'o.CODIGO_ALUM')
                ->where('e.ESTADO', 'MATRICULADO')
                ->select('e.CURSO', 'o.PERIODO', DB::raw('COUNT(*) as total'))
                ->groupBy('e.CURSO', 'o.PERIODO')
                ->get();
            foreach ($rowsObs as $r) {
                $obsConteo[$r->CURSO][$r->PERIODO] = $r->total;
            }
        } catch (\Exception $e) {}

        $observacionesReport = [];
        $directoresGrupo = DB::table('CODIGOS_DOC')
            ->whereNotNull('DIR_GRUPO')
            ->orderBy('DIR_GRUPO')
            ->get();

        foreach ($directoresGrupo as $dir) {
            $totalEst = $estPorCurso[$dir->DIR_GRUPO] ?? 0;
            $periodos = [];
            for ($p = 1; $p <= 4; $p++) {
                $periodos[$p] = [
                    'esperadas'  => $totalEst,
                    'ingresadas' => $obsConteo[$dir->DIR_GRUPO][$p] ?? 0,
                ];
            }
            $observacionesReport[] = [
                'codigo'  => $dir->CODIGO_EMP,
                'nombre'  => $dir->NOMBRE_DOC ?? $dir->CODIGO_EMP,
                'estado'  => $dir->ESTADO ?? 'ACTIVO',
                'curso'   => $dir->DIR_GRUPO,
                'periodos'=> $periodos,
            ];
        }

        $anio = date('Y');
        return view('notas.reporte', compact('docentes', 'anio', 'observacionesReport'));
    }

    public function index(Request $request)
    {
        $profile    = auth()->user()->PROFILE;
        $esSuperior = in_array($profile, ['SuperAd', 'Admin']);

        // Asignaciones calificables del docente (o todas si es SuperAd/Admin)
        $queryAsig = DB::table('ASIGNACION_PCM as a')
            ->join('CODIGOSMAT as m', 'a.CODIGO_MAT', '=', 'm.CODIGO_MAT')
            ->where('a.calificable', 1)
            ->select('a.CODIGO_EMP', 'a.CODIGO_MAT', 'a.CURSO', 'm.NOMBRE_MAT');

        if (!$esSuperior) {
            $queryAsig->where('a.CODIGO_EMP', $profile);
        }

        $asignaciones = $queryAsig->orderBy('m.NOMBRE_MAT')->orderBy('a.CURSO')->get();

        // Materias únicas del docente
        $materias = $asignaciones->unique('CODIGO_MAT')->values();

        // Cursos disponibles según materia seleccionada
        $matSelec   = $request->input('materia');
        $cursoSelec = $request->input('curso');

        $cursosDisponibles = $matSelec
            ? $asignaciones->where('CODIGO_MAT', $matSelec)->unique('CURSO')->values()
            : collect();

        // Mapa materia → cursos para JS
        $mapaMateriasCursos = [];
        foreach ($asignaciones as $a) {
            $mapaMateriasCursos[$a->CODIGO_MAT][] = $a->CURSO;
        }
        foreach ($mapaMateriasCursos as &$cursos) {
            $cursos = array_values(array_unique($cursos));
        }

        $estudiantes = collect();
        $notasMap    = [];

        if ($matSelec && $cursoSelec) {
            $estudiantes = $this->estudiantesPara((int) $matSelec, $cursoSelec);

            $codigosAlum = $estudiantes->pluck('CODIGO')->toArray();

            if (!empty($codigosAlum)) {
                try {
                    $notasRaw = DB::table($this->tablaNotas())
                        ->whereIn('CODIGO_ALUM', $codigosAlum)
                        ->where('CODIGO_MAT', $matSelec)
                        ->get();

                    foreach ($notasRaw as $n) {
                        $notasMap[$n->CODIGO_ALUM][$n->PERIODO] = $n->NOTA;
                    }
                } catch (\Exception $e) {
                    // La tabla del año podría no existir aún
                }
            }
        }

        $materiaNombre = $matSelec
            ? ($materias->firstWhere('CODIGO_MAT', $matSelec)->NOMBRE_MAT ?? '')
            : '';

        // Períodos abiertos según FECHAS (SuperAd y Admin siempre pueden editar)
        $periodosAbiertos = [];
        if ($esSuperior) {
            $periodosAbiertos = [1, 2, 3, 4];
        } else {
            foreach ([1, 2, 3, 4] as $p) {
                if (FechasController::estaActivo('N' . $p)) {
                    $periodosAbiertos[] = $p;
                }
            }
        }

        return view('notas.index', compact(
            'materias', 'cursosDisponibles', 'matSelec', 'cursoSelec',
            'estudiantes', 'notasMap', 'mapaMateriasCursos', 'materiaNombre',
            'periodosAbiertos'
        ));
    }

    public function guardar(Request $request)
    {
        $tabla    = $this->tablaNotas();
        $materia  = $request->input('CODIGO_MAT');
        $docente  = auth()->user()->PROFILE;
        $esSuperior = in_array($docente, ['SuperAd', 'Admin']);

        foreach (($request->input('notas', [])) as $codAlum => $periodos) {
            foreach ($periodos as $periodo => $nota) {
                $nota = trim($nota);
                if ($nota === '' || $nota === null) continue;

                // Verificar que el período esté abierto (SuperAd y Admin pueden siempre)
                if (!$esSuperior && !FechasController::estaActivo('N' . $periodo)) {
                    return back()->withErrors([
                        'fechas' => "El período {$periodo} no está abierto para ingreso de notas."
                    ]);
                }

                $nota = str_replace(',', '.', $nota);

                $existe = DB::table($tabla)
                    ->where('CODIGO_ALUM', $codAlum)
                    ->where('CODIGO_MAT', $materia)
                    ->where('PERIODO', $periodo)
                    ->exists();

                if ($existe) {
                    DB::table($tabla)
                        ->where('CODIGO_ALUM', $codAlum)
                        ->where('CODIGO_MAT', $materia)
                        ->where('PERIODO', $periodo)
                        ->update(['NOTA' => $nota, 'TIPODENOTA' => 'N', 'CODIGO_EMP' => $docente]);
                } else {
                    DB::table($tabla)->insert([
                        'CODIGO_ALUM' => $codAlum,
                        'PERIODO'     => $periodo,
                        'CODIGO_MAT'  => $materia,
                        'NOTA'        => $nota,
                        'TIPODENOTA'  => 'N',
                        'CODIGO_EMP'  => $docente,
                    ]);
                }
            }
        }

        return redirect()->back()->with('success', 'Notas guardadas correctamente.');
    }
}
