@extends('layouts.app-sidebar')

@section('header', 'Caracterización dirección de grupo – ' . $apellidos . ', ' . $nombreCompleto)

@section('slot')

@php
    $puedeObservar = auth()->user()->PROFILE === 'SuperAd' || str_starts_with(auth()->user()->PROFILE, 'Ori');
    $estadoActual  = $caract->ESTADO ?? 'pendiente';
    $docentePuede  = !$puedeObservar && ($estadoEtapa === 'abierto' || $estadoActual === 'con_observaciones');
@endphp

@if(session('saved'))
    <div class="mb-4 p-3 bg-green-100 text-green-800 rounded-xl text-sm">✅ {{ session('saved') }}</div>
@endif
@if($errors->has('etapa'))
    <div class="mb-4 p-3 bg-red-100 text-red-800 rounded-xl text-sm">🚫 {{ $errors->first('etapa') }}</div>
@endif

{{-- Banner etapa --}}
@if($estadoEtapa === 'cerrado')
    <div class="mb-3 px-4 py-2 rounded-lg text-sm bg-red-50 border border-red-200 text-red-800 flex items-center gap-2">🔒 <span>Etapa de caracterización <strong>cerrada</strong>. No se permiten cambios.</span></div>
@elseif($estadoEtapa === 'revision')
    <div class="mb-3 px-4 py-2 rounded-lg text-sm bg-blue-50 border border-blue-200 text-blue-800 flex items-center gap-2">👁 <span>Etapa <strong>en revisión</strong> — orientación está revisando las caracterizaciones{{ $puedeObservar ? '. Puedes aprobar.' : '. No puedes editar.' }}</span></div>
@elseif($estadoEtapa === 'finalizado')
    <div class="mb-3 px-4 py-2 rounded-lg text-sm bg-purple-50 border border-purple-200 text-purple-800 flex items-center gap-2">✓ <span>Etapa <strong>finalizada</strong>. Solo lectura.</span></div>
@endif

{{-- Badge de estado --}}
<div class="flex items-center gap-3 mb-3">
    <span class="text-xs font-semibold text-gray-500 uppercase">Estado:</span>
    @if($estadoActual === 'aprobado')
        <span class="inline-flex items-center gap-1 bg-green-100 text-green-700 text-xs px-3 py-1 rounded-full font-semibold">✓ Aprobado</span>
        @if($caract->APROBADO_POR ?? null)
            <span class="text-xs text-gray-400">por {{ $caract->APROBADO_POR }} el {{ \Carbon\Carbon::parse($caract->FECHA_APROBACION)->locale('es')->isoFormat('D MMM YYYY') }}</span>
        @endif
    @elseif($estadoActual === 'revision')
        <span class="inline-flex items-center gap-1 bg-blue-100 text-blue-700 text-xs px-3 py-1 rounded-full font-semibold">👁 En revisión</span>
    @elseif($estadoActual === 'con_observaciones')
        <span class="inline-flex items-center gap-1 bg-orange-100 text-orange-700 text-xs px-3 py-1 rounded-full font-semibold">💬 Observaciones pendientes</span>
    @else
        <span class="inline-flex items-center gap-1 bg-yellow-100 text-yellow-700 text-xs px-3 py-1 rounded-full font-semibold">Pendiente</span>
    @endif
</div>

<div class="flex items-center justify-between mb-4">
    <a href="{{ route('piar.anexo2.index') }}" class="text-blue-700 hover:underline text-sm">← Volver</a>
    <div class="flex gap-3">
        @if($puedeObservar)
            @if($estadoEtapa !== 'finalizado')
            <button type="button" id="btn-editar-contenido"
                onclick="activarEdicion()"
                class="bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-semibold px-5 py-2 rounded-lg transition border border-gray-300">
                ✏️ Editar contenido
            </button>
            <div id="btns-guardar-docente" class="hidden gap-3 flex">
                <button type="submit" form="form-caract-dir" name="accion" value="guardar"
                    class="bg-gray-600 hover:bg-gray-500 text-white text-sm font-semibold px-5 py-2 rounded-lg transition">
                    💾 Guardar borrador
                </button>
                @if($estadoActual !== 'aprobado')
                <button type="submit" form="form-caract-dir" name="accion" value="entregar"
                    class="bg-green-700 hover:bg-green-600 text-white text-sm font-semibold px-5 py-2 rounded-lg transition">
                    📤 Marcar como entregado
                </button>
                @endif
            </div>
            @endif
        @elseif($docentePuede)
            <button type="submit" form="form-caract-dir" name="accion" value="guardar"
                class="bg-gray-600 hover:bg-gray-500 text-white text-sm font-semibold px-5 py-2 rounded-lg transition">
                💾 Guardar borrador
            </button>
            @if($estadoActual !== 'aprobado')
            <button type="submit" form="form-caract-dir" name="accion" value="entregar"
                class="bg-green-700 hover:bg-green-600 text-white text-sm font-semibold px-5 py-2 rounded-lg transition">
                📤 Marcar como entregado
            </button>
            @endif
        @endif
    </div>
</div>

<form id="form-caract-dir" method="POST"
      action="{{ route('piar.caract.dir.guardar', $estudiante->CODIGO) }}">
@csrf

<div class="grid grid-cols-1 lg:grid-cols-3 gap-5 items-start">

    {{-- Columna principal (2/3) --}}
    <div class="lg:col-span-2 space-y-5">

        {{-- Datos del estudiante y director --}}
        <div class="bg-white rounded-xl shadow p-5">
            <h2 class="text-sm font-bold text-blue-900 uppercase tracking-wide border-b border-blue-100 pb-2 mb-4">
                Caracterización por director de grupo
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
                <div class="sm:col-span-2">
                    <span class="block text-xs font-semibold text-gray-400 uppercase mb-0.5">Director de grupo</span>
                    <span class="text-blue-800 font-semibold">{{ $docente->NOMBRE_DOC ?? '—' }}</span>
                    <span class="text-gray-400 text-xs ml-2">Grupo: {{ $estudiante->CURSO }}</span>
                </div>
            </div>
        </div>

        {{-- Caracterización --}}
        <div class="bg-white rounded-xl shadow p-5">
            <label class="block text-sm font-bold text-blue-900 mb-1">
                Caracterización general del estudiante
            </label>
            <p class="text-xs text-gray-400 mb-3">
                Descripción general del estudiante con énfasis en su estilo de aprendizaje, gustos e intereses,
                expectativas del estudiante y la familia. Descripción en términos de lo que hace, puede hacer o
                requiere apoyo para favorecer su proceso educativo. Incluye los dispositivos básicos de aprendizaje:
                atención, memoria, concentración, percepción, comunicación, socialización, motivación, control de
                impulsos y gestión emocional. También la caracterización pedagógica y procesos académicos por áreas.
            </p>
            <textarea name="CARACTERIZACION" rows="14"
                placeholder="Escribe aquí la caracterización general del estudiante desde dirección de grupo..."
                {{ $puedeObservar ? 'readonly' : '' }}
                class="campo-docente w-full border rounded-lg p-3 text-sm focus:outline-none resize-none transition
                    {{ $puedeObservar ? 'bg-gray-50 border-gray-200 text-gray-600 cursor-default' : 'border-gray-300 text-gray-800 focus:border-blue-400' }}">{{ $caract->CARACTERIZACION ?? '' }}</textarea>
        </div>

        @if($docentePuede)
        <div class="flex justify-end gap-3 pb-6">
            <button type="submit" name="accion" value="guardar"
                class="bg-gray-600 hover:bg-gray-500 text-white text-sm font-semibold px-6 py-2.5 rounded-lg transition">
                💾 Guardar borrador
            </button>
            @if($estadoActual !== 'aprobado')
            <button type="submit" name="accion" value="entregar"
                class="bg-green-700 hover:bg-green-600 text-white text-sm font-semibold px-6 py-2.5 rounded-lg transition">
                📤 Marcar como entregado
            </button>
            @endif
        </div>
        @endif

    </div>

    {{-- Panel de observaciones (1/3) --}}
    <div class="lg:col-span-1">
        <div class="bg-amber-50 border border-amber-200 rounded-xl shadow p-5 sticky top-4">
            <h3 class="text-sm font-bold text-amber-800 uppercase tracking-wide mb-1">
                📝 Observaciones del orientador
            </h3>
            <p class="text-xs text-amber-600 mb-3">
                @if($puedeObservar)
                    Registra aquí los cambios o ajustes que debe realizar el director de grupo.
                @else
                    Observaciones y cambios solicitados por orientación.
                @endif
            </p>
            @if($puedeObservar)
                <textarea name="OBSERVACIONES" rows="10"
                    placeholder="Escribe aquí las observaciones para el director de grupo..."
                    class="w-full border border-amber-300 bg-white rounded-lg p-3 text-sm text-gray-800 focus:outline-none focus:border-amber-500 resize-none">{{ $caract->OBSERVACIONES ?? '' }}</textarea>

                {{-- Acciones del orientador --}}
                <div class="mt-3 space-y-2">
                    @if($estadoActual === 'revision' || $estadoActual === 'con_observaciones')
                        <form method="POST" action="{{ route('piar.aprobar.caract.dir', $estudiante->CODIGO) }}">
                            @csrf
                            <button type="submit"
                                class="w-full bg-green-700 hover:bg-green-600 text-white text-xs font-bold px-4 py-2 rounded-lg transition">
                                ✓ Aprobar caracterización
                            </button>
                        </form>
                    @elseif($estadoActual === 'aprobado')
                        <p class="text-xs text-green-700 font-semibold text-center">
                            ✓ Aprobado por {{ $caract->APROBADO_POR ?? 'orientación' }}
                            @if($caract->FECHA_APROBACION ?? null)
                                · {{ \Carbon\Carbon::parse($caract->FECHA_APROBACION)->locale('es')->isoFormat('D MMM YYYY') }}
                            @endif
                        </p>
                    @else
                        <p class="text-xs text-amber-500 text-center italic">Pendiente de entrega por el director de grupo.</p>
                    @endif
                    <button type="submit" form="form-caract-dir" name="accion" value="observar"
                        class="w-full bg-orange-600 hover:bg-orange-500 text-white text-xs font-semibold px-4 py-2 rounded-lg transition">
                        💬 Enviar observaciones al docente
                    </button>
                    <button type="submit" form="form-caract-dir" name="accion" value="guardar"
                        class="w-full bg-amber-600 hover:bg-amber-500 text-white text-xs font-semibold px-4 py-2 rounded-lg transition">
                        💾 Guardar borrador de observaciones
                    </button>
                </div>
            @else
                @if($estadoActual === 'con_observaciones' && !empty($caract->OBSERVACIONES ?? null))
                    <div class="bg-orange-50 border border-orange-300 rounded-lg p-3 text-sm text-orange-800 leading-relaxed font-medium">
                        ⚠️ El orientador ha dejado observaciones que debes atender antes de volver a entregar:
                        <div class="mt-2 font-normal text-gray-700 whitespace-pre-wrap">{{ $caract->OBSERVACIONES }}</div>
                    </div>
                @elseif(!empty($caract->OBSERVACIONES ?? null))
                    <div class="bg-white border border-amber-200 rounded-lg p-3 text-sm text-gray-700 whitespace-pre-wrap leading-relaxed">{{ $caract->OBSERVACIONES }}</div>
                @else
                    <p class="text-xs text-amber-400 italic">Sin observaciones registradas.</p>
                @endif
            @endif
        </div>
    </div>

</div>
</form>

@if($puedeObservar)
<script>
function activarEdicion() {
    document.querySelectorAll('.campo-docente').forEach(el => {
        el.removeAttribute('readonly');
        el.classList.remove('bg-gray-50', 'border-gray-200', 'text-gray-600', 'cursor-default');
        el.classList.add('border-gray-300', 'text-gray-800', 'focus:border-blue-400');
    });
    document.getElementById('btn-editar-contenido').style.display = 'none';
    document.getElementById('btns-guardar-docente').classList.remove('hidden');
    document.getElementById('btns-guardar-docente').classList.add('flex');
}
</script>
@endif

@endsection
