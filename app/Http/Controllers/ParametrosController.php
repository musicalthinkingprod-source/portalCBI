<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ParametrosController extends Controller
{
    public function index()
    {
        $centroCostos      = DB::table('centro_costos')->orderBy('codigo_centro_costos')->get();
        $conceptos         = DB::table('conceptos')->orderBy('codigo_concepto')->get();
        $costoPension      = DB::table('costo_pension')->orderBy('codigo_valor_pension')->get();
        $costoTransporte   = DB::table('costo_transporte')->orderBy('codigo_transporte')->get();

        $pension = DB::table('pension as p')
            ->leftJoin('ESTUDIANTES as e', 'e.CODIGO', '=', 'p.codigo_alumno')
            ->select('p.*', DB::raw("CONCAT(COALESCE(e.NOMBRE1,''),' ',COALESCE(e.APELLIDO1,'')) as nombre_alumno"))
            ->orderBy('p.anio', 'desc')->orderBy('p.codigo_alumno')
            ->get();

        $transporte = DB::table('transporte as t')
            ->leftJoin('ESTUDIANTES as e', 'e.CODIGO', '=', 't.codigo_alumno')
            ->select('t.*', DB::raw("CONCAT(COALESCE(e.NOMBRE1,''),' ',COALESCE(e.APELLIDO1,'')) as nombre_alumno"))
            ->orderBy('t.anio', 'desc')->orderBy('t.codigo_alumno')
            ->get();

        $nivelacion = DB::table('nivelacion as n')
            ->leftJoin('ESTUDIANTES as e', 'e.CODIGO', '=', 'n.codigo_alumno')
            ->select('n.*', DB::raw("CONCAT(COALESCE(e.NOMBRE1,''),' ',COALESCE(e.APELLIDO1,'')) as nombre_alumno"))
            ->orderBy('n.anio', 'desc')->orderBy('n.codigo_alumno')
            ->get();

        $listadoTransporte = DB::table('listado_transporte as lt')
            ->leftJoin('ESTUDIANTES as e', 'e.CODIGO', '=', 'lt.codigo')
            ->select('lt.*', DB::raw("CONCAT(COALESCE(e.NOMBRE1,''),' ',COALESCE(e.APELLIDO1,'')) as nombre_alumno"))
            ->orderBy('lt.codigo')
            ->get();

        $observaciones = DB::table('observaciones_contables as o')
            ->leftJoin('ESTUDIANTES as e', 'e.CODIGO', '=', 'o.codigo_alumno')
            ->select('o.*', DB::raw("CONCAT(COALESCE(e.NOMBRE1,''),' ',COALESCE(e.APELLIDO1,'')) as nombre_alumno"))
            ->orderBy('o.codigo_alumno')
            ->get();

        $anioActual = date('Y');

        return view('parametros.index', compact(
            'centroCostos', 'conceptos', 'costoPension', 'costoTransporte',
            'pension', 'transporte', 'nivelacion', 'listadoTransporte', 'observaciones',
            'anioActual'
        ));
    }

    // ── Centro de Costos ────────────────────────────────────────────────────────

    public function storeCentroCostos(Request $request)
    {
        $request->validate([
            'codigo_centro_costos' => 'required|max:20',
            'nombre_centro_costos' => 'required|max:100',
        ]);

        DB::table('centro_costos')->updateOrInsert(
            ['codigo_centro_costos' => strtoupper(trim($request->codigo_centro_costos))],
            ['nombre_centro_costos' => $request->nombre_centro_costos, 'updated_at' => now(), 'created_at' => now()]
        );

        return redirect()->route('parametros.index', ['tab' => 'centro_costos'])->with('ok', 'Centro de costos guardado.');
    }

    public function destroyCentroCostos($codigo)
    {
        DB::table('centro_costos')->where('codigo_centro_costos', $codigo)->delete();
        return redirect()->route('parametros.index', ['tab' => 'centro_costos'])->with('ok', 'Eliminado.');
    }

    // ── Conceptos ───────────────────────────────────────────────────────────────

    public function storeConcepto(Request $request)
    {
        $request->validate([
            'codigo_concepto' => 'required|max:20',
            'concepto'        => 'required|max:100',
            'centro_costos'   => 'required|max:20',
        ]);

        DB::table('conceptos')->updateOrInsert(
            ['codigo_concepto' => strtoupper(trim($request->codigo_concepto))],
            ['concepto' => $request->concepto, 'centro_costos' => $request->centro_costos, 'updated_at' => now(), 'created_at' => now()]
        );

        return redirect()->route('parametros.index', ['tab' => 'conceptos'])->with('ok', 'Concepto guardado.');
    }

    public function destroyConcepto($codigo)
    {
        DB::table('conceptos')->where('codigo_concepto', $codigo)->delete();
        return redirect()->route('parametros.index', ['tab' => 'conceptos'])->with('ok', 'Eliminado.');
    }

    // ── Costo Pensión ───────────────────────────────────────────────────────────

    public function storeCostoPension(Request $request)
    {
        $request->validate([
            'codigo_valor_pension' => 'required|max:20',
            'valor'                => 'required|numeric|min:0',
        ]);

        DB::table('costo_pension')->updateOrInsert(
            ['codigo_valor_pension' => strtoupper(trim($request->codigo_valor_pension))],
            ['valor' => $request->valor, 'updated_at' => now(), 'created_at' => now()]
        );

        return redirect()->route('parametros.index', ['tab' => 'costo_pension'])->with('ok', 'Tarifa de pensión guardada.');
    }

    public function destroyCostoPension($codigo)
    {
        DB::table('costo_pension')->where('codigo_valor_pension', $codigo)->delete();
        return redirect()->route('parametros.index', ['tab' => 'costo_pension'])->with('ok', 'Eliminado.');
    }

    // ── Costo Transporte ────────────────────────────────────────────────────────

    public function storeCostoTransporte(Request $request)
    {
        $request->validate([
            'codigo_transporte' => 'required|max:20',
            'costo'             => 'required|numeric|min:0',
        ]);

        DB::table('costo_transporte')->updateOrInsert(
            ['codigo_transporte' => strtoupper(trim($request->codigo_transporte))],
            ['costo' => $request->costo, 'updated_at' => now(), 'created_at' => now()]
        );

        return redirect()->route('parametros.index', ['tab' => 'costo_transporte'])->with('ok', 'Tarifa de transporte guardada.');
    }

    public function destroyCostoTransporte($codigo)
    {
        DB::table('costo_transporte')->where('codigo_transporte', $codigo)->delete();
        return redirect()->route('parametros.index', ['tab' => 'costo_transporte'])->with('ok', 'Eliminado.');
    }

    // ── Pensión (asignación por alumno) ─────────────────────────────────────────

    public function storePension(Request $request)
    {
        $request->validate([
            'codigo_alumno'       => 'required|integer',
            'codigo_valor_pension'=> 'required|max:20',
            'codigo_concepto'     => 'required|max:20',
            'centro_costos'       => 'required|max:50',
            'anio'                => 'required|digits:4',
        ]);

        DB::table('pension')->updateOrInsert(
            ['codigo_alumno' => $request->codigo_alumno, 'anio' => $request->anio],
            [
                'codigo_valor_pension' => strtoupper(trim($request->codigo_valor_pension)),
                'codigo_concepto'      => strtoupper(trim($request->codigo_concepto)),
                'centro_costos'        => $request->centro_costos,
                'updated_at'           => now(),
                'created_at'           => now(),
            ]
        );

        return redirect()->route('parametros.index', ['tab' => 'pension'])->with('ok', 'Pensión asignada.');
    }

    public function destroyPension($id)
    {
        DB::table('pension')->where('id', $id)->delete();
        return redirect()->route('parametros.index', ['tab' => 'pension'])->with('ok', 'Eliminado.');
    }

    // ── Transporte (asignación por alumno) ──────────────────────────────────────

    public function storeTransporte(Request $request)
    {
        $request->validate([
            'codigo_alumno'    => 'required|integer',
            'codigo_transporte'=> 'required|max:20',
            'codigo_concepto'  => 'required|max:20',
            'centro_costos'    => 'required|max:50',
            'anio'             => 'required|digits:4',
        ]);

        DB::table('transporte')->updateOrInsert(
            ['codigo_alumno' => $request->codigo_alumno, 'anio' => $request->anio],
            [
                'codigo_transporte' => strtoupper(trim($request->codigo_transporte)),
                'codigo_concepto'   => strtoupper(trim($request->codigo_concepto)),
                'centro_costos'     => $request->centro_costos,
                'updated_at'        => now(),
                'created_at'        => now(),
            ]
        );

        return redirect()->route('parametros.index', ['tab' => 'transporte'])->with('ok', 'Transporte asignado.');
    }

    public function destroyTransporte($id)
    {
        DB::table('transporte')->where('id', $id)->delete();
        return redirect()->route('parametros.index', ['tab' => 'transporte'])->with('ok', 'Eliminado.');
    }

    // ── Nivelación (asignación por alumno) ──────────────────────────────────────

    public function storeNivelacion(Request $request)
    {
        $request->validate([
            'codigo_alumno'  => 'required|integer',
            'codigo_valor'   => 'required|max:20',
            'codigo_concepto'=> 'required|max:20',
            'centro_costos'  => 'required|max:50',
            'anio'           => 'required|digits:4',
        ]);

        DB::table('nivelacion')->updateOrInsert(
            ['codigo_alumno' => $request->codigo_alumno, 'anio' => $request->anio],
            [
                'codigo_valor'   => strtoupper(trim($request->codigo_valor)),
                'codigo_concepto'=> strtoupper(trim($request->codigo_concepto)),
                'centro_costos'  => $request->centro_costos,
                'updated_at'     => now(),
                'created_at'     => now(),
            ]
        );

        return redirect()->route('parametros.index', ['tab' => 'nivelacion'])->with('ok', 'Nivelación asignada.');
    }

    public function destroyNivelacion($id)
    {
        DB::table('nivelacion')->where('id', $id)->delete();
        return redirect()->route('parametros.index', ['tab' => 'nivelacion'])->with('ok', 'Eliminado.');
    }

    // ── Listado Transporte ──────────────────────────────────────────────────────

    public function storeListadoTransporte(Request $request)
    {
        $request->validate([
            'codigo'      => 'required|integer',
            'barrio'      => 'nullable|max:60',
            'telefono'    => 'nullable|max:20',
            'quien_recibe'=> 'nullable|max:80',
            'clase_ruta'  => 'nullable|max:30',
            'ruta'        => 'nullable|max:30',
            'direccion'   => 'nullable|max:100',
        ]);

        DB::table('listado_transporte')->updateOrInsert(
            ['codigo' => $request->codigo],
            [
                'barrio'       => $request->barrio,
                'telefono'     => $request->telefono,
                'quien_recibe' => $request->quien_recibe,
                'clase_ruta'   => $request->clase_ruta,
                'ruta'         => $request->ruta,
                'direccion'    => $request->direccion,
                'updated_at'   => now(),
                'created_at'   => now(),
            ]
        );

        return redirect()->route('parametros.index', ['tab' => 'listado_transporte'])->with('ok', 'Listado de transporte guardado.');
    }

    public function destroyListadoTransporte($id)
    {
        DB::table('listado_transporte')->where('id', $id)->delete();
        return redirect()->route('parametros.index', ['tab' => 'listado_transporte'])->with('ok', 'Eliminado.');
    }

    // ── Observaciones Contables ─────────────────────────────────────────────────

    public function storeObservacion(Request $request)
    {
        $request->validate([
            'codigo_alumno' => 'required|integer',
            'observacion'   => 'nullable|string',
        ]);

        DB::table('observaciones_contables')->updateOrInsert(
            ['codigo_alumno' => $request->codigo_alumno],
            ['observacion' => $request->observacion, 'updated_at' => now(), 'created_at' => now()]
        );

        return redirect()->route('parametros.index', ['tab' => 'observaciones'])->with('ok', 'Observación guardada.');
    }

    public function destroyObservacion($id)
    {
        DB::table('observaciones_contables')->where('id', $id)->delete();
        return redirect()->route('parametros.index', ['tab' => 'observaciones'])->with('ok', 'Eliminado.');
    }
}
