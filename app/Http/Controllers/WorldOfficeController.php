<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class WorldOfficeController extends Controller
{
    public function index()
    {
        $plantilla = DB::table('plantilla_facturacion')->first();
        $conceptos = DB::table('conceptos')->orderBy('codigo_concepto')->get();

        return view('world-office.index', compact('plantilla', 'conceptos'));
    }

    // ── Guardar / actualizar plantilla empresa ──────────────────────────────────

    public function guardarPlantilla(Request $request)
    {
        $request->validate([
            'empresa'           => 'required|max:100',
            'tipo'              => 'required|max:10',
            'prefijo'           => 'nullable|max:10',
            'cedula_facturador' => 'required|max:20',
            'forma_pago'        => 'required|max:50',
            'numero_inicio'     => 'required|integer|min:1',
        ]);

        $datos = [
            'empresa'           => $request->empresa,
            'tipo'              => strtoupper(trim($request->tipo)),
            'prefijo'           => $request->prefijo ? strtoupper(trim($request->prefijo)) : null,
            'cedula_facturador' => $request->cedula_facturador,
            'forma_pago'        => strtoupper(trim($request->forma_pago)),
            'numero_inicio'     => $request->numero_inicio,
            'updated_at'        => now(),
        ];

        if (DB::table('plantilla_facturacion')->exists()) {
            DB::table('plantilla_facturacion')->limit(1)->update($datos);
        } else {
            DB::table('plantilla_facturacion')->insert(array_merge($datos, ['created_at' => now()]));
        }

        return redirect()->route('world-office.index')->with('ok', 'Plantilla guardada correctamente.');
    }

    // ── Exportar XLSX ───────────────────────────────────────────────────────────

    public function exportarCSV(Request $request)
    {
        $request->validate([
            'mes'               => 'required|string',
            'fecha_facturacion' => 'required|date',
            'fecha_inicio'      => 'required|date',
            'fecha_venc'        => 'required|date',
            'encabezado'        => 'required|string|max:200',
            'nota'              => 'required|string|max:200',
            'cantidad'          => 'nullable|numeric',
            'iva'               => 'nullable|numeric',
        ]);

        $plantilla = DB::table('plantilla_facturacion')->first();
        $mes       = strtoupper(trim($request->mes));

        $filas = DB::table('facturacion as f')
            ->join('ESTUDIANTES as e', 'e.CODIGO', '=', 'f.codigo_alumno')
            ->leftJoin('INFO_PADRES as ip', 'ip.CODIGO', '=', 'f.codigo_alumno')
            ->where('f.mes', $mes)
            ->when($request->filled('conceptos'), fn($q) => $q->whereIn('f.codigo_concepto', $request->conceptos))
            ->select(
                'f.codigo_alumno',
                DB::raw("TRIM(CONCAT(COALESCE(e.NOMBRE1,''),' ',COALESCE(e.APELLIDO1,''),' ',COALESCE(e.APELLIDO2,''))) as nombre"),
                'f.codigo_concepto',
                'f.valor',
                'f.centro_costos',
                DB::raw("COALESCE(NULLIF(ip.CC_ACUD,''), NULLIF(ip.CC_MADRE,''), NULLIF(ip.CC_PADRE,''), '') as documento_id")
            )
            ->orderBy('e.APELLIDO1')
            ->orderBy('e.NOMBRE1')
            ->get();

        $numFactura = $plantilla ? (int) $plantilla->numero_inicio : 1;
        $empresa    = $plantilla->empresa           ?? '';
        $tipo       = $plantilla->tipo              ?? 'FV';
        $prefijo    = $plantilla->prefijo           ?? '';
        $cedula     = $plantilla->cedula_facturador ?? '';
        $formaPago  = $plantilla->forma_pago        ?? '';
        $cantidad   = (float) ($request->cantidad ?? 1);
        $iva        = (float) ($request->iva ?? 0);

        // ── Construir el libro Excel ────────────────────────────────────────────
        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Facturacion');

        $columnas = [
            'EMPRESA QUE FACTURA', 'TIPO', 'PREFIJO', 'NUMERO DE FACTURA',
            'FECHA FACTURACION',   'CEDULA FACTURADOR', 'DOCUMENTO ID',
            'ENCABEZADO FACTURA',  'FORMA DE PAGO',     'FECHA FACTURA',
            'CODIGO ALUMNO',       'NOMBRE',             'CODIGO CONCEPTO',
            'CANTIDAD',            'IVA',                'VALOR',
            'FECHA VENCIMIENTO',   'CENTRO DE COSTO',    'NOTA',
        ];

        // Encabezados con formato
        $sheet->fromArray($columnas, null, 'A1');

        $totalCols = count($columnas);
        $lastCol   = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($totalCols);

        $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E3A5F']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // Filas de datos
        $fila = 2;
        foreach ($filas as $f) {
            $sheet->fromArray([
                $empresa,
                $tipo,
                $prefijo,
                $numFactura++,
                $request->fecha_facturacion,
                $cedula,
                $f->documento_id,
                $request->encabezado,
                $formaPago,
                $request->fecha_inicio,
                (int) $f->codigo_alumno,
                $this->sinTildes($f->nombre),
                $f->codigo_concepto,
                $cantidad,
                $iva,
                (float) $f->valor,
                $request->fecha_venc,
                $f->centro_costos,
                $request->nota,
            ], null, "A{$fila}");

            // Filas alternas con fondo suave
            if ($fila % 2 === 0) {
                $sheet->getStyle("A{$fila}:{$lastCol}{$fila}")
                    ->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('F0F4FA');
            }

            $fila++;
        }

        // Ajustar ancho de columnas automáticamente
        foreach (range(1, $totalCols) as $colIdx) {
            $sheet->getColumnDimensionByColumn($colIdx)->setAutoSize(true);
        }

        // Alinear columna VALOR (P = col 16) a la derecha
        $sheet->getStyle("P2:P{$fila}")
            ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        // Formato numérico para VALOR
        $sheet->getStyle("P2:P{$fila}")
            ->getNumberFormat()->setFormatCode('#,##0.00');

        // ── Generar respuesta ───────────────────────────────────────────────────
        $nombre = 'world_office_' . $mes . '_' . date('Ymd') . '.xlsx';

        $writer = new Xlsx($spreadsheet);

        ob_start();
        $writer->save('php://output');
        $contenido = ob_get_clean();

        return response($contenido, 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $nombre . '"',
            'Cache-Control'       => 'max-age=0',
        ]);
    }

    private function sinTildes(string $s): string
    {
        $s = str_replace(
            ['Á','É','Í','Ó','Ú','á','é','í','ó','ú','ü','Ü','ñ','Ñ'],
            ['A','E','I','O','U','a','e','i','o','u','u','U','n','N'],
            $s
        );
        // Elimina espacios múltiples que quedan cuando campos del nombre están vacíos
        return preg_replace('/\s+/', ' ', trim($s));
    }
}
