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
            $pendiente = $facturado - $recaudado;
            $cartera = [
                'facturado' => $facturado,
                'recaudado' => $recaudado,
                'pendiente' => $pendiente,
                'pct'       => $facturado > 0 ? round(($recaudado / $facturado) * 100, 1) : 0,
            ];
        }

        // ── Digitación de notas (SuperAd, Admin) ──────────────────────────
        $notas = null;
        if (in_array($profile, ['SuperAd', 'Admin'])) {
            // Período actual: el mayor período con columnas de planilla registradas
            $periodoActual = DB::table('planilla_columnas')
                ->orderByDesc('periodo')
                ->value('periodo') ?? 1;

            // Total de asignaciones activas
            $totalAsignaciones = DB::table('ASIGNACION_PCM')->count();

            // Combinaciones (doc, mat, curso) que tienen al menos 1 columna en el período actual
            $conNotas = DB::table('planilla_columnas')
                ->where('periodo', $periodoActual)
                ->distinct()
                ->count(DB::raw('CONCAT(codigo_doc, codigo_mat, curso)'));

            $notas = [
                'periodo'   => $periodoActual,
                'con_notas' => $conNotas,
                'total'     => $totalAsignaciones,
                'pct'       => $totalAsignaciones > 0 ? round(($conNotas / $totalAsignaciones) * 100) : 0,
            ];
        }

        // ── Calendario ────────────────────────────────────────────────────
        $hoy = DB::table('calendario_academico')
            ->where('fecha', today()->toDateString())
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
            'profile', 'isDoc', 'cartera', 'notas', 'hoy', 'proximosEventos', 'asistencia'
        ));
    }
}
