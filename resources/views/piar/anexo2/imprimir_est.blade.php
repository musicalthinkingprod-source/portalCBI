<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8"/>
<title>PIAR Anexo 2 – {{ $apellidos }}, {{ $nombreCompleto }}</title>
<style>
@page { size: letter; margin: 1.4cm 2.3cm 1.6cm 2.3cm; }
* { box-sizing: border-box; }
html { background: white; }
body {
  margin: 0 auto;
  max-width: 21.6cm;
  padding: 1.4cm 2.3cm;
  font-family: Calibri, 'Segoe UI', Arial, sans-serif;
  font-size: 11pt;
  line-height: 1.15;
  color: #000;
}
@media print { body { padding: 0; } .no-print { display: none !important; } }
table { border-collapse: collapse; width: 100%; margin-bottom: 6pt; }
td, th { border: 1px solid #000; padding: 4pt 6pt; vertical-align: top; font-size: 11pt; }
p { margin: 0 0 2pt 0; line-height: 1.15; }
.btn-print { display:inline-block; margin:10px 5px; padding:8px 20px; background:#1e40af; color:white; border:none; border-radius:6px; font-size:13px; cursor:pointer; text-decoration:none; }
.btn-back  { background:#6b7280; }
.page-break { page-break-before: always; break-before: page; }
</style>
</head>
<body>

<div class="no-print" style="margin-bottom:16px;">
    <a href="{{ route('piar.informe') }}" class="btn-print btn-back">← Volver al informe</a>
    <button onclick="window.print()" class="btn-print">🖨️ Imprimir / PDF</button>
</div>

{{-- ══ TÍTULO ANEXO 2 ══ --}}
<table>
<tr>
<td colspan="4"><p style="text-align:center;margin:6pt 0;"><span style="color:#8496B0;font-size:12pt"><b>Plan Individual de Ajustes Razonables – PIAR – ANEXO 2</b></span></p></td>
</tr>
<tr>
<td><p style="margin-bottom:6pt"><b>Fecha de elaboración:</b></p><p>{{ now()->translatedFormat('F Y') }}</p></td>
<td><p style="margin-bottom:6pt"><b>Institución educativa:</b></p><p>Colegio Bilingüe Integral.</p></td>
<td><p style="margin-bottom:6pt"><b>Sede:</b></p><p>{{ $estudiante->SEDE ?? '' }}</p></td>
<td><p style="margin-bottom:6pt"><b>Jornada:</b></p><p style="text-align:center">Única.</p></td>
</tr>
<tr>
<td colspan="4">
    <p style="margin-bottom:6pt"><b>Docentes que elaboran y cargo:</b></p>
    @foreach($docentesElaboran as $doc)
    <p>{{ $doc->NOMBRE_DOC }}@if($doc->CARGO) – <span style="font-size:9pt;color:#555">{{ $doc->CARGO }}</span>@endif</p>
    @endforeach
</td>
</tr>
</table>

<p>&nbsp;</p>

{{-- ══ DATOS DEL ESTUDIANTE ══ --}}
<table>
<tr>
<td colspan="2"><p style="text-align:center;margin:4pt 0"><b>DATOS DEL ESTUDIANTE</b></p></td>
</tr>
<tr>
<td><p><b>Nombre del estudiante: </b>{{ $nombreCompleto }} {{ $apellidos }}</p></td>
<td><p><b>Documento de Identificación: </b>{{ $numId }}</p></td>
</tr>
<tr>
<td><p style="margin-bottom:4pt"><b>Edad: </b>{{ $edad }} años</p><p><b>Fecha de Nacimiento: </b>{{ $fechaNac }}</p></td>
<td><p style="margin-bottom:4pt"><b>Grado: </b>{{ $grado }}{{ $estudiante->CURSO ? ' – ' . $estudiante->CURSO : '' }}</p><p><b>Código: </b>{{ $estudiante->CODIGO }}</p></td>
</tr>
</table>

<p>&nbsp;</p>

{{-- ══ CARACTERIZACIÓN POR DIRECTOR DE GRUPO ══ --}}
@if($caractDir)
<table>
<tr>
<td style="background-color:#DEEAF6">
    <p style="text-align:center;margin:6pt 0"><span style="color:#2E74B5;font-size:11pt"><b>CARACTERIZACIÓN POR DIRECTOR DE GRUPO</b></span></p>
</td>
</tr>
<tr>
<td style="background-color:#F2F2F2">
    <p><b>{{ $caractDir->NOMBRE_DOC ?? 'Director(a) de grupo' }}</b></p>
</td>
</tr>
<tr>
<td>
    <p style="text-align:justify;line-height:1.4;white-space:pre-wrap">{{ $caractDir->CARACTERIZACION }}</p>
</td>
</tr>
</table>
<p>&nbsp;</p>
@endif

{{-- ══ CARACTERIZACIONES POR MATERIA ══ --}}
@if($caractMats->isNotEmpty())
<table>
<tr>
<td colspan="2" style="background-color:#DEEAF6">
    <p style="text-align:center;margin:6pt 0"><span style="color:#2E74B5;font-size:11pt"><b>CARACTERIZACIONES POR MATERIA</b></span></p>
</td>
</tr>
@foreach($caractMats as $cm)
<tr>
<td style="background-color:#F2F2F2;width:35%">
    <p><b>{{ $cm->NOMBRE_MAT }}</b></p>
    @if($cm->NOMBRE_DOC)<p style="font-size:9pt;color:#555">{{ $cm->NOMBRE_DOC }}</p>@endif
</td>
<td>
    <p style="text-align:justify;line-height:1.4;white-space:pre-wrap">{{ $cm->CARACTERIZACION }}</p>
</td>
</tr>
@endforeach
</table>
<p>&nbsp;</p>
@endif
</table>

<p>&nbsp;</p>

{{-- ══ AJUSTES RAZONABLES POR MATERIA ══ --}}
@if($ajustes->isNotEmpty())
<table style="margin-bottom:0;">
<tr>
<td colspan="4" style="background-color:#FFF2CC">
    <p style="text-align:center;margin:6pt 0"><span style="color:#2E74B5;font-size:11pt"><b>PLAN INDIVIDUAL DE AJUSTES RAZONABLES</b></span></p>
    <p style="text-align:center;margin-bottom:6pt"><span style="color:#2E74B5;font-size:10pt">A implementar en cada periodo del año lectivo.</span></p>
</td>
</tr>
<tr>
<td colspan="4"><p style="text-align:justify;margin:4pt 0;line-height:1.4"><span style="font-size:9pt">Este formato contiene ajustes al contexto institucional para lograr compatibilidad con nuestra conceptualización y dar respuesta al decreto 1421.</span></p></td>
</tr>
<tr>
<td colspan="4" style="background-color:#DEEAF6">
    <p style="text-align:center;margin:4pt 0"><span style="color:#2E74B5"><b>AJUSTES RAZONABLES ORGANIZADOS POR PERIODOS ACADÉMICOS</b></span></p>
</td>
</tr>
<tr>
<td rowspan="2" style="background-color:#E2EFD9;text-align:center;vertical-align:middle;"><p><b>PERIODO</b></p></td>
<td style="background-color:#FFFFFF"><p style="text-align:justify;margin:4pt 0;line-height:1.4"><span style="color:#00B0F0;font-size:9pt"><b>PROPÓSITOS / LOGROS</b></span></p><p style="font-size:9pt">Ajustes en propósitos y objetivos académicos en cada asignatura.</p></td>
<td style="background-color:#FFFFFF"><p style="text-align:justify;margin:4pt 0;line-height:1.4"><span style="color:#00B0F0;font-size:9pt"><b>AJUSTES RAZONABLES / METODOLOGÍA Y DIDÁCTICA</b></span></p><p style="font-size:9pt">Ajustes en herramientas pedagógicas y didácticas.</p></td>
<td style="background-color:#FFFFFF"><p style="text-align:center;margin:4pt 0;line-height:1.4"><span style="color:#00B0F0;font-size:9pt"><b>EVALUACIÓN DE LOS AJUSTES Y SEGUIMIENTO.</b></span></p><p style="font-size:9pt">Medición de la efectividad de los ajustes razonables.</p></td>
</tr>
<tr>
<td style="background-color:#FFFFFF"><p style="margin:4pt 0;font-size:9pt">Objetivos por periodo según los DBA.</p></td>
<td style="background-color:#FFFFFF"><p style="margin:4pt 0;font-size:9pt">Módulos, guías, material concreto, estrategias DUA.</p></td>
<td style="background-color:#FFFFFF"><p style="margin:4pt 0;font-size:9pt">Rúbricas de evaluación.</p></td>
</tr>

@foreach($ajustes as $aj)
@php $v = fn($campo) => ($aj->$campo !== null && $aj->$campo !== '') ? $aj->$campo : ''; @endphp
<tr>
<td colspan="4" style="background-color:#F2F2F2">
    <p style="margin:4pt 0"><span style="color:#2E74B5;font-size:11pt"><b>ASIGNATURA: {{ $aj->NOMBRE_MAT }}</b></span> &nbsp;<span style="font-size:9pt;color:#555">– {{ $aj->NOMBRE_DOC }}</span></p>
</td>
</tr>
<tr>
<td colspan="4">
    <p style="margin-bottom:4pt"><b>Barreras para acceder al aprendizaje:</b></p>
    <p style="text-align:justify;line-height:1.4;white-space:pre-wrap">{{ $v('BARRERAS') }}</p>
</td>
</tr>
@foreach([['num'=>1,'label'=>'PRIMER'],['num'=>2,'label'=>'SEGUNDO'],['num'=>3,'label'=>'TERCERO'],['num'=>4,'label'=>'CUARTO']] as $p)
<tr>
<td style="background-color:#E2EFD9;text-align:center;vertical-align:middle;"><p><b>{{ $p['label'] }}</b></p></td>
<td><p style="text-align:justify;line-height:1.15;white-space:pre-wrap">{{ $v('LOGRO'.$p['num']) }}</p><p>&nbsp;</p></td>
<td><p style="text-align:justify;line-height:1.15;white-space:pre-wrap">{{ $v('DIDACT'.$p['num']) }}</p></td>
<td><p style="line-height:1.4;white-space:pre-wrap">{{ $v('EVAL'.$p['num']) }}</p></td>
</tr>
@endforeach
@endforeach

</table>
@endif

</body>
</html>
