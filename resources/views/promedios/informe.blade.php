<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Promedios — {{ $estudiante->APELLIDO1 }} {{ $estudiante->APELLIDO2 }} {{ $estudiante->NOMBRE1 }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { font-family: 'Figtree', sans-serif; }

        /* Marca de agua (escudo). Posición fija → se repite en cada página impresa. */
        .marca-agua {
            position: fixed;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            pointer-events: none;
            opacity: 0.05;
            z-index: 0;
        }
        .marca-agua img { width: 50%; max-width: 420px; height: auto; }

        @media print {
            .no-print { display: none !important; }
            body { background: white !important; margin: 0; }
            .pagina {
                box-shadow: none !important;
                margin: 0 !important;
                padding: 16px !important;
                background: transparent !important;
            }
            table { page-break-inside: auto; }
            tr { page-break-inside: avoid; }
            .area-header { page-break-after: avoid; }
            /* Forzar impresión de la marca de agua */
            .marca-agua { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }

        @page {
            size: A4;
            margin: 1.5cm 1.5cm 2cm 1.5cm;
        }
    </style>
</head>
<body class="bg-gray-200 min-h-screen py-6 print:bg-white print:py-0">

{{-- Marca de agua (fija, se repite en cada página al imprimir) --}}
<div class="marca-agua" aria-hidden="true">
    <img src="{{ asset('images/escudoCBI.png') }}" alt="">
</div>

{{-- Barra de acciones (solo pantalla) --}}
<div class="no-print max-w-4xl mx-auto mb-4 flex justify-between items-center px-2">
    @if(isset($origen) && $origen === 'padres')
        <a href="{{ route('padres.portal') }}"
           class="bg-white hover:bg-gray-50 text-gray-700 text-sm font-semibold px-4 py-2 rounded-lg shadow transition">
            ← Portal
        </a>
    @else
        <a href="{{ url()->previous() }}"
           class="bg-white hover:bg-gray-50 text-gray-700 text-sm font-semibold px-4 py-2 rounded-lg shadow transition">
            ← Volver
        </a>
    @endif
    <button onclick="window.print()"
        class="bg-blue-800 hover:bg-blue-700 text-white text-sm font-semibold px-5 py-2 rounded-lg shadow transition">
        🖨️ Imprimir / Guardar PDF
    </button>
</div>

{{-- Página del informe --}}
<div class="pagina relative z-10 max-w-4xl mx-auto bg-white shadow-lg rounded-lg p-8 print:shadow-none print:rounded-none">

    {{-- ══════════════════ ENCABEZADO ══════════════════ --}}
    <div class="flex items-center gap-5 pb-4 mb-4 border-b-2 border-blue-900">
        <img src="{{ asset('images/escudoCBI.png') }}" alt="CBI" class="h-20 w-auto">
        <div class="flex-1">
            <h1 class="text-xl font-bold text-blue-900 uppercase tracking-wide leading-tight">
                Colegio Bilingüe Integral
            </h1>
            <p class="text-sm text-gray-500 mt-0.5">Bogotá D.C. — Colombia</p>
        </div>
        <div class="text-right">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-widest">Informe de Promedios</p>
            <p class="text-3xl font-bold text-blue-900">{{ $anio }}</p>
        </div>
    </div>

    {{-- ══════════════════ DATOS DEL ESTUDIANTE ══════════════════ --}}
    <div class="mb-5 p-4 bg-gray-50 rounded-lg border border-gray-200 text-sm grid grid-cols-2 gap-x-8 gap-y-1.5">
        <div class="flex gap-2">
            <span class="text-gray-500 w-32 shrink-0">Estudiante:</span>
            <span class="font-semibold text-gray-900 whitespace-nowrap">
                {{ \Str::title(mb_strtolower(trim("{$estudiante->NOMBRE1} {$estudiante->NOMBRE2} {$estudiante->APELLIDO1} {$estudiante->APELLIDO2}"))) }}
            </span>
        </div>
        <div class="flex gap-2">
            <span class="text-gray-500 w-20 shrink-0">Código:</span>
            <span class="font-semibold text-gray-900">{{ $estudiante->CODIGO }}</span>
        </div>
        <div class="flex gap-2">
            <span class="text-gray-500 w-32 shrink-0">Director de grupo:</span>
            <span class="font-semibold text-gray-900">
                {{ $director ? \Str::title(strtolower($director)) : '—' }}
            </span>
        </div>
        <div class="flex gap-2">
            <span class="text-gray-500 w-20 shrink-0">Curso:</span>
            <span class="font-semibold text-gray-900">{{ $estudiante->CURSO ?? '—' }}</span>
        </div>
    </div>

    {{-- ══════════════════ TABLA DE PROMEDIOS ══════════════════ --}}
    @if(empty($areas))
        <div class="text-center py-8 text-gray-400 text-sm">No hay notas registradas para el año {{ $anio }}.</div>
    @else

    @php
        $promsArea       = [];   // promedio ponderado de cada área → para el promedio general
        $visibles        = $periodosVisibles ?? [1,2,3,4];
        $colspanPeriodos = 4;
        $colspanTotal    = 1 + $colspanPeriodos + 2;
        $nivelPond       = $nivel ?? \App\Helpers\PonderacionArea::nivel($estudiante->CURSO ?? null);
        $esPreescolar    = $nivelPond === 'PE';

        $escalaPreescolar = [
            1  => ['texto' => '¡No entiendo!',            'color' => '#B084A6'],
            2  => ['texto' => '¡No sé qué hacer!',         'color' => '#D19C9E'],
            3  => ['texto' => '¡No sé por dónde empezar!', 'color' => '#E8B7AE'],
            4  => ['texto' => '¡Esto está difícil!',       'color' => '#F2CDA8'],
            5  => ['texto' => '¡Me cuesta un poco!',       'color' => '#F5E3A6'],
            6  => ['texto' => '¡Esto es un reto!',         'color' => '#DDE8CB'],
            7  => ['texto' => '¡Lo estoy logrando!',       'color' => '#D3DCF0'],
            8  => ['texto' => '¡Estoy mejorando!',         'color' => '#BAC7E8'],
            9  => ['texto' => '¡Lo logré!',                'color' => '#89A5AA'],
            10 => ['texto' => '¡Misión cumplida!',         'color' => '#7FA6D6'],
        ];
        $fraseDe = function ($nota) use ($escalaPreescolar) {
            if ($nota === null || $nota === '') return null;
            $idx = max(1, min(10, (int) ceil((float) $nota)));
            return $escalaPreescolar[$idx];
        };

        // Pre-cálculo de medias y promedio por área (para mostrar el promedio
        // del área en la misma fila del nombre del área).
        $areasCalc = [];
        foreach ($areas as $areaId => $area) {
            $mediasArea   = [];
            $materiasCalc = [];
            foreach ($area['materias'] as $matId => $materia) {
                $notasPeriodo = $materia['periodos'];
                $vals = array_filter(
                    array_map(fn($p) => in_array($p, $visibles) ? ($notasPeriodo[$p]['nota'] ?? null) : null, [1,2,3,4]),
                    fn($v) => $v !== null
                );
                $media   = count($vals) > 0 ? round(array_sum($vals) / count($vals), 1) : null;
                $pesoMat = \App\Helpers\PonderacionArea::peso((int)$matId, $nivelPond);
                if ($media !== null && $pesoMat > 0) {
                    $mediasArea[] = ['media' => $media, 'peso' => $pesoMat];
                }
                $materiasCalc[$matId] = ['data' => $materia, 'media' => $media];
            }
            $_sumP = array_sum(array_column($mediasArea, 'peso'));
            $promedioArea = $_sumP > 0
                ? round(array_sum(array_map(fn($m) => $m['media'] * $m['peso'], $mediasArea)) / $_sumP, 1)
                : null;
            if ($promedioArea !== null) $promsArea[] = $promedioArea;
            $areasCalc[$areaId] = [
                'nombre'   => $area['nombre'],
                'materias' => $materiasCalc,
                'promedio' => $promedioArea,
            ];
        }
        $promGeneral = count($promsArea) > 0 ? round(array_sum($promsArea) / count($promsArea), 1) : null;
    @endphp

    @if($esPreescolar)
    {{-- ══════════════════ PROMEDIOS PREESCOLAR (PJ / J / T) ══════════════════ --}}
    <table class="w-full text-sm border-collapse mb-5">
        <thead>
            <tr class="bg-blue-900 text-white">
                <th class="px-3 py-2 text-left font-semibold text-xs uppercase tracking-wide border border-blue-800">Dimensión</th>
                @foreach([1,2,3,4] as $p)
                <th class="px-2 py-2 text-center font-semibold text-xs uppercase tracking-wide border border-blue-800 w-36">
                    P{{ $p }}
                    @if(!in_array($p, $visibles))
                        <span class="block text-blue-300 font-normal normal-case" style="font-size:9px">pendiente</span>
                    @endif
                </th>
                @endforeach
            </tr>
        </thead>
        <tbody>
        @foreach($areas as $areaId => $area)
            @php
                // En preescolar se omiten English Acquisition (11) y Proyecto (31/131).
                $matsVisibles = array_filter(
                    $area['materias'],
                    fn($_m, $mid) => !in_array((int) $mid, [11, 31, 131], true),
                    ARRAY_FILTER_USE_BOTH
                );
                if (empty($matsVisibles)) continue;

                $promDim  = $areasCalc[$areaId]['promedio'] ?? null;
                $fraseDim = $fraseDe($promDim);
            @endphp
            <tr class="area-header bg-blue-50">
                <td class="px-3 py-1.5 border border-blue-200">
                    <div class="flex items-center justify-between gap-3">
                        <span class="font-bold text-blue-900 text-xs uppercase tracking-wide">{{ $area['nombre'] }}</span>
                        <span class="text-[10px] uppercase tracking-wide text-blue-700 font-semibold italic shrink-0">Prom. dimensión</span>
                    </div>
                </td>
                <td colspan="{{ $colspanPeriodos }}" class="px-2 py-1.5 text-center text-xs font-bold border border-blue-200 text-gray-900 leading-tight"
                    @if($fraseDim) style="background-color: {{ $fraseDim['color'] }}; -webkit-print-color-adjust: exact; print-color-adjust: exact;" @endif>
                    {{ $fraseDim['texto'] ?? '—' }}
                </td>
            </tr>
            @foreach($area['materias'] as $matId => $materia)
            @php
                if (in_array((int) $matId, [11, 31, 131], true)) continue;
                $notasPeriodo = $materia['periodos'];
            @endphp
            <tr class="border-b border-gray-100">
                <td class="px-3 py-1.5 text-gray-800 border-l border-r border-gray-200 pl-6">
                    {{ $materia['nombre'] }}
                    @if($materia['docente'])
                        <div class="text-xs text-gray-400 italic mt-0.5">{{ \Str::title(strtolower($materia['docente'])) }}</div>
                    @endif
                </td>
                @foreach([1,2,3,4] as $p)
                    @php
                        $reg         = $notasPeriodo[$p] ?? null;
                        $esPendiente = !in_array($p, $visibles);
                        $frase       = $esPendiente ? null : $fraseDe($reg['nota'] ?? null);
                    @endphp
                    <td class="px-2 py-1.5 text-center text-xs font-bold border border-gray-200 text-gray-900 leading-tight {{ $esPendiente ? 'bg-gray-50' : '' }}"
                        @if($frase) style="background-color: {{ $frase['color'] }}; -webkit-print-color-adjust: exact; print-color-adjust: exact;" @endif>
                        {{ $frase['texto'] ?? '—' }}
                    </td>
                @endforeach
            </tr>
            @endforeach
        @endforeach
        </tbody>
    </table>

    {{-- ══════════════════ ESCALA DE VALORACIÓN (PREESCOLAR) ══════════════════ --}}
    <div class="mb-5">
        <p class="text-xs font-bold text-gray-600 uppercase tracking-widest mb-1">Escala de Valoración</p>
        <div class="flex justify-between items-baseline mb-1.5 text-[10px] text-gray-500 italic font-semibold">
            <span>← Menor desempeño</span>
            <span>Mayor desempeño →</span>
        </div>
        {{-- Fila superior: posiciones impares (1, 3, 5, 7, 9) --}}
        <div class="grid gap-1 mb-1" style="grid-template-columns: repeat(11, minmax(0, 1fr));">
            @foreach($escalaPreescolar as $ix => $info)
                @if($ix % 2 === 1)
                    <div class="flex items-center justify-center min-w-0 h-10 px-1.5 py-1 text-gray-900 font-bold text-[10px] text-center rounded border border-gray-300 leading-tight break-words"
                         style="grid-column: {{ $ix }} / span 2; background-color: {{ $info['color'] }}; -webkit-print-color-adjust: exact; print-color-adjust: exact;">
                        <span>{{ $info['texto'] }}</span>
                    </div>
                @endif
            @endforeach
        </div>
        {{-- Fila inferior: posiciones pares (2, 4, 6, 8, 10), desfasadas media columna --}}
        <div class="grid gap-1" style="grid-template-columns: repeat(11, minmax(0, 1fr));">
            @foreach($escalaPreescolar as $ix => $info)
                @if($ix % 2 === 0)
                    <div class="flex items-center justify-center min-w-0 h-10 px-1.5 py-1 text-gray-900 font-bold text-[10px] text-center rounded border border-gray-300 leading-tight break-words"
                         style="grid-column: {{ $ix }} / span 2; background-color: {{ $info['color'] }}; -webkit-print-color-adjust: exact; print-color-adjust: exact;">
                        <span>{{ $info['texto'] }}</span>
                    </div>
                @endif
            @endforeach
        </div>
    </div>

    @else
    <table class="w-full text-sm border-collapse mb-5">
        <thead>
            <tr class="bg-blue-900 text-white">
                <th class="px-3 py-2 text-left font-semibold text-xs uppercase tracking-wide border border-blue-800">Área / Asignatura</th>
                @foreach([1,2,3,4] as $p)
                <th class="px-2 py-2 text-center font-semibold text-xs uppercase tracking-wide border border-blue-800 w-14">
                    P{{ $p }}
                    @if(!in_array($p, $visibles))
                        <span class="block text-blue-300 font-normal normal-case" style="font-size:9px">pendiente</span>
                    @endif
                </th>
                @endforeach
                <th class="px-2 py-2 text-center font-semibold text-xs uppercase tracking-wide border border-blue-800 w-20">Promedio</th>
                <th class="px-2 py-2 text-center font-semibold text-xs uppercase tracking-wide border border-blue-800 w-24">Desempeño</th>
            </tr>
        </thead>
        <tbody>
        @foreach($areasCalc as $areaId => $area)

            @php
                $pa = $area['promedio'];
                $desArea = match(true) {
                    $pa === null => null,
                    $pa > 9.0    => ['label' => 'Superior', 'color' => 'text-blue-700'],
                    $pa > 8.0    => ['label' => 'Alto',     'color' => 'text-green-700'],
                    $pa >= 7.0   => ['label' => 'Básico',   'color' => 'text-yellow-600'],
                    default      => ['label' => 'Bajo',     'color' => 'text-red-600 font-bold'],
                };
            @endphp
            {{-- Fila de área (con promedio del área en la misma fila) --}}
            <tr class="area-header bg-blue-50">
                <td colspan="{{ 1 + $colspanPeriodos }}" class="px-3 py-1.5 border border-blue-200">
                    <div class="flex items-center justify-between gap-3">
                        <span class="font-bold text-blue-900 text-xs uppercase tracking-wide">{{ $area['nombre'] }}</span>
                        <span class="text-[10px] uppercase tracking-wide text-blue-700 font-semibold italic shrink-0">Prom. área</span>
                    </div>
                </td>
                <td class="px-2 py-1.5 text-center font-bold text-blue-900 text-sm border border-blue-200">
                    {{ $pa !== null ? number_format($pa, 1) : '—' }}
                </td>
                <td class="px-2 py-1.5 text-center text-xs font-semibold border border-blue-200 {{ $desArea['color'] ?? 'text-gray-400' }}">
                    {{ $desArea['label'] ?? '—' }}
                </td>
            </tr>

            @foreach($area['materias'] as $matId => $matCalc)
            @php
                $materia      = $matCalc['data'];
                $media        = $matCalc['media'];
                $notasPeriodo = $materia['periodos'];
                $desempeno = match(true) {
                    $media === null => null,
                    $media > 9.0    => ['label' => 'Superior', 'color' => 'text-blue-700'],
                    $media > 8.0    => ['label' => 'Alto',     'color' => 'text-green-700'],
                    $media >= 7.0   => ['label' => 'Básico',   'color' => 'text-yellow-600'],
                    default         => ['label' => 'Bajo',     'color' => 'text-red-600 font-bold'],
                };
            @endphp
            <tr class="hover:bg-gray-50 border-b border-gray-100">
                <td class="px-3 py-1.5 text-gray-800 border-l border-r border-gray-200 pl-6">
                    {{ $materia['nombre'] }}
                    @if($materia['docente'])
                        <div class="text-xs text-gray-400 italic mt-0.5">{{ \Str::title(strtolower($materia['docente'])) }}</div>
                    @endif
                </td>

                @foreach([1,2,3,4] as $p)
                @php
                    $reg       = $notasPeriodo[$p] ?? null;
                    $nota      = $reg['nota'] ?? null;
                    $tipo      = $reg['tipo'] ?? null;
                    $esPendiente = !in_array($p, $visibles);
                @endphp
                <td class="px-1 py-1.5 text-center border border-gray-200 {{ $esPendiente ? 'bg-gray-50' : '' }}">
                    @if($esPendiente)
                        <span class="text-gray-300 text-xs">—</span>
                    @elseif($nota !== null)
                        <span class="{{ $nota < 7 ? 'text-red-600 font-bold' : 'text-gray-800' }}">
                            {{ number_format($nota, 1) }}
                        </span>
                        @if($tipo === 'R')
                            <sup class="text-blue-500 text-xs">R</sup>
                        @endif
                    @else
                        <span class="text-gray-300 text-xs">—</span>
                    @endif
                </td>
                @endforeach

                <td class="px-2 py-1.5 text-center border border-gray-200 font-semibold {{ $media !== null && $media < 7 ? 'text-red-600' : 'text-gray-900' }}">
                    {{ $media !== null ? number_format($media, 1) : '—' }}
                </td>
                <td class="px-2 py-1.5 text-center border border-gray-200 text-xs font-semibold {{ $desempeno['color'] ?? 'text-gray-400' }}">
                    {{ $desempeno['label'] ?? '—' }}
                </td>
            </tr>
            @endforeach

        @endforeach

        {{-- Promedio general = media simple de los promedios ponderados de cada área --}}
        <tr class="bg-blue-900 text-white">
            <td class="px-3 py-2 font-bold text-sm uppercase tracking-wide border border-blue-800" colspan="{{ 1 + $colspanPeriodos }}">
                Promedio General
            </td>
            <td class="px-2 py-2 text-center font-bold text-lg border border-blue-800">
                {{ $promGeneral !== null ? number_format($promGeneral, 1) : '—' }}
            </td>
            <td class="px-2 py-2 text-center font-bold text-sm border border-blue-800">
                @if($promGeneral !== null)
                    {{ $promGeneral > 9 ? 'Superior' : ($promGeneral > 8 ? 'Alto' : ($promGeneral >= 7 ? 'Básico' : 'Bajo')) }}
                @endif
            </td>
        </tr>
        </tbody>
    </table>

    {{-- ══════════════════ ESCALA DE VALORACIÓN ══════════════════ --}}
    <div class="flex gap-3 mb-5 text-xs flex-wrap">
        <span class="px-3 py-1 rounded bg-blue-100 text-blue-800 font-semibold">Superior: 9.1 – 10.0</span>
        <span class="px-3 py-1 rounded bg-green-100 text-green-800 font-semibold">Alto: 8.1 – 9.0</span>
        <span class="px-3 py-1 rounded bg-yellow-100 text-yellow-800 font-semibold">Básico: 7.0 – 8.0</span>
        <span class="px-3 py-1 rounded bg-red-100 text-red-800 font-semibold">Bajo: 1.0 – 6.9</span>
        <span class="px-3 py-1 rounded bg-blue-50 text-blue-600 font-semibold"><sup>R</sup> Recuperada</span>
    </div>
    @endif

    @if(count($visibles) < 4)
    <div class="mb-5 p-3 bg-amber-50 border border-amber-200 rounded-lg text-xs text-amber-800">
        <strong>Nota:</strong> Los períodos marcados como "pendiente" aún no han sido publicados.
        El promedio refleja únicamente los períodos disponibles.
    </div>
    @endif

    @endif

    {{-- Pie de página --}}
    <p class="mt-6 text-center text-xs text-gray-400">
        Documento generado por el Portal Cebeista — {{ now()->format('d/m/Y H:i') }}
    </p>

</div>

</body>
</html>
