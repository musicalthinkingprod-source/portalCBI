@extends('layouts.app-sidebar')

@section('header', 'PIAR Anexo 2 – ' . $apellidos . ', ' . $nombreCompleto)

@section('slot')

@if(session('saved'))
    <div class="mb-4 p-3 bg-green-100 text-green-800 rounded-xl text-sm">
        ✅ {{ session('saved') }}
    </div>
@endif

{{-- Barra de acciones --}}
<div class="flex items-center justify-between mb-4">
    <a href="{{ route('piar.anexo2.index') }}" class="text-blue-700 hover:underline text-sm">← Volver a la lista</a>
    <div class="flex gap-3">
        <button type="submit" form="form-anexo2"
            class="bg-green-700 hover:bg-green-600 text-white text-sm font-semibold px-5 py-2 rounded-lg transition">
            💾 Guardar
        </button>
        @if(in_array(auth()->user()->PROFILE, ['SuperAd', 'Ori']))
        <a href="{{ route('piar.anexo2.imprimir', [$estudiante->CODIGO, $codigoMat]) }}" target="_blank"
            class="bg-blue-800 hover:bg-blue-700 text-white text-sm font-semibold px-5 py-2 rounded-lg transition inline-block">
            🖨️ Imprimir Anexo 2
        </a>
        @endif
    </div>
</div>

<form id="form-anexo2" method="POST"
      action="{{ route('piar.anexo2.guardar', [$estudiante->CODIGO, $codigoMat]) }}">
@csrf

<div class="space-y-6 max-w-5xl mx-auto">

{{-- ── Encabezado ── --}}
<div class="bg-white rounded-xl shadow p-6">
    <div class="text-center mb-4">
        <h1 class="text-xl font-bold text-blue-900 uppercase tracking-wide">Plan Individual de Ajustes Razonables – PIAR – Anexo 2</h1>
    </div>
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
        <div>
            <span class="block text-xs font-semibold text-gray-400 uppercase">Fecha de elaboración</span>
            <span class="text-gray-800">{{ now()->translatedFormat('F Y') }}</span>
        </div>
        <div>
            <span class="block text-xs font-semibold text-gray-400 uppercase">Institución</span>
            <span class="text-gray-800">Colegio Bilingüe Integral</span>
        </div>
        <div>
            <span class="block text-xs font-semibold text-gray-400 uppercase">Sede</span>
            <span class="text-gray-800">{{ $estudiante->SEDE ?? '—' }}</span>
        </div>
        <div>
            <span class="block text-xs font-semibold text-gray-400 uppercase">Jornada</span>
            <span class="text-gray-800">Única</span>
        </div>
    </div>
</div>

{{-- ── Datos del estudiante ── --}}
<div class="bg-white rounded-xl shadow p-6">
    <h2 class="text-sm font-bold text-blue-900 uppercase tracking-wide border-b border-blue-100 pb-2 mb-4">Datos del Estudiante</h2>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
        <div>
            <span class="block text-xs font-semibold text-gray-400 uppercase mb-0.5">Nombre</span>
            <span class="text-gray-800 font-medium">{{ $nombreCompleto }} {{ $apellidos }}</span>
        </div>
        <div>
            <span class="block text-xs font-semibold text-gray-400 uppercase mb-0.5">Documento de identificación</span>
            <span class="text-gray-800">{{ $numId }}</span>
        </div>
        <div>
            <span class="block text-xs font-semibold text-gray-400 uppercase mb-0.5">Edad</span>
            <span class="text-gray-800">{{ $edad }} años</span>
        </div>
        <div>
            <span class="block text-xs font-semibold text-gray-400 uppercase mb-0.5">Fecha de nacimiento</span>
            <span class="text-gray-800">{{ $fechaNac }}</span>
        </div>
        <div>
            <span class="block text-xs font-semibold text-gray-400 uppercase mb-0.5">Grado</span>
            <span class="text-gray-800">{{ $grado }}{{ $estudiante->CURSO ? ' – ' . $estudiante->CURSO : '' }}</span>
        </div>
        <div>
            <span class="block text-xs font-semibold text-gray-400 uppercase mb-0.5">Diagnóstico PIAR</span>
            <span class="text-gray-800">{{ $piarDiag->DIAGNOSTICO ?? '—' }}</span>
        </div>
    </div>
</div>

{{-- ── Asignatura y docente ── --}}
<div class="bg-white rounded-xl shadow p-6">
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
        <div>
            <span class="block text-xs font-semibold text-gray-400 uppercase mb-0.5">Asignatura</span>
            <span class="text-blue-900 font-bold text-base">{{ $materia->NOMBRE_MAT ?? '—' }}</span>
        </div>
        <div>
            <span class="block text-xs font-semibold text-gray-400 uppercase mb-0.5">Nombre del docente</span>
            <span class="text-gray-800">{{ $docente->NOMBRE_DOC ?? auth()->user()->name ?? '—' }}</span>
        </div>
    </div>
</div>

{{-- ── Barreras ── --}}
<div class="bg-white rounded-xl shadow p-6">
    <label class="block text-sm font-bold text-blue-900 uppercase tracking-wide mb-2">
        Barreras para acceder al aprendizaje
    </label>
    <textarea name="BARRERAS" rows="4"
        placeholder="Describe las barreras de aprendizaje identificadas para esta asignatura..."
        class="w-full border border-gray-300 rounded-lg p-3 text-sm text-gray-800 focus:outline-none focus:border-blue-400 resize-none">{{ $v('BARRERAS') }}</textarea>
</div>

{{-- ── Ajustes por períodos ── --}}
<div class="bg-white rounded-xl shadow overflow-hidden">
    <div class="bg-yellow-50 border-b border-yellow-200 px-5 py-3">
        <p class="text-center font-bold text-blue-800 text-sm uppercase tracking-wide">Plan Individual de Ajustes Razonables</p>
        <p class="text-center text-blue-700 text-xs">A implementar en cada periodo del año lectivo.</p>
    </div>
    <div class="px-4 py-2 bg-white border-b border-gray-100">
        <p class="text-xs text-gray-400">Este formato contiene ajustes al contexto institucional para lograr compatibilidad con nuestra conceptualización y dar respuesta al decreto 1421.</p>
    </div>

    {{-- Tabla de períodos --}}
    <div class="overflow-x-auto">
        <table class="w-full text-sm border-collapse">
            <thead>
                <tr class="bg-blue-50">
                    <th class="border border-gray-200 px-3 py-2 w-16 text-center text-xs font-bold text-green-800 bg-green-50">PERIODO</th>
                    <th class="border border-gray-200 px-3 py-2 text-left">
                        <p class="text-blue-500 font-bold text-xs">PROPÓSITOS / LOGROS</p>
                        <p class="text-gray-400 text-xs font-normal mt-0.5">Objetivos para cada periodo según los DBA. Ajustes en propósitos y objetivos académicos.</p>
                    </th>
                    <th class="border border-gray-200 px-3 py-2 text-left">
                        <p class="text-blue-500 font-bold text-xs">AJUSTES RAZONABLES / METODOLOGÍA Y DIDÁCTICA</p>
                        <p class="text-gray-400 text-xs font-normal mt-0.5">Módulos de apoyo, guías diversificadas, material concreto, pictogramas, estrategias DUA.</p>
                    </th>
                    <th class="border border-gray-200 px-3 py-2 text-left">
                        <p class="text-blue-500 font-bold text-xs">EVALUACIÓN DE LOS AJUSTES Y SEGUIMIENTO</p>
                        <p class="text-gray-400 text-xs font-normal mt-0.5">Modificaciones parciales durante el año lectivo. Rúbricas de evaluación.</p>
                    </th>
                </tr>
            </thead>
            <tbody>
                @foreach([
                    ['num' => 1, 'label' => 'PRIMER'],
                    ['num' => 2, 'label' => 'SEGUNDO'],
                    ['num' => 3, 'label' => 'TERCERO'],
                    ['num' => 4, 'label' => 'CUARTO'],
                ] as $p)
                <tr>
                    <td class="border border-gray-200 px-2 py-2 text-center font-bold text-xs text-green-800 bg-green-50">
                        {{ $p['label'] }}
                    </td>
                    <td class="border border-gray-200 px-2 py-1">
                        <textarea name="LOGRO{{ $p['num'] }}" rows="4"
                            placeholder="Propósitos y logros del {{ strtolower($p['label']) }} período..."
                            class="w-full text-xs text-gray-800 focus:outline-none resize-none p-1 bg-transparent">{{ $v('LOGRO' . $p['num']) }}</textarea>
                    </td>
                    <td class="border border-gray-200 px-2 py-1">
                        <textarea name="DIDACT{{ $p['num'] }}" rows="4"
                            placeholder="Metodología y estrategias didácticas..."
                            class="w-full text-xs text-gray-800 focus:outline-none resize-none p-1 bg-transparent">{{ $v('DIDACT' . $p['num']) }}</textarea>
                    </td>
                    <td class="border border-gray-200 px-2 py-1">
                        <textarea name="EVAL{{ $p['num'] }}" rows="4"
                            placeholder="Evaluación y seguimiento..."
                            class="w-full text-xs text-gray-800 focus:outline-none resize-none p-1 bg-transparent">{{ $v('EVAL' . $p['num']) }}</textarea>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>


{{-- Botón inferior --}}
<div class="flex justify-end pb-6">
    <button type="submit"
        class="bg-green-700 hover:bg-green-600 text-white text-sm font-semibold px-6 py-2.5 rounded-lg transition">
        💾 Guardar Anexo 2
    </button>
</div>

</div>
</form>

@endsection
