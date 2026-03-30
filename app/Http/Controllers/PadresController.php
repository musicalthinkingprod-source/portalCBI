<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PadresController extends Controller
{
    public function estadoCuenta()
    {
        $estudiante = session('padre_estudiante');
        $codigo     = $estudiante->CODIGO;

        $facturacion  = DB::table('facturacion')->where('codigo_alumno', $codigo)->orderBy('fecha')->get();
        $pagos        = DB::table('registro_pagos')->where('codigo_alumno', $codigo)->orderBy('fecha')->get();
        $totalFactura = $facturacion->sum('valor');
        $totalPagado  = $pagos->sum('valor');
        $saldo        = $totalFactura - $totalPagado;

        return view('padres.estado_cuenta', compact('estudiante', 'facturacion', 'pagos', 'totalFactura', 'totalPagado', 'saldo'));
    }
}
