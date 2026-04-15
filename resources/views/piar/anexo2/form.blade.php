@extends('layouts.app-sidebar')

@section('header', 'Diligenciamiento PIAR – ' . $apellidos . ', ' . $nombreCompleto)

@section('slot')

@if(session('saved'))
    <div class="mb-4 p-3 bg-green-100 text-green-800 rounded-xl text-sm">✅ {{ session('saved') }}</div>
@endif
@if($errors->has('etapa'))
    <div class="mb-4 p-3 bg-red-100 text-red-800 rounded-xl text-sm">🚫 {{ $errors->first('etapa') }}</div>
@endif

@php
    $puedeObservar = in_array(auth()->user()->PROFILE, ['SuperAd', 'Ori']);
    $estadoActual  = $piarMat->ESTADO ?? 'pendiente';
    $docentePuede  = !$puedeObservar && ($estadoEtapa === 'abierto' || $estadoActual === 'con_observaciones');
@endphp

{{-- Banner etapa --}}
@if($estadoEtapa === 'cerrado')
    <div class="mb-3 px-4 py-2 rounded-lg text-sm bg-red-50 border border-red-200 text-red-800 flex items-center gap-2">🔒 <span>Etapa de ajustes razonables <strong>cerrada</strong>. No se permiten cambios.</span></div>
@elseif($estadoEtapa === 'revision')
    <div class="mb-3 px-4 py-2 rounded-lg text-sm bg-blue-50 border border-blue-200 text-blue-800 flex items-center gap-2">👁 <span>Etapa <strong>en revisión</strong> — orientación está revisando los ajustes{{ $puedeObservar ? '. Puedes aprobar.' : '. No puedes editar.' }}</span></div>
@elseif($estadoEtapa === 'finalizado')
    <div class="mb-3 px-4 py-2 rounded-lg text-sm bg-purple-50 border border-purple-200 text-purple-800 flex items-center gap-2">✓ <span>Etapa <strong>finalizada</strong>. Solo lectura.</span></div>
@endif
<div class="flex items-center gap-3 mb-3">
    <span class="text-xs font-semibold text-gray-500 uppercase">Estado:</span>
    @if($estadoActual === 'aprobado')
        <span class="inline-flex items-center gap-1 bg-green-100 text-green-700 text-xs px-3 py-1 rounded-full font-semibold">✓ Aprobado</span>
    @elseif($estadoActual === 'revision')
        <span class="inline-flex items-center gap-1 bg-blue-100 text-blue-700 text-xs px-3 py-1 rounded-full font-semibold">👁 En revisión</span>
    @elseif($estadoActual === 'con_observaciones')
        <span class="inline-flex items-center gap-1 bg-orange-100 text-orange-700 text-xs px-3 py-1 rounded-full font-semibold">💬 Observaciones pendientes</span>
    @else
        <span class="inline-flex items-center gap-1 bg-yellow-100 text-yellow-700 text-xs px-3 py-1 rounded-full font-semibold">Pendiente</span>
    @endif
    @if($estadoActual === 'aprobado' && ($piarMat->APROBADO_POR ?? null))
        <span class="text-xs text-gray-400">Aprobado por {{ $piarMat->APROBADO_POR }} el {{ \Carbon\Carbon::parse($piarMat->FECHA_APROBACION)->locale('es')->isoFormat('D MMM YYYY') }}</span>
    @endif
</div>

{{-- Barra de acciones --}}
<div class="flex items-center justify-between mb-4">
    <a href="{{ $puedeObservar ? route('piar.informe') : route('piar.anexo2.index') }}" class="text-blue-700 hover:underline text-sm">← Volver</a>
    <div class="flex gap-3">
        @if($puedeObservar)
            @if($estadoEtapa !== 'finalizado')
            <button type="button" id="btn-editar-contenido"
                onclick="activarEdicion()"
                class="bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-semibold px-5 py-2 rounded-lg transition border border-gray-300">
                ✏️ Editar contenido
            </button>
            <div id="btns-guardar-docente" class="hidden gap-3 flex">
                <button type="submit" form="form-anexo2" name="accion" value="guardar"
                    class="bg-gray-600 hover:bg-gray-500 text-white text-sm font-semibold px-5 py-2 rounded-lg transition">
                    💾 Guardar borrador
                </button>
                @if($estadoActual !== 'aprobado')
                <button type="submit" form="form-anexo2" name="accion" value="entregar"
                    class="bg-green-700 hover:bg-green-600 text-white text-sm font-semibold px-5 py-2 rounded-lg transition">
                    📤 Marcar como entregado
                </button>
                @endif
            </div>
            @endif
            <a href="{{ route('piar.anexo2.imprimir', [$estudiante->CODIGO, $codigoMat]) }}" target="_blank"
                class="bg-blue-800 hover:bg-blue-700 text-white text-sm font-semibold px-5 py-2 rounded-lg transition inline-block">
                🖨️ Imprimir Anexo 2
            </a>
        @elseif($docentePuede)
            <button type="submit" form="form-anexo2" name="accion" value="guardar"
                class="bg-gray-600 hover:bg-gray-500 text-white text-sm font-semibold px-5 py-2 rounded-lg transition">
                💾 Guardar borrador
            </button>
            @if($estadoActual !== 'aprobado')
            <button type="submit" form="form-anexo2" name="accion" value="entregar"
                class="bg-green-700 hover:bg-green-600 text-white text-sm font-semibold px-5 py-2 rounded-lg transition">
                📤 Marcar como entregado
            </button>
            @endif
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
        {{ $puedeObservar ? 'readonly' : '' }}
        class="campo-docente w-full border rounded-lg p-3 text-sm focus:outline-none resize-none transition
            {{ $puedeObservar ? 'bg-gray-50 border-gray-200 text-gray-600 cursor-default' : 'border-gray-300 text-gray-800 focus:border-blue-400' }}">{{ $v('BARRERAS') }}</textarea>
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
                @php
                    // Para docentes: editable solo en el período activo. Orientadores usan su propio toggle.
                    $esPeriodoActivo = ($p['num'] === $periodoActivo);
                    $bloqueadoPorPeriodo = !$puedeObservar && !$esPeriodoActivo;
                    $esReadonly = $puedeObservar || $bloqueadoPorPeriodo;
                    $clasesFondo = $esReadonly ? 'bg-gray-50 text-gray-400 cursor-default' : 'text-gray-800 bg-transparent';
                    $clasesTd   = $esReadonly ? 'bg-gray-50' : ($esPeriodoActivo ? 'bg-white ring-1 ring-inset ring-blue-200' : '');
                @endphp
                <tr class="{{ $esPeriodoActivo && !$puedeObservar ? 'ring-2 ring-blue-300 ring-inset' : '' }}">
                    <td class="border border-gray-200 px-2 py-2 text-center font-bold text-xs
                        {{ $esPeriodoActivo && !$puedeObservar ? 'text-blue-800 bg-blue-50' : 'text-green-800 bg-green-50' }}">
                        {{ $p['label'] }}
                        @if($esPeriodoActivo && !$puedeObservar)
                            <span class="block text-blue-500 font-normal mt-0.5" style="font-size:9px">ACTIVO</span>
                        @endif
                    </td>
                    <td class="border border-gray-200 px-2 py-1 {{ $clasesTd }}">
                        <textarea name="LOGRO{{ $p['num'] }}" rows="4"
                            placeholder="Propósitos y logros del {{ strtolower($p['label']) }} período..."
                            {{ $esReadonly ? 'readonly' : '' }}
                            class="campo-docente w-full text-xs focus:outline-none resize-none p-1 transition {{ $clasesFondo }}">{{ $v('LOGRO' . $p['num']) }}</textarea>
                    </td>
                    <td class="border border-gray-200 px-2 py-1 {{ $clasesTd }}">
                        <textarea name="DIDACT{{ $p['num'] }}" rows="4"
                            placeholder="Metodología y estrategias didácticas..."
                            {{ $esReadonly ? 'readonly' : '' }}
                            class="campo-docente w-full text-xs focus:outline-none resize-none p-1 transition {{ $clasesFondo }}">{{ $v('DIDACT' . $p['num']) }}</textarea>
                    </td>
                    <td class="border border-gray-200 px-2 py-1 {{ $clasesTd }}">
                        <textarea name="EVAL{{ $p['num'] }}" rows="4"
                            placeholder="Evaluación y seguimiento..."
                            {{ $esReadonly ? 'readonly' : '' }}
                            class="campo-docente w-full text-xs focus:outline-none resize-none p-1 transition {{ $clasesFondo }}">{{ $v('EVAL' . $p['num']) }}</textarea>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>


{{-- Observaciones del orientador --}}
<div class="bg-amber-50 border border-amber-200 rounded-xl shadow p-5">
    <h3 class="text-sm font-bold text-amber-800 uppercase tracking-wide mb-1">📝 Observaciones del orientador</h3>
    <p class="text-xs text-amber-600 mb-3">
        @if($puedeObservar)
            Registra aquí los cambios o ajustes que debe realizar el docente en este formulario.
        @else
            Observaciones y cambios solicitados por orientación.
        @endif
    </p>
    @if($puedeObservar)
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <textarea name="OBSERVACIONES" rows="5"
                placeholder="Escribe aquí las observaciones para el docente..."
                class="w-full border border-amber-300 bg-white rounded-lg p-3 text-sm text-gray-800 focus:outline-none focus:border-amber-500 resize-none">{{ $piarMat->OBSERVACIONES ?? '' }}</textarea>

            {{-- Acciones del orientador --}}
            <div class="flex flex-col justify-center gap-2">
                @if($estadoActual === 'revision' || $estadoActual === 'con_observaciones')
                    <button type="submit" form="form-aprobar-ajustes"
                        class="w-full bg-green-700 hover:bg-green-600 text-white text-sm font-bold px-4 py-2.5 rounded-lg transition">
                        ✓ Aprobar ajustes razonables
                    </button>
                @elseif($estadoActual === 'aprobado')
                    <p class="text-sm text-green-700 font-semibold text-center">
                        ✓ Aprobado por {{ $piarMat->APROBADO_POR ?? 'orientación' }}
                        @if($piarMat->FECHA_APROBACION ?? null)
                            · {{ \Carbon\Carbon::parse($piarMat->FECHA_APROBACION)->locale('es')->isoFormat('D MMM YYYY') }}
                        @endif
                    </p>
                @else
                    <p class="text-xs text-amber-500 text-center italic">Pendiente de entrega por el docente.</p>
                @endif
                <button type="submit" form="form-anexo2" name="accion" value="observar"
                    class="w-full bg-orange-600 hover:bg-orange-500 text-white text-sm font-semibold px-4 py-2.5 rounded-lg transition">
                    💬 Enviar observaciones al docente
                </button>
                <button type="submit" form="form-anexo2" name="accion" value="guardar"
                    class="w-full bg-amber-600 hover:bg-amber-500 text-white text-sm font-semibold px-4 py-2.5 rounded-lg transition">
                    💾 Guardar borrador de observaciones
                </button>
            </div>
        </div>
    @else
        @if($estadoActual === 'con_observaciones' && !empty($piarMat->OBSERVACIONES ?? null))
            <div class="bg-orange-50 border border-orange-300 rounded-lg p-3 text-sm text-orange-800 leading-relaxed font-medium">
                ⚠️ El orientador ha dejado observaciones que debes atender antes de volver a entregar:
                <div class="mt-2 font-normal text-gray-700 whitespace-pre-wrap">{{ $piarMat->OBSERVACIONES }}</div>
            </div>
        @elseif(!empty($piarMat->OBSERVACIONES ?? null))
            <div class="bg-white border border-amber-200 rounded-lg p-3 text-sm text-gray-700 whitespace-pre-wrap leading-relaxed">{{ $piarMat->OBSERVACIONES }}</div>
        @else
            <p class="text-xs text-amber-400 italic">Sin observaciones registradas.</p>
        @endif
    @endif
</div>

{{-- Botones inferiores --}}
@if($puedeObservar && $estadoEtapa !== 'finalizado')
<div id="btns-guardar-docente-bottom" class="hidden justify-end gap-3 pb-6">
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
@elseif($docentePuede)
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
</form>

@if($puedeObservar && ($estadoActual === 'revision' || $estadoActual === 'con_observaciones'))
<form id="form-aprobar-ajustes" method="POST" action="{{ route('piar.aprobar.ajustes', [$estudiante->CODIGO, $codigoMat]) }}">
    @csrf
</form>
@endif

@if($puedeObservar)
<script>
function activarEdicion() {
    document.querySelectorAll('.campo-docente').forEach(el => {
        el.removeAttribute('readonly');
        el.classList.remove('bg-gray-50', 'border-gray-200', 'text-gray-600', 'cursor-default', 'text-gray-500');
        el.classList.add('border-gray-300', 'text-gray-800', 'focus:border-blue-400');
    });
    // Quitar bg-gray-50 de las celdas <td> de la tabla de períodos
    document.querySelectorAll('td.bg-gray-50').forEach(td => {
        td.classList.remove('bg-gray-50');
    });
    document.getElementById('btn-editar-contenido').style.display = 'none';
    document.getElementById('btns-guardar-docente').classList.remove('hidden');
    document.getElementById('btns-guardar-docente').classList.add('flex');
    document.getElementById('btns-guardar-docente-bottom').classList.remove('hidden');
    document.getElementById('btns-guardar-docente-bottom').classList.add('flex');
}
</script>
@endif

@endsection
