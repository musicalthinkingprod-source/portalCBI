<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CiclosController extends Controller
{
    public function index(Request $request)
    {
        $anio   = (int) $request->input('anio', date('Y'));
        $periodo = (int) $request->input('periodo', 1);

        $ciclos = DB::table('ciclos_academicos')
            ->where('anio', $anio)
            ->orderBy('numero')
            ->get();

        // ── Asignaciones calificables con docente ────────────────────────────
        $asignaciones = DB::table('ASIGNACION_PCM as a')
            ->join('CODIGOSMAT as m', 'a.CODIGO_MAT', '=', 'm.CODIGO_MAT')
            ->leftJoin('CODIGOS_DOC as d', 'a.CODIGO_EMP', '=', 'd.CODIGO_EMP')
            ->where('a.calificable', 1)
            ->select('a.CODIGO_EMP', 'a.CODIGO_MAT', 'a.CURSO',
                     'm.NOMBRE_MAT', 'd.NOMBRE_DOC')
            ->orderBy('d.NOMBRE_DOC')->orderBy('m.NOMBRE_MAT')->orderBy('a.CURSO')
            ->get();

        // ── Para cada ciclo: qué filas (doc+mat+curso) registraron notas ─────
        // Una fila "cumple" si tiene al menos 1 planilla_nota con updated_at en el rango del ciclo
        $cumplimiento = []; // [ciclo_id][doc_mat_curso] = true|false

        foreach ($ciclos as $ciclo) {
            $entradas = DB::table('planilla_notas as pn')
                ->join('planilla_columnas as pc', 'pc.id', '=', 'pn.columna_id')
                ->where('pc.anio', $anio)
                ->where('pc.periodo', $periodo)
                ->whereBetween('pn.updated_at', [
                    $ciclo->fecha_inicio . ' 00:00:00',
                    $ciclo->fecha_fin    . ' 23:59:59',
                ])
                ->whereNotNull('pn.nota')
                ->select('pc.codigo_emp', 'pc.codigo_mat', 'pc.curso')
                ->distinct()
                ->get();

            foreach ($entradas as $e) {
                $key = $e->codigo_emp . '|' . $e->codigo_mat . '|' . $e->curso;
                $cumplimiento[$ciclo->id][$key] = true;
            }
        }

        return view('ciclos.index', compact(
            'ciclos', 'asignaciones', 'cumplimiento', 'anio', 'periodo'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'anio'         => 'required|integer|min:2020|max:2099',
            'numero'       => 'required|integer|min:1',
            'nombre'       => 'required|string|max:60',
            'fecha_inicio' => 'required|date',
            'fecha_fin'    => 'required|date|after_or_equal:fecha_inicio',
        ]);

        DB::table('ciclos_academicos')->insertOrIgnore([
            'anio'         => $request->anio,
            'numero'       => $request->numero,
            'nombre'       => trim($request->nombre),
            'fecha_inicio' => $request->fecha_inicio,
            'fecha_fin'    => $request->fecha_fin,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        return redirect()->back()->with('success', 'Ciclo creado correctamente.');
    }

    public function informe(Request $request)
    {
        $anio    = (int) $request->input('anio', date('Y'));
        $periodo = (int) $request->input('periodo', 1);

        // ── Inicios de ciclo (dia_ciclo=1) del año, recortados al período ──
        $todosInicios = DB::table('calendario_academico')
            ->where('anio', $anio)
            ->where('dia_ciclo', 1)
            ->orderBy('fecha')
            ->distinct()
            ->pluck('fecha')
            ->values();

        $offsetPeriodo = ($periodo - 1) * 7;
        $iniciosCiclo  = $todosInicios->slice($offsetPeriodo, 7)->values();

        // ── Días académicos del período (1–6 por ciclo, en orden cronológico) ──
        $diasGrid = collect(); // [{fecha, dia_ciclo, ciclo_num, evento}]
        $rangoFin = null;

        if ($iniciosCiclo->isNotEmpty()) {
            $primeroPeriodo = $iniciosCiclo->first();

            // Fin del período = día anterior al siguiente inicio global, o último del año
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

            // Asignar número de ciclo (1–7) a cada día
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

        // ── Asignaciones calificables ─────────────────────────────────────
        $asignaciones = DB::table('ASIGNACION_PCM as a')
            ->join('CODIGOSMAT as m', 'a.CODIGO_MAT', '=', 'm.CODIGO_MAT')
            ->leftJoin('CODIGOS_DOC as d', 'a.CODIGO_EMP', '=', 'd.CODIGO_EMP')
            ->where('a.calificable', 1)
            ->select('a.CODIGO_EMP', 'a.CODIGO_MAT', 'a.CURSO',
                     'm.NOMBRE_MAT', 'd.NOMBRE_DOC')
            ->orderBy('d.NOMBRE_DOC')->orderBy('m.NOMBRE_MAT')->orderBy('a.CURSO')
            ->get();

        // ── Conteos por día × (docente, materia, curso) ────────────────────
        $conteos = []; // [fecha][doc|mat|curso] = int

        if ($diasGrid->isNotEmpty()) {
            $filas = DB::table('planilla_notas as pn')
                ->join('planilla_columnas as pc', 'pc.id', '=', 'pn.columna_id')
                ->where('pc.anio', $anio)
                ->where('pc.periodo', $periodo)
                ->whereBetween(DB::raw('DATE(pn.updated_at)'), [
                    $diasGrid->first()->fecha,
                    $diasGrid->last()->fecha,
                ])
                ->whereNotNull('pn.nota')
                ->select(
                    'pc.codigo_emp',
                    'pc.codigo_mat',
                    'pc.curso',
                    DB::raw('DATE(pn.updated_at) as fecha'),
                    DB::raw('COUNT(*) as total')
                )
                ->groupBy('pc.codigo_emp', 'pc.codigo_mat', 'pc.curso', DB::raw('DATE(pn.updated_at)'))
                ->get();

            foreach ($filas as $f) {
                $key = $f->codigo_emp . '|' . $f->codigo_mat . '|' . $f->curso;
                $conteos[$f->fecha][$key] = (int) $f->total;
            }
        }

        // Agrupar días por ciclo para el colspan del encabezado
        $ciclosAgrupados = $diasGrid->groupBy('ciclo_num');

        return view('ciclos.informe', compact(
            'asignaciones', 'conteos', 'anio', 'periodo',
            'diasGrid', 'ciclosAgrupados'
        ));
    }

    public function destroy($id)
    {
        DB::table('ciclos_academicos')->where('id', $id)->delete();
        return redirect()->back()->with('success', 'Ciclo eliminado.');
    }
}
