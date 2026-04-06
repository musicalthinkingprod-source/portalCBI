<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ImportacionController extends Controller
{
    // ── Vistas ───────────────────────────────────────────────────────────────

    public function show()
    {
        $lotes = DB::table('registro_pagos')
            ->whereNotNull('lote_importacion')
            ->select('lote_importacion', DB::raw('COUNT(*) as total'), DB::raw('MIN(fecha) as fecha_min'), DB::raw('MAX(fecha) as fecha_max'))
            ->groupBy('lote_importacion')
            ->orderByDesc('lote_importacion')
            ->get();

        return view('importacion.registro_pagos', compact('lotes'));
    }

    public function showFacturacion()
    {
        $lotes = DB::table('facturacion')
            ->whereNotNull('lote_importacion')
            ->select('lote_importacion', DB::raw('COUNT(*) as total'), DB::raw('MIN(fecha) as fecha_min'), DB::raw('MAX(fecha) as fecha_max'))
            ->groupBy('lote_importacion')
            ->orderByDesc('lote_importacion')
            ->get();

        return view('importacion.facturacion', compact('lotes'));
    }

    // ── Importaciones ────────────────────────────────────────────────────────

    public function importarFacturacion(Request $request)
    {
        $request->validate(['archivo' => 'required|file|max:10240']);

        $archivo = $request->file('archivo');
        if (!$archivo || !$archivo->isValid()) {
            return back()->withErrors(['archivo' => 'El archivo no es válido o no se subió correctamente.']);
        }

        $lote   = 'FAC-' . date('Ymd-His');
        $handle = fopen($archivo->getPathname(), 'r');

        $primeraLinea = fgets($handle);
        $separador    = substr_count($primeraLinea, ';') > substr_count($primeraLinea, ',') ? ';' : ',';
        rewind($handle);
        fgetcsv($handle, 1000, $separador); // saltar encabezados

        $insertados = 0;
        $errores    = 0;
        $errDetalle = [];

        while (($fila = fgetcsv($handle, 1000, $separador)) !== false) {
            if (count($fila) < 7) { $errores++; continue; }

            try {
                $fecha = null;
                foreach (['d/m/Y', 'Y-m-d', 'm/d/Y', 'd-m-Y'] as $formato) {
                    $dt = \DateTime::createFromFormat($formato, trim($fila[7]));
                    if ($dt) { $fecha = $dt->format('Y-m-d'); break; }
                }

                DB::table('facturacion')->insert([
                    'codigo_alumno'    => trim($fila[0]),
                    'concepto'         => trim($fila[1]),
                    'valor'            => str_replace(',', '.', trim($fila[2])),
                    'mes'              => trim($fila[3]),
                    'orden'            => is_numeric(trim($fila[4])) ? (int) trim($fila[4]) : null,
                    'codigo_concepto'  => trim($fila[5]),
                    'centro_costos'    => trim($fila[6]),
                    'fecha'            => $fecha ?? trim($fila[7]),
                    'lote_importacion' => $lote,
                ]);
                $insertados++;
            } catch (\Exception $e) {
                $errores++;
                $errDetalle[] = $e->getMessage();
            }
        }

        fclose($handle);

        return back()->with([
            'success'     => true,
            'lote'        => $lote,
            'insertados'  => $insertados,
            'errores'     => $errores,
            'err_detalle' => $errDetalle[0] ?? null,
        ]);
    }

    public function importarRegistroPagos(Request $request)
    {
        $request->validate(['archivo' => 'required|file|max:10240']);

        $archivo = $request->file('archivo');
        if (!$archivo || !$archivo->isValid()) {
            return back()->withErrors(['archivo' => 'El archivo no es válido o no se subió correctamente.']);
        }

        $lote   = 'PAG-' . date('Ymd-His');
        $handle = fopen($archivo->getPathname(), 'r');

        $primeraLinea = fgets($handle);
        $separador    = substr_count($primeraLinea, ';') > substr_count($primeraLinea, ',') ? ';' : ',';
        rewind($handle);
        fgetcsv($handle, 1000, $separador); // saltar encabezados

        $insertados = 0;
        $errores    = 0;
        $errDetalle = [];

        while (($fila = fgetcsv($handle, 1000, $separador)) !== false) {
            if (count($fila) < 6) { $errores++; continue; }

            try {
                $fecha = null;
                foreach (['d/m/Y', 'Y-m-d', 'm/d/Y', 'd-m-Y'] as $formato) {
                    $dt = \DateTime::createFromFormat($formato, trim($fila[1]));
                    if ($dt) { $fecha = $dt->format('Y-m-d'); break; }
                }

                DB::table('registro_pagos')->insert([
                    'codigo_alumno'    => trim($fila[0]),
                    'fecha'            => $fecha ?? trim($fila[1]),
                    'valor'            => str_replace(',', '.', trim($fila[2])),
                    'concepto'         => trim($fila[3]),
                    'mes'              => trim($fila[4]),
                    'orden'            => trim($fila[5]) ?: null,
                    'lote_importacion' => $lote,
                ]);
                $insertados++;
            } catch (\Exception $e) {
                $errores++;
                $errDetalle[] = $e->getMessage();
            }
        }

        fclose($handle);

        return back()->with([
            'success'     => true,
            'lote'        => $lote,
            'insertados'  => $insertados,
            'errores'     => $errores,
            'err_detalle' => $errDetalle[0] ?? null,
        ]);
    }

    // ── Eliminación de lotes ─────────────────────────────────────────────────

    public function eliminarLoteFacturacion(string $lote)
    {
        $eliminados = DB::table('facturacion')->where('lote_importacion', $lote)->delete();
        return back()->with('lote_eliminado', "Lote {$lote}: {$eliminados} registros eliminados de facturación.");
    }

    public function eliminarLotePagos(string $lote)
    {
        $eliminados = DB::table('registro_pagos')->where('lote_importacion', $lote)->delete();
        return back()->with('lote_eliminado', "Lote {$lote}: {$eliminados} registros eliminados de pagos.");
    }
}
