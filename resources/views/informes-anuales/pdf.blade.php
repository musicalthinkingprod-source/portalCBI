<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Informe desempeño académico {{ $anio }} — {{ $estudiante->APELLIDO1 }} {{ $estudiante->APELLIDO2 }}</title>
    <style>
        @page { size: letter; margin: 0.9cm 1.7cm; }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10pt;
            color: #000;
            margin: 0;
        }

        /* ─── Marca de agua (todas las páginas) ─── */
        .marca {
            position: fixed;
            top: 28%; left: 22%;
            width: 56%;
            opacity: 0.06;
            text-align: center;
            z-index: -1;
        }
        .marca img { width: 100%; }

        /* ─── Banda institucional ─── */
        table.banda {
            width: 100%; border-collapse: collapse;
            background: #9db7f0;
        }
        table.banda td { padding: 6px 10px; }
        table.banda td.logo { width: 80px; text-align: center; }
        table.banda td.logo img { height: 52px; }
        table.banda td.titulo {
            text-align: center; font-weight: bold;
            font-size: 13pt; line-height: 1.35;
            padding-right: 90px; /* compensa el logo para centrar el texto */
        }

        /* ─── Cajas de datos del estudiante ─── */
        .caja {
            border: 1px solid #aaa;
            padding: 4px 8px;
        }
        table.datos { width: 100%; border-collapse: separate; border-spacing: 4px 2px; }
        table.datos td {
            border: 1px solid #aaa;
            padding: 2px 8px;
            font-size: 10pt;
        }
        td.lbl { font-weight: bold; }
        td.ctr { text-align: center; }

        .nombre-est {
            border: 1px solid #aaa;
            text-align: center; font-weight: bold; font-size: 12pt;
            padding: 3px 8px;
            margin: 10px 2px 2px 2px;
        }

        /* ─── Títulos de sección ─── */
        .titulo-seccion {
            border: 1px solid #aaa;
            text-align: center; font-weight: bold; font-size: 11pt;
            padding: 2px 8px;
            margin: 7px 2px 2px 2px;
        }

        /* ─── Tabla de áreas ─── */
        table.areas { width: 100%; border-collapse: separate; border-spacing: 4px 1px; }
        table.areas td.area {
            border: 1px solid #aaa;
            padding: 1px 8px; font-size: 9pt;
        }
        table.areas td.prom {
            border: 1px solid #aaa;
            width: 9%; text-align: center; font-weight: bold; font-size: 9.5pt;
        }

        /* ─── Tabla de materias ─── */
        table.materias { width: 100%; border-collapse: separate; border-spacing: 4px 1px; table-layout: fixed; }
        table.materias th {
            border: 1px solid #aaa;
            font-size: 8pt; font-weight: bold; text-align: center;
            padding: 2px 2px; vertical-align: middle;
        }
        table.materias th.sin-borde, table.materias td.sin-borde { border: none; }
        table.materias td {
            border: 1px solid #aaa;
            padding: 1px 6px; font-size: 9pt; vertical-align: middle;
        }
        table.materias td.acum { text-align: center; font-weight: bold; font-size: 9.5pt; }
        table.materias td.per  { text-align: center; font-size: 8.5pt; }
        .mala { color: #c00; text-decoration: underline; font-weight: bold; }

        /* ─── Observaciones ─── */
        .observaciones {
            border: 1px solid #aaa;
            margin: 7px 2px 0 2px;
            padding: 4px 8px;
            font-size: 9pt;
            min-height: 40px;
        }

        /* ─── Firmas (página 2) ─── */
        .firmas { page-break-before: always; }
        table.firmas-tabla { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table.firmas-tabla td { width: 50%; padding-right: 60px; }
        .linea-firma {
            border-top: 1px solid #000;
            width: 85%;
            padding-top: 3px;
            font-size: 10pt;
        }
    </style>
</head>
<body>

<div class="marca">
    <img src="{{ public_path('images/escudoCBI.png') }}" alt="">
</div>

{{-- Banda institucional --}}
<table class="banda">
    <tr>
        <td class="logo">
            {{-- Versión compuesta sobre el azul de la banda: DomPDF aplana la transparencia PNG a blanco --}}
            <img src="{{ public_path('images/escudoCBI_banda.png') }}" alt="CBI">
        </td>
        <td class="titulo">
            COLEGIO BILINGÜE INTEGRAL<br>
            INFORME DESEMPEÑO ACADÉMICO GENERAL
        </td>
    </tr>
</table>

{{-- Datos del estudiante --}}
<div class="nombre-est">{{ mb_strtoupper($nombreCompleto) }}</div>

<table class="datos">
    <tr>
        <td class="lbl" style="width:14%;">Código:</td>
        <td class="ctr" style="width:18%;"><strong>{{ $codigo }}</strong></td>
        <td class="lbl" style="width:12%;">Curso:</td>
        <td class="ctr" style="width:14%;"><strong>{{ $cursoAnio }}</strong></td>
        <td class="lbl" style="width:10%;">Año:</td>
        <td class="ctr" style="width:14%;"><strong>{{ $anio }}</strong></td>
    </tr>
</table>
<table class="datos">
    <tr>
        <td class="lbl" style="width:32%;">Director de grupo:</td>
        <td class="ctr"><strong>{{ $director }}</strong></td>
    </tr>
</table>

{{-- Promedio por área --}}
<div class="titulo-seccion">PROMEDIO POR AREA</div>
<table class="areas">
    @foreach($areas as $a)
    <tr>
        <td class="area">{{ mb_strtoupper($a['nombre']) }}</td>
        <td class="prom">{{ number_format($a['promedio'], 1, '.', '') }}</td>
    </tr>
    @endforeach
</table>

{{-- Promedio por materia --}}
<div class="titulo-seccion">PROMEDIO POR MATERIA</div>
<table class="materias">
    <thead>
        <tr>
            <th style="width:34%;">MATERIA</th>
            <th style="width:13%;">PROMEDIO<br>ACUMULADO</th>
            <th class="sin-borde" style="width:3%;"></th>
            <th style="width:8%;">1ER<br>PER.</th>
            <th style="width:8%;">2DO<br>PER.</th>
            <th style="width:8%;">3ER<br>PER.</th>
            <th style="width:8%;">4TO<br>PER.</th>
        </tr>
    </thead>
    <tbody>
        @foreach($materias as $mat)
        <tr>
            <td>{{ $mat['nombre'] }}</td>
            <td class="acum">
                <span class="{{ $mat['acumulado'] < \App\Http\Controllers\InformeAnualController::APROBADO_MIN ? 'mala' : '' }}">
                    {{ number_format($mat['acumulado'], 1, '.', '') }}
                </span>
            </td>
            <td class="sin-borde"></td>
            @for($p = 1; $p <= 4; $p++)
                @php $nota = $mat['periodos'][$p] ?? null; @endphp
                <td class="per">
                    @if($nota !== null)
                        <span class="{{ round($nota, 1) < \App\Http\Controllers\InformeAnualController::APROBADO_MIN ? 'mala' : '' }}">
                            {{ number_format(round($nota, 1), 1, '.', '') }}
                        </span>
                    @endif
                </td>
            @endfor
        </tr>
        @endforeach
    </tbody>
</table>

{{-- Observaciones --}}
<div class="observaciones">{{ $observaciones }}</div>

{{-- Firmas --}}
<div class="firmas">
    <table class="firmas-tabla">
        <tr>
            <td>
                <div class="linea-firma">Rectoría</div>
            </td>
            <td>
                <div class="linea-firma">Director de grupo</div>
            </td>
        </tr>
    </table>
</div>

</body>
</html>
