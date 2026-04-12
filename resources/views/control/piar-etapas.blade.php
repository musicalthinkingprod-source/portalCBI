@extends('layouts.app-sidebar')

@section('header', 'Control de Etapas PIAR – ' . $anio)

@section('slot')

@php
$colores = [
    'cerrado'    => ['bg' => 'bg-gray-100',   'text' => 'text-gray-500',   'border' => 'border-gray-200',   'dot' => 'bg-gray-400'],
    'abierto'    => ['bg' => 'bg-green-50',   'text' => 'text-green-700',  'border' => 'border-green-300',  'dot' => 'bg-green-500'],
    'revision'   => ['bg' => 'bg-blue-50',    'text' => 'text-blue-700',   'border' => 'border-blue-300',   'dot' => 'bg-blue-500'],
    'finalizado' => ['bg' => 'bg-purple-50',  'text' => 'text-purple-700', 'border' => 'border-purple-300', 'dot' => 'bg-purple-500'],
];
$etiquetas = [
    'cerrado'    => 'Cerrado',
    'abierto'    => 'Abierto',
    'revision'   => 'En revisión',
    'finalizado' => 'Finalizado',
];
@endphp

@if(session('saved'))
<div class="mb-4 p-3 bg-green-100 text-green-800 rounded-xl text-sm font-semibold">✅ {{ session('saved') }}</div>
@endif

<div class="max-w-2xl mx-auto space-y-5">

{{-- Encabezado --}}
<div class="bg-blue-900 text-white rounded-xl px-6 py-4">
    <h1 class="text-lg font-bold tracking-wide uppercase">Control de Etapas PIAR – {{ $anio }}</h1>
    <p class="text-blue-200 text-xs mt-1">
        Abre o cierra cada etapa según el momento del proceso. Los docentes solo pueden diligenciar cuando la etapa está <strong class="text-white">Abierta</strong>.
    </p>
</div>

<form method="POST" action="{{ route('control.piar_fechas.guardar') }}">
@csrf

{{-- Período activo --}}
<div class="bg-white rounded-xl shadow px-6 py-4 flex items-center justify-between gap-4">
    <div>
        <p class="text-sm font-bold text-gray-800">Período activo para ajustes</p>
        <p class="text-xs text-gray-400 mt-0.5">Define qué período están diligenciando actualmente los docentes en la pantalla de ajustes razonables.</p>
    </div>
    <select name="periodo_activo"
        class="text-sm border border-gray-300 rounded-lg px-4 py-2 bg-white text-gray-800 font-semibold focus:outline-none focus:ring-2 focus:ring-blue-400 cursor-pointer shrink-0">
        @foreach([1 => 'Período 1', 2 => 'Período 2', 3 => 'Período 3', 4 => 'Período 4'] as $num => $label)
        <option value="{{ $num }}" {{ $periodoActivo === $num ? 'selected' : '' }}>{{ $label }}</option>
        @endforeach
    </select>
</div>

<div class="bg-white rounded-xl shadow divide-y divide-gray-100">
    @foreach($etapas as $key => $info)
    @php
        $estadoActual = $grid[$key] ?? 'cerrado';
        $c = $colores[$estadoActual];
    @endphp
    <div class="flex items-center justify-between px-6 py-4 gap-6">

        {{-- Info de la etapa --}}
        <div class="flex-1 min-w-0">
            <p class="text-sm font-bold text-gray-800">{{ $info['label'] }}</p>
            <p class="text-xs text-gray-400 mt-0.5 leading-snug">{{ $info['desc'] }}</p>
            <span class="inline-block mt-1.5 text-xs bg-gray-100 text-gray-500 px-2 py-0.5 rounded-full font-semibold">
                {{ $info['rol'] }}
            </span>
        </div>

        {{-- Estado actual + selector --}}
        <div class="flex flex-col items-end gap-2 shrink-0">
            <span id="badge-{{ $key }}" class="inline-flex items-center gap-1.5 {{ $c['bg'] }} {{ $c['text'] }} text-xs font-bold px-3 py-1 rounded-full border {{ $c['border'] }}">
                <span class="w-1.5 h-1.5 rounded-full {{ $c['dot'] }}"></span>
                {{ $etiquetas[$estadoActual] }}
            </span>
            <select name="{{ $key }}" id="sel-{{ $key }}"
                class="text-xs border border-gray-200 rounded-lg px-3 py-1.5 bg-white text-gray-700 focus:outline-none focus:ring-1 focus:ring-blue-400 cursor-pointer">
                @foreach($etiquetas as $val => $lbl)
                <option value="{{ $val }}" {{ $estadoActual === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                @endforeach
            </select>
        </div>

    </div>
    @endforeach
</div>

{{-- Botón guardar --}}
<div class="flex justify-end pb-4">
    <button type="submit"
        class="bg-blue-900 hover:bg-blue-800 text-white font-bold text-sm px-8 py-3 rounded-xl transition shadow">
        💾 Guardar
    </button>
</div>

</form>

{{-- Referencia rápida --}}
<div class="bg-blue-50 border border-blue-200 rounded-xl p-5 text-sm text-blue-800 space-y-2">
    <p class="font-bold text-blue-900 text-base">¿Cómo funciona?</p>
    <ul class="space-y-1.5 text-xs text-blue-700 list-disc list-inside">
        <li><strong>Cerrado</strong>: la etapa no está habilitada. Los docentes ven los formularios pero no pueden guardar.</li>
        <li><strong>Abierto</strong>: los docentes pueden diligenciar y marcar como entregado.</li>
        <li><strong>En revisión</strong>: los docentes ya no pueden editar. Orientadores revisan, agregan observaciones y aprueban.</li>
        <li><strong>Finalizado</strong>: etapa cerrada definitivamente. Todo queda en solo lectura.</li>
    </ul>
    <p class="text-xs text-blue-600 mt-2 pt-2 border-t border-blue-200">
        Flujo normal: <strong>Cerrado → Abierto → En revisión → Finalizado</strong>
    </p>
</div>

</div>

<script>
const clases = {
    cerrado:    'inline-flex items-center gap-1.5 bg-gray-100 text-gray-500 text-xs font-bold px-3 py-1 rounded-full border border-gray-200',
    abierto:    'inline-flex items-center gap-1.5 bg-green-50 text-green-700 text-xs font-bold px-3 py-1 rounded-full border border-green-300',
    revision:   'inline-flex items-center gap-1.5 bg-blue-50 text-blue-700 text-xs font-bold px-3 py-1 rounded-full border border-blue-300',
    finalizado: 'inline-flex items-center gap-1.5 bg-purple-50 text-purple-700 text-xs font-bold px-3 py-1 rounded-full border border-purple-300',
};
const dots = {
    cerrado: 'bg-gray-400', abierto: 'bg-green-500', revision: 'bg-blue-500', finalizado: 'bg-purple-500',
};
const labels = {
    cerrado: 'Cerrado', abierto: 'Abierto', revision: 'En revisión', finalizado: 'Finalizado',
};

document.querySelectorAll('select[name]').forEach(sel => {
    sel.addEventListener('change', function () {
        const key   = this.id.replace('sel-', '');
        const badge = document.getElementById('badge-' + key);
        const val   = this.value;
        badge.className = clases[val];
        badge.innerHTML = `<span class="w-1.5 h-1.5 rounded-full ${dots[val]}"></span>${labels[val]}`;
    });
});
</script>

@endsection
