<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FacturacionController extends Controller
{
    public function index()
    {
        $facturas = DB::table('facturacion')
            ->orderBy('fecha', 'desc')
            ->paginate(40);

        return view('facturacion.index', compact('facturas'));
    }

    public function create()
    {
        return view('facturacion.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'codigo_alumno'  => 'required|integer',
            'concepto'       => 'required|string|max:100',
            'valor'          => 'required|numeric',
            'mes'            => 'required|string|max:20',
            'orden'          => 'nullable|integer',
            'codigo_concepto'=> 'nullable|string|max:20',
            'concepto_otro'  => 'nullable|string|max:100',
            'centro_costos'  => 'nullable|string|max:50',
            'fecha'          => 'required|date',
        ]);

        DB::table('facturacion')->insert([
            'codigo_alumno'  => $request->codigo_alumno,
            'concepto'       => $request->concepto,
            'valor'          => $request->valor,
            'mes'            => $request->mes,
            'orden'          => $request->orden,
            'codigo_concepto'=> $request->codigo_concepto,
            'concepto_otro'  => $request->concepto === 'OTRO' ? $request->concepto_otro : null,
            'centro_costos'  => $request->centro_costos,
            'fecha'          => $request->fecha,
        ]);

        return redirect()->route('facturacion.index')->with('success', 'Factura registrada correctamente.');
    }
}
