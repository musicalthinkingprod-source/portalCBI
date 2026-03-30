<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ControlEstudianteController extends Controller
{
    public function index(Request $request)
    {
        $estudiante   = null;
        $facturacion  = collect();
        $pagos        = collect();
        $totalFactura = 0;
        $totalPagado  = 0;

        if ($request->filled('codigo')) {
            $codigo = $request->codigo;

            $estudiante = DB::table('ESTUDIANTES')->where('CODIGO', $codigo)->first();

            if ($estudiante) {
                $facturacion  = DB::table('facturacion')->where('codigo_alumno', $codigo)->orderBy('fecha')->get();
                $pagos        = DB::table('registro_pagos')->where('codigo_alumno', $codigo)->orderBy('fecha')->get();
                $totalFactura = $facturacion->sum('valor');
                $totalPagado  = $pagos->sum('valor');
            }
        }

        return view('control.estudiante', compact('estudiante', 'facturacion', 'pagos', 'totalFactura', 'totalPagado'));
    }
}
