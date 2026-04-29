@extends('layouts.app-sidebar')

@section('header', 'Plan Casero – ' . $apellidos . ', ' . $nombreCompleto)

@section('slot')

@include('partials.piar_header_estudiante')

@php
    $puedeObservar = in_array(auth()->user()->PROFILE, ['SuperAd', 'Ori', 'Piar']);
    $estadoActual  = $piarMat->ESTADO_CASERO ?? 'pendiente';
    $docentePuede  = !$puedeObservar && ($estadoEtapa === 'abierto' || $estadoActual === 'con_observaciones');
@endphp

@if(session('saved'))
    <div class="mb-4 p-3 bg-green-100 text-green-800 rounded-xl text-sm">✅ {{ session('saved') }}</div>
@endif
@if(session('aprobado'))
    <div class="mb-4 p-3 bg-green-100 text-green-800 rounded-xl text-sm font-semibold">✅ {{ session('aprobado') }}</div>
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

{{-- Badge de estado --}}
<div class="flex items-center gap-3 mb-3">
    <span class="text-xs font-semibold text-gray-500 uppercase">Estado:</span>
    @if($estadoActual === 'aprobado')
        <span class="inline-flex items-center gap-1 bg-green-100 text-green-700 text-xs px-3 py-1 rounded-full font-semibold">✓ Aprobado</span>
        @if($piarMat->APROBADO_CASERO_POR ?? null)
            <span class="text-xs text-gray-400">por {{ $piarMat->APROBADO_CASERO_POR }} el {{ \Carbon\Carbon::parse($piarMat->FECHA_APROB_CASERO)->locale('es')->isoFormat('D MMM YYYY') }}</span>
        @endif
    @elseif($estadoActual === 'revision')
        <span class="inline-flex items-center gap-1 bg-blue-100 text-blue-700 text-xs px-3 py-1 rounded-full font-semibold">👁 En revisión</span>
    @elseif($estadoActual === 'con_observaciones')
        <span class="inline-flex items-center gap-1 bg-orange-100 text-orange-700 text-xs px-3 py-1 rounded-full font-semibold">💬 Observaciones pendientes</span>
    @else
        <span class="inline-flex items-center gap-1 bg-yellow-100 text-yellow-700 text-xs px-3 py-1 rounded-full font-semibold">Pendiente</span>
    @endif
</div>

{{-- Barra de acciones --}}
<div class="flex items-center justify-between mb-4">
    <a href="{{ $puedeObservar ? route('piar.informe') : route('piar.anexo2.index') }}" class="text-blue-700 hover:underline text-sm">← Volver</a>
    <div class="flex gap-3">
        @if($puedeObservar && $estadoEtapa !== 'finalizado')
            <button type="button" id="btn-editar-contenido"
                onclick="activarEdicion()"
                class="bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-semibold px-5 py-2 rounded-lg transition border border-gray-300">
                ✏️ Editar contenido
            </button>
            <div id="btns-guardar-docente" class="hidden gap-3 flex">
                <button type="submit" form="form-plan-casero" name="accion" value="guardar"
                    class="bg-gray-600 hover:bg-gray-500 text-white text-sm font-semibold px-5 py-2 rounded-lg transition">
                    💾 Guardar borrador
                </button>
                <button type="submit" form="form-plan-casero" name="accion" value="entregar"
                    class="bg-green-700 hover:bg-green-600 text-white text-sm font-semibold px-5 py-2 rounded-lg transition">
                    📤 Guardar como entregado
                </button>
            </div>
        @elseif($docentePuede && $estadoActual !== 'aprobado')
            <button type="submit" form="form-plan-casero" name="accion" value="guardar"
                class="bg-gray-600 hover:bg-gray-500 text-white text-sm font-semibold px-5 py-2 rounded-lg transition">
                💾 Guardar borrador
            </button>
            <button type="submit" form="form-plan-casero" name="accion" value="entregar"
                class="bg-green-700 hover:bg-green-600 text-white text-sm font-semibold px-5 py-2 rounded-lg transition">
                📤 Marcar como entregado
            </button>
        @endif
    </div>
</div>

<form id="form-plan-casero" method="POST"
      action="{{ route('piar.plan_casero.guardar', [$estudiante->CODIGO, $codigoMat]) }}">
@csrf

<div class="grid grid-cols-1 lg:grid-cols-3 gap-5 items-start">

    {{-- Columna principal (2/3) --}}
    <div class="lg:col-span-2 space-y-5">

        {{-- Encabezado del estudiante --}}
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

        {{-- Caracterización (solo lectura) --}}
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

        {{-- Ajustes razonables por período (solo lectura) --}}
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

        {{-- Plan Casero (editable) --}}
        @php
            $soloLectura = (!$docentePuede && !$puedeObservar) || $estadoEtapa === 'finalizado' || $estadoActual === 'aprobado';
        @endphp
        <div class="bg-white rounded-xl shadow p-5">
            <h2 class="text-sm font-bold text-indigo-900 uppercase tracking-wide border-b border-indigo-100 pb-2 mb-4">
                🏠 Plan Casero
            </h2>
            <p class="text-xs text-gray-400 mb-4">
                Estrategias y actividades que el estudiante debe trabajar en casa para fortalecer su proceso de aprendizaje.
            </p>

            @php
                $limEstrag = 16777215; // MEDIUMTEXT (~16 MB)
                $limFrec   = 255;      // VARCHAR(255)
            @endphp
            <div class="space-y-4">
                <div>
                    <div class="flex items-baseline justify-between mb-1">
                        <label class="block text-xs font-semibold text-gray-500 uppercase">
                            Estrategias / Actividades para el hogar
                        </label>
                        <span class="text-[11px] text-gray-400">
                            <span data-counter-for="ESTRAG_CASERA">{{ strlen($piarMat->ESTRAG_CASERA ?? '') }}</span>
                            / {{ number_format($limEstrag) }} caracteres
                        </span>
                    </div>
                    <textarea name="ESTRAG_CASERA" rows="6"
                        maxlength="{{ $limEstrag }}"
                        data-limit="{{ $limEstrag }}"
                        placeholder="Describe las estrategias y actividades que el estudiante debe realizar en casa..."
                        {{ $puedeObservar || $soloLectura ? 'readonly' : '' }}
                        class="campo-docente campo-con-limite w-full border rounded-lg p-3 text-sm focus:outline-none resize-none transition
                            {{ $puedeObservar || $soloLectura
                                ? 'bg-gray-50 border-gray-200 text-gray-600 cursor-default'
                                : 'border-gray-300 text-gray-800 focus:border-indigo-400' }}">{{ $piarMat->ESTRAG_CASERA ?? '' }}</textarea>
                </div>

                <div>
                    <div class="flex items-baseline justify-between mb-1">
                        <label class="block text-xs font-semibold text-gray-500 uppercase">
                            Frecuencia
                        </label>
                        <span class="text-[11px] text-gray-400">
                            <span data-counter-for="FREC_CASERA">{{ strlen($piarMat->FREC_CASERA ?? '') }}</span>
                            / {{ $limFrec }} caracteres
                        </span>
                    </div>
                    <input type="text" name="FREC_CASERA"
                        maxlength="{{ $limFrec }}"
                        data-limit="{{ $limFrec }}"
                        value="{{ $piarMat->FREC_CASERA ?? '' }}"
                        placeholder="Ej: Diaria, 3 veces por semana, Semanal..."
                        {{ $puedeObservar || $soloLectura ? 'readonly' : '' }}
                        class="campo-docente campo-con-limite w-full border rounded-lg px-3 py-2 text-sm focus:outline-none transition
                            {{ $puedeObservar || $soloLectura
                                ? 'bg-gray-50 border-gray-200 text-gray-600 cursor-default'
                                : 'border-gray-300 text-gray-800 focus:border-indigo-400' }}">
                </div>
            </div>

            @if($docentePuede && $estadoActual !== 'aprobado')
            <div class="flex justify-end gap-3 mt-5">
                <button type="submit" name="accion" value="guardar"
                    class="bg-gray-600 hover:bg-gray-500 text-white text-sm font-semibold px-6 py-2.5 rounded-lg transition">
                    💾 Guardar borrador
                </button>
                <button type="submit" name="accion" value="entregar"
                    class="bg-green-700 hover:bg-green-600 text-white text-sm font-semibold px-6 py-2.5 rounded-lg transition">
                    📤 Marcar como entregado
                </button>
            </div>
            @endif
        </div>

    </div>

    {{-- Panel de observaciones (1/3) --}}
    <div class="lg:col-span-1">
        <div id="observaciones" class="bg-amber-50 border border-amber-200 rounded-xl shadow p-5 sticky top-4" style="scroll-margin-top:96px;">
            <h3 class="text-sm font-bold text-amber-800 uppercase tracking-wide mb-1">
                📝 Observaciones del orientador
            </h3>
            <p class="text-xs text-amber-600 mb-3">
                @if($puedeObservar)
                    Registra aquí los cambios o ajustes que debe realizar el docente.
                @else
                    Observaciones y cambios solicitados por orientación.
                @endif
            </p>
            @if($puedeObservar)
                @php $limObs = 16777215; @endphp
                <div class="flex items-baseline justify-end mb-1">
                    <span class="text-[11px] text-amber-700/70">
                        <span data-counter-for="OBSERVACIONES_CASERO">{{ strlen($piarMat->OBSERVACIONES_CASERO ?? '') }}</span>
                        / {{ number_format($limObs) }} caracteres
                    </span>
                </div>
                <textarea name="OBSERVACIONES_CASERO" rows="10"
                    maxlength="{{ $limObs }}"
                    data-limit="{{ $limObs }}"
                    placeholder="Escribe aquí las observaciones para el docente..."
                    class="campo-con-limite w-full border border-amber-300 bg-white rounded-lg p-3 text-sm text-gray-800 focus:outline-none focus:border-amber-500 resize-none">{{ $piarMat->OBSERVACIONES_CASERO ?? '' }}</textarea>

                <div class="mt-3 space-y-2">
                    @if($estadoActual === 'revision' || $estadoActual === 'con_observaciones')
                        <form method="POST" action="{{ route('piar.aprobar.plan_casero', [$estudiante->CODIGO, $codigoMat]) }}">
                            @csrf
                            <button type="submit"
                                class="w-full bg-green-700 hover:bg-green-600 text-white text-xs font-bold px-4 py-2 rounded-lg transition">
                                ✓ Aprobar Plan Casero
                            </button>
                        </form>
                    @elseif($estadoActual === 'aprobado')
                        <p class="text-xs text-green-700 font-semibold text-center">
                            ✓ Aprobado por {{ $piarMat->APROBADO_CASERO_POR ?? 'orientación' }}
                            @if($piarMat->FECHA_APROB_CASERO ?? null)
                                · {{ \Carbon\Carbon::parse($piarMat->FECHA_APROB_CASERO)->locale('es')->isoFormat('D MMM YYYY') }}
                            @endif
                        </p>
                    @else
                        <p class="text-xs text-amber-500 text-center italic">Pendiente de entrega por el docente.</p>
                    @endif
                    <button type="submit" form="form-plan-casero" name="accion" value="observar"
                        class="w-full bg-orange-600 hover:bg-orange-500 text-white text-xs font-semibold px-4 py-2 rounded-lg transition">
                        💬 Enviar observaciones al docente
                    </button>
                    <button type="submit" form="form-plan-casero" name="accion" value="guardar"
                        class="w-full bg-amber-600 hover:bg-amber-500 text-white text-xs font-semibold px-4 py-2 rounded-lg transition">
                        💾 Guardar borrador de observaciones
                    </button>
                </div>
            @else
                @if($estadoActual === 'con_observaciones' && !empty($piarMat->OBSERVACIONES_CASERO ?? null))
                    <div class="bg-orange-50 border border-orange-300 rounded-lg p-3 text-sm text-orange-800 leading-relaxed font-medium">
                        ⚠️ El orientador ha dejado observaciones que debes atender antes de volver a entregar:
                        <div class="mt-2 font-normal text-gray-700 whitespace-pre-wrap">{{ $piarMat->OBSERVACIONES_CASERO }}</div>
                    </div>
                @elseif(!empty($piarMat->OBSERVACIONES_CASERO ?? null))
                    <div class="bg-white border border-amber-200 rounded-lg p-3 text-sm text-gray-700 whitespace-pre-wrap leading-relaxed">{{ $piarMat->OBSERVACIONES_CASERO }}</div>
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
        el.classList.add('border-gray-300', 'text-gray-800', 'focus:border-indigo-400');
    });
    document.getElementById('btn-editar-contenido').style.display = 'none';
    document.getElementById('btns-guardar-docente').classList.remove('hidden');
    document.getElementById('btns-guardar-docente').classList.add('flex');
}
</script>
@endif

<script>
document.querySelectorAll('.campo-con-limite').forEach(el => {
    const limite   = parseInt(el.dataset.limit, 10);
    const contador = document.querySelector('[data-counter-for="' + el.name + '"]');
    if (!contador) return;
    const refrescar = () => {
        const n = el.value.length;
        contador.textContent = n.toLocaleString('es-CO');
        const cont = contador.parentElement;
        cont.classList.remove('text-red-600', 'text-amber-600', 'font-semibold');
        if (n >= limite)               cont.classList.add('text-red-600', 'font-semibold');
        else if (n >= limite * 0.9)    cont.classList.add('text-amber-600', 'font-semibold');
    };
    el.addEventListener('input', refrescar);
    refrescar();
});
</script>

@endsection
