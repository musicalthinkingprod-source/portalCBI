@extends('layouts.app-sidebar')

@section('header', 'Claves de respuestas · Exámenes de Admisión')

@section('slot')

@if(session('success'))
<div class="mb-5 bg-green-50 border border-green-300 text-green-800 px-4 py-3 rounded-lg text-sm font-medium">✅ {{ session('success') }}</div>
@endif
@if(session('error'))
<div class="mb-5 bg-red-50 border border-red-300 text-red-800 px-4 py-3 rounded-lg text-sm font-medium">⚠️ {{ session('error') }}</div>
@endif

<div class="mb-4">
    <a href="{{ route('admision.index') }}" class="text-sm text-blue-700 hover:underline">← Volver a Admisiones</a>
</div>

<div class="bg-white rounded-xl shadow p-5">
    <h2 class="text-base font-semibold text-gray-800 mb-1">Clave de respuestas correctas</h2>
    <p class="text-xs text-gray-500 mb-4">
        Elige un grado para revisar o corregir la respuesta correcta de cada pregunta. Al guardar, se
        <b>recalifican automáticamente</b> las evaluaciones ya registradas de ese grado. Solo aplica a los
        grados de opción múltiple (3º a 11º).
    </p>

    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
        @foreach($grados as $g)
        <a href="{{ route('admision.claves.edit', $g['key']) }}"
           class="border border-gray-200 rounded-xl p-3 hover:border-blue-400 hover:shadow-sm transition block">
            <div class="font-semibold text-gray-800 text-sm mb-0.5">{{ $g['nombre'] }}</div>
            <p class="text-[11px] text-gray-400">{{ $g['total'] }} preguntas</p>
            <span class="text-xs text-blue-700 font-semibold">Editar clave →</span>
        </a>
        @endforeach
    </div>
</div>

@endsection
