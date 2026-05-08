<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Certificado de notas — {{ $estudiante->APELLIDO1 }} {{ $estudiante->APELLIDO2 }}</title>
    <style>
        @page { size: letter; margin: 3.6cm 1.5cm 2.4cm 1.5cm; }
        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 12pt;
            color: #000;
            margin: 0;
        }

        /* ─── Header (se repite en cada página gracias a position:fixed en DomPDF) ─── */
        .doc-header {
            position: fixed;
            top: -3cm; left: 0; right: 0;
            height: 2.6cm;
            border-bottom: 1px solid #000;
        }
        .doc-header table { width: 100%; border-collapse: collapse; }
        .doc-header td.escudo-cell { width: 100px; vertical-align: middle; }
        .doc-header td.escudo-cell img { height: 85px; }
        .doc-header td.info { vertical-align: middle; text-align: center; line-height: 1.25; }
        .doc-header .nombre { font-size: 16pt; font-weight: bold; letter-spacing: 0.5px; }
        .doc-header .meta { font-size: 9pt; }

        /* ─── Footer (se repite en cada página) ─── */
        .doc-footer {
            position: fixed;
            bottom: -2cm; left: 0; right: 0;
            height: 1.6cm;
            border-top: 1px solid #000;
            padding-top: 4px;
            font-size: 9pt;
            text-align: center;
            line-height: 1.35;
        }

        /* ─── Marca de agua ─── */
        .marca {
            position: fixed;
            top: 35%; left: 25%;
            width: 50%;
            opacity: 0.06;
            text-align: center;
            z-index: -1;
        }
        .marca img { width: 100%; }

        /* ─── Cuerpo ─── */
        h1.titulo {
            font-size: 13pt; text-align: center;
            font-weight: bold; text-transform: uppercase;
            margin: 6px 0 0 0;
        }
        p.certifica {
            font-size: 14pt; text-align: center; font-weight: bold;
            margin: 22px 0 22px 0;
        }
        p.cuerpo { text-align: justify; line-height: 1.6; margin: 0; }

        table.notas {
            width: 100%; border-collapse: collapse;
            table-layout: fixed;
            font-size: 10.5pt; margin-top: 14px;
        }
        table.notas th, table.notas td { border: 1px solid #000; padding: 3px 6px; }
        table.notas th { background: #f0f0f0; text-align: center; font-weight: bold; }
        table.notas td.c { text-align: center; }
        table.notas td.sep, table.notas th.sep { border: none; width: 14px; padding: 0; background: transparent; }

        .firma { margin-top: 90px; }
        .firma p { margin: 2px 0; font-weight: bold; }
    </style>
</head>
<body>

<div class="marca">
    <img src="{{ public_path('images/escudoCBI.png') }}" alt="">
</div>

<div class="doc-header">
    <table>
        <tr>
            <td class="escudo-cell">
                <img src="{{ public_path('images/escudoCBI.png') }}" alt="CBI">
            </td>
            <td class="info">
                <div class="nombre">COLEGIO BILINGÜE INTEGRAL</div>
                <div class="meta"><strong>Nit:</strong> 901.302.189-8</div>
                <div class="meta">Según resolución No. 2528 del 11 de Julio de 2008</div>
                <div class="meta">Resolución 2331 de julio 27 de 1999: Preescolar &mdash; Básica Primaria &mdash; Básica Secundaria</div>
                <div class="meta">Resolución No. 14-029 de noviembre 19 de 2009: Educación Media</div>
                <div class="meta">Código ICFES 158832 &mdash; Código DANE 311001030073</div>
            </td>
        </tr>
    </table>
</div>

<div class="doc-footer">
    <div><strong>SEDE A:</strong> Calle 1ª # 29-05 &nbsp;&nbsp; <strong>SEDE B:</strong> Cra 29 # 8-04 Sur &nbsp;&nbsp; <strong>SEDE C:</strong> Cra. 28 # 1ª-12</div>
    <div><strong>PBX:</strong> 601 8051107 &nbsp;&nbsp; <strong>E-mail:</strong> administration@cbi.edu.co &nbsp;&nbsp; Bogotá &mdash; Colombia</div>
</div>

<h1 class="titulo">LA SUSCRITA RECTORA DEL COLEGIO BILINGÜE INTEGRAL</h1>

<p class="certifica">CERTIFICA:</p>

<p class="cuerpo">
    Que el/la estudiante
    <strong>{{ mb_strtoupper($nombreCompleto) }}</strong>,
    Identificado/a con
    @if($tipoDoc)
        <strong>{{ $tipoDoc }}</strong> <strong>{{ $numDoc }}</strong> de {{ $lugarExped }},
    @else
        documento <strong>—</strong>,
    @endif
    cursó y aprobó en esta institución educativa los estudios correspondientes al grado
    <strong>{{ $gradoTexto }}</strong> de la Educación {{ $nivelEducativo }}
    del año lectivo <strong>{{ $anio }}</strong> con las siguientes calificaciones:
</p>

@if(empty($materias))
    <p style="text-align:center; margin-top: 20px; font-style: italic;">
        No hay notas registradas para este estudiante en el año {{ $anio }}.
    </p>
@else
<table class="notas">
    <thead>
        <tr>
            <th style="width: 31%;">ASIGNATURA</th>
            <th style="width: 8%;">ESCALA</th>
            <th style="width: 10%;">NIVEL</th>
            <th class="sep" style="width: 2%;"></th>
            <th style="width: 31%;">ASIGNATURA</th>
            <th style="width: 8%;">ESCALA</th>
            <th style="width: 10%;">NIVEL</th>
        </tr>
    </thead>
    <tbody>
        @php $filas = max(count($colIzq), count($colDer)); @endphp
        @for($i = 0; $i < $filas; $i++)
            @php
                $izq = $colIzq[$i] ?? null;
                $der = $colDer[$i] ?? null;
            @endphp
            <tr>
                <td>{{ $izq['nombre'] ?? '' }}</td>
                <td class="c">{{ $izq['escala'] ?? '' }}</td>
                <td class="c">{{ $izq['nivel'] ?? '' }}</td>
                <td class="sep"></td>
                <td>{{ $der['nombre'] ?? '' }}</td>
                <td class="c">{{ $der['escala'] ?? '' }}</td>
                <td class="c">{{ $der['nivel'] ?? '' }}</td>
            </tr>
        @endfor
    </tbody>
</table>
@endif

<p class="cuerpo" style="margin-top: 20px;">
    Esta solicitud se expide en Bogotá, D.C., a los
    <strong>{{ $fecha->isoFormat('D') }}</strong>
    días del mes de
    <strong>{{ \Carbon\Carbon::parse($fecha)->locale('es')->isoFormat('MMMM') }}</strong>
    del año <strong>{{ $fecha->isoFormat('YYYY') }}</strong>.
</p>

<div class="firma">
    <p>LUZ ANGELA VEGA BUENAHORA</p>
    <p>Rectora</p>
</div>

</body>
</html>
