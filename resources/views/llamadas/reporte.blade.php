@extends('layouts.app-sidebar')

@section('header', 'Reporte de Llamadas por Inasistencia')

@section('slot')

{{-- Filtros --}}
<div class="bg-white rounded-xl shadow p-5 mb-6">
    <form method="GET" action="{{ route('llamadas.reporte') }}" class="flex flex-wrap gap-3 items-end">
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Desde</label>
            <input type="date" name="desde" value="{{ $desde }}"
                class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Hasta</label>
            <input type="date" name="hasta" value="{{ $hasta }}"
                class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Buscar estudiante</label>
            <input type="text" name="busqueda" value="{{ $busqueda }}"
                placeholder="Apellido, nombre o código"
                class="border border-gray-300 rounded-lg px-3 py-2 text-sm w-52">
        </div>
        <button type="submit"
            class="bg-blue-800 hover:bg-blue-700 text-white px-5 py-2 rounded-lg text-sm font-semibold transition">
            Filtrar
        </button>
        @if($busqueda || $codigo)
        <a href="{{ route('llamadas.reporte', ['desde' => $desde, 'hasta' => $hasta]) }}"
            class="text-sm text-gray-500 hover:text-gray-700 px-3 py-2 rounded-lg border border-gray-300 transition">
            Limpiar
        </a>
        @endif
    </form>
</div>

{{-- Resumen --}}
@php
    $porFecha = $registros->groupBy(fn($r) => $r->fecha_inasistencia);
@endphp
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 text-center">
        <p class="text-xs text-blue-400 uppercase tracking-wide mb-0.5">Total llamadas</p>
        <p class="text-2xl font-bold text-blue-800">{{ $registros->count() }}</p>
    </div>
    <div class="bg-purple-50 border border-purple-200 rounded-xl p-4 text-center">
        <p class="text-xs text-purple-400 uppercase tracking-wide mb-0.5">Días con registro</p>
        <p class="text-2xl font-bold text-purple-700">{{ $porFecha->count() }}</p>
    </div>
</div>

@if($registros->isEmpty())
    <div class="bg-white rounded-xl shadow p-8 text-center text-gray-400 text-sm">
        No hay llamadas registradas en el rango seleccionado.
    </div>
@else

{{-- Agrupadas por fecha --}}
@foreach($porFecha as $fecha => $filas)
<div class="bg-white rounded-xl shadow mb-5 overflow-hidden">

    {{-- Encabezado de fecha --}}
    <div class="bg-blue-800 px-5 py-3 flex items-center justify-between">
        <span class="text-white font-bold">
            📅 {{ \Carbon\Carbon::parse($fecha)->translatedFormat('l d \d\e F \d\e Y') }}
        </span>
        <span class="text-blue-200 text-sm">{{ $filas->count() }} llamada{{ $filas->count() !== 1 ? 's' : '' }}</span>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                <tr>
                    <th class="px-4 py-3 text-left">Codigo</th>
                    <th class="px-4 py-3 text-left">Estudiante</th>
                    <th class="px-4 py-3 text-left">Curso</th>
                    <th class="px-4 py-3 text-left">Ruta</th>
                    <th class="px-4 py-3 text-left">Motivo</th>
                    <th class="px-4 py-3 text-left">Quien atendio</th>
                    <th class="px-4 py-3 text-left">Observacion</th>
                    <th class="px-4 py-3 text-left">Registrado por</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($filas->sortBy('nombre_completo') as $r)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-mono text-gray-500">{{ $r->codigo }}</td>
                    <td class="px-4 py-3 font-semibold text-gray-800">{{ $r->nombre_completo }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $r->CURSO ?: '—' }}</td>
                    <td class="px-4 py-3">
                        @if($r->ruta_transporte)
                            <span class="text-blue-600 font-medium">🚌 {{ $r->ruta_transporte }}</span>
                        @else
                            <span class="text-gray-300">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-gray-700">{{ $r->motivo }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $r->quien_atendio ?: '—' }}</td>
                    <td class="px-4 py-3 text-gray-500 text-xs">{{ $r->observacion ?: '—' }}</td>
                    <td class="px-4 py-3 text-gray-400 text-xs">{{ $r->registrado_nombre ?: '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

</div>
@endforeach

@endif

@endsection
