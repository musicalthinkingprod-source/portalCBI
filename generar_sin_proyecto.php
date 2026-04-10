<?php
// Script temporal: genera Excel de estudiantes sin grupo de Proyecto
// Ejecutar: php generar_sin_proyecto.php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Font;

// ── Datos ────────────────────────────────────────────────────────────────────

$sinProyecto = DB::table('ESTUDIANTES as e')
    ->leftJoin('LISTADOS_ESPECIALES as le', function ($j) {
        $j->on('le.CODIGO_ALUM', '=', 'e.CODIGO')->where('le.GRUPO', 'LIKE', 'GP%');
    })
    ->where('e.ESTADO', 'MATRICULADO')
    ->whereNull('le.CODIGO_ALUM')
    ->selectRaw("e.CODIGO, TRIM(CONCAT(
        COALESCE(e.APELLIDO1,''),' ',
        COALESCE(e.APELLIDO2,''),' ',
        COALESCE(e.NOMBRE1,''),' ',
        COALESCE(e.NOMBRE2,'')
    )) as nombre_completo, e.CURSO")
    ->orderBy('e.CURSO')
    ->orderBy('e.APELLIDO1')
    ->orderBy('e.NOMBRE1')
    ->get()
    ->map(function ($r) {
        $r->nombre_completo = preg_replace('/\s+/', ' ', trim($r->nombre_completo));
        return $r;
    });

$gruposExistentes = DB::table('LISTADOS_ESPECIALES as le')
    ->join('ESTUDIANTES as e', 'e.CODIGO', '=', 'le.CODIGO_ALUM')
    ->where('e.ESTADO', 'MATRICULADO')
    ->where('le.GRUPO', 'LIKE', 'GP%')
    ->selectRaw("le.GRUPO, COUNT(*) as total")
    ->groupBy('le.GRUPO')
    ->orderByRaw("CAST(SUBSTRING(le.GRUPO, 3) AS UNSIGNED)")
    ->get();

// ── Spreadsheet ──────────────────────────────────────────────────────────────

$spreadsheet = new Spreadsheet();

// ═══ HOJA 1: Lista para docentes ════════════════════════════════════════════

$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Sin Proyecto');

// Título
$sheet->mergeCells('A1:E1');
$sheet->setCellValue('A1', 'ESTUDIANTES SIN GRUPO DE PROYECTO — ' . date('d/m/Y'));
$sheet->getStyle('A1')->applyFromArray([
    'font'      => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E3A5F']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
]);
$sheet->getRowDimension(1)->setRowHeight(28);

// Instrucción
$sheet->mergeCells('A2:E2');
$sheet->setCellValue('A2', 'Cada docente debe escribir su nombre en la columna "Docente que lo recibe" para los estudiantes que son de su grupo.');
$sheet->getStyle('A2')->applyFromArray([
    'font'      => ['italic' => true, 'size' => 10, 'color' => ['rgb' => '555555']],
    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F0F4F8']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
]);
$sheet->getRowDimension(2)->setRowHeight(22);

// Encabezados
$headers = ['Código', 'Nombre completo', 'Curso', 'Docente que lo recibe', 'Grupo asignado (GP#)'];
$headerCols = ['A', 'B', 'C', 'D', 'E'];
$row = 3;

foreach ($headers as $i => $h) {
    $col = $headerCols[$i];
    $sheet->setCellValue("{$col}{$row}", $h);
}

$sheet->getStyle("A{$row}:E{$row}")->applyFromArray([
    'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2563EB']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
    'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'AAAAAA']]],
]);
$sheet->getRowDimension($row)->setRowHeight(18);
$row++;

// Datos
$cursoAnterior = null;
foreach ($sinProyecto as $est) {
    // Separador visual de curso
    if ($est->CURSO !== $cursoAnterior) {
        $sheet->mergeCells("A{$row}:E{$row}");
        $sheet->setCellValue("A{$row}", "  Curso: {$est->CURSO}");
        $sheet->getStyle("A{$row}:E{$row}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => '1E3A5F']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DBEAFE']],
        ]);
        $sheet->getRowDimension($row)->setRowHeight(16);
        $row++;
        $cursoAnterior = $est->CURSO;
    }

    $sheet->setCellValue("A{$row}", $est->CODIGO);
    $sheet->setCellValue("B{$row}", $est->nombre_completo);
    $sheet->setCellValue("C{$row}", $est->CURSO);
    $sheet->setCellValue("D{$row}", '');
    $sheet->setCellValue("E{$row}", '');

    $bgColor = ($row % 2 === 0) ? 'F9FAFB' : 'FFFFFF';
    $sheet->getStyle("A{$row}:E{$row}")->applyFromArray([
        'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bgColor]],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E5E7EB']]],
    ]);
    $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle("C{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

    $row++;
}

// Columnas: ancho
$sheet->getColumnDimension('A')->setWidth(10);
$sheet->getColumnDimension('B')->setWidth(35);
$sheet->getColumnDimension('C')->setWidth(10);
$sheet->getColumnDimension('D')->setWidth(35);
$sheet->getColumnDimension('E')->setWidth(18);

// Borde exterior tabla
$lastRow = $row - 1;
$sheet->getStyle("A3:E{$lastRow}")->getBorders()->getOutline()->setBorderStyle(Border::BORDER_MEDIUM)->getColor()->setRGB('2563EB');

// ═══ HOJA 2: Grupos GP existentes ═══════════════════════════════════════════

$sheet2 = $spreadsheet->createSheet();
$sheet2->setTitle('Grupos GP existentes');

$sheet2->mergeCells('A1:C1');
$sheet2->setCellValue('A1', 'GRUPOS DE PROYECTO YA ASIGNADOS');
$sheet2->getStyle('A1')->applyFromArray([
    'font'      => ['bold' => true, 'size' => 13, 'color' => ['rgb' => 'FFFFFF']],
    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E3A5F']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
]);
$sheet2->getRowDimension(1)->setRowHeight(24);

foreach (['A' => 'Grupo', 'B' => 'Estudiantes', 'C' => 'Disponible para sumar'] as $col => $header) {
    $sheet2->setCellValue("{$col}2", $header);
}
$sheet2->getStyle('A2:C2')->applyFromArray([
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2563EB']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
]);

$r = 3;
foreach ($gruposExistentes as $g) {
    $sheet2->setCellValue("A{$r}", $g->GRUPO);
    $sheet2->setCellValue("B{$r}", $g->total);
    $sheet2->setCellValue("C{$r}", '');
    $sheet2->getStyle("A{$r}:C{$r}")->applyFromArray([
        'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => ($r % 2 === 0 ? 'F9FAFB' : 'FFFFFF')]],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E5E7EB']]],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
    ]);
    $r++;
}

$sheet2->getColumnDimension('A')->setWidth(12);
$sheet2->getColumnDimension('B')->setWidth(16);
$sheet2->getColumnDimension('C')->setWidth(24);

// ── Guardar ──────────────────────────────────────────────────────────────────

$spreadsheet->setActiveSheetIndex(0);

$filename = __DIR__ . '/storage/app/sin_proyecto_' . date('Ymd_Hi') . '.xlsx';
$writer = new Xlsx($spreadsheet);
$writer->save($filename);

echo "✓ Archivo generado: {$filename}\n";
echo "  Total estudiantes sin proyecto: " . count($sinProyecto) . "\n";
echo "  Grupos GP existentes: " . count($gruposExistentes) . "\n";
