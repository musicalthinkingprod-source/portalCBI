<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Certificado de notas — {{ $estudiante->APELLIDO1 }} {{ $estudiante->APELLIDO2 }} {{ $estudiante->NOMBRE1 }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { font-family: 'Times New Roman', Times, serif; font-size: 12pt; color: #000; }

        .marca-agua {
            position: fixed; inset: 0;
            display: flex; align-items: center; justify-content: center;
            pointer-events: none; opacity: 0.06; z-index: 0;
        }
        .marca-agua img { width: 50%; max-width: 420px; height: auto; }

        /* ─── Encabezado institucional ─── */
        .doc-header {
            display: flex; align-items: center; gap: 14px;
            padding-bottom: 6px; border-bottom: 1px solid #000;
            margin-bottom: 14px;
        }
        .doc-header img.escudo { height: 90px; width: auto; }
        .doc-header .info { flex: 1; text-align: center; line-height: 1.25; }
        .doc-header .info .nombre {
            font-size: 16pt; font-weight: bold; letter-spacing: 0.5px;
        }
        .doc-header .info .meta { font-size: 9.5pt; }
        .doc-header .info .meta strong { font-weight: bold; }

        /* ─── Pie institucional ─── */
        .doc-footer {
            border-top: 1px solid #000;
            padding-top: 4px; margin-top: 28px;
            font-size: 9pt; text-align: center; line-height: 1.3;
        }

        h1.titulo {
            font-size: 13pt; text-align: center;
            font-weight: bold; text-transform: uppercase;
            margin: 18px 0 0 0;
        }
        p.certifica {
            font-size: 14pt; text-align: center; font-weight: bold;
            margin: 22px 0 22px 0;
        }
        p.cuerpo { text-align: justify; line-height: 1.6; margin: 0; }

        table.notas { width: 100%; border-collapse: collapse; font-size: 11pt; margin-top: 14px; }
        table.notas th, table.notas td { border: 1px solid #000; padding: 3px 6px; }
        table.notas th { background: #f3f3f3; text-align: center; font-weight: 700; }
        table.notas td.center { text-align: center; }
        table.notas td.sep, table.notas th.sep { border: none; width: 14px; padding: 0; }

        .firma { margin-top: 90px; }
        .firma p { margin: 2px 0; font-weight: bold; }

        @media print {
            .no-print { display: none !important; }
            body { background: white !important; margin: 0; }
            .pagina {
                box-shadow: none !important; margin: 0 !important;
                padding: 0 !important; background: transparent !important;
            }
            .marca-agua { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
        @page { size: letter; margin: 1.2cm 1.5cm; }
    </style>
</head>
<body class="bg-gray-200 min-h-screen py-6 print:bg-white print:py-0">

<div class="marca-agua" aria-hidden="true">
    <img src="{{ asset('images/escudoCBI.png') }}" alt="">
</div>

{{-- Barra de acciones (solo pantalla) --}}
<div class="no-print max-w-4xl mx-auto mb-4 flex justify-between items-center px-2 gap-3 flex-wrap">
    <a href="{{ route('certificados.buscar', ['anio' => $anio]) }}"
       class="bg-white hover:bg-gray-50 text-gray-700 text-sm font-semibold px-4 py-2 rounded-lg shadow transition">
        ← Volver
    </a>

    <form method="GET" action="{{ route('certificados.ver', ['codigo' => $codigo]) }}" class="flex items-center gap-2 bg-white rounded-lg shadow px-3 py-2">
        <input type="hidden" name="anio" value="{{ $anio }}">
        <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Fecha de expedición:</label>
        <input type="date" name="fecha" value="{{ $fecha->format('Y-m-d') }}"
               class="border border-gray-300 rounded px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        <button type="submit" class="bg-gray-700 hover:bg-gray-800 text-white text-xs font-semibold px-3 py-1.5 rounded transition">
            Aplicar
        </button>
    </form>

    <div class="flex gap-2">
        <a href="{{ route('certificados.pdf', ['codigo' => $codigo, 'anio' => $anio, 'fecha' => $fecha->format('Y-m-d')]) }}"
           target="_blank"
           class="bg-red-700 hover:bg-red-800 text-white text-sm font-semibold px-4 py-2 rounded-lg shadow transition">
            ⬇️ Descargar PDF
        </a>
        <button onclick="window.print()"
            class="bg-blue-800 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded-lg shadow transition">
            🖨️ Imprimir
        </button>
    </div>
</div>

<div class="pagina relative z-10 max-w-4xl mx-auto bg-white shadow-lg rounded-lg p-10 print:shadow-none print:rounded-none">

    {{-- ══ Encabezado institucional ══ --}}
    <div class="doc-header">
        <img src="{{ asset('images/escudoCBI.png') }}" alt="CBI" class="escudo">
        <div class="info">
            <div class="nombre">COLEGIO BILINGÜE INTEGRAL</div>
            <div class="meta"><strong>Nit:</strong> 901.302.189-8</div>
            <div class="meta">Según resolución No. 2528 del 11 de Julio de 2008</div>
            <div class="meta">Resolución 2331 de julio 27 de 1999: Preescolar &mdash; Básica Primaria &mdash; Básica Secundaria</div>
            <div class="meta">Resolución No. 14-029 de noviembre 19 de 2009: Educación Media</div>
            <div class="meta">Código ICFES 158832 &mdash; Código DANE 311001030073</div>
        </div>
    </div>

    {{-- ══ Cuerpo del certificado ══ --}}
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
                <th>ASIGNATURA</th>
                <th style="width: 55px;">I.H.S.</th>
                <th style="width: 65px;">ESCALA</th>
                <th style="width: 75px;">NIVEL</th>
                <th class="sep"></th>
                <th>ASIGNATURA</th>
                <th style="width: 65px;">ESCALA</th>
                <th style="width: 75px;">NIVEL</th>
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
                    <td class="center">{{ $izq['ihs'] ?? '' }}</td>
                    <td class="center">{{ $izq['escala'] ?? '' }}</td>
                    <td class="center">{{ $izq['nivel'] ?? '' }}</td>
                    <td class="sep"></td>
                    <td>{{ $der['nombre'] ?? '' }}</td>
                    <td class="center">{{ $der['escala'] ?? '' }}</td>
                    <td class="center">{{ $der['nivel'] ?? '' }}</td>
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

    {{-- ══ Pie institucional ══ --}}
    <div class="doc-footer">
        <div><strong>SEDE A:</strong> Calle 1ª # 29-05 &nbsp;&nbsp; <strong>SEDE B:</strong> Cra 29 # 8-04 Sur &nbsp;&nbsp; <strong>SEDE C:</strong> Cra. 28 # 1ª-12</div>
        <div><strong>PBX:</strong> 601 8051107 &nbsp;&nbsp; <strong>E-mail:</strong> administration@cbi.edu.co &nbsp;&nbsp; Bogotá &mdash; Colombia</div>
    </div>

</div>

</body>
</html>
