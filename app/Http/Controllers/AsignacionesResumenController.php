<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AsignacionesResumenController extends Controller
{
    public function index(Request $request)
    {
        $asignaciones = DB::table('ASIGNACION_PCM as a')
            ->leftJoin('CODIGOSMAT as m', 'a.CODIGO_MAT', '=', 'm.CODIGO_MAT')
            ->leftJoin('CODIGOS_DOC as d', 'a.CODIGO_EMP', '=', 'd.CODIGO_EMP')
            ->select(
                'a.CURSO',
                'a.CODIGO_MAT',
                DB::raw('COALESCE(m.NOMBRE_MAT, CONCAT("Mat ", a.CODIGO_MAT)) as NOMBRE_MAT'),
                'a.CODIGO_EMP',
                DB::raw('COALESCE(d.NOMBRE_DOC, a.CODIGO_EMP) as NOMBRE_DOC'),
                'a.IHS',
                'd.ESTADO as DOC_ESTADO'
            )
            ->get();

        $cmpCurso = function ($a, $b) {
            $key = fn($c) => match(true) {
                $c === 'J'  => [-2, ''],
                $c === 'T'  => [-1, ''],
                default     => [(int) $c, ltrim($c, '0123456789')],
            };
            [$na, $la] = $key($a);
            [$nb, $lb] = $key($b);
            return $na !== $nb ? $na <=> $nb : strcmp($la, $lb);
        };

        $cursos = $asignaciones->pluck('CURSO')->filter()->unique()->sort($cmpCurso)->values();

        $docentes = $asignaciones
            ->groupBy('CODIGO_EMP')
            ->map(fn($items) => (object) [
                'CODIGO_EMP' => $items->first()->CODIGO_EMP,
                'NOMBRE_DOC' => $items->first()->NOMBRE_DOC,
                'DOC_ESTADO' => $items->first()->DOC_ESTADO,
            ])
            ->sortBy('NOMBRE_DOC')
            ->values();

        $porCurso = $asignaciones
            ->sort(fn($a, $b) =>
                $cmpCurso($a->CURSO, $b->CURSO)
                    ?: strcmp($a->NOMBRE_MAT, $b->NOMBRE_MAT)
                    ?: strcmp($a->NOMBRE_DOC, $b->NOMBRE_DOC)
            )
            ->groupBy('CURSO');

        $porDocente = $asignaciones
            ->sort(fn($a, $b) =>
                strcmp($a->NOMBRE_DOC, $b->NOMBRE_DOC)
                    ?: $cmpCurso($a->CURSO, $b->CURSO)
                    ?: strcmp($a->NOMBRE_MAT, $b->NOMBRE_MAT)
            )
            ->groupBy('CODIGO_EMP');

        return view('asignaciones.resumen', compact('cursos', 'docentes', 'porCurso', 'porDocente'));
    }
}
