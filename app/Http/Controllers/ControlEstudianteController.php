<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ControlEstudianteController extends Controller
{
    public function saveObservacion(Request $request)
    {
        $request->validate([
            'codigo_alumno' => 'required|integer',
            'observacion'   => 'nullable|string',
        ]);

        DB::table('observaciones_contables')->updateOrInsert(
            ['codigo_alumno' => $request->codigo_alumno],
            ['observacion' => $request->observacion, 'updated_at' => now(), 'created_at' => now()]
        );

        return redirect()->route('control.estudiante', ['codigo' => $request->codigo_alumno])
            ->with('ok', 'Observación guardada correctamente.');
    }

    public function index(Request $request)
    {
        $estudiante   = null;
        $facturacion  = collect();
        $pagos        = collect();
        $totalFactura = 0;
        $totalPagado  = 0;
        $observacion  = null;
        $transporte   = null;

        if ($request->filled('codigo')) {
            $codigo = $request->codigo;

            $estudiante = DB::table('ESTUDIANTES')->where('CODIGO', $codigo)->first();

            if ($estudiante) {
                $facturacion  = DB::table('facturacion')->where('codigo_alumno', $codigo)->orderBy('fecha')->get();
                $pagos        = DB::table('registro_pagos')->where('codigo_alumno', $codigo)->orderBy('fecha')->get();
                $totalFactura = $facturacion->sum('valor');
                $totalPagado  = $pagos->sum('valor');
                $observacion  = DB::table('observaciones_contables')->where('codigo_alumno', $codigo)->first();
                $transporte   = DB::table('listado_transporte')->where('codigo', $codigo)->first();
            }
        }

        return view('control.estudiante', compact('estudiante', 'facturacion', 'pagos', 'totalFactura', 'totalPagado', 'observacion', 'transporte'));
    }
}
