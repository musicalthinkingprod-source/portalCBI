<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ControlPlanillaController extends Controller
{
    public function index(Request $request)
    {
        $anio    = (int) $request->input('anio', date('Y'));
        $periodo = (int) $request->input('periodo', 1);
        $curso   = $request->input('curso', '');
        $materia = $request->input('materia', '');

        $esSuperior = in_array(auth()->user()->PROFILE ?? '', ['SuperAd', 'Admin']);

        // Atención a Padres es una materia ficticia (sólo horarios), no aplica aquí.
        $matExcluidas = [200];

        // Filtros disponibles (basados en planillas con notas)
        $cursosDisponibles = DB::table('planilla_columnas')
            ->where('anio', $anio)
            ->where('periodo', $periodo)
            ->whereNotIn('codigo_mat', $matExcluidas)
            ->select('curso')->distinct()->orderBy('curso')->pluck('curso');

        $materiasDisponibles = DB::table('planilla_columnas')
            ->join('CODIGOSMAT as m', 'm.CODIGO_MAT', '=', 'planilla_columnas.codigo_mat')
            ->where('planilla_columnas.anio', $anio)
            ->where('planilla_columnas.periodo', $periodo)
            ->whereNotIn('planilla_columnas.codigo_mat', $matExcluidas)
            ->select('planilla_columnas.codigo_mat', 'm.NOMBRE_MAT')
            ->distinct()->orderBy('m.NOMBRE_MAT')->get();

        // ── Inicios de ciclo (dia_ciclo=1), recortados al período ──
        $todosInicios = DB::table('calendario_academico')
            ->where('anio', $anio)
            ->where('dia_ciclo', 1)
            ->orderBy('fecha')
            ->distinct()
            ->pluck('fecha')
            ->values();

        $offsetPeriodo = ($periodo - 1) * 7;
        $iniciosCiclo  = $todosInicios->slice($offsetPeriodo, 7)->values();

        // ── Días académicos del período (1-6 por ciclo) ──
        $diasGrid = collect();
        if ($iniciosCiclo->isNotEmpty()) {
            $primeroPeriodo  = $iniciosCiclo->first();
            $siguienteInicio = $todosInicios->slice($offsetPeriodo + 7, 1)->first();
            $rangoFin = $siguienteInicio
                ? \Carbon\Carbon::parse($siguienteInicio)->subDay()->toDateString()
                : ($anio . '-12-31');

            $diasGrid = DB::table('calendario_academico')
                ->where('anio', $anio)
                ->whereBetween('fecha', [$primeroPeriodo, $rangoFin])
                ->whereBetween('dia_ciclo', [1, 6])
                ->orderBy('fecha')
                ->select('fecha', 'dia_ciclo', 'evento')
                ->get();

            $iniciosArr = $iniciosCiclo->all();
            $diasGrid = $diasGrid->map(function ($d) use ($iniciosArr) {
                $num = 0;
                foreach ($iniciosArr as $i => $ini) {
                    if ($d->fecha >= $ini) $num = $i + 1;
                    else break;
                }
                $d->ciclo_num = $num ?: null;
                return $d;
            })->filter(fn($d) => $d->ciclo_num !== null)->values();
        }

        // ── Asignaciones calificables (excluyendo materias ficticias) ──
        $asigQuery = DB::table('ASIGNACION_PCM as a')
            ->join('CODIGOSMAT as m', 'a.CODIGO_MAT', '=', 'm.CODIGO_MAT')
            ->leftJoin('CODIGOS_DOC as d', 'a.CODIGO_DOC', '=', 'd.CODIGO_DOC')
            ->where('a.calificable', 1)
            ->whereNotIn('a.CODIGO_MAT', $matExcluidas)
            ->select('a.CODIGO_DOC', 'a.CODIGO_MAT', 'a.CURSO',
                     'm.NOMBRE_MAT', 'd.NOMBRE_DOC')
            ->orderBy('d.NOMBRE_DOC')->orderBy('m.NOMBRE_MAT')->orderBy('a.CURSO');

        if ($curso)   $asigQuery->where('a.CURSO', $curso);
        if ($materia) $asigQuery->where('a.CODIGO_MAT', $materia);

        $asignaciones = $asigQuery->get();

        // Agrupar asignaciones por docente para colapsar/expandir
        $porDocente = $asignaciones->groupBy('CODIGO_DOC');

        // ── Conteos por día × (doc|mat|curso) × categoría ──
        $conteosCat = []; // [fecha][key]['P'|'C'|'A'] = int
        $detalles   = []; // [fecha][key][categoria] = [{actividad, cantidad}]

        if ($diasGrid->isNotEmpty()) {
            $q = DB::table('planilla_notas as pn')
                ->join('planilla_columnas as pc', 'pc.id', '=', 'pn.columna_id')
                ->where('pc.anio', $anio)
                ->where('pc.periodo', $periodo)
                ->whereNotIn('pc.codigo_mat', $matExcluidas)
                ->whereBetween(DB::raw('DATE(pn.updated_at)'), [
                    $diasGrid->first()->fecha,
                    $diasGrid->last()->fecha,
                ])
                ->whereNotNull('pn.nota');

            if ($curso)   $q->where('pc.curso', $curso);
            if ($materia) $q->where('pc.codigo_mat', $materia);

            $filas = $q->select(
                    'pc.codigo_doc',
                    'pc.codigo_mat',
                    'pc.curso',
                    'pc.nombre_actividad',
                    'pc.categoria',
                    DB::raw('DATE(pn.updated_at) as fecha'),
                    DB::raw('COUNT(*) as total')
                )
                ->groupBy('pc.codigo_doc', 'pc.codigo_mat', 'pc.curso',
                          'pc.nombre_actividad', 'pc.categoria',
                          DB::raw('DATE(pn.updated_at)'))
                ->get();

            foreach ($filas as $f) {
                $key = $f->codigo_doc . '|' . $f->codigo_mat . '|' . $f->curso;
                $cat = $f->categoria;
                $conteosCat[$f->fecha][$key][$cat] = ($conteosCat[$f->fecha][$key][$cat] ?? 0) + (int) $f->total;
                $detalles[$f->fecha][$key][$cat][] = [
                    'actividad' => $f->nombre_actividad,
                    'cantidad'  => (int) $f->total,
                ];
            }
        }

        $ciclosAgrupados = $diasGrid->groupBy('ciclo_num');

        return view('control.planilla', compact(
            'anio', 'periodo', 'curso', 'materia',
            'cursosDisponibles', 'materiasDisponibles',
            'asignaciones', 'porDocente', 'diasGrid', 'ciclosAgrupados',
            'conteosCat', 'detalles', 'esSuperior'
        ));
    }
}
