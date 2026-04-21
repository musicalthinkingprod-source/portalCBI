<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $profile = auth()->user()->PROFILE;
        $isDoc   = str_starts_with($profile, 'DOC');

        // ── Cartera (SuperAd, Admin, Contab) ──────────────────────────────
        $cartera = null;
        if (in_array($profile, ['SuperAd', 'Admin', 'Contab'])) {
            $facturado = (float) DB::table('facturacion')->sum('valor');
            $recaudado = (float) DB::table('registro_pagos')->sum('valor');

            // Pendiente = suma de saldos positivos por alumno (igual que control de pagos).
            // Los anticipos (saldo < 0) no descuentan la deuda de otros.
            $facturaPorAlumno = DB::table('facturacion')
                ->select('codigo_alumno', DB::raw('SUM(valor) as total_facturado'))
                ->groupBy('codigo_alumno');

            $pendiente = (float) DB::query()
                ->fromSub($facturaPorAlumno, 'f')
                ->leftJoinSub(
                    DB::table('registro_pagos')
                        ->select('codigo_alumno', DB::raw('SUM(valor) as total_pagado'))
                        ->groupBy('codigo_alumno'),
                    'p',
                    'f.codigo_alumno', '=', 'p.codigo_alumno'
                )
                ->whereRaw('f.total_facturado - COALESCE(p.total_pagado, 0) > 0')
                ->sum(DB::raw('f.total_facturado - COALESCE(p.total_pagado, 0)'));

            $cartera = [
                'facturado' => $facturado,
                'recaudado' => $recaudado,
                'pendiente' => $pendiente,
                'pct'       => $facturado > 0 ? round(($recaudado / $facturado) * 100, 1) : 0,
            ];
        }

        // ── Digitación de notas finales (SuperAd, Admin) ──────────────────
        $notas = null;
        $ciclosNotas = null;
        if (in_array($profile, ['SuperAd', 'Admin'])) {
            $anio      = (int) date('Y');
            $tablaNotas = 'NOTAS_' . $anio;

            // Período actual según calendario académico: cada 7 inicios de ciclo
            // (dia_ciclo=1) marcan un período. El período activo es el del ciclo
            // cuyo inicio ya llegó y el siguiente aún no.
            $todosInicios = DB::table('calendario_academico')
                ->where('anio', $anio)
                ->where('dia_ciclo', 1)
                ->orderBy('fecha')
                ->distinct()
                ->pluck('fecha')
                ->values();

            $hoyStr = today()->toDateString();
            $ciclosPasados = $todosInicios->filter(fn($d) => $d <= $hoyStr)->count();
            $periodoActual = $ciclosPasados > 0
                ? max(1, min(4, (int) ceil($ciclosPasados / 7)))
                : 1;

            // Período de referencia para la tarjeta de digitación:
            // el más reciente con notas registradas; si no hay, el actual del calendario
            try {
                $periodoDigitacion = (int) (DB::table($tablaNotas)->max('PERIODO') ?? $periodoActual);
            } catch (\Exception $e) {
                $periodoDigitacion = $periodoActual;
            }

            // Total de asignaciones calificables
            $totalAsignaciones = DB::table('ASIGNACION_PCM')
                ->where('calificable', 1)
                ->count();

            // Asignaciones (doc, mat, curso) con al menos 1 nota real en el período,
            // respetando las 4 formas de mapear CURSO según la materia:
            //   - Normal (no 25/26/31): ASIGNACION.CURSO = ESTUDIANTES.CURSO
            //   - Proyecto (31):        ASIGNACION.CURSO = LISTADOS_ESPECIALES.GRUPO
            //   - Artes/Música 7°+:     ASIGNACION.CURSO (con -1/-2) = LISTADOS_ESPECIALES.GRUPO
            //   - Artes/Música base:    ASIGNACION.CURSO = ESTUDIANTES.CURSO
            $conNotas = 0;
            try {
                $conNotas = (int) DB::table('ASIGNACION_PCM as a')
                    ->join($tablaNotas . ' as n', function ($j) use ($periodoDigitacion) {
                        $j->on('n.CODIGO_DOC', '=', 'a.CODIGO_DOC')
                          ->on('n.CODIGO_MAT', '=', 'a.CODIGO_MAT')
                          ->where('n.PERIODO', '=', $periodoDigitacion);
                    })
                    ->join('ESTUDIANTES as e', function ($j) {
                        $j->on('e.CODIGO', '=', 'n.CODIGO_ALUM')
                          ->where('e.ESTADO', '=', 'MATRICULADO');
                    })
                    ->leftJoin('LISTADOS_ESPECIALES as le', 'le.CODIGO_ALUM', '=', 'n.CODIGO_ALUM')
                    ->where('a.calificable', 1)
                    ->whereRaw("(
                        (a.CODIGO_MAT NOT IN (25,26,31) AND a.CURSO = e.CURSO) OR
                        (a.CODIGO_MAT = 31 AND le.GRUPO = a.CURSO) OR
                        (a.CODIGO_MAT IN (25,26) AND a.CURSO REGEXP '-[12]\$' AND le.GRUPO = a.CURSO) OR
                        (a.CODIGO_MAT IN (25,26) AND a.CURSO NOT REGEXP '-[12]\$' AND a.CURSO = e.CURSO)
                    )")
                    ->distinct()
                    ->count(DB::raw("CONCAT_WS('|', a.CODIGO_DOC, a.CODIGO_MAT, a.CURSO)"));
            } catch (\Exception $e) {
                // tabla del año podría no existir
            }

            $notas = [
                'periodo'   => $periodoDigitacion,
                'con_notas' => $conNotas,
                'total'     => $totalAsignaciones,
                'pct'       => $totalAsignaciones > 0 ? round(($conNotas / $totalAsignaciones) * 100) : 0,
            ];

            // ── Notas por ciclo (planilla ponderada) del período actual ───
            $offset       = ($periodoActual - 1) * 7;
            $iniciosCiclo = $todosInicios->slice($offset, 7)->values();

            $ciclos = [];
            foreach ($iniciosCiclo as $i => $inicio) {
                $fin = $iniciosCiclo[$i + 1] ?? null; // siguiente inicio marca el fin (exclusivo)
                $ciclos[] = [
                    'numero' => $i + 1,
                    'inicio' => $inicio,
                    'fin'    => $fin,
                    'activo' => $hoyStr >= $inicio && ($fin === null || $hoyStr < $fin),
                    'futuro' => $hoyStr < $inicio,
                    'total'  => 0,
                ];
            }

            if (!empty($ciclos)) {
                $rangoInicio = $ciclos[0]['inicio'];
                $rangoFin    = end($ciclos)['fin'] ?? '9999-12-31';

                $conteosPorFecha = DB::table('planilla_notas as pn')
                    ->join('planilla_columnas as pc', 'pc.id', '=', 'pn.columna_id')
                    ->where('pc.anio', $anio)
                    ->where('pc.periodo', $periodoActual)
                    ->whereNotNull('pn.nota')
                    ->whereBetween(DB::raw('DATE(pn.updated_at)'), [$rangoInicio, $rangoFin])
                    ->select(DB::raw('DATE(pn.updated_at) as fecha'), DB::raw('COUNT(*) as total'))
                    ->groupBy(DB::raw('DATE(pn.updated_at)'))
                    ->pluck('total', 'fecha');

                foreach ($conteosPorFecha as $fecha => $total) {
                    foreach ($ciclos as $idx => $c) {
                        if ($fecha >= $c['inicio'] && ($c['fin'] === null || $fecha < $c['fin'])) {
                            $ciclos[$idx]['total'] += (int) $total;
                            break;
                        }
                    }
                }
            }

            $ciclosNotas = [
                'periodo' => $periodoActual,
                'ciclos'  => $ciclos,
                'max'     => collect($ciclos)->max('total') ?: 0,
                'totalP'  => collect($ciclos)->sum('total'),
            ];
        }

        // ── Calendario ────────────────────────────────────────────────────
        $hoy = DB::table('calendario_academico')
            ->where('fecha', today()->toDateString())
            ->first();

        $manana = DB::table('calendario_academico')
            ->where('fecha', today()->addDay()->toDateString())
            ->first();

        $proximosEventos = DB::table('calendario_academico')
            ->where('fecha', '>=', today()->toDateString())
            ->whereNotNull('evento')
            ->orderBy('fecha')
            ->limit(4)
            ->get();

        // ── Asistencia del día (SuperAd, Admin, Sec*) ─────────────────────
        $asistencia = null;
        if (in_array($profile, ['SuperAd', 'Admin']) || str_starts_with($profile, 'Sec')) {
            $totalEstudiantes = DB::table('ESTUDIANTES')
                ->whereRaw("TRIM(ESTADO) = 'MATRICULADO'")
                ->count();

            $registradosHoy = DB::table('ASISTENCIA')
                ->where('FECHA', today()->toDateString())
                ->distinct()
                ->count('CODIGO');

            $asistencia = [
                'total'       => $totalEstudiantes,
                'registrados' => $registradosHoy,
                'pct'         => $totalEstudiantes > 0
                    ? round(($registradosHoy / $totalEstudiantes) * 100)
                    : 0,
            ];
        }

        return view('dashboard', compact(
            'profile', 'isDoc', 'cartera', 'notas', 'ciclosNotas',
            'hoy', 'manana', 'proximosEventos', 'asistencia'
        ));
    }
}
