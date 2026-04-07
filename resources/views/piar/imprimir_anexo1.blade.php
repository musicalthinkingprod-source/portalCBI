<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>PIAR Anexo 1 – {{ $apellidos }}, {{ $nombreCompleto }}</title>
<style>
@page {
  size: letter;
  margin: 1.4cm 2.3cm 1.6cm 2.3cm;
}
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
@media print {
  body { padding: 0; }
  .no-print { display: none !important; }
}
table {
  border-collapse: collapse;
  width: 100%;
  margin-bottom: 4pt;
}
tr {
  -webkit-box-decoration-break: clone;
  box-decoration-break: clone;
}
td, th {
  border: 1px solid #000;
  padding: 3pt 5pt;
  vertical-align: middle;
  font-size: 12pt;
  -webkit-box-decoration-break: clone;
  box-decoration-break: clone;
}
p {
  margin: 0 0 1pt 0;
  line-height: 1.15;
}
img {
  max-width: 100%;
  display: inline-block;
}
.btn-print {
  display: inline-block;
  margin: 10px 5px;
  padding: 8px 20px;
  background: #1e40af;
  color: white;
  border: none;
  border-radius: 6px;
  font-size: 13px;
  cursor: pointer;
  text-decoration: none;
}
.btn-back {
  background: #6b7280;
}
</style>
</head>
<body>

{{-- Barra de acciones (no se imprime) --}}
<div class="no-print" style="margin-bottom:16px;">
    <a href="{{ route('piar.crear', $estudiante->CODIGO) }}" class="btn-print btn-back">← Volver</a>
    <button onclick="window.print()" class="btn-print">🖨️ Imprimir / PDF</button>
</div>

@php
    $p     = $piar;
    $v     = fn($campo, $default = '') => ($p && $p->$campo !== null && $p->$campo !== '') ? $p->$campo : $default;
    $si    = fn($campo) => ($p && $p->$campo) ? 'X' : '';
    $no    = fn($campo) => (!$p || !$p->$campo) ? 'X' : '';
    $siVal = fn($campo, $default = '') => ($p && $p->$campo) ? ($p->$campo) : $default;
    $noVal = fn($campo) => (!$p || !$p->$campo) ? 'X' : '';
@endphp

{{-- ══ ENCABEZADO ══ --}}
<p>&nbsp;</p>
<table border="1" cellspacing="0" cellpadding="4" style="border-collapse:collapse;width:100%">
<tr>
<td><p>&nbsp;</p><p style="text-align:center;margin-top:6pt;margin-bottom:6pt"><span style="color:#8496B0;font-size:16pt"><b>PLAN INDIVIDUAL DE AJUSTES RAZONABLES. </b></span></p><p style="text-align:center;margin-top:6pt;margin-bottom:6pt"><span style="color:#8496B0">COLEGIO BILINGÜE INTEGRAL</span></p><p style="text-align:center;margin-top:6pt;margin-bottom:6pt"><span style="color:#8496B0">CBI. </span></p><p>&nbsp;</p></td>
</tr>
<tr>
<td><p>&nbsp;</p><p style="text-align:center"><span style="color:#8496B0;font-size:9pt">*El presente instrumento contiene ajustes de contexto sobre Versión Original del MEN.</span></p><p>&nbsp;</p></td>
</tr>
</table>

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

<p style="text-align:center"><span style="display:inline-block;width:200px;height:260px;border:2.5px dashed #333;text-align:center;line-height:260px;font-size:13pt;font-family:Arial,sans-serif;color:#555;letter-spacing:2px;">PEGAR FOTO</span></p>
<p style="text-align:center;margin-top:10pt;margin-bottom:10pt;"><span style="display:inline-block;border:2px solid #000;padding:12pt 20pt;text-transform:uppercase;"><strong><span style="font-size:16pt;font-family:Arial,sans-serif;">Nombre: {{ $nombreCompleto }} {{ $apellidos }}</span></strong><br/><strong><span style="font-size:16pt;font-family:Arial,sans-serif;">{{ $tipoDoc }}: {{ $numId }}</span></strong><br/><strong><span style="font-size:16pt;font-family:Arial,sans-serif;">Curso: {{ $grado }}{{ $curso ? ' – ' . $curso : '' }}{{ $sede ? ' – ' . $sede : '' }}</span></strong><br/><strong><span style="font-size:16pt;font-family:Arial,sans-serif;">DX: {{ $v('DIAGNOSTICO') }}</span></strong></span></p>

<p>&nbsp;</p>
<p>&nbsp;</p>

{{-- ══ TABLA DE DILIGENCIAMIENTO ══ --}}
<table border="1" cellspacing="0" cellpadding="4" style="border-collapse:collapse;width:100%;page-break-before:always;break-before:page;">
<tr>
<td colspan="2">
    <p style="text-align:center;margin-top:6pt;margin-bottom:6pt"><span style="font-size:12pt"><b>INFORMACIÓN GENERAL DEL ESTUDIANTE</b></span></p>
    <p style="text-align:center;margin-top:6pt;margin-bottom:6pt"><span style="color:#2F5496;font-size:12pt"><b>(Información para la matrícula – Anexo 1 PIAR)</b></span></p>
</td>
</tr>
<tr>
<td><p style="margin-top:6pt;margin-bottom:6pt;line-height:1.5"><span style="font-size:12pt">Fecha y Lugar de Diligenciamiento:</span></p></td>
<td><p style="line-height:1.5">&nbsp;</p><p style="line-height:1.5"><span style="font-size:12pt">{{ $v('LUGAR_DIL', 'Bogotá, Colegio Bilingüe Integral. Año ' . date('Y')) }}</span></p></td>
</tr>
<tr>
<td><p style="margin-top:6pt;margin-bottom:6pt;line-height:1.5"><span style="font-size:12pt">Nombre de la Persona que diligencia:</span></p></td>
<td><p style="margin-top:6pt;margin-bottom:6pt">&nbsp;</p><p style="margin-top:6pt;margin-bottom:6pt"><span style="font-size:12pt">{{ $v('PERSONA_DIL', 'Jennifer Andrea Martínez Londoño') }}</span></p></td>
</tr>
</table>

<p style="text-align:justify">&nbsp;</p>

{{-- ══ SECCIÓN 1 – INFORMACIÓN GENERAL ══ --}}
<table border="1" cellspacing="0" cellpadding="4" style="border-collapse:collapse;width:100%">
<tr>
<td colspan="4" style="background-color:#F2F2F2"><p style="margin-top:6pt;margin-bottom:6pt"><span style="color:#8496B0;font-size:12pt"><b>1) INFORMACIÓN GENERAL DEL ESTUDIANTE.</b></span></p></td>
</tr>
<tr>
<td colspan="2"><p><span style="font-size:12pt"><b>Nombres:</b></span></p><p><span style="font-size:12pt">{{ $nombreCompleto }}</span></p></td>
<td colspan="2"><p><span style="font-size:12pt"><b>Apellidos:</b></span></p><p><span style="font-size:12pt">{{ $apellidos }}</span></p></td>
</tr>
<tr>
<td colspan="2"><p style="margin-top:6pt;margin-bottom:6pt"><span style="font-size:12pt"><b>Curso: </b></span><span style="font-size:12pt">{{ $grado }}{{ $curso ? ' – ' . $curso : '' }} </span><span style="font-size:12pt"><b>Sede </b></span><span style="font-size:12pt">{{ $sede }} </span><span style="font-size:12pt"><b>Jornada: </b></span><span style="font-size:12pt">– {{ $sede }} Jornada única.</span></p></td>
<td colspan="2"><p style="margin-top:6pt;margin-bottom:6pt"><span style="color:#000000;font-size:12pt">Colegio Bilingüe Integral.</span></p></td>
</tr>
<tr>
<td colspan="2"><p><span style="font-size:12pt"><b>Lugar de nacimiento: </b></span><span style="font-size:12pt">{{ $lugarNac }}</span></p></td>
<td><p><span style="font-size:12pt"><b>Edad: </b></span></p><p><span style="font-size:12pt">{{ $edad }} años</span></p></td>
<td><p><span style="font-size:12pt"><b>Fecha de nacimiento:</b></span></p><p><span style="font-size:12pt">{{ $fechaNac }}</span></p></td>
</tr>
<tr>
@php
    $esTI = $tipoDoc === 'TI';
    $esCC = $tipoDoc === 'CC';
    $esRC = $tipoDoc === 'RC';
    $esOtro = !$esTI && !$esCC && !$esRC;
@endphp
<td><p style="margin-top:6pt;margin-bottom:6pt"><span style="font-size:12pt"><b>Tipo: TI_{{ $esTI ? 'X' : '_' }}_ CC_{{ $esCC ? 'X' : '_' }}_ RC_{{ $esRC ? 'X' : '_' }}_ otro: ¿cuál? {{ $esOtro ? $tipoDoc : '' }}</b></span></p></td>
<td colspan="3"><p style="margin-top:6pt;margin-bottom:6pt"><span style="font-size:12pt"><b>No de identificación:</b></span></p><p style="margin-top:6pt;margin-bottom:6pt"><span style="font-size:12pt">{{ $numId }}</span></p></td>
</tr>
<tr>
<td><p style="margin-top:6pt;margin-bottom:6pt"><span style="font-size:12pt"><b>Departamento donde vive</b></span></p></td>
<td><p style="margin-top:6pt;margin-bottom:6pt"><span style="font-size:12pt">Cundinamarca.</span></p></td>
<td colspan="2"><p style="margin-top:6pt;margin-bottom:6pt"><span style="font-size:12pt"><b>Municipio: </b></span><span style="font-size:12pt">{{ $v('MUNICIPIO', 'Bogotá') }}</span></p></td>
</tr>
<tr>
<td><p style="margin-top:6pt;margin-bottom:6pt"><span style="font-size:12pt"><b>Dirección de vivienda</b></span></p></td>
<td><p style="margin-top:6pt;margin-bottom:6pt"><span style="font-size:12pt">{{ $direccion }}</span></p></td>
<td colspan="2"><p style="margin-top:6pt;margin-bottom:6pt"><span style="font-size:12pt"><b>Barrio/vereda: </b></span><span style="font-size:12pt">{{ $barrio }}</span></p></td>
</tr>
<tr>
<td><p style="margin-top:6pt;margin-bottom:6pt"><span style="font-size:12pt"><b>Teléfono</b></span></p></td>
<td><p style="margin-top:6pt;margin-bottom:6pt"><span style="font-size:12pt">{{ $v('TELEFONO', $telPadres) }}</span></p></td>
<td colspan="2"><p style="margin-top:6pt;margin-bottom:6pt"><span style="font-size:12pt"><b>Correo electrónico: </b></span><span style="font-size:12pt">{{ $v('EMAIL', $correoPadres) }}</span></p></td>
</tr>
<tr>
<td colspan="2"><p style="margin-top:6pt;margin-bottom:6pt"><span style="font-size:12pt"><b>¿Está en centro de protección? NO: </b></span><span style="font-size:12pt">{{ $no('PROTEC') }}</span><span style="font-size:12pt"><b>   SI: {{ $si('PROTEC') }} ¿dónde? </b></span><span style="font-size:12pt">{{ $v('PROTEC_WHICH') }}</span></p></td>
<td colspan="2"><p style="margin-top:6pt;margin-bottom:6pt"><span style="font-size:12pt"><b>Grado al que aspira ingresar: </b></span></p><p style="margin-top:6pt;margin-bottom:6pt"><span style="font-size:12pt">{{ $v('ASPIRA', $grado) }}</span></p></td>
</tr>
<tr>
<td colspan="4"><p style="margin-top:6pt;margin-bottom:6pt"><span style="font-size:12pt"><b>Si el estudiante no tiene registro civil debe iniciarse la gestión con la familia y la Registraduría. </b></span><span style="font-size:12pt">{{ $v('REGIS', 'No aplica') }}</span></p></td>
</tr>
<tr>
<td colspan="4"><p style="margin-top:6pt;margin-bottom:6pt"><span style="font-size:12pt"><b>¿Se reconoce o pertenece a un grupo étnico? </b></span><span style="font-size:12pt">{{ $no('ETNIC') ? 'NO' : '' }}</span><span style="font-size:12pt"><b> ¿Cuál? </b></span><span style="font-size:12pt">{{ $v('ETNIC_WHICH') }}</span></p></td>
</tr>
<tr>
<td colspan="4"><p style="margin-top:6pt;margin-bottom:6pt"><span style="font-size:12pt"><b>¿Se reconoce como víctima del conflicto armado? Si {{ $si('CONFARM') }} No {{ $no('CONFARM') }} (¿Cuenta con el respectivo registro? </b></span><span style="font-size:12pt">{{ $v('CONFARM_REG') }}</span><span style="font-size:12pt"><b>)</b></span></p></td>
</tr>
</table>

<p style="text-align:justify;margin-top:6pt;margin-bottom:6pt">&nbsp;</p>

{{-- ══ SECCIÓN 2 – ENTORNO SALUD ══ --}}
<table border="1" cellspacing="0" cellpadding="4" style="border-collapse:collapse;width:100%">
<tr>
<td colspan="7" style="background-color:#F2F2F2"><p style="text-align:justify;margin-top:6pt;margin-bottom:6pt"><span style="color:#8496B0;font-size:12pt"><b>2) ENTORNO SALUD.</b></span></p></td>
</tr>
<tr>
<td colspan="3"><p style="margin-top:6pt;margin-bottom:6pt"><span style="font-size:12pt"><b>Afiliación al sistema de salud SI {{ $si('SALUD') }}   No {{ $no('SALUD') }}</b></span></p></td>
<td><p style="margin-top:6pt;margin-bottom:6pt"><span style="font-size:12pt"><b>EPS</b></span></p></td>
<td><p style="margin-top:6pt;margin-bottom:6pt"><span style="font-size:12pt">{{ $v('EPS', $epsEst) }}</span></p></td>
<td><p style="margin-top:6pt;margin-bottom:6pt"><span style="font-size:12pt"><b>Contributivo. </b></span><span style="font-size:12pt">{{ ($p && $p->CONT) || !$p ? 'X' : '' }}</span></p></td>
<td><p style="margin-top:6pt;margin-bottom:6pt"><span style="font-size:12pt"><b>Subsidiado </b></span><span style="font-size:12pt">{{ ($p && !$p->CONT) ? 'X' : '' }}</span></p></td>
</tr>
<tr>
<td colspan="7"><p style="margin-top:6pt;margin-bottom:6pt"><span style="font-size:12pt"><b>Lugar donde le atienden en caso de emergencia: </b></span><span style="font-size:12pt">{{ $v('EMERG') }}</span></p></td>
</tr>
<tr>
<td><p style="margin-top:6pt;margin-bottom:6pt"><span style="font-size:12pt"><b>¿El niño está siendo atendido por el sector salud?</b></span></p></td>
<td><p style="margin-top:6pt;margin-bottom:6pt"><span style="font-size:12pt"><b>Si</b></span></p><p style="margin-top:6pt;margin-bottom:6pt"><span style="font-size:12pt">{{ $si('PROTEGIDO') }}</span></p></td>
<td><p style="margin-top:6pt;margin-bottom:6pt"><span style="font-size:12pt"><b>No</b></span></p><p style="margin-top:6pt;margin-bottom:6pt"><span style="font-size:12pt">{{ $no('PROTEGIDO') }}</span></p></td>
<td colspan="4"><p style="margin-top:6pt;margin-bottom:6pt"><span style="font-size:12pt"><b>Frecuencia: </b></span></p><p style="margin-top:6pt;margin-bottom:6pt"><span style="font-size:12pt">{{ $v('FREC_PROTEG') }}</span></p></td>
</tr>
<tr>
<td><p style="margin-top:6pt;margin-bottom:6pt"><span style="font-size:12pt"><b>Tiene diagnóstico médico:</b></span></p></td>
<td><p style="margin-top:6pt;margin-bottom:6pt"><span style="font-size:12pt"><b>Si</b></span></p><p style="margin-top:6pt;margin-bottom:6pt"><span style="font-size:12pt">{{ $si('DIAGMED') }}</span></p></td>
<td><p style="margin-top:6pt;margin-bottom:6pt"><span style="font-size:12pt"><b>No</b></span></p><p style="margin-top:6pt;margin-bottom:6pt"><span style="font-size:12pt">{{ $no('DIAGMED') }}</span></p></td>
<td colspan="4"><p style="margin-top:6pt;margin-bottom:6pt"><span style="font-size:12pt"><b>Cuál: </b></span><span style="font-size:12pt">{{ $v('DIAGMED_WHICH', $enferEst) }}</span></p></td>
</tr>
<tr>
<td rowspan="3"><p style="margin-top:6pt;margin-bottom:6pt"><span style="font-size:12pt"><b>¿El niño está asistiendo a terapias?</b></span></p></td>
<td rowspan="3"><p style="margin-top:6pt;margin-bottom:6pt"><span style="font-size:12pt"><b>Si</b></span></p><p style="margin-top:6pt;margin-bottom:6pt"><span style="font-size:12pt">{{ $si('TERAP') }}</span></p></td>
<td rowspan="3"><p style="margin-top:6pt;margin-bottom:6pt"><span style="font-size:12pt"><b>No</b></span></p><p style="margin-top:6pt;margin-bottom:6pt"><span style="font-size:12pt">{{ $no('TERAP') }}</span></p></td>
<td colspan="2"><p style="margin-top:6pt;margin-bottom:6pt"><span style="font-size:12pt"><b>¿Cuál? </b></span><span style="font-size:12pt">{{ $v('TERAP_WHICH1') }}</span></p></td>
<td colspan="2"><p style="margin-top:6pt;margin-bottom:6pt"><span style="font-size:12pt"><b>Frecuencia. </b></span><span style="font-size:12pt">{{ $v('TERAP_FREC1') }}</span></p></td>
</tr>
<tr>
<td colspan="2"><p style="margin-top:6pt;margin-bottom:6pt"><span style="font-size:12pt"><b>¿Cuál? </b></span><span style="font-size:12pt">{{ $v('TERAP_WHICH2') }}</span></p></td>
<td colspan="2"><p style="margin-top:6pt;margin-bottom:6pt"><span style="font-size:12pt">{{ $v('TERAP_FREC2') }}</span></p></td>
</tr>
<tr>
<td colspan="2"><p style="margin-top:6pt;margin-bottom:6pt"><span style="font-size:12pt"><b>¿Cuál? </b></span><span style="font-size:12pt">{{ $v('TERAP_WHICH3') }}</span></p></td>
<td colspan="2"><p style="margin-top:6pt;margin-bottom:6pt"><span style="font-size:12pt"><b>Frecuencia. </b></span><span style="font-size:12pt">{{ $v('TERAP_FREC3') }}</span></p></td>
</tr>
<tr>
<td colspan="3"><p style="margin-top:6pt;margin-bottom:6pt"><span style="font-size:12pt"><b>¿Actualmente recibe tratamiento médico por alguna enfermedad en particular? SI__ {{ $si('ENFERPAR') }}_ NO {{ $no('ENFERPAR') }}</b></span></p></td>
<td colspan="4"><p style="margin-top:6pt;margin-bottom:6pt"><span style="font-size:12pt"><b>¿Cuál? </b></span><span style="font-size:12pt">{{ $v('ENFERPAR_WHICH') }}</span></p></td>
</tr>
<tr>
<td colspan="7"><p style="margin-top:6pt;margin-bottom:6pt"><span style="font-size:12pt"><b>¿Consume medicamentos? Si__ {{ $si('MEDIC') }}_ No {{ $no('MEDIC') }} Frecuencia y horario: </b></span><span style="font-size:12pt">{{ $v('MEDIC_FREC') }}</span></p></td>
</tr>
<tr>
<td colspan="3"><p style="margin-top:6pt;margin-bottom:6pt"><span style="font-size:12pt"><b>¿Cuenta con productos de apoyo para favorecer su movilidad, comunicación e independencia?</b></span></p></td>
<td colspan="4"><p style="margin-top:6pt;margin-bottom:6pt"><span style="font-size:12pt"><b>NO__ {{ $no('MOVILID') }}_ SI {{ $si('MOVILID') }} ¿Cuáles? </b></span><span style="font-size:12pt">{{ $v('MOVILID_WHICH') }}</span></p></td>
</tr>
</table>

<p style="text-align:justify">&nbsp;</p>
<p style="text-align:justify">&nbsp;</p>

{{-- ══ SECCIÓN 3 – ENTORNO HOGAR ══ --}}
<table border="1" cellspacing="0" cellpadding="4" style="border-collapse:collapse;width:100%">
<tr>
<td colspan="4" style="background-color:#F2F2F2"><p style="text-align:justify;margin-top:6pt;margin-bottom:6pt"><span style="color:#8496B0;font-size:12pt"><b>3) ENTORNO HOGAR.</b></span></p></td>
</tr>
<tr>
<td colspan="2"><p><span style="font-size:12pt"><b>Nombre de la madre:</b></span></p><p><span style="font-size:12pt">{{ $nombreMadre }}</span></p></td>
<td colspan="2"><p><span style="font-size:12pt"><b>Nombre del padre:</b></span></p><p><span style="font-size:12pt">{{ $nombrePadre }}</span></p></td>
</tr>
<tr>
<td colspan="2"><p><span style="font-size:12pt"><b>Ocupación de la madre:</b></span></p></td>
<td colspan="2"><p><span style="font-size:12pt"><b>Ocupación del padre:</b></span></p></td>
</tr>
<tr>
<td colspan="2"><p><span style="font-size:12pt">{{ $v('OCUP_MADRE', $empMadre) }}</span></p></td>
<td colspan="2"><p><span style="font-size:12pt">{{ $v('OCUP_PADRE', $empPadre) }}</span></p></td>
</tr>
<tr>
<td><p><span style="font-size:12pt"><b>Nivel educativo alcanzado</b></span></p></td>
<td><p style="text-align:center"><span style="font-size:12pt">{{ $v('EDUC_MADRE') }}</span></p></td>
<td><p><span style="font-size:12pt"><b>Nivel educativo alcanzado</b></span></p></td>
<td><p style="text-align:center"><span style="font-size:12pt">{{ $v('EDUC_PADRE') }}</span></p></td>
</tr>
<tr>
<td rowspan="2"><p><span style="font-size:12pt"><b>Nombre Cuidador</b></span></p><p style="text-align:center">&nbsp;</p><p style="text-align:center"><span style="font-size:12pt">{{ $v('NOMB_CUID', $nombreAcud) }}</span></p></td>
<td rowspan="2"><p><span style="font-size:12pt"><b>Parentesco con el estudiante:</b></span></p><p>&nbsp;</p><p><span style="font-size:12pt">{{ $v('PAREN_CUID') }}</span></p></td>
<td rowspan="2"><p><span style="font-size:12pt"><b>Nivel educativo cuidador</b></span></p><p><span style="font-size:12pt">{{ $v('EDUC_CUID') }}</span></p></td>
<td><p><span style="font-size:12pt"><b>Teléfono </b></span><span style="font-size:12pt">{{ $v('TEL_CUID', $celAcud ?: $celMadre) }}</span></p></td>
</tr>
<tr>
<td><p><span style="font-size:12pt"><b>Correo electrónico:</b></span></p><p><span style="font-size:12pt">{{ $v('EMAIL_CUID', $emailAcud) }}</span></p></td>
</tr>
<tr>
<td><p><span style="font-size:12pt"><b>No. Hermanos.</b></span></p></td>
<td><p><span style="font-size:12pt">{{ $v('HERMANOS') }}</span></p></td>
<td><p><span style="font-size:12pt"><b>Lugar que ocupa:</b></span></p></td>
<td rowspan="2"><p><span style="font-size:12pt"><b>¿Quiénes apoyan la crianza del estudiante?</b></span></p><p>&nbsp;</p><p><span style="font-size:12pt">{{ $v('CRIANZA') }}</span></p></td>
</tr>
<tr>
<td><p><span style="font-size:12pt"><b>Personas con quien vive:</b></span></p><p>&nbsp;</p></td>
<td colspan="2"><p>&nbsp;</p><p><span style="font-size:12pt">{{ $v('PERS_VIVE') }}</span></p></td>
</tr>
<tr>
<td><p><span style="font-size:12pt"><b>¿Está bajo protección?</b></span></p></td>
<td colspan="3"><p><span style="font-size:12pt"><b>Si__ {{ $si('HOG_PROTEC') }}   No__ {{ $no('HOG_PROTEC') }} / Institución a cargo: </b></span><span style="font-size:12pt">{{ $v('HOG_PROTEC_WHICH') }}</span></p></td>
</tr>
<tr>
<td colspan="4"><p style="text-align:justify"><span style="font-size:12pt"><b>La familia recibe algún subsidio de alguna entidad o institución: SI__ {{ $si('HOG_SUB') }} NO__ {{ $no('HOG_SUB') }} ¿Cuál? </b></span><span style="font-size:12pt">{{ $v('HOG_SUB_WHICH') }}</span></p></td>
</tr>
</table>

<p style="text-align:justify">&nbsp;</p>
<p>&nbsp;</p>

{{-- ══ SECCIÓN 4 – ENTORNO EDUCATIVO ══ --}}
<table border="1" cellspacing="0" cellpadding="4" style="border-collapse:collapse;width:100%">
<tr>
<td colspan="3"><p style="margin-top:6pt;margin-bottom:6pt"><span style="color:#8496B0;font-size:12pt"><b>4) ENTORNO EDUCATIVO. Información de la Trayectoria Educativa</b></span></p></td>
</tr>
<tr>
<td><p><span style="font-size:12pt"><b>¿Ha estado vinculado en otra institución educativa, fundación o modalidad de educación inicial? SI.</b></span></p></td>
<td colspan="2"><p style="text-align:justify"><span style="font-size:12pt"><b>NO {{ $no('INSTITUPREV') }} ¿Por qué?</b></span></p><p style="text-align:justify">&nbsp;</p><p style="text-align:justify"><span style="font-size:12pt"><b>SI {{ $si('INSTITUPREV') }} ¿Cuál? </b></span><span style="font-size:12pt">{{ $v('INTITUPREV_WHICH') }}</span></p></td>
</tr>
<tr>
<td><p><span style="font-size:12pt"><b>Ultimo grado cursado:</b></span></p><p><span style="font-size:12pt">{{ $v('ULTGRADO', $grado) }}</span></p></td>
<td><p style="text-align:justify"><span style="font-size:12pt"><b>¿Aprobó? SI__ {{ $si('APRUEBA') }}_ NO__ {{ $no('APRUEBA') }}</b></span></p></td>
<td><p style="text-align:justify"><span style="font-size:12pt"><b>Observaciones: </b></span><span style="font-size:12pt">{{ $v('OBSERV') }}</span></p></td>
</tr>
<tr>
<td><p><span style="font-size:12pt"><b>¿Se recibe informe pedagógico cualitativo que describa el proceso de desarrollo y aprendizaje del estudiante y/o PIAR?</b></span></p><p><span style="font-size:12pt"><b>NO__ {{ $no('INFOPIAR') }}  SI {{ $si('INFOPIAR') }}</b></span></p></td>
<td colspan="2"><p>&nbsp;</p><p><span style="font-size:12pt"><b>¿De qué institución o modalidad proviene el informe?</b></span></p><p><span style="font-size:12pt">{{ $v('INFOPIAR_WHICH') }}</span></p></td>
</tr>
<tr>
<td><p><span style="font-size:12pt"><b>¿Está asistiendo en la actualidad a programas complementarios? NO {{ $no('COMPLEM') }} SI {{ $si('COMPLEM') }}</b></span></p></td>
<td colspan="2"><p><span style="font-size:12pt"><b>¿Cuáles? </b></span><span style="font-size:12pt">{{ $v('COMPLEM_WHICH') }}</span></p><p>&nbsp;</p></td>
</tr>
</table>

<p style="text-align:justify">&nbsp;</p>
<p style="text-align:justify">&nbsp;</p>

{{-- ══ INFORMACIÓN INSTITUCIÓN ══ --}}
<table border="1" cellspacing="0" cellpadding="4" style="border-collapse:collapse;width:100%">
<tr>
<td colspan="2"><p style="text-align:justify;margin-top:6pt;margin-bottom:6pt"><span style="color:#8496B0;font-size:12pt"><b>Información de la institución educativa en la que se matricula:</b></span></p></td>
</tr>
<tr>
<td><p><span style="font-size:12pt">Nombre de la Institución educativa a la que se matricula:</span></p><p>&nbsp;</p><p><span style="font-size:12pt">Colegio Bilingüe Integral CBI.</span></p><p>&nbsp;</p></td>
<td><p><span style="font-size:12pt">Sede:</span></p><p>&nbsp;</p><p><span style="font-size:12pt">{{ $sede }}</span></p></td>
</tr>
<tr>
<td><p style="text-align:justify"><span style="font-size:12pt"><b>Medio que usará el estudiante para transportarse a la institución educativa.</b></span></p><p style="text-align:justify">&nbsp;</p></td>
<td><p><span style="font-size:12pt"><b>Distancia entre la institución educativa o sede y el hogar del estudiante</b></span></p></td>
</tr>
<tr>
<td><p style="text-align:justify"><span style="font-size:12pt">{{ $v('TRANSPOR') }}</span></p></td>
<td><p><span style="font-size:12pt">{{ $v('DISTANCIA') }}</span></p></td>
</tr>
</table>

{{-- ══ FIRMAS ══ --}}
<table border="1" cellspacing="0" cellpadding="4" style="border-collapse:collapse;width:100%">
<tr>
<td><p style="text-align:center">&nbsp;</p><p style="text-align:center">&nbsp;</p><p style="text-align:center">&nbsp;</p><p style="text-align:center">&nbsp;</p><p style="text-align:center">&nbsp;</p><p style="text-align:center">&nbsp;</p><p style="text-align:center">&nbsp;</p><p style="text-align:center"><span style="font-size:12pt"><b>Nombre y firma.</b></span></p></td>
<td><p style="text-align:center">&nbsp;</p><p style="text-align:center">&nbsp;</p><p style="text-align:center">&nbsp;</p><p style="text-align:center">&nbsp;</p><p style="text-align:center">&nbsp;</p><p style="text-align:center">&nbsp;</p><p style="text-align:center">&nbsp;</p><p style="text-align:center"><span style="font-size:12pt"><b>Nombre y firma</b></span></p></td>
<td><p style="text-align:center">&nbsp;</p><p style="text-align:center">&nbsp;</p><p style="text-align:center">&nbsp;</p><p style="text-align:center">&nbsp;</p><p style="text-align:center">&nbsp;</p><p style="text-align:center">&nbsp;</p><p style="text-align:center">&nbsp;</p><p style="text-align:center"><span style="font-size:12pt"><b>Nombre y firma</b></span></p></td>
</tr>
<tr>
<td><p style="text-align:center"><span style="font-size:12pt"><b>Área. Orientación escolar.</b></span></p></td>
<td><p style="text-align:center"><span style="font-size:12pt"><b>Mamá.</b></span></p></td>
<td><p style="text-align:center"><span style="font-size:12pt"><b>Papá</b></span></p></td>
</tr>
</table>

</body>
</html>
