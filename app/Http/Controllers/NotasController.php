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

        // Todos los docentes con asignaciones (activos e inactivos)
        $asignaciones = DB::table('ASIGNACION_PCM as a')
            ->join('CODIGOSMAT as m', 'a.CODIGO_MAT', '=', 'm.CODIGO_MAT')
            ->leftJoin('CODIGOS_DOC as d', 'a.CODIGO_DOC', '=', 'd.CODIGO_DOC')
            ->select('a.CODIGO_DOC', 'a.CODIGO_MAT', 'a.CURSO', 'm.NOMBRE_MAT',
                     'd.NOMBRE_DOC', 'd.ESTADO as ESTADO_DOC')
            ->orderByRaw("CASE WHEN d.ESTADO = 'ACTIVO' OR d.ESTADO IS NULL THEN 0 ELSE 1 END")
            ->orderBy('d.NOMBRE_DOC')
            ->orderBy('a.CURSO')
            ->get();

        // Solo estudiantes MATRICULADOS por curso
        $estPorCurso = DB::table('ESTUDIANTES')
            ->where('ESTADO', 'MATRICULADO')
            ->select('CURSO', DB::raw('COUNT(*) as total'))
            ->groupBy('CURSO')
            ->pluck('total', 'CURSO');

        // Notas ingresadas: solo alumnos MATRICULADOS del curso asignado
        $notasConteo = [];
        try {
            $rows = DB::table($tabla . ' as n')
                ->join('ESTUDIANTES as e', function ($join) {
                    $join->on('e.CODIGO', '=', 'n.CODIGO_ALUM')
                         ->where('e.ESTADO', '=', 'MATRICULADO');
                })
                ->join('ASIGNACION_PCM as a', function ($join) {
                    $join->on('a.CODIGO_DOC', '=', 'n.CODIGO_DOC')
                         ->on('a.CODIGO_MAT', '=', 'n.CODIGO_MAT')
                         ->on('a.CURSO',      '=', 'e.CURSO');
                })
                ->select('n.CODIGO_DOC', 'n.CODIGO_MAT', 'e.CURSO', 'n.PERIODO',
                         DB::raw('COUNT(*) as total'))
                ->groupBy('n.CODIGO_DOC', 'n.CODIGO_MAT', 'e.CURSO', 'n.PERIODO')
                ->get();

            foreach ($rows as $r) {
                $notasConteo[$r->CODIGO_DOC][$r->CODIGO_MAT][$r->CURSO][$r->PERIODO] = $r->total;
            }
        } catch (\Exception $e) {
            // tabla del año podría no existir
        }

        // Construir reporte por docente
        $docentes = [];
        foreach ($asignaciones as $a) {
            $doc = $a->CODIGO_DOC;

            if (!isset($docentes[$doc])) {
                $docentes[$doc] = [
                    'codigo'       => $doc,
                    'nombre'       => $a->NOMBRE_DOC ?? $doc,
                    'estado'       => $a->ESTADO_DOC ?? 'ACTIVO',
                    'periodos'     => array_fill(1, 4, ['esperadas' => 0, 'ingresadas' => 0]),
                    'asignaciones' => [],
                ];
            }

            $totalEst = $estPorCurso[$a->CURSO] ?? 0;
            $detalle  = ['materia' => $a->NOMBRE_MAT, 'curso' => $a->CURSO, 'estudiantes' => $totalEst, 'periodos' => []];

            for ($p = 1; $p <= 4; $p++) {
                $ing = $notasConteo[$doc][$a->CODIGO_MAT][$a->CURSO][$p] ?? 0;
                $docentes[$doc]['periodos'][$p]['esperadas']  += $totalEst;
                $docentes[$doc]['periodos'][$p]['ingresadas'] += $ing;
                $detalle['periodos'][$p] = ['esperadas' => $totalEst, 'ingresadas' => $ing];
            }

            $docentes[$doc]['asignaciones'][] = $detalle;
        }

        $anio = date('Y');
        return view('notas.reporte', compact('docentes', 'anio'));
    }

    public function index(Request $request)
    {
        $profile    = auth()->user()->PROFILE;
        $esSuperior = in_array($profile, ['SuperAd', 'Admin']);

        // Asignaciones del docente (o todas si es SuperAd/Admin)
        $queryAsig = DB::table('ASIGNACION_PCM as a')
            ->join('CODIGOSMAT as m', 'a.CODIGO_MAT', '=', 'm.CODIGO_MAT')
            ->select('a.CODIGO_DOC', 'a.CODIGO_MAT', 'a.CURSO', 'm.NOMBRE_MAT');

        if (!$esSuperior) {
            $queryAsig->where('a.CODIGO_DOC', $profile);
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
            $estudiantes = DB::table('ESTUDIANTES')
                ->where('CURSO', $cursoSelec)
                ->where('ESTADO', 'MATRICULADO')
                ->orderBy('APELLIDO1')->orderBy('APELLIDO2')
                ->orderBy('NOMBRE1')->orderBy('NOMBRE2')
                ->get();

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

        return view('notas.index', compact(
            'materias', 'cursosDisponibles', 'matSelec', 'cursoSelec',
            'estudiantes', 'notasMap', 'mapaMateriasCursos', 'materiaNombre'
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
                if (!$esSuperior && !FechasController::estaActivo('P' . $periodo)) {
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
                        ->update(['NOTA' => $nota, 'CODIGO_DOC' => $docente]);
                } else {
                    DB::table($tabla)->insert([
                        'CODIGO_ALUM' => $codAlum,
                        'PERIODO'     => $periodo,
                        'CODIGO_MAT'  => $materia,
                        'NOTA'        => $nota,
                        'TIPODENOTA'  => 'N',
                        'CODIGO_DOC'  => $docente,
                    ]);
                }
            }
        }

        return redirect()->back()->with('success', 'Notas guardadas correctamente.');
    }
}
