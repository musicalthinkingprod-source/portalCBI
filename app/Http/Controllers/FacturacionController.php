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

    // ── Facturación Automática ──────────────────────────────────────────────────

    public function autoIndex()
    {
        $conceptos = DB::table('conceptos')->orderBy('codigo_concepto')->get();

        $lotes = DB::table('facturacion')
            ->whereNotNull('lote_importacion')
            ->where('lote_importacion', 'like', 'AUTO-%')
            ->select('lote_importacion', DB::raw('COUNT(*) as total'), DB::raw('SUM(valor) as suma'), DB::raw('MAX(fecha) as fecha'))
            ->groupBy('lote_importacion')
            ->orderByDesc('fecha')
            ->get();

        return view('facturacion.auto', compact('conceptos', 'lotes'));
    }

    public function autoPreview(Request $request)
    {
        $request->validate([
            'mes'             => 'required|string|max:20',
            'codigo_concepto' => 'required|string|max:20',
            'fecha'           => 'required|date',
            'orden'           => 'nullable|integer',
        ]);

        $codigoConcepto = strtoupper(trim($request->codigo_concepto));
        $mes            = strtoupper(trim($request->mes));
        $fecha          = $request->fecha;
        $orden          = $request->orden;

        $conceptoReg = DB::table('conceptos')->where('codigo_concepto', $codigoConcepto)->first();

        // Estudiantes desde tabla pension
        $desdePension = DB::table('pension as p')
            ->join('costo_pension as cp', 'cp.codigo_valor_pension', '=', 'p.codigo_valor_pension')
            ->leftJoin('ESTUDIANTES as e', 'e.CODIGO', '=', 'p.codigo_alumno')
            ->where('p.codigo_concepto', $codigoConcepto)
            ->select(
                'p.codigo_alumno',
                'p.centro_costos',
                DB::raw('cp.valor as valor'),
                DB::raw("TRIM(CONCAT(COALESCE(e.NOMBRE1,''),' ',COALESCE(e.APELLIDO1,''),' ',COALESCE(e.APELLIDO2,''))) as nombre")
            )
            ->get();

        // Estudiantes desde tabla transporte
        $desdeTransporte = DB::table('transporte as t')
            ->join('costo_transporte as ct', 'ct.codigo_transporte', '=', 't.codigo_transporte')
            ->leftJoin('ESTUDIANTES as e', 'e.CODIGO', '=', 't.codigo_alumno')
            ->where('t.codigo_concepto', $codigoConcepto)
            ->select(
                't.codigo_alumno',
                't.centro_costos',
                DB::raw('ct.costo as valor'),
                DB::raw("TRIM(CONCAT(COALESCE(e.NOMBRE1,''),' ',COALESCE(e.APELLIDO1,''),' ',COALESCE(e.APELLIDO2,''))) as nombre")
            )
            ->get();

        // Estudiantes desde tabla nivelacion (sin tabla de costos propia)
        $desdeNivelacion = DB::table('nivelacion as n')
            ->leftJoin('costo_pension as cp', 'cp.codigo_valor_pension', '=', 'n.codigo_valor')
            ->leftJoin('ESTUDIANTES as e', 'e.CODIGO', '=', 'n.codigo_alumno')
            ->where('n.codigo_concepto', $codigoConcepto)
            ->select(
                'n.codigo_alumno',
                'n.centro_costos',
                DB::raw('COALESCE(cp.valor, 0) as valor'),
                DB::raw("TRIM(CONCAT(COALESCE(e.NOMBRE1,''),' ',COALESCE(e.APELLIDO1,''),' ',COALESCE(e.APELLIDO2,''))) as nombre")
            )
            ->get();

        // Unir y deduplicar por codigo_alumno (prioridad: pension > transporte > nivelacion)
        $todos = $desdePension->merge($desdeTransporte)->merge($desdeNivelacion)
            ->unique('codigo_alumno')
            ->values();

        // Detectar ya facturados este mes + concepto
        $yaFacturados = DB::table('facturacion')
            ->where('mes', $mes)
            ->where('codigo_concepto', $codigoConcepto)
            ->pluck('codigo_alumno')
            ->flip();

        $conceptos = DB::table('conceptos')->orderBy('codigo_concepto')->get();
        $lotes = DB::table('facturacion')
            ->whereNotNull('lote_importacion')
            ->where('lote_importacion', 'like', 'AUTO-%')
            ->select('lote_importacion', DB::raw('COUNT(*) as total'), DB::raw('SUM(valor) as suma'), DB::raw('MAX(fecha) as fecha'))
            ->groupBy('lote_importacion')
            ->orderByDesc('fecha')
            ->get();

        return view('facturacion.auto', compact(
            'conceptos', 'lotes', 'todos', 'yaFacturados',
            'mes', 'fecha', 'orden', 'codigoConcepto', 'conceptoReg'
        ));
    }

    public function autoGenerar(Request $request)
    {
        $request->validate([
            'mes'             => 'required|string|max:20',
            'codigo_concepto' => 'required|string|max:20',
            'fecha'           => 'required|date',
            'orden'           => 'nullable|integer',
        ]);

        $codigoConcepto = strtoupper(trim($request->codigo_concepto));
        $mes            = strtoupper(trim($request->mes));
        $fecha          = $request->fecha;
        $orden          = $request->orden;
        $lote           = 'AUTO-' . $mes . '-' . $codigoConcepto . '-' . date('Y');

        $conceptoReg = DB::table('conceptos')->where('codigo_concepto', $codigoConcepto)->first();
        $descripcion = $conceptoReg ? $this->sinTildes($conceptoReg->concepto) : $codigoConcepto;

        // Misma lógica de búsqueda que autoPreview
        $desdePension = DB::table('pension as p')
            ->join('costo_pension as cp', 'cp.codigo_valor_pension', '=', 'p.codigo_valor_pension')
            ->where('p.codigo_concepto', $codigoConcepto)
            ->select('p.codigo_alumno', 'p.centro_costos', DB::raw('cp.valor as valor'))
            ->get();

        $desdeTransporte = DB::table('transporte as t')
            ->join('costo_transporte as ct', 'ct.codigo_transporte', '=', 't.codigo_transporte')
            ->where('t.codigo_concepto', $codigoConcepto)
            ->select('t.codigo_alumno', 't.centro_costos', DB::raw('ct.costo as valor'))
            ->get();

        $desdeNivelacion = DB::table('nivelacion as n')
            ->leftJoin('costo_pension as cp', 'cp.codigo_valor_pension', '=', 'n.codigo_valor')
            ->where('n.codigo_concepto', $codigoConcepto)
            ->select('n.codigo_alumno', 'n.centro_costos', DB::raw('COALESCE(cp.valor, 0) as valor'))
            ->get();

        $todos = $desdePension->merge($desdeTransporte)->merge($desdeNivelacion)
            ->unique('codigo_alumno')
            ->values();

        // Excluir ya facturados
        $yaFacturados = DB::table('facturacion')
            ->where('mes', $mes)
            ->where('codigo_concepto', $codigoConcepto)
            ->pluck('codigo_alumno')
            ->flip();

        $insertados = 0;
        $filas = [];

        foreach ($todos as $row) {
            if (isset($yaFacturados[$row->codigo_alumno])) {
                continue;
            }
            $filas[] = [
                'codigo_alumno'   => $row->codigo_alumno,
                'concepto'        => $descripcion,
                'valor'           => $row->valor,
                'mes'             => $mes,
                'orden'           => $orden,
                'codigo_concepto' => $codigoConcepto,
                'concepto_otro'   => null,
                'centro_costos'   => $row->centro_costos,
                'fecha'           => $fecha,
                'lote_importacion'=> $lote,
            ];
            $insertados++;
        }

        if (!empty($filas)) {
            DB::table('facturacion')->insert($filas);
        }

        return redirect()->route('facturacion.auto')->with('ok',
            "Se generaron {$insertados} registros — Lote: {$lote}"
        );
    }

    private function sinTildes(string $s): string
    {
        return str_replace(
            ['Á','É','Í','Ó','Ú','á','é','í','ó','ú','ü','Ü','ñ','Ñ'],
            ['A','E','I','O','U','a','e','i','o','u','u','U','n','N'],
            $s
        );
    }

    public function autoEliminarLote($lote)
    {
        DB::table('facturacion')
            ->where('lote_importacion', $lote)
            ->where('lote_importacion', 'like', 'AUTO-%')
            ->delete();

        return redirect()->route('facturacion.auto')->with('ok', "Lote {$lote} eliminado.");
    }
}
