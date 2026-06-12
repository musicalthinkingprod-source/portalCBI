<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Informe desempeño académico {{ $anio }} — {{ $estudiante->APELLIDO1 }} {{ $estudiante->APELLIDO2 }} {{ $estudiante->NOMBRE1 }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { font-family: Arial, Helvetica, sans-serif; font-size: 10pt; color: #000; }

        .marca-agua {
            position: fixed; inset: 0;
            display: flex; align-items: center; justify-content: center;
            pointer-events: none; opacity: 0.06; z-index: 0;
        }
        .marca-agua img { width: 50%; max-width: 420px; height: auto; }

        .banda {
            display: flex; align-items: center;
            background: #9db7f0; padding: 8px 10px; gap: 10px;
        }
        .banda img { height: 52px; display: block; }
        .banda .titulo {
            flex: 1; text-align: center; font-weight: bold;
            font-size: 13pt; line-height: 1.35;
            margin-right: 80px; /* compensa el logo para centrar el texto */
        }

        .nombre-est {
            border: 1px solid #aaa;
            text-align: center; font-weight: bold; font-size: 12pt;
            padding: 5px 8px; margin: 14px 2px 0 2px;
        }
        table.datos { width: 100%; border-collapse: separate; border-spacing: 4px 3px; }
        table.datos td { border: 1px solid #aaa; padding: 4px 8px; font-size: 10pt; }
        td.lbl { font-weight: bold; }
        td.ctr { text-align: center; }

        .titulo-seccion {
            border: 1px solid #aaa;
            text-align: center; font-weight: bold; font-size: 11pt;
            padding: 4px 8px; margin: 10px 2px 4px 2px;
        }
        table.areas { width: 100%; border-collapse: separate; border-spacing: 4px 3px; }
        table.areas td.area { border: 1px solid #aaa; padding: 3px 8px; font-size: 9.5pt; }
        table.areas td.prom { border: 1px solid #aaa; width: 9%; text-align: center; font-weight: bold; font-size: 10pt; }

        table.materias { width: 100%; border-collapse: separate; border-spacing: 4px 3px; table-layout: fixed; }
        table.materias th {
            border: 1px solid #aaa; font-size: 8.5pt; font-weight: bold;
            text-align: center; padding: 3px 2px; vertical-align: middle;
        }
        table.materias th.sin-borde, table.materias td.sin-borde { border: none; }
        table.materias td { border: 1px solid #aaa; padding: 3px 6px; font-size: 9.5pt; vertical-align: middle; }
        table.materias td.acum { text-align: center; font-weight: bold; font-size: 10pt; }
        table.materias td.per  { text-align: center; font-size: 9pt; }
        .mala { color: #c00; text-decoration: underline; font-weight: bold; }

        .observaciones {
            border: 1px solid #aaa; margin: 10px 2px 0 2px;
            padding: 6px 8px; font-size: 9.5pt; min-height: 48px;
        }

        .firmas { margin-top: 70px; }
        table.firmas-tabla { width: 100%; border-collapse: collapse; }
        table.firmas-tabla td { width: 50%; padding-right: 60px; }
        .linea-firma { border-top: 1px solid #000; width: 85%; padding-top: 3px; font-size: 10pt; }

        @media print {
            .no-print { display: none !important; }
            body { background: white !important; margin: 0; }
            .pagina { box-shadow: none !important; margin: 0 !important; padding: 1cm !important; background: white !important; }
            .firmas { page-break-before: always; margin-top: 10px; }
            .banda, .marca-agua { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
        @page { size: letter; margin: 1.2cm 1.5cm; }
    </style>
</head>
<body class="bg-gray-200 min-h-screen py-6 print:bg-white print:py-0">

<div class="marca-agua" aria-hidden="true">
    <img src="{{ asset('images/escudoCBI.png') }}" alt="">
</div>

{{-- Barra de acciones y edición (solo pantalla) --}}
<div class="no-print max-w-4xl mx-auto mb-4 px-2 relative z-10">
    <div class="flex justify-between items-center gap-3 flex-wrap mb-3">
        <a href="{{ route('informe-anual.buscar', ['anio' => $anio]) }}"
           class="bg-white hover:bg-gray-50 text-gray-700 text-sm font-semibold px-4 py-2 rounded-lg shadow transition">
            ← Volver a búsqueda
        </a>
        <div class="flex gap-2">
            <a href="{{ route('informe-anual.pdf', ['codigo' => $codigo, 'anio' => $anio, 'director' => $director, 'obs' => $observaciones]) }}"
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

    <form method="GET" action="{{ route('informe-anual.ver', $codigo) }}"
          class="bg-white rounded-xl shadow p-4 flex gap-3 flex-wrap items-end">
        <input type="hidden" name="anio" value="{{ $anio }}">
        <div class="flex-1 min-w-48">
            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Director de grupo</label>
            <input type="text" name="director" value="{{ $director }}"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        <div class="flex-[2] min-w-64">
            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Observaciones</label>
            <input type="text" name="obs" value="{{ $observaciones }}"
                placeholder="Ej: Habilitó Inglés y aprobó"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        <button type="submit"
            class="bg-gray-800 hover:bg-gray-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition">
            Aplicar
        </button>
    </form>
    <p class="text-xs text-gray-500 mt-2 px-1">
        El director de grupo se prellena con el actual del curso {{ $cursoAnio }}; corrígelo si en {{ $anio }} era otro docente. Los cambios se reflejan también en el PDF.
    </p>
</div>

{{-- Página del informe --}}
<div class="pagina max-w-4xl mx-auto bg-white shadow-lg p-10 relative z-10">

    <div class="banda">
        <img src="{{ asset('images/escudoCBI.png') }}" alt="CBI">
        <div class="titulo">
            COLEGIO BILINGÜE INTEGRAL<br>
            INFORME DESEMPEÑO ACADÉMICO GENERAL
        </div>
    </div>

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

    <div class="titulo-seccion">PROMEDIO POR AREA</div>
    <table class="areas">
        @foreach($areas as $a)
        <tr>
            <td class="area">{{ mb_strtoupper($a['nombre']) }}</td>
            <td class="prom">{{ number_format($a['promedio'], 1, '.', '') }}</td>
        </tr>
        @endforeach
    </table>

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

    <div class="observaciones">{{ $observaciones }}</div>

    <div class="firmas">
        <table class="firmas-tabla">
            <tr>
                <td><div class="linea-firma">Rectoría</div></td>
                <td><div class="linea-firma">Director de grupo</div></td>
            </tr>
        </table>
    </div>

</div>

</body>
</html>
