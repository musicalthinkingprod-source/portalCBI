<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CarteraController extends Controller
{
    public function index()
    {
        // Totales generales
        $totalFacturado = DB::table('facturacion')->sum('valor');
        $totalPagado    = DB::table('registro_pagos')->sum('valor');
        $totalCartera   = $totalFacturado - $totalPagado;
        $porcentajeRecaudo = $totalFacturado > 0
            ? round(($totalPagado / $totalFacturado) * 100, 1)
            : 0;

        // Saldo por estudiante
        $facturaPorAlumno = DB::table('facturacion')
            ->select('codigo_alumno', DB::raw('SUM(valor) as total_facturado'))
            ->groupBy('codigo_alumno');

        $pagoPorAlumno = DB::table('registro_pagos')
            ->select('codigo_alumno', DB::raw('SUM(valor) as total_pagado'))
            ->groupBy('codigo_alumno');

        $saldos = DB::table(DB::raw("({$facturaPorAlumno->toSql()}) as f"))
            ->mergeBindings($facturaPorAlumno)
            ->leftJoinSub($pagoPorAlumno, 'p', 'f.codigo_alumno', '=', 'p.codigo_alumno')
            ->select(
                'f.codigo_alumno',
                'f.total_facturado',
                DB::raw('COALESCE(p.total_pagado, 0) as total_pagado'),
                DB::raw('f.total_facturado - COALESCE(p.total_pagado, 0) as saldo')
            )
            ->get();

        $alDia    = $saldos->where('saldo', '<=', 0)->count();
        $debiendo = $saldos->where('saldo', '>', 0)->count();

        // Top 10 mayores deudores
        $topDeudores = $saldos->where('saldo', '>', 0)
            ->sortByDesc('saldo')
            ->take(10)
            ->values();

        // Obtener nombres de estudiantes para top deudores
        $codigos    = $topDeudores->pluck('codigo_alumno')->toArray();
        $estudiantes = DB::table('ESTUDIANTES')
            ->whereIn('CODIGO', $codigos)
            ->get()
            ->keyBy('CODIGO');

        // Facturación por mes
        $porMes = DB::table('facturacion')
            ->select('mes', DB::raw('SUM(valor) as total'))
            ->groupBy('mes')
            ->orderBy('mes')
            ->get();

        // Pagos por mes
        $pagosPorMes = DB::table('registro_pagos')
            ->select('mes', DB::raw('SUM(valor) as total'))
            ->groupBy('mes')
            ->orderBy('mes')
            ->get()
            ->keyBy('mes');

        return view('cartera.index', compact(
            'totalFacturado', 'totalPagado', 'totalCartera', 'porcentajeRecaudo',
            'alDia', 'debiendo', 'topDeudores', 'estudiantes', 'porMes', 'pagosPorMes'
        ));
    }
}
