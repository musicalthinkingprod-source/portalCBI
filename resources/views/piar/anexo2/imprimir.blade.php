<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>PIAR Anexo 2 – {{ $apellidos }}, {{ $nombreCompleto }} – {{ $materia->NOMBRE_MAT ?? '' }}</title>
<style>
@page { size: letter; margin: 1.4cm 2.3cm 1.6cm 2.3cm; }
* { box-sizing: border-box; }
html { background: white; }
body {
  margin: 0 auto;
  max-width: 21.6cm;
  padding: 1.4cm 2.3cm;
  font-family: Calibri, 'Segoe UI', Arial, sans-serif;
  font-size: 12pt;
  line-height: 1.15;
  color: #000;
}
@media print { body { padding: 0; } .no-print { display: none !important; } }
table { border-collapse: collapse; width: 100%; margin-bottom: 4pt; }
td, th { border: 1px solid #000; padding: 3pt 5pt; vertical-align: top; font-size: 12pt; }
p { margin: 0 0 1pt 0; line-height: 1.15; }
.btn-print { display:inline-block; margin:10px 5px; padding:8px 20px; background:#1e40af; color:white; border:none; border-radius:6px; font-size:13px; cursor:pointer; text-decoration:none; }
.btn-back  { background:#6b7280; }
</style>
</head>
<body>

<div class="no-print" style="margin-bottom:16px;">
    <a href="{{ route('piar.anexo2.form', [$estudiante->CODIGO, $codigoMat]) }}" class="btn-print btn-back">← Volver</a>
    <button onclick="window.print()" class="btn-print">🖨️ Imprimir / PDF</button>
</div>

@php
    $v = fn($campo, $default = '') => ($piarMat && $piarMat->$campo !== null && $piarMat->$campo !== '') ? $piarMat->$campo : $default;
@endphp

{{-- ══ ENCABEZADO ══ --}}
<table border="1" cellspacing="0" cellpadding="6" style="border-collapse:collapse;width:100%">
<tr>
<td style="width:32%;text-align:center;vertical-align:middle;padding:8pt;">
    <img src="{{ asset('images/mineducacion.png') }}" alt="MinEducación" style="max-height:50pt;max-width:100%;display:block;margin:0 auto 5pt auto;">
    <img src="{{ asset('images/colombia.png') }}" alt="Gobierno de Colombia" style="max-height:28pt;max-width:100%;display:block;margin:0 auto;">
</td>
<td style="text-align:center;vertical-align:middle;padding:8pt;">
    <p style="text-align:center;line-height:1.5"><span style="color:#8496B0;font-size:18pt"><b>PIAR</b></span></p>
    <p style="text-align:center;line-height:1.5"><span style="color:#8496B0;font-size:9pt"><b>Decreto 1421/2017</b></span></p>
</td>
<td style="width:28%;text-align:center;vertical-align:middle;padding:8pt;">
    <img src="{{ asset('images/escudoCBI.png') }}" alt="CBI" style="max-height:55pt;max-width:100%;display:block;margin:0 auto 4pt auto;">
    <p style="margin:0;font-size:9pt;font-weight:bold;color:#1e3a8a;">Colegio Bilingüe Integral</p>
</td>
</tr>
</table>

<p>&nbsp;</p>

{{-- ══ ANEXO 2 – ENCABEZADO ══ --}}
<table border="1" cellspacing="0" cellpadding="4" style="border-collapse:collapse;width:100%;page-break-before:always;break-before:page;">
<tr>
<td colspan="4"><p style="text-align:center;margin-top:6pt;margin-bottom:6pt;line-height:1.5"><span style="color:#8496B0;font-size:12pt"><b>Plan Individual de Ajustes Razonables – PIAR – ANEXO 2</b></span></p></td>
</tr>
<tr>
<td><p style="margin-bottom:8pt;line-height:1.08"><b>Fecha de elaboración:</b></p><p style="margin-bottom:8pt;line-height:1.08">{{ now()->translatedFormat('F Y') }}</p></td>
<td><p style="margin-bottom:8pt;line-height:1.08"><b>Institución educativa:</b></p><p style="margin-bottom:8pt;line-height:1.08">Colegio Bilingüe Integral.</p></td>
<td><p style="margin-bottom:8pt;line-height:1.08"><b>Sede:</b></p><p style="margin-bottom:8pt;line-height:1.08">{{ $estudiante->SEDE ?? '' }}</p></td>
<td><p style="margin-bottom:8pt;line-height:1.08"><b>Jornada:</b></p><p style="text-align:center;margin-bottom:8pt;line-height:1.08">Única.</p></td>
</tr>
<tr>
<td colspan="4"><p style="margin-bottom:8pt;line-height:1.08"><b>Docentes que elaboran y cargo:</b></p><p><b>{{ $docente->NOMBRE_DOC ?? '' }}</b></p></td>
</tr>
<tr>
<td colspan="4"><p style="margin-bottom:8pt;line-height:1.08">&nbsp;</p></td>
</tr>
</table>

<p style="margin-bottom:8pt;line-height:1.08">&nbsp;</p>

{{-- ══ DATOS DEL ESTUDIANTE ══ --}}
<table border="1" cellspacing="0" cellpadding="4" style="border-collapse:collapse;width:100%">
<tr>
<td colspan="2"><p style="text-align:center;margin-bottom:8pt;line-height:1.08"><b>DATOS DEL ESTUDIANTE</b></p></td>
</tr>
<tr>
<td><p style="margin-bottom:8pt;line-height:1.08"><b>Nombre del estudiante: </b>{{ $nombreCompleto }} {{ $apellidos }}</p></td>
<td><p style="margin-bottom:8pt;line-height:1.08"><b>Documento de Identificación: </b>{{ $numId }}</p></td>
</tr>
<tr>
<td><p style="margin-bottom:8pt;line-height:1.08"><b>Edad: </b>{{ $edad }} años</p><p><b>Fecha de Nacimiento: </b>{{ $fechaNac }}</p></td>
<td><p style="margin-bottom:8pt;line-height:1.08"><b>Grado: </b>{{ $grado }}{{ $estudiante->CURSO ? ' – ' . $estudiante->CURSO : '' }}</p></td>
</tr>
<tr>
<td colspan="2">
    <p style="margin-top:6pt;margin-bottom:4pt;line-height:1.15"><span style="color:#5B9BD5"><b>Diagnóstico PIAR:</b></span></p>
    <p style="margin-bottom:6pt;line-height:1.15">{{ $piarDiag->DIAGNOSTICO ?? '' }}</p>
</td>
</tr>
</table>

<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>

{{-- ══ PLAN DE AJUSTES RAZONABLES ══ --}}
<table border="1" cellspacing="0" cellpadding="4" style="border-collapse:collapse;width:100%">
<tr>
<td colspan="4" style="background-color:#FFF2CC"><p style="text-align:center;margin-top:6pt;margin-bottom:6pt"><span style="color:#2E74B5;font-size:11pt"><b>PLAN INDIVIDUAL DE AJUSTES RAZONABLES</b></span></p><p style="text-align:center;margin-top:6pt;margin-bottom:6pt"><span style="color:#2E74B5;font-size:11pt">A implementar en cada periodo del año lectivo.</span></p></td>
</tr>
<tr>
<td colspan="4" style="background-color:#FFFFFF"><p style="text-align:justify;margin-top:6pt;margin-bottom:6pt;line-height:1.5"><span style="font-size:9pt">Este formato contiene ajustes al contexto institucional para lograr compatibilidad con nuestra conceptualización y dar respuesta al decreto 1421.</span></p></td>
</tr>
<tr>
<td colspan="4" style="background-color:#F2F2F2"><p style="margin-top:6pt;margin-bottom:6pt;line-height:1.5"><span style="color:#2E74B5;font-size:12pt"><b>ASIGNATURA: {{ $materia->NOMBRE_MAT ?? '' }}</b></span></p></td>
</tr>
<tr>
<td colspan="4" style="background-color:#FFFFFF"><p style="margin-top:6pt;margin-bottom:6pt;line-height:1.5"><b>Nombre del docente: {{ $docente->NOMBRE_DOC ?? '' }}</b></p></td>
</tr>
<tr>
<td colspan="4" style="background-color:#FFFFFF">
    <p style="margin-top:6pt;margin-bottom:4pt;line-height:1.08"><b>Barreras para acceder al aprendizaje.</b></p>
    <p style="margin-top:0.1pt;line-height:1.08">{{ $v('BARRERAS') }}</p>
    <p>&nbsp;</p>
</td>
</tr>
<tr>
<td colspan="4" style="background-color:#DEEAF6"><p style="text-align:center;margin-top:6pt;margin-bottom:6pt;line-height:1.5"><span style="color:#2E74B5"><b>AJUSTES RAZONABLES ORGANIZADOS POR PERIODOS ACADÉMICOS</b></span></p></td>
</tr>
<tr>
<td rowspan="2" style="background-color:#E2EFD9;text-align:center;vertical-align:middle;"><p><b>PERIODO</b></p></td>
<td style="background-color:#FFFFFF"><p style="text-align:justify;margin-top:6pt;margin-bottom:6pt;line-height:1.5"><span style="color:#00B0F0;font-size:9pt"><b>PROPÓSITOS / LOGROS</b></span></p><p style="text-align:justify;margin-top:6pt;margin-bottom:6pt;line-height:1.5"><span style="font-size:9pt">Objetivos/propósitos para el año lectivo de cada periodo escolar (Estas son para todo el grado, de acuerdo con los DBA)</span></p></td>
<td style="background-color:#FFFFFF"><p style="text-align:justify;margin-top:6pt;margin-bottom:6pt;line-height:1.5"><span style="color:#00B0F0;font-size:9pt"><b>AJUSTES RAZONABLES / METODOLOGÍA Y DIDÁCTICA</b></span></p><p style="text-align:justify;margin-top:6pt;margin-bottom:6pt;line-height:1.5"><span style="font-size:9pt">Módulos de apoyo, guías diversificadas, material concreto, pictogramas, estrategias pedagógicas (DUA).</span></p></td>
<td style="background-color:#FFFFFF"><p style="text-align:center;margin-top:6pt;margin-bottom:6pt;line-height:1.5"><span style="color:#00B0F0;font-size:9pt"><b>EVALUACIÓN DE LOS AJUSTES Y SEGUIMIENTO.</b></span></p><p style="text-align:justify;margin-top:6pt;margin-bottom:6pt;line-height:1.5"><span style="font-size:9pt">Esta casilla será diligenciada durante el transcurso del año lectivo.</span></p></td>
</tr>
<tr>
<td style="background-color:#FFFFFF"><p style="margin-top:6pt;margin-bottom:6pt;line-height:1.5"><span style="color:#00B0F0;font-size:9pt">Ajustes Razonables </span><span style="font-size:9pt">en propósitos y objetivos académicos en cada asignatura.</span></p></td>
<td style="background-color:#FFFFFF"><p style="margin-top:6pt;margin-bottom:6pt;line-height:1.5"><span style="color:#00B0F0;font-size:9pt">Ajustes razonables </span><span style="font-size:9pt">en las herramientas pedagógicas y didácticas que se requiere implementar.</span></p></td>
<td style="background-color:#FFFFFF"><p style="margin-top:6pt;margin-bottom:6pt;line-height:1.5"><span style="font-size:9pt">Medición de la efectividad de la implementación de los </span><span style="color:#00B0F0;font-size:9pt">ajustes razonables</span><span style="font-size:9pt"> determinados, rúbricas de evaluación.</span></p></td>
</tr>
@foreach([
    ['num'=>1,'label'=>'PRIMER'],
    ['num'=>2,'label'=>'SEGUNDO'],
    ['num'=>3,'label'=>'TERCERO'],
    ['num'=>4,'label'=>'CUARTO'],
] as $p)
<tr>
<td style="background-color:#E2EFD9;text-align:center;vertical-align:middle;"><p><b>{{ $p['label'] }}</b></p></td>
<td style="background-color:#FFFFFF"><p style="text-align:justify;margin-bottom:0.1pt;line-height:1.15">{{ $v('LOGRO'.$p['num']) }}</p><p>&nbsp;</p></td>
<td style="background-color:#FFFFFF"><p style="text-align:justify;margin-top:6pt;margin-bottom:6pt;line-height:1.15">{{ $v('DIDACT'.$p['num']) }}</p></td>
<td style="background-color:#FFFFFF"><p style="margin-top:6pt;margin-bottom:6pt;line-height:1.5">{{ $v('EVAL'.$p['num']) }}</p></td>
</tr>
@endforeach


</table>

</body>
</html>
