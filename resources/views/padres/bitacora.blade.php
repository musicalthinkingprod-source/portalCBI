@extends('layouts.padres')

@section('header', 'Bitácora del Estudiante')

@section('slot')

@php
    $nombreCompleto = preg_replace('/\s+/', ' ', trim(
        ($estudiante->NOMBRE1 ?? '') . ' ' . ($estudiante->NOMBRE2 ?? '') . ' ' .
        ($estudiante->APELLIDO1 ?? '') . ' ' . ($estudiante->APELLIDO2 ?? '')
    ));
    $badge = fn($color) => match ($color) {
        'blue'   => 'bg-blue-100 text-blue-800',
        'indigo' => 'bg-indigo-100 text-indigo-800',
        'red'    => 'bg-red-100 text-red-800',
        'green'  => 'bg-green-100 text-green-800',
        'amber'  => 'bg-amber-100 text-amber-800',
        default  => 'bg-gray-100 text-gray-700',
    };
@endphp

<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-800">📖 Bitácora del estudiante</h2>
    <p class="text-gray-500 text-sm mt-0.5">
        Observaciones registradas por la institución sobre
        <strong class="text-gray-700">{{ $nombreCompleto }}</strong>
        (código <span class="font-mono">{{ $estudiante->CODIGO }}</span>).
    </p>
</div>

@if($entradas->isEmpty())
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 text-center">
    <p class="text-4xl mb-3">📭</p>
    <p class="text-gray-600 font-medium">Aún no hay observaciones registradas.</p>
    <p class="text-gray-400 text-sm mt-1">Cuando la institución registre una observación sobre tu hijo(a), aparecerá aquí.</p>
</div>
@else
<div class="space-y-3">
    @foreach($entradas as $e)
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
        <div class="flex items-center justify-between flex-wrap gap-2 mb-2">
            <span class="inline-block px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $badge($e->categoria_color) }}">{{ $e->categoria }}</span>
            <span class="text-xs text-gray-400">{{ \Carbon\Carbon::parse($e->fecha)->locale('es')->isoFormat('dddd D [de] MMMM [de] YYYY') }}</span>
        </div>
        @if($e->es_aula && $e->materia)
        <p class="text-xs font-semibold text-gray-500 mb-1">📘 {{ $e->materia }}</p>
        @endif
        <p class="text-sm text-gray-700 whitespace-pre-line leading-relaxed">{{ $e->observacion }}</p>
        @if($e->es_aula && $e->registrado_nombre)
        <p class="text-xs text-gray-400 mt-2 pt-2 border-t border-gray-50">Registrado por: <span class="font-medium text-gray-600">{{ $e->registrado_nombre }}</span></p>
        @endif
    </div>
    @endforeach
</div>
@endif

@endsection
