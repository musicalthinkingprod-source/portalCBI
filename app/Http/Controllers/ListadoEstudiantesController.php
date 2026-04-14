<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class ListadoEstudiantesController extends Controller
{
    public function index()
    {
        $sedes  = DB::table('ESTUDIANTES')
            ->whereRaw("TRIM(UPPER(ESTADO)) = 'MATRICULADO'")
            ->whereNotNull('SEDE')->where('SEDE', '<>', '')
            ->distinct()->orderBy('SEDE')->pluck('SEDE');

        $cursos = DB::table('ESTUDIANTES')
            ->whereRaw("TRIM(UPPER(ESTADO)) = 'MATRICULADO'")
            ->whereNotNull('CURSO')->where('CURSO', '<>', '')
            ->distinct()->orderBy('CURSO')->pluck('CURSO');

        return view('listado-estudiantes.index', compact('sedes', 'cursos'));
    }

    public function exportar(Request $request)
    {
        $query = DB::table('ESTUDIANTES')
            ->whereRaw("TRIM(UPPER(ESTADO)) = 'MATRICULADO'")
            ->select(
                'CODIGO',
                DB::raw("TRIM(CONCAT(COALESCE(NOMBRE1,''),' ',COALESCE(NOMBRE2,''),' ',COALESCE(APELLIDO1,''),' ',COALESCE(APELLIDO2,''))) as NOMBRE_COMPLETO"),
                'CURSO',
                'SEDE'
            );

        if ($request->filled('sede')) {
            $query->where('SEDE', $request->sede);
        }
        if ($request->filled('curso')) {
            $query->where('CURSO', $request->curso);
        }

        $query->orderBy('CURSO')->orderBy('APELLIDO1')->orderBy('NOMBRE1');

        $estudiantes = $query->get();

        // ── Construir Excel ───────────────────────────────────────────────────
        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Estudiantes');

        $encabezados = ['CODIGO', 'NOMBRE', 'CURSO', 'SEDE'];
        $sheet->fromArray($encabezados, null, 'A1');

        $sheet->getStyle('A1:D1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E3A5F']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $fila = 2;
        foreach ($estudiantes as $e) {
            $sheet->fromArray([
                (int) $e->CODIGO,
                trim(preg_replace('/\s+/', ' ', $e->NOMBRE_COMPLETO)),
                $e->CURSO,
                $e->SEDE,
            ], null, "A{$fila}");

            if ($fila % 2 === 0) {
                $sheet->getStyle("A{$fila}:D{$fila}")
                    ->getFill()->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('F0F4FA');
            }

            $fila++;
        }

        foreach (range(1, 4) as $col) {
            $sheet->getColumnDimensionByColumn($col)->setAutoSize(true);
        }

        $nombre = 'listado_estudiantes_' . date('Ymd') . '.xlsx';
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
}
