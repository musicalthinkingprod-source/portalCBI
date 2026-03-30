<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ImportacionController extends Controller
{
    public function show()
    {
        return view('importacion.registro_pagos');
    }

    public function showFacturacion()
    {
        return view('importacion.facturacion');
    }

    public function importarFacturacion(Request $request)
    {
        $request->validate([
            'archivo' => 'required|file|max:10240',
        ]);

        $archivo = $request->file('archivo');

        if (!$archivo || !$archivo->isValid()) {
            return back()->withErrors(['archivo' => 'El archivo no es válido o no se subió correctamente.']);
        }

        $handle = fopen($archivo->getPathname(), 'r');

        $primeraLinea = fgets($handle);
        $separador    = substr_count($primeraLinea, ';') > substr_count($primeraLinea, ',') ? ';' : ',';
        rewind($handle);

        // Saltar encabezados
        fgetcsv($handle, 1000, $separador);

        $insertados = 0;
        $errores    = 0;
        $errDetalle = [];

        while (($fila = fgetcsv($handle, 1000, $separador)) !== false) {
            if (count($fila) < 7) {
                $errores++;
                continue;
            }

            try {
                $fecha = null;
                foreach (['d/m/Y', 'Y-m-d', 'm/d/Y', 'd-m-Y'] as $formato) {
                    $dt = \DateTime::createFromFormat($formato, trim($fila[7]));
                    if ($dt) { $fecha = $dt->format('Y-m-d'); break; }
                }

                DB::table('facturacion')->insert([
                    'codigo_alumno'   => trim($fila[0]),
                    'concepto'        => trim($fila[1]),
                    'valor'           => str_replace(',', '.', trim($fila[2])),
                    'mes'             => trim($fila[3]),
                    'orden'           => is_numeric(trim($fila[4])) ? (int) trim($fila[4]) : null,
                    'codigo_concepto' => trim($fila[5]),
                    'centro_costos'   => trim($fila[6]),
                    'fecha'           => $fecha ?? trim($fila[7]),
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
            'insertados'  => $insertados,
            'errores'     => $errores,
            'err_detalle' => $errDetalle[0] ?? null,
        ]);
    }

    public function importarRegistroPagos(Request $request)
    {
        $request->validate([
            'archivo' => 'required|file|max:10240',
        ]);

        $archivo = $request->file('archivo');

        if (!$archivo || !$archivo->isValid()) {
            return back()->withErrors(['archivo' => 'El archivo no es válido o no se subió correctamente.']);
        }

        $ruta   = $archivo->getPathname();
        $handle = fopen($ruta, 'r');

        // Detectar separador leyendo la primera línea
        $primeraLinea = fgets($handle);
        $separador    = substr_count($primeraLinea, ';') > substr_count($primeraLinea, ',') ? ';' : ',';
        rewind($handle);

        // Saltar encabezados
        fgetcsv($handle, 1000, $separador);

        $insertados = 0;
        $errores    = 0;
        $errDetalle = [];

        while (($fila = fgetcsv($handle, 1000, $separador)) !== false) {
            if (count($fila) < 6) {
                $errores++;
                continue;
            }

            try {
                // Intentar parsear fecha en varios formatos
                $fecha = null;
                foreach (['d/m/Y', 'Y-m-d', 'm/d/Y', 'd-m-Y'] as $formato) {
                    $dt = \DateTime::createFromFormat($formato, trim($fila[1]));
                    if ($dt) {
                        $fecha = $dt->format('Y-m-d');
                        break;
                    }
                }

                DB::table('registro_pagos')->insert([
                    'codigo_alumno' => trim($fila[0]),
                    'fecha'         => $fecha ?? trim($fila[1]),
                    'valor'         => str_replace(',', '.', trim($fila[2])),
                    'concepto'      => trim($fila[3]),
                    'mes'           => trim($fila[4]),
                    'orden'         => trim($fila[5]) ?: null,
                ]);
                $insertados++;
            } catch (\Exception $e) {
                $errores++;
                $errDetalle[] = $e->getMessage();
            }
        }

        fclose($handle);

        return back()->with([
            'success'    => true,
            'insertados' => $insertados,
            'errores'    => $errores,
            'err_detalle' => $errDetalle[0] ?? null,
        ]);
    }
}
