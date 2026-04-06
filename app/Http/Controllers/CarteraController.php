<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CarteraController extends Controller
{
    public function index()
    {
        // Totales de facturación y recaudo (brutos)
        $totalFacturado = DB::table('facturacion')->sum('valor');
        $totalPagado    = DB::table('registro_pagos')->sum('valor');

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

        // Cartera real: solo suma los saldos positivos (deudores)
        // Los pagos adelantados no restan la cartera de otros
        $totalCartera = $saldos->where('saldo', '>', 0)->sum('saldo');

        $porcentajeRecaudo = $totalFacturado > 0
            ? round(($totalPagado / $totalFacturado) * 100, 1)
            : 0;

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

    public function deudores(Request $request)
    {
        $facturaSub = DB::table('facturacion')
            ->select('codigo_alumno', DB::raw('SUM(valor) as total_facturado'))
            ->groupBy('codigo_alumno');

        $pagoSub = DB::table('registro_pagos')
            ->select('codigo_alumno', DB::raw('SUM(valor) as total_pagado'))
            ->groupBy('codigo_alumno');

        $deudores = DB::table(DB::raw("({$facturaSub->toSql()}) as f"))
            ->mergeBindings($facturaSub)
            ->leftJoinSub($pagoSub, 'p', 'f.codigo_alumno', '=', 'p.codigo_alumno')
            ->leftJoin('ESTUDIANTES as e', 'e.CODIGO', '=', 'f.codigo_alumno')
            ->leftJoin('INFO_PADRES as ip', 'ip.CODIGO', '=', 'f.codigo_alumno')
            ->select(
                'f.codigo_alumno',
                'f.total_facturado',
                DB::raw('COALESCE(p.total_pagado, 0) as total_pagado'),
                DB::raw('f.total_facturado - COALESCE(p.total_pagado, 0) as saldo'),
                'e.NOMBRE1', 'e.NOMBRE2', 'e.APELLIDO1', 'e.APELLIDO2', 'e.CURSO',
                'ip.MADRE', 'ip.PADRE', 'ip.ACUD',
                'ip.CEL_MADRE', 'ip.CEL_PADRE', 'ip.CEL_ACUD',
                'ip.TEL_MADRE', 'ip.TEL_PADRE', 'ip.TEL_ACUD'
            )
            ->whereRaw('f.total_facturado - COALESCE(p.total_pagado, 0) > 0')
            ->orderByDesc('saldo')
            ->paginate(25)
            ->withQueryString();

        return view('cartera.deudores', compact('deudores'));
    }
}
