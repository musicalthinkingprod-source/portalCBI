@extends('layouts.app-sidebar')

@section('header', 'Informe anual de desempeño')

@section('slot')

<div class="max-w-3xl">

    <div class="bg-blue-50 border border-blue-200 rounded-xl px-5 py-3 mb-5 text-sm text-blue-800">
        Genera el <strong>Informe de desempeño académico general</strong> de un estudiante a partir de las notas registradas en el año seleccionado.
    </div>

    {{-- Selector de año --}}
    <div class="flex gap-1 mb-5">
        @foreach($aniosDisponibles as $a)
        @php
            $params = array_filter(['q' => $q ?: null, 'anio' => $a]);
        @endphp
        <a href="{{ route('informe-anual.buscar', $params) }}"
           class="px-5 py-2 rounded-lg text-sm font-semibold transition
                  {{ $anio === $a
                     ? 'bg-blue-800 text-white shadow'
                     : 'bg-white text-gray-600 border border-gray-200 hover:bg-blue-50 hover:text-blue-700' }}">
            Año {{ $a }}
        </a>
        @endforeach
    </div>

    {{-- Buscador --}}
    <div class="bg-white rounded-xl shadow p-5 mb-6">
        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Buscar estudiante</p>
        <form method="GET" action="{{ route('informe-anual.buscar') }}" class="flex gap-3">
            <input type="hidden" name="anio" value="{{ $anio }}">
            <input type="text" name="q" value="{{ $q }}"
                placeholder="Nombre, apellido o código..."
                autofocus
                class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            <button type="submit"
                class="bg-blue-800 hover:bg-blue-700 text-white font-semibold px-5 py-2 rounded-lg text-sm transition">
                Buscar
            </button>
        </form>
        <p class="text-xs text-gray-400 mt-2">Solo aparecen estudiantes que tenían curso registrado en {{ $anio }}.</p>
    </div>

    {{-- Resultados --}}
    @if($estudiantes->isNotEmpty())
    <div class="bg-white rounded-xl shadow overflow-hidden">
        <div class="px-5 py-3 bg-blue-800 text-white flex items-center justify-between">
            <h3 class="font-bold text-sm uppercase tracking-wide">Resultados</h3>
            <span class="text-blue-300 text-xs">{{ $estudiantes->count() }} estudiante(s)</span>
        </div>
        <ul class="divide-y divide-gray-100">
            @foreach($estudiantes as $est)
            <li class="flex items-center justify-between px-5 py-3 hover:bg-gray-50">
                <div>
                    <p class="font-semibold text-gray-800 text-sm">
                        {{ $est->CODIGO }} — {{ $est->APELLIDO1 }} {{ $est->APELLIDO2 }}, {{ $est->NOMBRE1 }} {{ $est->NOMBRE2 }}
                    </p>
                    <p class="text-xs text-gray-400 mt-0.5">Curso {{ $anio }}: <span class="font-semibold text-gray-600">{{ $est->CURSO_ANIO }}</span></p>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <a href="{{ route('informe-anual.ver', ['codigo' => $est->CODIGO, 'anio' => $anio]) }}"
                        target="_blank"
                        class="text-xs font-semibold text-blue-700 hover:text-blue-900 bg-blue-50 hover:bg-blue-100 px-3 py-1.5 rounded-lg transition whitespace-nowrap">
                        📄 Ver informe
                    </a>
                    <a href="{{ route('informe-anual.pdf', ['codigo' => $est->CODIGO, 'anio' => $anio]) }}"
                        target="_blank"
                        class="text-xs font-semibold text-red-700 hover:text-red-900 bg-red-50 hover:bg-red-100 px-3 py-1.5 rounded-lg transition whitespace-nowrap">
                        ⬇️ PDF
                    </a>
                </div>
            </li>
            @endforeach
        </ul>
    </div>
    @elseif(strlen($q) >= 2)
        <div class="bg-white rounded-xl shadow p-6 text-center text-gray-400 text-sm">
            No se encontraron estudiantes con curso registrado en {{ $anio }} que coincidan con "{{ $q }}".
        </div>
    @elseif(strlen($q) > 0 && strlen($q) < 2)
        <p class="text-sm text-gray-400 text-center">Escribe al menos 2 caracteres para buscar.</p>
    @endif

</div>

@endsection
