@extends('layouts.padres')
@section('header', 'Consultar Promedios')
@section('slot')

@php
    $periodos = $notas->groupBy('PERIODO');
    $materias = $notas->groupBy('CODIGO_MAT');
@endphp

@if($notas->isEmpty())
    <div class="bg-white rounded-xl shadow p-8 text-center">
        <p class="text-4xl mb-3">📋</p>
        <p class="font-semibold text-gray-700">Sin notas registradas para {{ $anio }}</p>
    </div>
@else
<div class="overflow-x-auto bg-white rounded-xl shadow">
    <div class="px-5 py-3 bg-blue-800 text-white">
        <h3 class="font-bold text-sm uppercase tracking-wide">Notas {{ $anio }}</h3>
        <p class="text-blue-300 text-xs mt-0.5">
            {{ $estudiante->NOMBRE1 }} {{ $estudiante->APELLIDO1 }}
        </p>
    </div>
    <table class="w-full text-sm">
        <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
            <tr>
                <th class="px-4 py-3 text-left">Materia</th>
                @foreach($periodosVisibles as $p)
                    <th class="px-4 py-3 text-center w-20">P{{ $p }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @foreach($materias as $codMat => $registros)
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-2 font-medium">{{ $registros->first()->NOMBRE_MAT }}</td>
                @foreach($periodosVisibles as $p)
                    @php $nota = $registros->firstWhere('PERIODO', $p); @endphp
                    <td class="px-4 py-2 text-center">
                        @if($nota)
                            <span class="{{ $nota->NOTA < 7 ? 'text-red-600 font-bold' : 'text-gray-700' }}">
                                {{ number_format($nota->NOTA, 1) }}
                            </span>
                            @if($nota->TIPODENOTA === 'R')
                                <span class="text-xs text-blue-500 ml-0.5">R</span>
                            @endif
                        @else
                            <span class="text-gray-300">—</span>
                        @endif
                    </td>
                @endforeach
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

@endsection
