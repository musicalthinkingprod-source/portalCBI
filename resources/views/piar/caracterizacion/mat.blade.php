@extends('layouts.app-sidebar')

@section('header', 'Caracterización – ' . $apellidos . ', ' . $nombreCompleto)

@section('slot')

@if(session('saved'))
    <div class="mb-4 p-3 bg-green-100 text-green-800 rounded-xl text-sm">✅ {{ session('saved') }}</div>
@endif

<div class="flex items-center justify-between mb-4">
    <a href="{{ route('piar.anexo2.index') }}" class="text-blue-700 hover:underline text-sm">← Volver</a>
    <button type="submit" form="form-caract-mat"
        class="bg-green-700 hover:bg-green-600 text-white text-sm font-semibold px-5 py-2 rounded-lg transition">
        💾 Guardar caracterización
    </button>
</div>

<form id="form-caract-mat" method="POST"
      action="{{ route('piar.caract.mat.guardar', [$estudiante->CODIGO, $codigoMat]) }}">
@csrf

<div class="max-w-4xl mx-auto space-y-5">

    {{-- Datos del estudiante y materia --}}
    <div class="bg-white rounded-xl shadow p-5">
        <h2 class="text-sm font-bold text-blue-900 uppercase tracking-wide border-b border-blue-100 pb-2 mb-4">
            Caracterización por materia
        </h2>
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 text-sm">
            <div>
                <span class="block text-xs font-semibold text-gray-400 uppercase mb-0.5">Estudiante</span>
                <span class="text-gray-800 font-medium">{{ $nombreCompleto }} {{ $apellidos }}</span>
            </div>
            <div>
                <span class="block text-xs font-semibold text-gray-400 uppercase mb-0.5">Grado / Curso</span>
                <span class="text-gray-800">{{ $estudiante->GRADO }} – {{ $estudiante->CURSO }}</span>
            </div>
            <div>
                <span class="block text-xs font-semibold text-gray-400 uppercase mb-0.5">Diagnóstico PIAR</span>
                <span class="text-gray-600 text-xs">{{ $piarDiag->DIAGNOSTICO ?? '—' }}</span>
            </div>
            <div>
                <span class="block text-xs font-semibold text-gray-400 uppercase mb-0.5">Asignatura</span>
                <span class="text-blue-900 font-bold">{{ $materia->NOMBRE_MAT ?? '—' }}</span>
            </div>
            <div>
                <span class="block text-xs font-semibold text-gray-400 uppercase mb-0.5">Docente</span>
                <span class="text-gray-700">{{ $docente->NOMBRE_DOC ?? '—' }}</span>
            </div>
        </div>
    </div>

    {{-- Caracterización --}}
    <div class="bg-white rounded-xl shadow p-5">
        <label class="block text-sm font-bold text-blue-900 mb-1">
            Caracterización del estudiante en {{ $materia->NOMBRE_MAT ?? 'esta materia' }}
        </label>
        <p class="text-xs text-gray-400 mb-3">
            Descripción general del estudiante con énfasis en su estilo de aprendizaje, gustos, intereses, fortalezas y
            aspectos a mejorar en relación con esta asignatura. Incluye descripción de los dispositivos básicos de aprendizaje:
            atención, memoria, concentración, motivación, comunicación y gestión emocional.
        </p>
        <textarea name="CARACTERIZACION" rows="12"
            placeholder="Escribe aquí la caracterización del estudiante en esta materia..."
            class="w-full border border-gray-300 rounded-lg p-3 text-sm text-gray-800 focus:outline-none focus:border-blue-400 resize-none">{{ $caract->CARACTERIZACION ?? '' }}</textarea>
    </div>

    <div class="flex justify-end pb-6">
        <button type="submit"
            class="bg-green-700 hover:bg-green-600 text-white text-sm font-semibold px-6 py-2.5 rounded-lg transition">
            💾 Guardar caracterización
        </button>
    </div>

</div>
</form>

@endsection
