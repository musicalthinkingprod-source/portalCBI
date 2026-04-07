<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PagosController extends Controller
{
    public function index(Request $request)
    {
        $sortable  = ['codigo_alumno', 'fecha', 'concepto', 'mes', 'valor'];
        $sortCol   = in_array($request->sort, $sortable) ? $request->sort : 'fecha';
        $sortDir   = $request->direction === 'asc' ? 'asc' : 'desc';

        $query = DB::table('registro_pagos')->orderBy($sortCol, $sortDir);

        if ($request->filled('codigo_alumno')) {
            $query->where('codigo_alumno', $request->codigo_alumno);
        }
        if ($request->filled('fecha')) {
            $query->where('fecha', $request->fecha);
        }
        if ($request->filled('concepto')) {
            $query->where('concepto', 'like', '%' . $request->concepto . '%');
        }
        if ($request->filled('mes')) {
            $query->where('mes', 'like', '%' . $request->mes . '%');
        }
        if ($request->filled('orden')) {
            $query->where('orden', 'like', '%' . $request->orden . '%');
        }

        $pagos = $query->paginate(40)->withQueryString();

        return view('pagos.index', compact('pagos', 'sortCol', 'sortDir'));
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
