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

        @media print {
            .no-print { display: none !important; }
            body { background: white !important; margin: 0; }
            .pagina { box-shadow: none !important; margin: 0 !important; padding: 16px !important; }
            table { page-break-inside: auto; }
            tr { page-break-inside: avoid; }
            .area-header { page-break-after: avoid; }
        }

        @page {
            size: A4;
            margin: 1.5cm 1.5cm 2cm 1.5cm;
        }
    </style>
</head>
<body class="bg-gray-200 min-h-screen py-6 print:bg-white print:py-0">

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

{{-- Página del boletín --}}
<div class="pagina max-w-4xl mx-auto bg-white shadow-lg rounded-lg p-8 print:shadow-none print:rounded-none">

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
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-widest">Informe Académico</p>
            <p class="text-3xl font-bold text-blue-900">{{ $anio }}</p>
        </div>
    </div>

    {{-- ══════════════════ DATOS DEL ESTUDIANTE ══════════════════ --}}
    <div class="grid grid-cols-2 gap-x-8 gap-y-1.5 mb-5 p-4 bg-gray-50 rounded-lg border border-gray-200 text-sm">
        <div class="flex gap-2">
            <span class="text-gray-500 w-32 shrink-0">Estudiante:</span>
            <span class="font-semibold text-gray-900">
                {{ $estudiante->APELLIDO1 }} {{ $estudiante->APELLIDO2 }}, {{ $estudiante->NOMBRE1 }} {{ $estudiante->NOMBRE2 }}
            </span>
        </div>
        <div class="flex gap-2">
            <span class="text-gray-500 w-32 shrink-0">Documento:</span>
            <span class="font-semibold text-gray-900">{{ $estudiante->TAR_ID ?? '—' }}</span>
        </div>
        <div class="flex gap-2">
            <span class="text-gray-500 w-32 shrink-0">Curso:</span>
            <span class="font-semibold text-gray-900">{{ $estudiante->CURSO ?? '—' }}</span>
        </div>
        <div class="flex gap-2">
            <span class="text-gray-500 w-32 shrink-0">Director de grupo:</span>
            <span class="font-semibold text-gray-900">
                {{ $director ? \Str::title(strtolower($director)) : '—' }}
            </span>
        </div>
        <div class="flex gap-2">
            <span class="text-gray-500 w-32 shrink-0">Año lectivo:</span>
            <span class="font-semibold text-gray-900">{{ $anio }}</span>
        </div>
        <div class="flex gap-2">
            <span class="text-gray-500 w-32 shrink-0">Estado:</span>
            <span class="font-semibold text-gray-900">{{ $estudiante->ESTADO ?? '—' }}</span>
        </div>
    </div>

    {{-- ══════════════════ TABLA DE NOTAS ══════════════════ --}}
    @if(empty($areas))
        <div class="text-center py-8 text-gray-400 text-sm">No hay notas registradas para el año {{ $anio }}.</div>
    @else

    @php
        $todasLasMedias = [];
    @endphp

    <table class="w-full text-sm border-collapse mb-5">
        <thead>
            <tr class="bg-blue-900 text-white">
                <th class="px-3 py-2 text-left font-semibold text-xs uppercase tracking-wide border border-blue-800">Área / Asignatura</th>
                @foreach([1,2,3,4] as $p)
                <th class="px-2 py-2 text-center font-semibold text-xs uppercase tracking-wide border border-blue-800 w-14">P{{ $p }}</th>
                @endforeach
                <th class="px-2 py-2 text-center font-semibold text-xs uppercase tracking-wide border border-blue-800 w-20">Promedio</th>
                <th class="px-2 py-2 text-center font-semibold text-xs uppercase tracking-wide border border-blue-800 w-24">Desempeño</th>
            </tr>
        </thead>
        <tbody>
        @foreach($areas as $areaId => $area)

            {{-- Fila de área --}}
            <tr class="area-header bg-blue-50">
                <td colspan="7" class="px-3 py-1.5 font-bold text-blue-900 text-xs uppercase tracking-wide border border-blue-200">
                    {{ $area['nombre'] }}
                </td>
            </tr>

            @php $mediasArea = []; @endphp

            @foreach($area['materias'] as $matId => $materia)
            @php
                $notasPeriodo = $materia['periodos'];
                $vals = array_filter(array_map(fn($p) => $notasPeriodo[$p]['nota'] ?? null, [1,2,3,4]), fn($v) => $v !== null);
                $media = count($vals) > 0 ? round(array_sum($vals) / count($vals), 1) : null;
                if ($media !== null) {
                    $mediasArea[] = $media;
                    $todasLasMedias[] = $media;
                }
                $desempeno = match(true) {
                    $media === null      => null,
                    $media >= 9.0        => ['label' => 'Superior',  'color' => 'text-blue-700'],
                    $media >= 8.0        => ['label' => 'Alto',       'color' => 'text-green-700'],
                    $media >= 6.0        => ['label' => 'Básico',     'color' => 'text-yellow-600'],
                    default              => ['label' => 'Bajo',       'color' => 'text-red-600 font-bold'],
                };
            @endphp
            <tr class="hover:bg-gray-50 border-b border-gray-100">
                <td class="px-3 py-1.5 text-gray-800 border-l border-r border-gray-200 pl-6">{{ $materia['nombre'] }}</td>

                @foreach([1,2,3,4] as $p)
                @php
                    $reg  = $notasPeriodo[$p] ?? null;
                    $nota = $reg['nota'] ?? null;
                    $tipo = $reg['tipo'] ?? null;
                @endphp
                <td class="px-1 py-1.5 text-center border border-gray-200">
                    @if($nota !== null)
                        <span class="{{ $nota < 6 ? 'text-red-600 font-bold' : 'text-gray-800' }}">
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

                <td class="px-2 py-1.5 text-center border border-gray-200 font-semibold {{ $media !== null && $media < 6 ? 'text-red-600' : 'text-gray-900' }}">
                    {{ $media !== null ? number_format($media, 1) : '—' }}
                </td>
                <td class="px-2 py-1.5 text-center border border-gray-200 text-xs font-semibold {{ $desempeno['color'] ?? 'text-gray-400' }}">
                    {{ $desempeno['label'] ?? '—' }}
                </td>
            </tr>
            @endforeach

            {{-- Promedio del área --}}
            @php $promedioArea = count($mediasArea) > 0 ? round(array_sum($mediasArea) / count($mediasArea), 1) : null; @endphp
            <tr class="bg-gray-100">
                <td class="px-3 py-1 text-xs font-bold text-gray-600 uppercase tracking-wide border border-gray-200 pl-6 italic">
                    Promedio del área
                </td>
                <td colspan="4" class="border border-gray-200"></td>
                <td class="px-2 py-1 text-center border border-gray-200 font-bold text-blue-900">
                    {{ $promedioArea !== null ? number_format($promedioArea, 1) : '—' }}
                </td>
                <td class="border border-gray-200"></td>
            </tr>

        @endforeach

        {{-- Promedio general --}}
        @php $promGeneral = count($todasLasMedias) > 0 ? round(array_sum($todasLasMedias) / count($todasLasMedias), 1) : null; @endphp
        <tr class="bg-blue-900 text-white">
            <td class="px-3 py-2 font-bold text-sm uppercase tracking-wide border border-blue-800" colspan="5">
                Promedio General
            </td>
            <td class="px-2 py-2 text-center font-bold text-lg border border-blue-800">
                {{ $promGeneral !== null ? number_format($promGeneral, 1) : '—' }}
            </td>
            <td class="px-2 py-2 text-center font-bold text-sm border border-blue-800">
                @if($promGeneral !== null)
                    {{ $promGeneral >= 9 ? 'Superior' : ($promGeneral >= 8 ? 'Alto' : ($promGeneral >= 6 ? 'Básico' : 'Bajo')) }}
                @endif
            </td>
        </tr>
        </tbody>
    </table>

    {{-- ══════════════════ ESCALA DE VALORACIÓN ══════════════════ --}}
    <div class="flex gap-3 mb-5 text-xs">
        <span class="px-3 py-1 rounded bg-blue-100 text-blue-800 font-semibold">Superior: 9.0 – 10.0</span>
        <span class="px-3 py-1 rounded bg-green-100 text-green-800 font-semibold">Alto: 8.0 – 8.9</span>
        <span class="px-3 py-1 rounded bg-yellow-100 text-yellow-800 font-semibold">Básico: 6.0 – 7.9</span>
        <span class="px-3 py-1 rounded bg-red-100 text-red-800 font-semibold">Bajo: 0.0 – 5.9</span>
        <span class="px-3 py-1 rounded bg-blue-50 text-blue-600 font-semibold"><sup>R</sup> Recuperada en derrotero</span>
    </div>

    @endif

    {{-- ══════════════════ OBSERVACIONES ══════════════════ --}}
    @if($observaciones->isNotEmpty())
    <div class="mb-6">
        <h3 class="text-xs font-bold text-gray-600 uppercase tracking-widest mb-2 border-b border-gray-200 pb-1">
            Observaciones del Director de Grupo
        </h3>
        <div class="space-y-2">
            @foreach($observaciones as $periodo => $obs)
            <div class="flex gap-3 text-sm">
                <span class="shrink-0 font-semibold text-blue-800 w-16">Período {{ $periodo }}:</span>
                <span class="text-gray-700 italic">{{ $obs->OBSERVACION }}</span>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- ══════════════════ FIRMAS ══════════════════ --}}
    <div class="mt-10 pt-4 border-t border-gray-300 grid grid-cols-3 gap-8 text-center text-xs text-gray-600">
        <div>
            <div class="border-b border-gray-400 mb-2 pb-8"></div>
            <p class="font-semibold">{{ $director ? \Str::title(strtolower($director)) : '________________________' }}</p>
            <p class="text-gray-500">Director(a) de Grupo</p>
        </div>
        <div>
            <div class="border-b border-gray-400 mb-2 pb-8"></div>
            <p class="font-semibold">Acudiente / Padre de Familia</p>
            <p class="text-gray-500">Firma y C.C.</p>
        </div>
        <div>
            <div class="border-b border-gray-400 mb-2 pb-8"></div>
            <p class="font-semibold">Rector(a)</p>
            <p class="text-gray-500">Colegio Bilingüe Integral</p>
        </div>
    </div>

    {{-- Pie de página --}}
    <p class="mt-6 text-center text-xs text-gray-400">
        Documento generado por el Portal Cebeista — {{ now()->format('d/m/Y H:i') }}
    </p>

</div>

</body>
</html>
