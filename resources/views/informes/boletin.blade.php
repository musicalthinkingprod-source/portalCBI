@extends('layouts.app-sidebar')

@section('header', 'Informes Académicos — Boletines')

@section('slot')

<div class="max-w-2xl">

    @if($esDocente)
    {{-- Vista del docente: muestra su curso directo --}}
    <div class="bg-blue-50 border border-blue-200 rounded-xl px-5 py-3 mb-5 text-sm text-blue-800">
        Mostrando estudiantes de tu curso: <strong>{{ $cursoDir }}</strong>
    </div>
    @else
    {{-- Buscador para SuperAd/Admin --}}
    <div class="bg-white rounded-xl shadow p-5 mb-6">
        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Buscar estudiante</p>
        <form method="GET" action="{{ route('informes.boletin') }}" class="flex gap-3">
            <input type="text" name="q" value="{{ $q }}"
                placeholder="Nombre, apellido o código..."
                autofocus
                class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            <button type="submit"
                class="bg-blue-800 hover:bg-blue-700 text-white font-semibold px-5 py-2 rounded-lg text-sm transition">
                Buscar
            </button>
        </form>
    </div>
    @endif

    {{-- Resultados --}}
    @if($estudiantes->isNotEmpty())
    <div class="bg-white rounded-xl shadow overflow-hidden">
        <div class="px-5 py-3 bg-blue-800 text-white flex items-center justify-between">
            <h3 class="font-bold text-sm uppercase tracking-wide">
                {{ $esDocente ? 'Estudiantes — Curso ' . $cursoDir : 'Resultados' }}
            </h3>
            <span class="text-blue-300 text-xs">{{ $estudiantes->count() }} estudiante(s)</span>
        </div>
        <ul class="divide-y divide-gray-100">
            @foreach($estudiantes as $est)
            <li>
                <a href="{{ route('boletines.ver', $est->CODIGO) }}"
                    {{ $puedeImprimir ? 'target="_blank"' : '' }}
                    class="flex items-center justify-between px-5 py-3 hover:bg-blue-50 transition group">
                    <div>
                        <p class="font-semibold text-gray-800 text-sm group-hover:text-blue-800">
                            {{ $est->APELLIDO1 }} {{ $est->APELLIDO2 }}, {{ $est->NOMBRE1 }} {{ $est->NOMBRE2 }}
                        </p>
                        <p class="text-xs text-gray-400 mt-0.5">
                            Código: {{ $est->CODIGO }} &nbsp;·&nbsp; Curso: {{ $est->CURSO ?? '—' }}
                        </p>
                    </div>
                    <span class="text-blue-600 text-xs font-semibold group-hover:underline">Ver boletín →</span>
                </a>
            </li>
            @endforeach
        </ul>
    </div>

    @elseif(!$esDocente && strlen($q) >= 2)
        <div class="bg-white rounded-xl shadow p-6 text-center text-gray-400 text-sm">
            No se encontraron estudiantes matriculados con "{{ $q }}".
        </div>
    @elseif(!$esDocente && strlen($q) > 0 && strlen($q) < 2)
        <p class="text-sm text-gray-400 text-center">Escribe al menos 2 caracteres para buscar.</p>
    @endif

</div>

@endsection
