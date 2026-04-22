<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Boletín — {{ $estudiante->APELLIDO1 }} {{ $estudiante->APELLIDO2 }} {{ $estudiante->NOMBRE1 }} {{ $estudiante->NOMBRE2 }}</title>
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
    @if($origen === 'interno')
        <a href="{{ url()->previous() }}"
           class="bg-white hover:bg-gray-50 text-gray-700 text-sm font-semibold px-4 py-2 rounded-lg shadow transition">
            ← Volver
        </a>
    @else
        <a href="{{ route('padres.portal') }}"
           class="bg-white hover:bg-gray-50 text-gray-700 text-sm font-semibold px-4 py-2 rounded-lg shadow transition">
            ← Portal
        </a>
    @endif
    @if($puedeImprimir ?? true)
    <button onclick="window.print()"
        class="bg-blue-800 hover:bg-blue-700 text-white text-sm font-semibold px-5 py-2 rounded-lg shadow transition">
        🖨️ Imprimir / Guardar PDF
    </button>
    @else
    <span class="text-xs text-gray-400 italic">Solo lectura — no disponible para imprimir</span>
    @endif
</div>

{{-- Selector de período (solo portal de padres). Muestra P1–P4, los no habilitados quedan bloqueados. --}}
@if(($origen ?? null) === 'padres')
<div class="no-print max-w-4xl mx-auto mb-4 px-2">
    <div class="flex items-center gap-2 bg-white rounded-lg shadow px-4 py-2 flex-wrap">
        <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide shrink-0">Período:</span>
        @foreach([1,2,3,4] as $p)
            @php $habilitado = in_array($p, $periodosDisponibles ?? [], true); @endphp
            @if($habilitado)
                <a href="{{ route('padres.boletines', ['periodo' => $p]) }}"
                   class="text-xs font-semibold px-3 py-1 rounded transition {{ (int)($periodoSel ?? 0) === $p ? 'bg-blue-800 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                    P{{ $p }}
                </a>
            @else
                <span class="text-xs font-semibold px-3 py-1 rounded bg-gray-50 text-gray-400 cursor-not-allowed inline-flex items-center gap-1"
                      title="Aún no habilitado">
                    🔒 P{{ $p }}
                </span>
            @endif
        @endforeach
    </div>
</div>
@endif

{{-- Página del boletín --}}
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
            @php
                $ordinal = [1 => 'Primer', 2 => 'Segundo', 3 => 'Tercer', 4 => 'Cuarto'][$periodoFiltro ?? 0] ?? null;
            @endphp
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-widest">Informe Académico</p>
            <p class="text-3xl font-bold text-blue-900">{{ $anio }}</p>
            @if($ordinal)
                <p class="text-sm font-semibold text-blue-800 mt-0.5">{{ $ordinal }} Periodo</p>
            @endif
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

    {{-- ══════════════════ TABLA DE NOTAS ══════════════════ --}}
    @if(empty($areas))
        <div class="text-center py-8 text-gray-400 text-sm">No hay notas registradas para el año {{ $anio }}.</div>
    @else

    @php
        $promsArea        = [];   // promedio ponderado de cada área → para el promedio general
        $periodosVisibles = isset($periodoFiltro) && $periodoFiltro ? [$periodoFiltro] : [1,2,3,4];
        $colN             = count($periodosVisibles);
        $colspan          = 2 + $colN;
        $nivelPond        = $nivel ?? \App\Helpers\PonderacionArea::nivel($estudiante->CURSO ?? null);
        $esPreescolar     = $nivelPond === 'PE';

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

        // Pre-cálculo de medias de materias y promedio por área (para mostrar
        // el promedio del área en la misma fila del nombre del área).
        $areasCalc = [];
        foreach ($areas as $areaId => $area) {
            $mediasArea    = [];
            $materiasCalc  = [];
            foreach ($area['materias'] as $matId => $materia) {
                $notasPeriodo = $materia['periodos'];
                $vals  = array_filter(array_map(fn($p) => $notasPeriodo[$p]['nota'] ?? null, $periodosVisibles), fn($v) => $v !== null);
                $media = count($vals) > 0 ? round(array_sum($vals) / count($vals), 1) : null;
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
    {{-- ══════════════════ BOLETÍN PREESCOLAR (PJ / J / T) ══════════════════ --}}
    <table class="w-full text-sm border-collapse mb-5">
        <thead>
            <tr class="bg-blue-900 text-white">
                <th class="px-3 py-2 text-left font-semibold text-xs uppercase tracking-wide border border-blue-800">Dimensión</th>
                @foreach($periodosVisibles as $p)
                <th class="px-2 py-2 text-center font-semibold text-xs uppercase tracking-wide border border-blue-800 w-36">{{ $colN === 1 ? 'Valoración' : 'P'.$p }}</th>
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
                <td colspan="{{ $colN }}" class="px-2 py-1.5 text-center text-xs font-bold border border-blue-200 text-gray-900 leading-tight"
                    @if($fraseDim) style="background-color: {{ $fraseDim['color'] }}; -webkit-print-color-adjust: exact; print-color-adjust: exact;" @endif>
                    {{ $fraseDim['texto'] ?? '—' }}
                </td>
            </tr>
            @foreach($area['materias'] as $matId => $materia)
            @php
                // En preescolar no se muestran English Acquisition (11) ni Proyecto (31/131),
                // aunque tengan nota cargada.
                if (in_array((int) $matId, [11, 31, 131], true)) continue;
            @endphp
            <tr class="border-b border-gray-100">
                <td class="px-3 py-1.5 text-gray-800 border-l border-r border-gray-200 pl-6">
                    {{ $materia['nombre'] }}
                    @if($materia['docente'])
                        <div class="text-xs text-gray-400 italic mt-0.5">{{ \Str::title(strtolower($materia['docente'])) }}</div>
                    @endif
                </td>
                @foreach($periodosVisibles as $p)
                    @php
                        $reg   = $materia['periodos'][$p] ?? null;
                        $frase = $fraseDe($reg['nota'] ?? null);
                    @endphp
                    <td class="px-2 py-1.5 text-center text-xs font-bold border border-gray-200 text-gray-900 leading-tight"
                        @if($frase) style="background-color: {{ $frase['color'] }}; -webkit-print-color-adjust: exact; print-color-adjust: exact;" @endif>
                        {{ $frase['texto'] ?? '—' }}
                    </td>
                @endforeach
            </tr>
            @php
                $logrosData = [];
                foreach ($periodosVisibles as $lp) {
                    $txt = $materia['periodos'][$lp]['logro'] ?? null;
                    if ($txt) $logrosData[$lp] = $txt;
                }
                $textosSinDup = array_unique(array_values($logrosData));
            @endphp
            @if(!empty($logrosData))
            <tr class="border-b border-gray-100">
                <td colspan="{{ 1 + $colN }}" class="px-6 py-2 text-xs text-gray-600 italic leading-snug bg-gray-50 border-l border-r border-gray-200">
                    @if(count($textosSinDup) === 1)
                        {{ $textosSinDup[0] }}
                    @else
                        @foreach($logrosData as $lp => $ltxt)
                            <span class="font-semibold not-italic text-gray-500">P{{ $lp }}:</span> {{ $ltxt }}<br>
                        @endforeach
                    @endif
                </td>
            </tr>
            @endif
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
                @foreach($periodosVisibles as $p)
                <th class="px-2 py-2 text-center font-semibold text-xs uppercase tracking-wide border border-blue-800 w-16">{{ $colN === 1 ? 'Nota' : 'P'.$p }}</th>
                @endforeach
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
                <td class="px-3 py-1.5 border border-blue-200">
                    <div class="flex items-center justify-between gap-3">
                        <span class="font-bold text-blue-900 text-xs uppercase tracking-wide">{{ $area['nombre'] }}</span>
                        <span class="text-[10px] uppercase tracking-wide text-blue-700 font-semibold italic shrink-0">Prom. área</span>
                    </div>
                </td>
                <td colspan="{{ $colN }}" class="px-2 py-1.5 text-center font-bold text-blue-900 text-sm border border-blue-200">
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

                @foreach($periodosVisibles as $p)
                @php
                    $reg  = $notasPeriodo[$p] ?? null;
                    $nota = $reg['nota'] ?? null;
                    $tipo = $reg['tipo'] ?? null;
                @endphp
                <td class="px-1 py-1.5 text-center border border-gray-200">
                    @if($nota !== null)
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

                <td class="px-2 py-1.5 text-center border border-gray-200 text-xs font-semibold {{ $desempeno['color'] ?? 'text-gray-400' }}">
                    {{ $desempeno['label'] ?? '—' }}
                </td>
            </tr>
            @php
                // Recopilar logros únicos de los períodos visibles para esta materia
                $logrosData = [];
                foreach ($periodosVisibles as $lp) {
                    $txt = $notasPeriodo[$lp]['logro'] ?? null;
                    if ($txt) $logrosData[$lp] = $txt;
                }
                $textosSinDup = array_unique(array_values($logrosData));
            @endphp
            @if(!empty($logrosData))
            <tr class="border-b border-gray-100">
                <td colspan="{{ $colspan }}" class="px-6 py-2 text-xs text-gray-600 italic leading-snug bg-gray-50 border-l border-r border-gray-200">
                    @if(count($textosSinDup) === 1)
                        {{ $textosSinDup[0] }}
                    @else
                        @foreach($logrosData as $lp => $ltxt)
                            <span class="font-semibold not-italic text-gray-500">P{{ $lp }}:</span> {{ $ltxt }}<br>
                        @endforeach
                    @endif
                </td>
            </tr>
            @endif
            @endforeach

        @endforeach

        {{-- Promedio general = media simple de los promedios ponderados de cada área --}}
        @php
            $desGeneral = match(true) {
                $promGeneral === null => null,
                $promGeneral > 9.0    => 'Superior',
                $promGeneral > 8.0    => 'Alto',
                $promGeneral >= 7.0   => 'Básico',
                default               => 'Bajo',
            };
        @endphp
        <tr class="bg-blue-900 text-white">
            <td class="px-3 py-2 font-bold text-sm uppercase tracking-wide border border-blue-800">
                Promedio General
            </td>
            <td colspan="{{ $colN }}" class="px-2 py-2 text-center font-bold text-lg border border-blue-800">
                {{ $promGeneral !== null ? number_format($promGeneral, 1) : '—' }}
            </td>
            <td class="px-2 py-2 text-center text-xs font-semibold border border-blue-800">
                {{ $desGeneral ?? '—' }}
            </td>
        </tr>
        </tbody>
    </table>

    {{-- ══════════════════ ESCALA DE VALORACIÓN ══════════════════ --}}
    <div class="flex gap-3 mb-5 text-xs">
        <span class="px-3 py-1 rounded bg-blue-100 text-blue-800 font-semibold">Superior: 9.1 – 10.0</span>
        <span class="px-3 py-1 rounded bg-green-100 text-green-800 font-semibold">Alto: 8.1 – 9.0</span>
        <span class="px-3 py-1 rounded bg-yellow-100 text-yellow-800 font-semibold">Básico: 7.0 – 8.0</span>
        <span class="px-3 py-1 rounded bg-red-100 text-red-800 font-semibold">Bajo: 1.0 – 6.9</span>
        <span class="px-3 py-1 rounded bg-blue-50 text-blue-600 font-semibold"><sup>R</sup> Recuperada</span>
    </div>
    @endif

    @endif

    {{-- ══════════════════ OBSERVACIONES ══════════════════ --}}
    @php
        $obsVisibles = isset($periodoFiltro) && $periodoFiltro
            ? $observaciones->filter(fn($o, $p) => (int) $p === (int) $periodoFiltro)
            : $observaciones;
    @endphp
    @if($obsVisibles->isNotEmpty())
    <div class="pb-4 border-b border-gray-300">
        <h3 class="text-xs font-bold text-gray-600 uppercase tracking-widest mb-2 border-b border-gray-200 pb-1">
            Observaciones del Director de Grupo
        </h3>
        <div class="space-y-2">
            @foreach($obsVisibles as $periodo => $obs)
            <p class="text-sm text-gray-700 italic">{{ $obs->OBSERVACION }}</p>
            @endforeach
        </div>
    </div>
    @endif

    {{-- ══════════════════ FIRMAS (solo versión interna; nunca en portal de padres) ══════════════════ --}}
    @if(($origen ?? null) !== 'padres')
    <div>
        <div class="h-28"></div>
        <div class="grid grid-cols-2 gap-8 text-center text-xs text-gray-600">
            <div>
                <div class="border-b border-gray-400 mb-2"></div>
                <p class="font-semibold">{{ $director ? \Str::title(strtolower($director)) : '________________________' }}</p>
                <p class="text-gray-500">Director(a) de Grupo</p>
            </div>
            <div>
                <div class="border-b border-gray-400 mb-2"></div>
                <p class="font-semibold">Rector(a)</p>
                <p class="text-gray-500">Colegio Bilingüe Integral</p>
            </div>
        </div>
    </div>
    @else
    <p class="mt-6 text-center text-[10px] text-gray-400 italic">
        Documento de consulta para padres de familia. Los boletines firmados son emitidos por la institución únicamente bajo solicitud.
    </p>
    @endif

    {{-- Pie de página --}}
    <p class="mt-6 text-center text-xs text-gray-400">
        Documento generado por el Portal Cebeista — {{ now()->format('d/m/Y H:i') }}
    </p>

</div>

</body>
</html>
