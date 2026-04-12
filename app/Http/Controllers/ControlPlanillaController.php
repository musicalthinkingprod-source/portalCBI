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

        // Opciones de filtro (solo las que existen con notas reales)
        $cursosDisponibles = DB::table('planilla_columnas')
            ->where('anio', $anio)
            ->where('periodo', $periodo)
            ->select('curso')->distinct()->orderBy('curso')->pluck('curso');

        $materiasDisponibles = DB::table('planilla_columnas')
            ->join('CODIGOSMAT as m', 'm.CODIGO_MAT', '=', 'planilla_columnas.codigo_mat')
            ->where('planilla_columnas.anio', $anio)
            ->where('planilla_columnas.periodo', $periodo)
            ->select('planilla_columnas.codigo_mat', 'm.NOMBRE_MAT')
            ->distinct()->orderBy('m.NOMBRE_MAT')->get();

        // Fechas únicas de inicio de ciclo (dia_ciclo=1), sin duplicados por múltiples eventos
        $todosInicios = DB::table('calendario_academico')
            ->where('anio', $anio)
            ->where('dia_ciclo', 1)
            ->orderBy('fecha')
            ->distinct()
            ->pluck('fecha')
            ->values();

        // Los ciclos se reinician cada 7 por período: período 2 empieza en el ciclo 8 global, etc.
        // Tomamos solo los 7 inicios que corresponden al período seleccionado
        $offsetPeriodo  = ($periodo - 1) * 7;
        $iniciosCiclo   = $todosInicios->slice($offsetPeriodo, 7)->values();

        // Consulta principal: notas agrupadas por docente + actividad + fecha
        $query = DB::table('planilla_notas as pn')
            ->join('planilla_columnas as pc', 'pc.id', '=', 'pn.columna_id')
            ->join('CODIGOSMAT as m', 'm.CODIGO_MAT', '=', 'pc.codigo_mat')
            ->join('CODIGOS_DOC as d', 'd.CODIGO_DOC', '=', 'pc.codigo_doc')
            ->where('pc.anio', $anio)
            ->where('pc.periodo', $periodo)
            ->whereNotNull('pn.nota');

        if ($curso)   $query->where('pc.curso', $curso);
        if ($materia) $query->where('pc.codigo_mat', $materia);

        $filas = $query->select(
                'pc.codigo_doc',
                'd.NOMBRE_DOC',
                'pc.id as columna_id',
                'pc.nombre_actividad',
                'pc.categoria',
                'pc.codigo_mat',
                'm.NOMBRE_MAT',
                'pc.curso',
                DB::raw('DATE(pn.updated_at) as fecha'),
                DB::raw('COUNT(pn.id) as cantidad'),
                DB::raw('MAX(pn.updated_at) as ultima')
            )
            ->groupBy(
                'pc.codigo_doc', 'd.NOMBRE_DOC',
                'pc.id', 'pc.nombre_actividad', 'pc.categoria',
                'pc.codigo_mat', 'm.NOMBRE_MAT', 'pc.curso',
                DB::raw('DATE(pn.updated_at)')
            )
            ->orderBy('pc.codigo_doc')
            ->orderByDesc(DB::raw('DATE(pn.updated_at)'))
            ->orderBy('pc.id')
            ->get();

        // Calcula el número de ciclo de una fecha contando fechas únicas de inicio ≤ esa fecha
        $numeroCiclo = function (string $fecha) use ($iniciosCiclo): ?int {
            if ($iniciosCiclo->isEmpty()) return null;
            $num = $iniciosCiclo->filter(fn($d) => $d <= $fecha)->count();
            return $num ?: null;
        };

        // Organizar: [codigo_doc => [ciclo_num => [fecha => [actividades]]]]
        $porDocente = [];
        foreach ($filas as $fila) {
            $doc   = $fila->codigo_doc;
            $fecha = $fila->fecha;
            $ciclo = $numeroCiclo($fecha);

            if (!isset($porDocente[$doc])) {
                $porDocente[$doc] = [
                    'nombre' => $fila->NOMBRE_DOC,
                    'ciclos' => [],
                ];
            }

            $clave = $ciclo ?? 'sin-ciclo';

            if (!isset($porDocente[$doc]['ciclos'][$clave])) {
                $porDocente[$doc]['ciclos'][$clave] = [
                    'numero' => $ciclo,
                    'fechas' => [],
                ];
            }

            if (!isset($porDocente[$doc]['ciclos'][$clave]['fechas'][$fecha])) {
                $porDocente[$doc]['ciclos'][$clave]['fechas'][$fecha] = [];
            }

            $porDocente[$doc]['ciclos'][$clave]['fechas'][$fecha][] = $fila;
        }

        // Ordenar ciclos descendente dentro de cada docente
        foreach ($porDocente as &$data) {
            krsort($data['ciclos']);
        }

        return view('control.planilla', compact(
            'porDocente', 'anio', 'periodo', 'curso', 'materia',
            'cursosDisponibles', 'materiasDisponibles'
        ));
    }
}
