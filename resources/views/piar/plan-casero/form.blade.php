@extends('layouts.app-sidebar')

@section('header', 'Plan Casero – ' . $apellidos . ', ' . $nombreCompleto)

@section('slot')

@include('partials.piar_header_estudiante')

@php
    $puedeObservar = in_array(auth()->user()->PROFILE, ['SuperAd', 'Ori', 'Piar']);
    $etiquetas     = [1 => 'PRIMER', 2 => 'SEGUNDO', 3 => 'TERCERO', 4 => 'CUARTO'];

    $planActual   = $planesPorPeriodo[$periodoActivo] ?? null;
    $estadoActual = $planActual->ESTADO ?? 'pendiente';

    // El docente edita solo el período activo (con etapa abierta o con observaciones).
    $docentePuede = !$puedeObservar && ($estadoEtapa === 'abierto' || $estadoActual === 'con_observaciones');
    $soloLectura  = (!$docentePuede && !$puedeObservar) || $estadoEtapa === 'finalizado' || $estadoActual === 'aprobado';

    $badge = function ($estado) {
        return match ($estado) {
            'aprobado'          => '<span class="inline-flex items-center gap-1 bg-green-100 text-green-700 text-xs px-2 py-0.5 rounded-full font-semibold">✓ Aprobado</span>',
            'revision'          => '<span class="inline-flex items-center gap-1 bg-blue-100 text-blue-700 text-xs px-2 py-0.5 rounded-full font-semibold">👁 En revisión</span>',
            'con_observaciones' => '<span class="inline-flex items-center gap-1 bg-orange-100 text-orange-700 text-xs px-2 py-0.5 rounded-full font-semibold">💬 Observaciones</span>',
            default             => '<span class="inline-flex items-center gap-1 bg-yellow-100 text-yellow-700 text-xs px-2 py-0.5 rounded-full font-semibold">Pendiente</span>',
        };
    };
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

{{-- Banner etapa (aplica al período activo) --}}
@if($estadoEtapa === 'cerrado')
    <div class="mb-3 px-4 py-2 rounded-lg text-sm bg-red-50 border border-red-200 text-red-800 flex items-center gap-2">🔒 <span>Etapa de Plan Casero <strong>cerrada</strong>. No se permiten cambios.</span></div>
@elseif($estadoEtapa === 'revision')
    <div class="mb-3 px-4 py-2 rounded-lg text-sm bg-blue-50 border border-blue-200 text-blue-800 flex items-center gap-2">👁 <span>Etapa <strong>en revisión</strong>{{ $puedeObservar ? '. Puedes revisar.' : '. No puedes editar.' }}</span></div>
@elseif($estadoEtapa === 'finalizado')
    <div class="mb-3 px-4 py-2 rounded-lg text-sm bg-purple-50 border border-purple-200 text-purple-800 flex items-center gap-2">✓ <span>Etapa <strong>finalizada</strong>. Solo lectura.</span></div>
@endif

{{-- Badge de estado del período activo --}}
<div class="flex items-center gap-3 mb-3">
    <span class="text-xs font-semibold text-gray-500 uppercase">Estado período activo (P{{ $periodoActivo }}):</span>
    {!! $badge($estadoActual) !!}
    @if($estadoActual === 'aprobado' && ($planActual->APROBADO_POR ?? null))
        <span class="text-xs text-gray-400">por {{ $planActual->APROBADO_POR }} el {{ \Carbon\Carbon::parse($planActual->FECHA_APROBACION)->locale('es')->isoFormat('D MMM YYYY') }}</span>
    @endif
</div>

{{-- Barra de acciones --}}
<div class="flex items-center justify-between mb-4">
    <a href="{{ $puedeObservar ? route('piar.informe') : route('piar.anexo2.index') }}" class="text-blue-700 hover:underline text-sm">← Volver</a>
    <div class="flex gap-3">
        @include('piar.partials.menu_imprimir', [
            'rutaBase' => route('piar.plan_casero.imprimir.est', $estudiante->CODIGO),
            'titulo'   => 'Imprimir Anexo 3',
        ])
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
<input type="hidden" name="PERIODO" value="{{ $periodoActivo }}">

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

        {{-- Plan Casero por período (solo el activo editable) --}}
        <div class="bg-white rounded-xl shadow overflow-hidden">
            <div class="bg-indigo-50 border-b border-indigo-100 px-5 py-3">
                <h2 class="text-center text-sm font-bold text-indigo-900 uppercase tracking-wide">🏠 Plan Casero por período</h2>
                <p class="text-center text-indigo-400 text-xs mt-0.5">Estrategias y actividades para el hogar. Solo el período activo (P{{ $periodoActivo }}) es editable.</p>
            </div>
            @php $limFrec = 255; $limEstrag = 16777215; @endphp
            <div class="overflow-x-auto">
                <table class="w-full text-sm border-collapse">
                    <thead>
                        <tr class="bg-blue-50">
                            <th class="border border-gray-200 px-2 py-2 w-20 text-center text-xs font-bold text-green-800 bg-green-50">PERÍODO</th>
                            <th class="border border-gray-200 px-3 py-2 text-left text-xs font-bold text-indigo-700">Estrategias / Actividades para el hogar</th>
                            <th class="border border-gray-200 px-3 py-2 text-left text-xs font-bold text-indigo-700 w-44">Frecuencia</th>
                            <th class="border border-gray-200 px-2 py-2 text-center text-xs font-bold text-gray-500 w-28">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($etiquetas as $num => $label)
                        @php
                            $reg      = $planesPorPeriodo[$num] ?? null;
                            $esActivo = ($num === $periodoActivo);
                            $estPer   = $reg->ESTADO ?? 'pendiente';
                            $valE     = $reg->ESTRAG ?? '';
                            $valF     = $reg->FREC ?? '';
                            $editable = $esActivo && !$soloLectura;       // docente activo
                            $editableOri = $esActivo && $puedeObservar;   // orientador (vía "editar contenido")
                        @endphp
                        <tr class="{{ $esActivo && !$puedeObservar ? 'ring-2 ring-indigo-300 ring-inset' : '' }}">
                            <td class="border border-gray-200 px-2 py-2 text-center font-bold text-xs
                                {{ $esActivo ? 'text-indigo-800 bg-indigo-50' : 'text-green-800 bg-green-50' }}">
                                {{ $label }}
                                @if($esActivo)<span class="block text-indigo-500 font-normal mt-0.5" style="font-size:9px">ACTIVO</span>@endif
                            </td>

                            {{-- Estrategias --}}
                            <td class="border border-gray-200 px-2 py-1 {{ $esActivo ? '' : 'bg-gray-50' }}">
                                @if($editable || $editableOri)
                                    <textarea name="ESTRAG_CASERA" rows="4"
                                        maxlength="{{ $limEstrag }}"
                                        placeholder="Estrategias y actividades para el hogar del {{ strtolower($label) }} período..."
                                        {{ $editableOri ? 'readonly' : '' }}
                                        style="min-height:5rem;overflow:hidden;"
                                        class="campo-docente auto-grow w-full text-xs focus:outline-none p-1 transition
                                            {{ $editableOri ? 'bg-gray-50 text-gray-500 cursor-default' : 'text-gray-800 bg-transparent' }}">{{ $valE }}</textarea>
                                @else
                                    <div class="text-xs text-gray-600 whitespace-pre-wrap p-1 align-top">{{ $valE ?: '—' }}</div>
                                @endif
                            </td>

                            {{-- Frecuencia --}}
                            <td class="border border-gray-200 px-2 py-1 {{ $esActivo ? '' : 'bg-gray-50' }}">
                                @if($editable || $editableOri)
                                    <input type="text" name="FREC_CASERA"
                                        maxlength="{{ $limFrec }}"
                                        value="{{ $valF }}"
                                        placeholder="Ej: Diaria, Semanal..."
                                        {{ $editableOri ? 'readonly' : '' }}
                                        class="campo-docente w-full text-xs focus:outline-none p-1 transition
                                            {{ $editableOri ? 'bg-gray-50 text-gray-500 cursor-default' : 'text-gray-800 bg-transparent' }}">
                                @else
                                    <div class="text-xs text-gray-600 whitespace-pre-wrap p-1 align-top">{{ $valF ?: '—' }}</div>
                                @endif
                            </td>

                            {{-- Estado del período --}}
                            <td class="border border-gray-200 px-2 py-2 text-center {{ $esActivo ? '' : 'bg-gray-50' }}">
                                {!! $badge($estPer) !!}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    {{-- Panel de observaciones (1/3) — período activo --}}
    <div class="lg:col-span-1">
        <div id="observaciones" class="bg-amber-50 border border-amber-200 rounded-xl shadow p-5 sticky top-4" style="scroll-margin-top:96px;">
            <h3 class="text-sm font-bold text-amber-800 uppercase tracking-wide mb-1">
                📝 Observaciones del orientador <span class="text-amber-500 font-normal normal-case">(P{{ $periodoActivo }})</span>
            </h3>
            <p class="text-xs text-amber-600 mb-3">
                @if($puedeObservar)
                    Registra aquí los cambios o ajustes que debe realizar el docente en el período activo.
                @else
                    Observaciones y cambios solicitados por orientación.
                @endif
            </p>
            @if($puedeObservar)
                @php $limObs = 16777215; @endphp
                <textarea name="OBSERVACIONES_CASERO" rows="10"
                    maxlength="{{ $limObs }}"
                    placeholder="Escribe aquí las observaciones para el docente..."
                    class="w-full border border-amber-300 bg-white rounded-lg p-3 text-sm text-gray-800 focus:outline-none focus:border-amber-500 resize-none">{{ $planActual->OBSERVACIONES ?? '' }}</textarea>

                <div class="mt-3 space-y-2">
                    @if($estadoActual === 'revision' || $estadoActual === 'con_observaciones')
                        <form method="POST" action="{{ route('piar.aprobar.plan_casero', [$estudiante->CODIGO, $codigoMat, $periodoActivo]) }}">
                            @csrf
                            <button type="submit"
                                class="w-full bg-green-700 hover:bg-green-600 text-white text-xs font-bold px-4 py-2 rounded-lg transition">
                                ✓ Aprobar Plan Casero (P{{ $periodoActivo }})
                            </button>
                        </form>
                    @elseif($estadoActual === 'aprobado')
                        <p class="text-xs text-green-700 font-semibold text-center">
                            ✓ Aprobado por {{ $planActual->APROBADO_POR ?? 'orientación' }}
                            @if($planActual->FECHA_APROBACION ?? null)
                                · {{ \Carbon\Carbon::parse($planActual->FECHA_APROBACION)->locale('es')->isoFormat('D MMM YYYY') }}
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
                @if($estadoActual === 'con_observaciones' && !empty($planActual->OBSERVACIONES ?? null))
                    <div class="bg-orange-50 border border-orange-300 rounded-lg p-3 text-sm text-orange-800 leading-relaxed font-medium">
                        ⚠️ El orientador ha dejado observaciones que debes atender antes de volver a entregar:
                        <div class="mt-2 font-normal text-gray-700 whitespace-pre-wrap">{{ $planActual->OBSERVACIONES }}</div>
                    </div>
                @elseif(!empty($planActual->OBSERVACIONES ?? null))
                    <div class="bg-white border border-amber-200 rounded-lg p-3 text-sm text-gray-700 whitespace-pre-wrap leading-relaxed">{{ $planActual->OBSERVACIONES }}</div>
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
        el.classList.remove('bg-gray-50', 'border-gray-200', 'text-gray-600', 'text-gray-500', 'cursor-default');
        el.classList.add('text-gray-800');
    });
    document.getElementById('btn-editar-contenido').style.display = 'none';
    document.getElementById('btns-guardar-docente').classList.remove('hidden');
    document.getElementById('btns-guardar-docente').classList.add('flex');
}
</script>
@endif

{{-- Auto-ajuste de altura del textarea del Plan Casero para que el texto se vea completo --}}
<script>
(function () {
    function ajustar(el) {
        el.style.height = 'auto';
        el.style.height = (el.scrollHeight + 2) + 'px';
    }
    document.querySelectorAll('textarea.auto-grow').forEach(function (el) {
        ajustar(el);
        el.addEventListener('input', function () { ajustar(el); });
    });
})();
</script>

@endsection
