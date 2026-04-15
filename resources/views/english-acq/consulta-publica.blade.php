<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>English Acquisition — CBI</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-gray-100 min-h-screen">

<div class="min-h-screen flex flex-col items-center justify-start py-10 px-4">

    {{-- Encabezado --}}
    <div class="flex items-center gap-4 mb-8">
        <img src="{{ asset('images/escudoCBI.png') }}" alt="Logo CBI" class="h-14 w-auto opacity-90">
        <div>
            <h1 class="text-2xl font-bold text-blue-900 leading-tight">English Acquisition</h1>
            <p class="text-sm text-gray-500">Colegio Bilingüe Integral — Consulta de notas</p>
        </div>
    </div>

    {{-- Formulario de acceso --}}
    @if(!$estudiante)
    <div class="w-full max-w-md bg-white rounded-xl shadow p-6 mb-6">
        <h2 class="text-base font-bold text-blue-800 mb-1">Consultar notas</h2>
        <p class="text-xs text-gray-500 mb-4">Ingresa el código del estudiante y tu número de cédula como acudiente para consultar.</p>

        @if($error)
            <div class="mb-4 bg-red-50 border border-red-200 text-red-700 rounded-lg px-4 py-3 text-sm">
                {{ $error }}
            </div>
        @endif

        <form method="POST" action="{{ route('ingles.consulta') }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Código del estudiante</label>
                <input type="text" name="codigo" required autofocus
                    value="{{ old('codigo') }}"
                    placeholder="Ej: 21008"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Tu número de cédula (acudiente)</label>
                <input type="text" name="cc" required
                    value="{{ old('cc') }}"
                    placeholder="Solo números, sin puntos"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <button type="submit"
                class="w-full bg-blue-800 hover:bg-blue-700 text-white font-semibold py-2 rounded-lg text-sm transition">
                Consultar
            </button>
        </form>
    </div>
    @endif

    {{-- Resultados --}}
    @if($estudiante)
    <div class="w-full max-w-2xl space-y-5">

        {{-- Info estudiante --}}
        <div class="bg-blue-800 text-white rounded-xl px-5 py-4 flex items-center justify-between flex-wrap gap-3">
            <div>
                <p class="text-xs text-blue-300 uppercase tracking-wide mb-0.5">Estudiante</p>
                <p class="font-bold text-lg leading-tight">
                    {{ $estudiante->APELLIDO1 }} {{ $estudiante->APELLIDO2 }}
                    {{ $estudiante->NOMBRE1 }} {{ $estudiante->NOMBRE2 }}
                </p>
                <p class="text-blue-300 text-xs mt-0.5">Código: {{ $estudiante->CODIGO }} · Curso: {{ $estudiante->CURSO ?? '—' }}</p>
            </div>
            <a href="{{ route('ingles.consulta') }}"
                class="text-xs text-blue-200 hover:text-white underline">
                Consultar otro estudiante
            </a>
        </div>

        {{-- Notas por período --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            @foreach([1,2,3,4] as $p)
            @php $nota = $notas[$p] ?? 10; @endphp
            <div class="bg-white rounded-xl shadow p-4 text-center">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">Período {{ $p }}</p>
                <p class="text-4xl font-bold {{ $nota < 7 ? 'text-red-600' : ($nota < 8 ? 'text-yellow-500' : 'text-green-600') }}">
                    {{ number_format($nota, 2) }}
                </p>
                <p class="text-xs text-gray-400 mt-1">/10.00</p>
            </div>
            @endforeach
        </div>

        {{-- Historial de descuentos --}}
        <div class="bg-white rounded-xl shadow overflow-hidden">
            <div class="px-5 py-3 bg-gray-800 text-white">
                <h3 class="font-bold text-sm uppercase tracking-wide">Historial de descuentos · {{ date('Y') }}</h3>
                <p class="text-gray-400 text-xs mt-0.5">Cada registro representa −0.25 puntos sobre la nota de 10</p>
            </div>

            @if(empty($detalle))
                <div class="px-5 py-8 text-center text-gray-400 text-sm">
                    Sin descuentos registrados este año.
                </div>
            @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                        <tr>
                            <th class="px-4 py-3 text-center w-28">Período</th>
                            <th class="px-4 py-3 text-left">Fecha y hora</th>
                            <th class="px-4 py-3 text-center w-28">Descuento</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($detalle as $d)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2 text-center font-semibold text-blue-700">P{{ $d['periodo'] }}</td>
                            <td class="px-4 py-2 text-gray-600">
                                {{ \Carbon\Carbon::parse($d['fecha'])->format('d/m/Y H:i') }}
                            </td>
                            <td class="px-4 py-2 text-center font-semibold text-red-600">−0.25</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>

        <p class="text-center text-xs text-gray-400">
            Los datos mostrados corresponden al año {{ date('Y') }}.
        </p>
    </div>
    @endif

</div>

</body>
</html>
