<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>PIAR Anexo 3 – {{ $apellidos }}, {{ $nombreCompleto }}</title>
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
    <a href="{{ route('piar.informe') }}" class="btn-print btn-back">← Volver</a>
    <button onclick="window.print()" class="btn-print">🖨️ Imprimir / PDF</button>
</div>

{{-- ══ ENCABEZADO ══ --}}
<table border="1" cellspacing="0" cellpadding="6" style="border-collapse:collapse;width:100%">
<tr>
<td style="width:32%;text-align:center;vertical-align:middle;padding:8pt;">
    <img src="{{ asset('images/mineducacion.png') }}" alt="MinEducación" style="max-height:50pt;width:70%;display:block;margin:0 auto 5pt auto;">
    <img src="{{ asset('images/colombia.png') }}" alt="Gobierno de Colombia" style="max-height:28pt;max-width:100%;height:auto;width:auto;display:block;margin:0 auto;">
</td>
<td style="text-align:center;vertical-align:middle;padding:8pt;">
    <p style="text-align:center;line-height:1.5"><span style="color:#8496B0;font-size:18pt"><b>PIAR</b></span></p>
    <p style="text-align:center;line-height:1.5"><span style="color:#8496B0;font-size:9pt"><b>Anexo 3 – Decreto 1421/2017</b></span></p>
</td>
<td style="width:28%;text-align:center;vertical-align:middle;padding:8pt;">
    <img src="{{ asset('images/escudoCBI.png') }}" alt="CBI" style="max-height:55pt;max-width:100%;display:block;margin:0 auto 4pt auto;">
    <p style="margin:0;font-size:9pt;font-weight:bold;color:#1e3a8a;">Colegio Bilingüe Integral</p>
</td>
</tr>
</table>

<p>&nbsp;</p>

@include('partials.piar_anexo3')

</body>
</html>
