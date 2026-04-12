@extends('layouts.app-sidebar')

@section('header', 'Control de Fechas – PIAR')

@section('slot')

@if(session('saved'))
    <div class="mb-4 p-3 bg-green-100 text-green-800 rounded-xl text-sm">✅ {{ session('saved') }}</div>
@endif

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

    {{-- ── Formulario de fechas ──────────────────────────────────────────── --}}
    <div class="xl:col-span-2 space-y-4">

        <div class="bg-white rounded-xl shadow p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h2 class="text-base font-bold text-blue-900">Fechas límite PIAR – {{ $anio }}</h2>
                <p class="text-xs text-gray-500 mt-0.5">
                    Define hasta cuándo deben estar diligenciadas cada tarea por período.
                    Estas fechas se usan en las alertas del Anexo 2 para docentes.
                </p>
            </div>
            <form method="POST" action="{{ route('control.piar_fechas.cargar') }}">
                @csrf
                <button type="submit"
                    onclick="return confirm('¿Cargar fechas sugeridas desde el calendario académico? Solo se llenarán las que estén vacías.')"
                    class="whitespace-nowrap bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-semibold px-4 py-2 rounded-lg transition">
                    📅 Cargar del calendario
                </button>
            </form>
        </div>

        <form method="POST" action="{{ route('control.piar_fechas.guardar') }}">
            @csrf

            @foreach($tareas as $grupo => $items)
            <div class="bg-white rounded-xl shadow overflow-hidden">
                <div class="px-5 py-3 bg-blue-800 text-white">
                    <h3 class="text-sm font-bold uppercase tracking-wide">{{ $grupo }}</h3>
                </div>
                <div class="divide-y divide-gray-100">
                    @foreach($items as $tarea)
                    @php
                        $fechaGuardada = $fechas[$tarea['key']] ?? null;
                        $hoy = today()->toDateString();
                        $vencida = $fechaGuardada && $fechaGuardada < $hoy;
                        $proxima = $fechaGuardada && !$vencida && \Carbon\Carbon::parse($fechaGuardada)->diffInDays(today()) <= 7;
                    @endphp
                    <div class="flex items-center gap-4 px-5 py-3">
                        <div class="flex-1">
                            <span class="text-sm font-medium text-gray-800">Período {{ $tarea['periodo'] }}</span>
                            @if($fechaGuardada)
                                @if($vencida)
                                    <span class="ml-2 text-xs text-red-600 font-semibold">· venció</span>
                                @elseif($proxima)
                                    <span class="ml-2 text-xs text-orange-500 font-semibold">· en {{ \Carbon\Carbon::parse($fechaGuardada)->diffInDays(today()) }} días</span>
                                @endif
                            @endif
                        </div>
                        <input type="date" name="{{ $tarea['key'] }}"
                            value="{{ $fechaGuardada ?? '' }}"
                            class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500
                                {{ $vencida ? 'border-red-300 bg-red-50' : ($proxima ? 'border-orange-300 bg-orange-50' : '') }}">
                        @if($fechaGuardada)
                            <span class="text-xs text-gray-400 whitespace-nowrap">
                                {{ \Carbon\Carbon::parse($fechaGuardada)->locale('es')->isoFormat('D MMM YYYY') }}
                            </span>
                        @else
                            <span class="text-xs text-gray-300 whitespace-nowrap">Sin fecha</span>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
            @endforeach

            <div class="flex justify-end pt-2 pb-4">
                <button type="submit"
                    class="bg-blue-800 hover:bg-blue-700 text-white font-semibold text-sm px-8 py-2.5 rounded-lg transition">
                    💾 Guardar fechas
                </button>
            </div>
        </form>
    </div>

    {{-- ── Panel: Eventos del calendario relacionados ───────────────────── --}}
    <div class="space-y-4">
        <div class="bg-white rounded-xl shadow overflow-hidden">
            <div class="px-5 py-3 bg-gray-700 text-white">
                <h3 class="text-sm font-bold uppercase tracking-wide">Eventos del calendario académico</h3>
                <p class="text-gray-300 text-xs mt-0.5">Eventos relacionados con PIAR/Anexos – {{ $anio }}</p>
            </div>
            @if($eventosCalendario->isEmpty())
                <p class="text-sm text-gray-400 px-5 py-4 italic">
                    No hay eventos relacionados con PIAR en el calendario de {{ $anio }}.
                </p>
            @else
                <div class="divide-y divide-gray-100">
                    @foreach($eventosCalendario as $ev)
                    <div class="px-5 py-3 flex gap-3 items-start">
                        <span class="font-mono text-xs text-blue-700 whitespace-nowrap mt-0.5">
                            {{ \Carbon\Carbon::parse($ev->fecha)->format('d/m') }}
                        </span>
                        <span class="text-xs text-gray-700 leading-snug">{{ $ev->evento }}</span>
                    </div>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 text-xs text-blue-700 space-y-1.5">
            <p class="font-semibold text-blue-900">¿Cómo funciona?</p>
            <p>Las fechas límite que defines aquí aparecen como alertas en el módulo de Anexo 2 de los docentes.</p>
            <p>Si una tarea aún no está diligenciada y ya venció su fecha límite, el sistema la marca en rojo.</p>
            <p><strong>Cargar del calendario</strong> busca eventos en el calendario académico con palabras clave (PIAR, ANEXO, AJUSTE, CARACT) y pre-llena las fechas vacías. Luego puedes ajustarlas.</p>
        </div>
    </div>

</div>

@endsection
