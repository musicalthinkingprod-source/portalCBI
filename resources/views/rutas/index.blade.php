@extends('layouts.app-sidebar')

@section('header', 'Listado de Rutas')

@section('slot')

{{-- Filtro --}}
<div class="bg-white rounded-xl shadow p-5 mb-6">
    <form method="GET" action="{{ route('rutas.index') }}" class="flex flex-wrap gap-3 items-end">
        <div class="flex-1 min-w-48">
            <label class="block text-xs font-medium text-gray-500 mb-1">Filtrar por ruta</label>
            <select name="ruta" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                <option value="">Todas las rutas</option>
                @foreach($rutas as $r)
                    <option value="{{ $r }}" @selected($rutaFiltro === $r)>{{ $r }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit"
            class="bg-blue-800 hover:bg-blue-700 text-white px-5 py-2 rounded-lg text-sm font-semibold transition">
            Filtrar
        </button>
        @if($rutaFiltro)
        <a href="{{ route('rutas.index') }}"
            class="text-sm text-gray-500 hover:text-gray-700 px-3 py-2 rounded-lg border border-gray-300 transition">
            Limpiar
        </a>
        @endif
    </form>
</div>

{{-- Resumen --}}
@php $totalEstudiantes = $listado->flatten()->count(); @endphp
<div class="flex gap-4 mb-6">
    <div class="bg-blue-50 border border-blue-200 rounded-xl px-5 py-3 text-center">
        <p class="text-xs text-blue-400 uppercase tracking-wide mb-0.5">Rutas</p>
        <p class="text-2xl font-bold text-blue-800">{{ $listado->count() }}</p>
    </div>
    <div class="bg-green-50 border border-green-200 rounded-xl px-5 py-3 text-center">
        <p class="text-xs text-green-400 uppercase tracking-wide mb-0.5">Estudiantes</p>
        <p class="text-2xl font-bold text-green-700">{{ $totalEstudiantes }}</p>
    </div>
</div>

@if($listado->isEmpty())
    <div class="bg-white rounded-xl shadow p-8 text-center text-gray-400 text-sm">
        No hay estudiantes con información de transporte registrada.
    </div>
@else

@foreach($listado as $ruta => $estudiantes)
<div class="bg-white rounded-xl shadow mb-5 overflow-hidden">

    {{-- Encabezado de ruta --}}
    <div class="bg-blue-800 px-5 py-3 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <span class="text-white font-bold text-base">🚌 Ruta: {{ $ruta ?: 'Sin ruta asignada' }}</span>
            @php $claseRuta = $estudiantes->first()->clase_ruta; @endphp
            @if($claseRuta)
            <span class="bg-blue-600 text-white text-xs px-2 py-0.5 rounded-full">{{ $claseRuta }}</span>
            @endif
        </div>
        <span class="text-blue-200 text-sm">{{ $estudiantes->count() }} estudiante{{ $estudiantes->count() !== 1 ? 's' : '' }}</span>
    </div>

    {{-- Tabla de estudiantes --}}
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                <tr>
                    <th class="px-4 py-3 text-left">Código</th>
                    <th class="px-4 py-3 text-left">Estudiante</th>
                    <th class="px-4 py-3 text-left">Curso</th>
                    <th class="px-4 py-3 text-left">Barrio</th>
                    <th class="px-4 py-3 text-left">Dirección</th>
                    <th class="px-4 py-3 text-left">Teléfono</th>
                    <th class="px-4 py-3 text-left">Quien recibe</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($estudiantes as $est)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-mono text-gray-500">{{ $est->codigo }}</td>
                    <td class="px-4 py-3 font-semibold text-gray-800">{{ $est->nombre_completo }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $est->CURSO ?: '—' }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $est->barrio ?: '—' }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $est->direccion ?: '—' }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $est->telefono ?: '—' }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $est->quien_recibe ?: '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

</div>
@endforeach

@endif

@endsection
