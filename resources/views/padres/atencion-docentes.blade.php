@extends('layouts.padres')

@section('header', 'Atención a Padres')

@section('slot')

@php
    $diasNombre = [1=>'Día 1',2=>'Día 2',3=>'Día 3',4=>'Día 4',5=>'Día 5',6=>'Día 6'];
    $diaCicloHoy = \App\Models\Horario::diaCicloHoy();
@endphp

<div class="mb-5 p-4 bg-blue-50 border border-blue-200 rounded-xl text-sm text-blue-800">
    <p class="font-semibold mb-0.5">Horarios de atención a padres</p>
    <p class="text-blue-600">Los docentes marcados con <span class="font-semibold text-blue-800">★ Tu docente</span> son quienes le dictan clases a <strong>{{ $estudiante->NOMBRE1 }} {{ $estudiante->APELLIDO1 }}</strong>. Los horarios corresponden a los días del ciclo académico.</p>
</div>

@if($docentes->isEmpty())
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-10 text-center text-gray-400">
        <p class="text-3xl mb-2">📭</p>
        <p>Aún no hay horarios de atención registrados.</p>
    </div>
@else

{{-- Docentes propios primero, luego el resto --}}
@php
    $propios = $docentes->where('es_propio', true)->values();
    $otros   = $docentes->where('es_propio', false)->values();
@endphp

@if($propios->isNotEmpty())
<p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-3">Docentes de {{ $estudiante->NOMBRE1 }}</p>
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
    @foreach($propios as $doc)
        @include('padres._card-docente', ['doc' => $doc])
    @endforeach
</div>
@endif

@if($otros->isNotEmpty())
<p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-3">Otros docentes</p>
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
    @foreach($otros as $doc)
        @include('padres._card-docente', ['doc' => $doc])
    @endforeach
</div>
@endif

@endif

{{-- ── Dirección y Administración ───────────────────────────────────── --}}
@if(!empty($directivos))
<div class="mt-8">
    <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-3">Dirección y Administración</p>
    @php
        $secciones = collect($directivos)->groupBy('seccion');
    @endphp
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach($secciones as $seccion => $personas)
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 flex flex-col gap-3">

            {{-- Etiqueta de sección --}}
            <div class="flex items-center gap-2">
                <span class="text-xs font-bold uppercase tracking-widest px-2 py-0.5 rounded-full bg-gray-100 text-gray-500">
                    {{ $seccion }}
                </span>
            </div>

            @foreach($personas as $persona)
            <div class="{{ !$loop->first ? 'pt-3 border-t border-gray-100' : '' }}">

                {{-- Nombre y cargo --}}
                <p class="font-bold text-gray-800 text-sm leading-tight">{{ $persona['nombre'] }}</p>
                <p class="text-xs text-gray-500 mt-0.5">{{ $persona['cargo'] }}</p>

                {{-- Horario --}}
                <div class="mt-2 rounded-xl bg-amber-50 border border-amber-100 px-3 py-2">
                    @foreach(explode("\n", $persona['horario']) as $linea)
                        <p class="text-xs text-amber-800 leading-snug">{{ trim($linea) }}</p>
                    @endforeach
                </div>

                {{-- Correo --}}
                @if($persona['correo'])
                <a href="mailto:{{ $persona['correo'] }}"
                   class="mt-2 flex items-center gap-1.5 text-xs text-blue-600 hover:text-blue-800 transition">
                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    <span class="truncate">{{ $persona['correo'] }}</span>
                </a>
                @endif

            </div>
            @endforeach

        </div>
        @endforeach
    </div>
</div>
@endif

@endsection
