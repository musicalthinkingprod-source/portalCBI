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

    /**
     * Facturación y pagos del estudiante, opcionalmente acotados a un rango de
     * fechas. Mismo criterio que cartera/deudores: se filtra por el campo fecha
     * de cada movimiento.
     */
    private function movimientos($codigo, ?string $fechaDesde, ?string $fechaHasta): array
    {
        $facturacion = DB::table('facturacion')
            ->where('codigo_alumno', $codigo)
            ->when($fechaDesde, fn($q) => $q->whereDate('fecha', '>=', $fechaDesde))
            ->when($fechaHasta, fn($q) => $q->whereDate('fecha', '<=', $fechaHasta))
            ->orderBy('fecha')
            ->get();

        $pagos = DB::table('registro_pagos')
            ->where('codigo_alumno', $codigo)
            ->when($fechaDesde, fn($q) => $q->whereDate('fecha', '>=', $fechaDesde))
            ->when($fechaHasta, fn($q) => $q->whereDate('fecha', '<=', $fechaHasta))
            ->orderBy('fecha')
            ->get();

        return [$facturacion, $pagos];
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
        $saldoGlobal  = null;

        $fechaDesde = $request->filled('fecha_desde') ? $request->input('fecha_desde') : null;
        $fechaHasta = $request->filled('fecha_hasta') ? $request->input('fecha_hasta') : null;

        if ($request->filled('codigo')) {
            $codigo = $request->codigo;

            $estudiante = DB::table('ESTUDIANTES')->where('CODIGO', $codigo)->first();

            if ($estudiante) {
                [$facturacion, $pagos] = $this->movimientos($codigo, $fechaDesde, $fechaHasta);
                $totalFactura = $facturacion->sum('valor');
                $totalPagado  = $pagos->sum('valor');
                $observacion  = DB::table('observaciones_contables')->where('codigo_alumno', $codigo)->first();
                $transporte   = DB::table('listado_transporte')->where('codigo', $codigo)->first();

                // Con filtro activo se muestra además el saldo histórico completo,
                // para no confundir el saldo del rango con la deuda real
                if ($fechaDesde || $fechaHasta) {
                    $saldoGlobal = DB::table('facturacion')->where('codigo_alumno', $codigo)->sum('valor')
                                 - DB::table('registro_pagos')->where('codigo_alumno', $codigo)->sum('valor');
                }
            }
        }

        return view('control.estudiante', compact(
            'estudiante', 'facturacion', 'pagos', 'totalFactura', 'totalPagado',
            'observacion', 'transporte', 'fechaDesde', 'fechaHasta', 'saldoGlobal'
        ));
    }

    public function exportarExcel(Request $request)
    {
        $codigo = $request->input('codigo');
        abort_unless($codigo, 400, 'Falta el código del estudiante.');

        $estudiante = DB::table('ESTUDIANTES')->where('CODIGO', $codigo)->first();
        abort_unless($estudiante, 404, 'Estudiante no encontrado.');

        $fechaDesde = $request->filled('fecha_desde') ? $request->input('fecha_desde') : null;
        $fechaHasta = $request->filled('fecha_hasta') ? $request->input('fecha_hasta') : null;

        [$facturacion, $pagos] = $this->movimientos($codigo, $fechaDesde, $fechaHasta);

        $nombre = trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
            $estudiante->NOMBRE1, $estudiante->NOMBRE2, $estudiante->APELLIDO1, $estudiante->APELLIDO2
        ]))));

        $writer = new \App\Helpers\SimpleXlsx();
        $writer->addRow(['CONTROL POR ESTUDIANTE']);
        $writer->addRow(['CODIGO', (int) $estudiante->CODIGO]);
        $writer->addRow(['NOMBRE', $nombre]);
        $writer->addRow(['CURSO', $estudiante->CURSO ?? '']);
        $writer->addRow(['RANGO', self::textoRango($fechaDesde, $fechaHasta)]);
        $writer->addRow([]);

        $writer->addRow(['FACTURACION']);
        $writer->addRow(['CODIGO', 'FECHA', 'CONCEPTO', 'MES', 'ORDEN', 'VALOR']);
        foreach ($facturacion as $f) {
            $writer->addRow([
                (int) $estudiante->CODIGO,
                (string) $f->fecha,
                $f->concepto ?? '',
                $f->mes ?? '',
                $f->orden ?? '',
                (float) $f->valor,
            ]);
        }
        $writer->addRow(['', '', '', '', 'TOTAL FACTURADO', (float) $facturacion->sum('valor')]);
        $writer->addRow([]);

        $writer->addRow(['PAGOS REALIZADOS']);
        $writer->addRow(['CODIGO', 'FECHA', 'CONCEPTO', 'MES', 'ORDEN', 'VALOR']);
        foreach ($pagos as $p) {
            $writer->addRow([
                (int) $estudiante->CODIGO,
                (string) $p->fecha,
                $p->concepto ?? '',
                $p->mes ?? '',
                $p->orden ?? '',
                (float) $p->valor,
            ]);
        }
        $writer->addRow(['', '', '', '', 'TOTAL PAGADO', (float) $pagos->sum('valor')]);
        $writer->addRow([]);
        $writer->addRow(['', '', '', '', 'SALDO', (float) ($facturacion->sum('valor') - $pagos->sum('valor'))]);

        $tmp = storage_path('app') . DIRECTORY_SEPARATOR . 'ctrl_' . uniqid() . '.xlsx';
        $writer->save($tmp);

        $archivo = 'control_estudiante_' . $estudiante->CODIGO . '_' . date('Ymd_His') . '.xlsx';
        return response()->download($tmp, $archivo, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /** Etiqueta legible del rango, para encabezar los archivos exportados. */
    public static function textoRango(?string $desde, ?string $hasta): string
    {
        if (!$desde && !$hasta) return 'Acumulado total (sin filtro de fechas)';
        if ($desde && $hasta)   return "Del {$desde} al {$hasta}";
        if ($desde)             return "Desde {$desde}";
        return "Hasta {$hasta}";
    }
}
