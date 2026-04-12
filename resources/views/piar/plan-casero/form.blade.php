@extends('layouts.app-sidebar')

@section('header', 'Plan Casero – ' . $apellidos . ', ' . $nombreCompleto)

@section('slot')

@php
    $puedeObservar = in_array(auth()->user()->PROFILE, ['SuperAd', 'Ori']);
    $docentePuede  = !$puedeObservar && $estadoEtapa === 'abierto';
@endphp

@if(session('saved'))
    <div class="mb-4 p-3 bg-green-100 text-green-800 rounded-xl text-sm">✅ {{ session('saved') }}</div>
@endif
@if($errors->has('etapa'))
    <div class="mb-4 p-3 bg-red-100 text-red-800 rounded-xl text-sm">🚫 {{ $errors->first('etapa') }}</div>
@endif

{{-- Banner etapa --}}
@if($estadoEtapa === 'cerrado')
    <div class="mb-3 px-4 py-2 rounded-lg text-sm bg-red-50 border border-red-200 text-red-800 flex items-center gap-2">🔒 <span>Etapa de Plan Casero <strong>cerrada</strong>. No se permiten cambios.</span></div>
@elseif($estadoEtapa === 'revision')
    <div class="mb-3 px-4 py-2 rounded-lg text-sm bg-blue-50 border border-blue-200 text-blue-800 flex items-center gap-2">👁 <span>Etapa <strong>en revisión</strong>{{ $puedeObservar ? '. Puedes revisar.' : '. No puedes editar.' }}</span></div>
@elseif($estadoEtapa === 'finalizado')
    <div class="mb-3 px-4 py-2 rounded-lg text-sm bg-purple-50 border border-purple-200 text-purple-800 flex items-center gap-2">✓ <span>Etapa <strong>finalizada</strong>. Solo lectura.</span></div>
@endif

{{-- Barra de acciones --}}
<div class="flex items-center justify-between mb-4">
    <a href="{{ route('piar.anexo2.index') }}" class="text-blue-700 hover:underline text-sm">← Volver al listado</a>
    @if($docentePuede)
    <button type="submit" form="form-plan-casero"
        class="bg-indigo-700 hover:bg-indigo-600 text-white text-sm font-semibold px-5 py-2 rounded-lg transition">
        💾 Guardar Plan Casero
    </button>
    @endif
</div>

<div class="space-y-5 max-w-5xl mx-auto">

{{-- ── Encabezado del estudiante ── --}}
<div class="bg-white rounded-xl shadow p-5">
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
        <div>
            <span class="block text-xs font-semibold text-gray-400 uppercase mb-0.5">Estudiante</span>
            <span class="text-gray-800 font-medium">{{ $nombreCompleto }} {{ $apellidos }}</span>
        </div>
        <div>
            <span class="block text-xs font-semibold text-gray-400 uppercase mb-0.5">Grado / Curso</span>
            <span class="text-gray-800">{{ $estudiante->GRADO }} – {{ $estudiante->CURSO }}</span>
        </div>
        <div>
            <span class="block text-xs font-semibold text-gray-400 uppercase mb-0.5">Asignatura</span>
            <span class="text-blue-900 font-bold">{{ $materia->NOMBRE_MAT ?? '—' }}</span>
        </div>
        <div>
            <span class="block text-xs font-semibold text-gray-400 uppercase mb-0.5">Diagnóstico</span>
            <span class="text-gray-600 text-xs">{{ $piarDiag->DIAGNOSTICO ?? '—' }}</span>
        </div>
    </div>
</div>

{{-- ── Caracterización (solo lectura) ── --}}
<div class="bg-gray-50 border border-gray-200 rounded-xl p-5">
    <h2 class="text-sm font-bold text-gray-600 uppercase tracking-wide mb-3 flex items-center gap-2">
        <span class="bg-gray-200 text-gray-600 text-xs px-2 py-0.5 rounded">Solo lectura</span>
        Caracterización del estudiante
    </h2>
    @if(!empty($caract->CARACTERIZACION))
        <div class="bg-white border border-gray-200 rounded-lg p-4 text-sm text-gray-700 whitespace-pre-wrap leading-relaxed">{{ $caract->CARACTERIZACION }}</div>
    @else
        <p class="text-sm text-gray-400 italic">Sin caracterización registrada aún.</p>
    @endif
</div>

{{-- ── Ajustes razonables por período (solo lectura) ── --}}
<div class="bg-gray-50 border border-gray-200 rounded-xl overflow-hidden">
    <div class="px-5 py-3 border-b border-gray-200 flex items-center gap-2">
        <span class="bg-gray-200 text-gray-600 text-xs px-2 py-0.5 rounded font-semibold">Solo lectura</span>
        <h2 class="text-sm font-bold text-gray-600 uppercase tracking-wide">Ajustes razonables por período</h2>
    </div>
    @php
        $periodos = [
            ['num' => 1, 'label' => 'Primer período'],
            ['num' => 2, 'label' => 'Segundo período'],
            ['num' => 3, 'label' => 'Tercer período'],
            ['num' => 4, 'label' => 'Cuarto período'],
        ];
        $tieneAjustes = $piarMat && (
            !empty($piarMat->LOGRO1) || !empty($piarMat->LOGRO2) ||
            !empty($piarMat->LOGRO3) || !empty($piarMat->LOGRO4)
        );
    @endphp
    @if(!$tieneAjustes)
        <p class="px-5 py-4 text-sm text-gray-400 italic">Sin ajustes razonables registrados aún.</p>
    @else
    <div class="overflow-x-auto">
        <table class="w-full text-sm border-collapse">
            <thead>
                <tr class="bg-blue-50">
                    <th class="border border-gray-200 px-3 py-2 w-28 text-center text-xs font-bold text-green-800 bg-green-50">Período</th>
                    <th class="border border-gray-200 px-3 py-2 text-left text-xs font-bold text-blue-700">Propósitos / Logros</th>
                    <th class="border border-gray-200 px-3 py-2 text-left text-xs font-bold text-blue-700">Metodología y Didáctica</th>
                    <th class="border border-gray-200 px-3 py-2 text-left text-xs font-bold text-blue-700">Evaluación y Seguimiento</th>
                </tr>
            </thead>
            <tbody>
                @foreach($periodos as $p)
                @php
                    $logro  = $piarMat->{'LOGRO'  . $p['num']} ?? '';
                    $didact = $piarMat->{'DIDACT' . $p['num']} ?? '';
                    $eval   = $piarMat->{'EVAL'   . $p['num']} ?? '';
                    $vacio  = empty($logro) && empty($didact) && empty($eval);
                @endphp
                @if(!$vacio)
                <tr>
                    <td class="border border-gray-200 px-2 py-2 text-center font-bold text-xs text-green-800 bg-green-50">{{ $p['label'] }}</td>
                    <td class="border border-gray-200 px-3 py-2 text-xs text-gray-700 align-top whitespace-pre-wrap">{{ $logro ?: '—' }}</td>
                    <td class="border border-gray-200 px-3 py-2 text-xs text-gray-700 align-top whitespace-pre-wrap">{{ $didact ?: '—' }}</td>
                    <td class="border border-gray-200 px-3 py-2 text-xs text-gray-700 align-top whitespace-pre-wrap">{{ $eval ?: '—' }}</td>
                </tr>
                @endif
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>

{{-- ── Plan Casero (editable) ── --}}
<form id="form-plan-casero" method="POST"
      action="{{ route('piar.plan_casero.guardar', [$estudiante->CODIGO, $codigoMat]) }}">
@csrf
<div class="bg-white rounded-xl shadow p-5">
    <h2 class="text-sm font-bold text-indigo-900 uppercase tracking-wide border-b border-indigo-100 pb-2 mb-4">
        🏠 Plan Casero
    </h2>
    <p class="text-xs text-gray-400 mb-4">
        Estrategias y actividades que el estudiante debe trabajar en casa para fortalecer su proceso de aprendizaje.
    </p>

    <div class="space-y-4">
        <div>
            <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">
                Estrategias / Actividades para el hogar
            </label>
            <textarea name="ESTRAG_CASERA" rows="6"
                placeholder="Describe las estrategias y actividades que el estudiante debe realizar en casa..."
                {{ (!$docentePuede && !$puedeObservar) || $estadoEtapa === 'finalizado' ? 'readonly' : '' }}
                class="w-full border rounded-lg p-3 text-sm focus:outline-none resize-none transition
                    {{ (!$docentePuede && !$puedeObservar) || $estadoEtapa === 'finalizado'
                        ? 'bg-gray-50 border-gray-200 text-gray-600 cursor-default'
                        : 'border-gray-300 text-gray-800 focus:border-indigo-400' }}">{{ $piarMat->ESTRAG_CASERA ?? '' }}</textarea>
        </div>

        <div>
            <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">
                Frecuencia
            </label>
            <input type="text" name="FREC_CASERA"
                value="{{ $piarMat->FREC_CASERA ?? '' }}"
                placeholder="Ej: Diaria, 3 veces por semana, Semanal..."
                {{ (!$docentePuede && !$puedeObservar) || $estadoEtapa === 'finalizado' ? 'readonly' : '' }}
                class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none transition
                    {{ (!$docentePuede && !$puedeObservar) || $estadoEtapa === 'finalizado'
                        ? 'bg-gray-50 border-gray-200 text-gray-600 cursor-default'
                        : 'border-gray-300 text-gray-800 focus:border-indigo-400' }}">
        </div>
    </div>

    @if($docentePuede)
    <div class="flex justify-end mt-5">
        <button type="submit"
            class="bg-indigo-700 hover:bg-indigo-600 text-white text-sm font-semibold px-6 py-2.5 rounded-lg transition">
            💾 Guardar Plan Casero
        </button>
    </div>
    @elseif($puedeObservar && $estadoEtapa !== 'finalizado')
    <div class="flex justify-end mt-5">
        <button type="submit"
            class="bg-amber-600 hover:bg-amber-500 text-white text-sm font-semibold px-6 py-2.5 rounded-lg transition">
            💾 Guardar cambios
        </button>
    </div>
    @endif
</div>
</form>

</div>
@endsection
