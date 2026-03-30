<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PagosController extends Controller
{
    public function index()
    {
        $pagos = DB::table('registro_pagos')
            ->orderBy('fecha', 'desc')
            ->paginate(40);

        return view('pagos.index', compact('pagos'));
    }

    public function create()
    {
        return view('pagos.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'codigo_alumno' => 'required|integer',
            'fecha'         => 'required|date',
            'valor'         => 'required|numeric',
            'concepto'      => 'required|string|max:100',
            'mes'           => 'required|string|max:20',
            'orden'         => 'nullable|string|max:100',
        ]);

        DB::table('registro_pagos')->insert([
            'codigo_alumno' => $request->codigo_alumno,
            'fecha'         => $request->fecha,
            'valor'         => $request->valor,
            'concepto'      => $request->concepto,
            'mes'           => $request->mes,
            'orden'         => $request->orden,
        ]);

        return redirect()->route('pagos.index')->with('success', 'Pago registrado correctamente.');
    }
}
