@extends('layouts.app-sidebar')

@section('header', 'Control de Planilla Ponderada')

@section('slot')

@php
    $catBadge = [
        'P' => 'bg-blue-100 text-blue-800',
        'C' => 'bg-purple-100 text-purple-800',
        'A' => 'bg-green-100 text-green-800',
    ];
@endphp

{{-- Filtros --}}
<div class="bg-white rounded-xl shadow p-5 mb-6">
    <form method="GET" action="{{ route('control.planilla') }}">
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 items-end">
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Año</label>
                <select name="anio" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @foreach([2026, 2025] as $a)
                        <option value="{{ $a }}" {{ $anio == $a ? 'selected' : '' }}>{{ $a }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Período</label>
                <select name="periodo" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @foreach([1,2,3,4] as $p)
                        <option value="{{ $p }}" {{ $periodo == $p ? 'selected' : '' }}>Período {{ $p }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Curso</label>
                <select name="curso" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">— Todos —</option>
                    @foreach($cursosDisponibles as $c)
                        <option value="{{ $c }}" {{ $curso == $c ? 'selected' : '' }}>{{ $c }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Materia</label>
                <select name="materia" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">— Todas —</option>
                    @foreach($materiasDisponibles as $m)
                        <option value="{{ $m->codigo_mat }}" {{ $materia == $m->codigo_mat ? 'selected' : '' }}>{{ $m->NOMBRE_MAT }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="mt-3 flex justify-end">
            <button type="submit" class="bg-blue-800 hover:bg-blue-700 text-white font-semibold text-sm px-5 py-2 rounded-lg transition">
                Consultar
            </button>
        </div>
    </form>
</div>

@if(empty($porDocente))
    <div class="bg-gray-50 border border-gray-200 text-gray-500 rounded-xl p-6 text-center text-sm">
        No hay notas registradas en la planilla ponderada para los filtros seleccionados.
    </div>
@else

<p class="text-xs text-gray-400 mb-4">
    {{ count($porDocente) }} docente(s) con notas registradas · Período {{ $periodo }} · {{ $anio }}
    @if($curso) · Curso <strong>{{ $curso }}</strong>@endif
    @if($materia) · <strong>{{ $materiasDisponibles->firstWhere('codigo_mat', $materia)?->NOMBRE_MAT }}</strong>@endif
</p>

<div class="space-y-6">
@foreach($porDocente as $codDoc => $data)

@php
    $totalNotas = 0;
    $actividadesUnicas = collect();
    foreach ($data['ciclos'] as $cicloData) {
        foreach ($cicloData['fechas'] as $registros) {
            foreach ($registros as $r) {
                $totalNotas += $r->cantidad;
                $actividadesUnicas->push($r->columna_id);
            }
        }
    }
    $totalActividades = $actividadesUnicas->unique()->count();
    $primerCiclo = collect($data['ciclos'])->first();
    $ultimaFecha = $primerCiclo ? array_key_first($primerCiclo['fechas']) : null;
@endphp

<div class="bg-white rounded-xl shadow overflow-hidden">

    {{-- Cabecera docente --}}
    <div class="px-5 py-3 bg-blue-800 text-white flex items-center justify-between flex-wrap gap-2">
        <div>
            <p class="font-bold text-sm">{{ $data['nombre'] }}</p>
            <p class="text-blue-300 text-xs mt-0.5">
                {{ $codDoc }} · {{ $totalActividades }} actividad(es) · {{ $totalNotas }} nota(s) ingresadas
            </p>
        </div>
        @if($ultimaFecha)
        <span class="text-xs text-blue-200">
            Último ingreso: {{ \Carbon\Carbon::parse($ultimaFecha)->translatedFormat('d \d\e F Y') }}
        </span>
        @endif
    </div>

    {{-- Ciclos --}}
    @foreach($data['ciclos'] as $cicloKey => $cicloData)
    @php
        $totalCiclo = 0;
        foreach ($cicloData['fechas'] as $registros) {
            foreach ($registros as $r) $totalCiclo += $r->cantidad;
        }
        $label = $cicloData['numero'] ? 'Ciclo ' . $cicloData['numero'] : 'Sin ciclo';
    @endphp

    <div class="border-t border-gray-100">
        {{-- Encabezado ciclo --}}
        <div class="px-5 py-2 bg-gray-50 flex items-center justify-between">
            <span class="text-xs font-bold text-gray-600 uppercase tracking-wide">{{ $label }}</span>
            <span class="text-xs text-gray-400">{{ $totalCiclo }} nota(s) en este ciclo</span>
        </div>

        {{-- Fechas dentro del ciclo --}}
        <div class="divide-y divide-gray-50">
            @foreach($cicloData['fechas'] as $fecha => $registros)
            @php $totalDia = collect($registros)->sum('cantidad'); @endphp
            <div class="px-5 py-4">
                <div class="flex items-center gap-3 mb-3">
                    <span class="bg-blue-50 text-blue-700 text-xs font-bold px-3 py-1 rounded-full border border-blue-200">
                        {{ \Carbon\Carbon::parse($fecha)->translatedFormat('l d \d\e F') }}
                    </span>
                    <span class="text-xs text-gray-400">{{ $totalDia }} estudiante(s) calificado(s)</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                    @foreach($registros as $r)
                    <div class="border border-gray-200 rounded-lg px-4 py-3 bg-white">
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold text-gray-800 leading-tight" title="{{ $r->nombre_actividad }}">
                                    {{ $r->nombre_actividad }}
                                </p>
                                <p class="text-xs text-gray-500 mt-0.5">{{ $r->NOMBRE_MAT }} · Curso {{ $r->curso }}</p>
                            </div>
                            <span class="{{ $catBadge[$r->categoria] ?? 'bg-gray-100 text-gray-600' }} text-xs font-bold px-2 py-0.5 rounded whitespace-nowrap">
                                {{ $r->categoria }}
                            </span>
                        </div>
                        <div class="mt-2 flex items-center justify-between text-xs text-gray-400">
                            <span>{{ $r->cantidad }} estudiante(s)</span>
                            <span>{{ \Carbon\Carbon::parse($r->ultima)->format('H:i') }}</span>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endforeach

</div>
@endforeach
</div>

@endif

@endsection
