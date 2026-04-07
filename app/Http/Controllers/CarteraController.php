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

    public function estudiante(Request $request, $codigo)
    {
        // Datos del estudiante
        $estudiante = DB::table('ESTUDIANTES')->where('CODIGO', $codigo)->first();
        $infoPadres = DB::table('INFO_PADRES')->where('CODIGO', $codigo)->first();

        // Resumen financiero
        $totalFacturado = DB::table('facturacion')->where('codigo_alumno', $codigo)->sum('valor');
        $totalPagado    = DB::table('registro_pagos')->where('codigo_alumno', $codigo)->sum('valor');
        $saldo          = $totalFacturado - $totalPagado;

        // Detalle de facturas
        $facturas = DB::table('facturacion')
            ->where('codigo_alumno', $codigo)
            ->orderBy('fecha', 'desc')
            ->get();

        // Detalle de pagos
        $pagos = DB::table('registro_pagos')
            ->where('codigo_alumno', $codigo)
            ->orderBy('fecha', 'desc')
            ->get();

        // Seguimientos
        $seguimientos = DB::table('seguimiento_cartera')
            ->where('codigo_alumno', $codigo)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('cartera.estudiante', compact(
            'estudiante', 'infoPadres', 'codigo',
            'totalFacturado', 'totalPagado', 'saldo',
            'facturas', 'pagos', 'seguimientos'
        ));
    }

    public function storeSeguimiento(Request $request, $codigo)
    {
        $request->validate([
            'tipo' => 'required|string|max:30',
            'nota' => 'required|string|max:2000',
        ]);

        DB::table('seguimiento_cartera')->insert([
            'codigo_alumno' => $codigo,
            'tipo'          => $request->tipo,
            'nota'          => $request->nota,
            'usuario'       => auth()->user()->name ?? null,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        return redirect()->route('cartera.estudiante', $codigo)
            ->with('success', 'Registro guardado.');
    }

    public function destroySeguimiento(Request $request, $id)
    {
        $seg = DB::table('seguimiento_cartera')->where('id', $id)->first();
        if ($seg) {
            DB::table('seguimiento_cartera')->where('id', $id)->delete();
            return redirect()->route('cartera.estudiante', $seg->codigo_alumno)
                ->with('success', 'Registro eliminado.');
        }
        return back();
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
