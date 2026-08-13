@extends('layouts.app-sidebar')

@section('header', 'Entrevista de Admisión · '.$entrevista->aspirante_nombre)

@section('slot')

@php
    $e  = $entrevista;
    $av = $e->avance();
    $isSuperAd = optional(auth()->user())->PROFILE === 'SuperAd';

    $semCls = fn($k) => match($k) {
        'ok'       => 'bg-green-100 text-green-700',
        'atencion' => 'bg-amber-100 text-amber-700',
        'alerta'   => 'bg-red-100 text-red-700',
        default    => 'bg-gray-100 text-gray-400',
    };
    $viaCls = fn($v) => match($v) {
        'viable'       => 'bg-green-100 text-green-700',
        'condicionado' => 'bg-amber-100 text-amber-700',
        'no_viable'    => 'bg-red-100 text-red-700',
        default        => 'bg-gray-100 text-gray-500',
    };
@endphp

@if(session('success'))
<div class="mb-5 bg-green-50 border border-green-300 text-green-800 px-4 py-3 rounded-lg text-sm font-medium">✅ {{ session('success') }}</div>
@endif

{{-- ── Barra de acciones ───────────────────────────────────────────────── --}}
<div class="flex flex-wrap items-center justify-between gap-3 mb-6">
    <a href="{{ route('admision.entrevistas.index') }}" class="text-sm text-blue-700 hover:underline">← Volver al listado</a>
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('admision.entrevistas.balance', $e) }}" target="_blank"
           class="bg-green-700 hover:bg-green-600 text-white text-sm font-semibold px-4 py-2 rounded-lg transition">📊 Balance para rectoría</a>
        <a href="{{ route('admision.entrevistas.imprimir', $e) }}" target="_blank"
           class="bg-blue-800 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition">🖨️ Imprimir entrevista</a>
        <a href="{{ route('admision.entrevistas.edit', $e) }}"
           class="bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-semibold px-4 py-2 rounded-lg transition">✏️ Editar</a>
        @if($isSuperAd)
        <form method="POST" action="{{ route('admision.entrevistas.destroy', $e) }}" class="inline"
              onsubmit="return confirm('¿Eliminar la entrevista de {{ addslashes($e->aspirante_nombre) }}? Esta acción no se puede deshacer.');">
            @csrf @method('DELETE')
            <button class="bg-red-50 hover:bg-red-100 text-red-700 text-sm font-semibold px-4 py-2 rounded-lg transition">🗑 Borrar</button>
        </form>
        @endif
    </div>
</div>

{{-- ── Cabecera del aspirante ──────────────────────────────────────────── --}}
<div class="bg-white rounded-xl shadow p-5 mb-6">
    <div class="flex flex-wrap items-start justify-between gap-3 mb-4">
        <div>
            <h2 class="text-lg font-bold text-gray-800">{{ $e->aspirante_nombre }}</h2>
            <p class="text-sm text-gray-500">
                Aspira a {{ $e->grado_nombre ?: 'grado sin definir' }}
                @if($e->edad) · {{ $e->edad }} años @endif
            </p>
        </div>
        <div class="flex items-center gap-2">
            <span class="text-xs px-2 py-0.5 rounded-full {{ $e->estado === 'finalizada' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-500' }}">
                {{ $e->estado === 'finalizada' ? 'Finalizada' : 'Borrador' }}
            </span>
            <span class="text-xs px-2.5 py-0.5 rounded-full font-semibold {{ $viaCls($e->viabilidad) }}">{{ $e->viabilidadTexto() }}</span>
        </div>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4 text-sm">
        <div><p class="text-[10px] uppercase text-gray-400">Documento</p><p class="font-medium text-gray-700">{{ trim($e->documento_tipo.' '.$e->aspirante_documento) ?: '—' }}</p></div>
        <div><p class="text-[10px] uppercase text-gray-400">Nacimiento</p><p class="font-medium text-gray-700">{{ optional($e->fecha_nacimiento)->format('d/m/Y') ?? '—' }}{{ $e->lugar_nacimiento ? ' · '.$e->lugar_nacimiento : '' }}</p></div>
        <div><p class="text-[10px] uppercase text-gray-400">Fecha entrevista</p><p class="font-medium text-gray-700">{{ optional($e->fecha_entrevista)->format('d/m/Y') ?? '—' }}</p></div>
        <div><p class="text-[10px] uppercase text-gray-400">Acudiente</p><p class="font-medium text-gray-700">{{ $e->acudiente ?: '—' }}{{ $e->acudiente_parentesco ? ' ('.$e->acudiente_parentesco.')' : '' }}</p></div>
        <div><p class="text-[10px] uppercase text-gray-400">Teléfono</p><p class="font-medium text-gray-700">{{ $e->acudiente_telefono ?: ($e->telefono ?: '—') }}</p></div>
        <div><p class="text-[10px] uppercase text-gray-400">Orientador(a)</p><p class="font-medium text-gray-700">{{ $e->orientador ?: '—' }}</p></div>
        <div class="col-span-2 lg:col-span-3"><p class="text-[10px] uppercase text-gray-400">Dirección</p><p class="font-medium text-gray-700">{{ $e->direccion ?: '—' }}</p></div>
        <div class="col-span-2 lg:col-span-3"><p class="text-[10px] uppercase text-gray-400">Colegio de procedencia</p><p class="font-medium text-gray-700">{{ $e->institucion_procedencia ?: '—' }}{{ $e->tiempo_permanencia ? ' · '.$e->tiempo_permanencia : '' }}</p></div>
    </div>

    <div class="mt-4 flex items-center gap-3">
        <span class="text-[11px] text-gray-400 uppercase">Diligenciamiento</span>
        <span class="flex-1 bg-gray-100 rounded-full h-2 overflow-hidden">
            <span class="block h-full bg-blue-600 rounded-full" style="width: {{ $av['porcentaje'] }}%"></span>
        </span>
        <span class="text-xs font-semibold text-gray-600">{{ $av['respondidas'] }}/{{ $av['total'] }} preguntas</span>
    </div>
</div>

{{-- ── Examen vinculado ────────────────────────────────────────────────── --}}
@if($e->evaluacion)
@php $ev = $e->evaluacion; @endphp
<div class="bg-white rounded-xl shadow p-5 mb-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <p class="text-[10px] uppercase text-gray-400">Examen de admisión</p>
            <p class="text-sm font-medium text-gray-700">
                {{ $ev->grado_nombre }} ·
                @if($ev->tipo === 'opcion_multiple'){{ $ev->puntaje }}/{{ $ev->total_preguntas }} · @endif
                {{ rtrim(rtrim(number_format($ev->porcentaje, 1), '0'), '.') }}%
                <span class="ml-1 text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-600">{{ $ev->resultados['total']['nivel'] ?? '—' }}</span>
            </p>
        </div>
        <a href="{{ route('admision.show', $ev) }}" class="text-blue-700 hover:underline text-sm font-semibold">Ver reporte del examen →</a>
    </div>
</div>
@else
<div class="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-6 text-sm text-amber-800">
    ⚠️ Esta entrevista no tiene un examen de admisión vinculado. El balance para rectoría se generará solo con la información de la entrevista.
</div>
@endif

{{-- ── Sección I: contexto de ingreso ──────────────────────────────────── --}}
<div class="bg-white rounded-xl shadow p-5 mb-6">
    <h3 class="text-base font-semibold text-gray-800 mb-4">I. Contexto de ingreso</h3>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
        <div><p class="text-[10px] uppercase text-gray-400">Motivo del cambio de institución</p><p class="text-gray-700">{{ $e->motivo_retiro ?: '—' }}</p></div>
        <div><p class="text-[10px] uppercase text-gray-400">Ha repetido algún grado</p><p class="text-gray-700">{{ $e->ha_reprobado ?: '—' }}</p></div>
        <div><p class="text-[10px] uppercase text-gray-400">Cambios de colegio</p><p class="text-gray-700">{{ $e->cambios_colegio ?: '—' }}</p></div>
        <div><p class="text-[10px] uppercase text-gray-400">Núcleo familiar</p><p class="text-gray-700">{{ $e->nucleo_familiar ?: '—' }}</p></div>
        <div><p class="text-[10px] uppercase text-gray-400">Acompaña procesos académicos</p><p class="text-gray-700">{{ $e->responsable_academico ?: '—' }}</p></div>
        <div><p class="text-[10px] uppercase text-gray-400">Red de apoyo</p><p class="text-gray-700">{{ $e->red_apoyo ?: '—' }}</p></div>
    </div>

    @if($e->familia)
    <div class="mt-5 overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-[10px] uppercase tracking-wide text-gray-400 border-b">
                    <th class="py-1 pr-3">Familiar</th>
                    <th class="py-1 pr-3">Parentesco</th>
                    <th class="py-1 pr-3">Edad</th>
                    <th class="py-1 pr-3">Ocupación</th>
                    <th class="py-1 pr-3">Positivo</th>
                    <th class="py-1 pr-3">Por mejorar</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($e->familia as $f)
                <tr>
                    <td class="py-1.5 pr-3 font-medium text-gray-700">{{ $f['nombre'] }}</td>
                    <td class="py-1.5 pr-3 text-gray-600">{{ $f['parentesco'] ?: '—' }}</td>
                    <td class="py-1.5 pr-3 text-gray-600">{{ $f['edad'] ?? '—' }}</td>
                    <td class="py-1.5 pr-3 text-gray-600">{{ $f['ocupacion'] ?: '—' }}</td>
                    <td class="py-1.5 pr-3 text-gray-600">{{ $f['positivo'] ?: '—' }}</td>
                    <td class="py-1.5 pr-3 text-gray-600">{{ $f['negativo'] ?: '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>

{{-- ── Secciones II a VII ──────────────────────────────────────────────── --}}
@foreach($secciones as $slug => $sec)
<div class="bg-white rounded-xl shadow p-5 mb-6">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-base font-semibold text-gray-800">{{ $sec['icono'] }} {{ $sec['num'] }}. {{ $sec['titulo'] }}</h3>
        <span class="text-[11px] px-2.5 py-0.5 rounded-full font-semibold {{ $semCls($e->alerta($slug)) }}">
            {{ $e->alertaTexto($slug) }}
        </span>
    </div>
    <div class="space-y-3">
        @foreach($sec['preguntas'] as $i => $p)
        <div>
            <p class="text-xs text-gray-500">{{ $i + 1 }}. {{ $p['t'] }}</p>
            <p class="text-sm text-gray-800 pl-3 border-l-2 {{ $e->r($slug, $p['k']) ? 'border-blue-200' : 'border-gray-100 text-gray-300' }} mt-0.5">
                {{ $e->r($slug, $p['k']) ?: 'Sin respuesta' }}
            </p>
        </div>
        @endforeach
    </div>
</div>
@endforeach

{{-- ── VIII. Concepto ──────────────────────────────────────────────────── --}}
<div class="bg-white rounded-xl shadow p-5 mb-6 border-t-4 border-blue-800">
    <h3 class="text-base font-semibold text-gray-800 mb-4">📋 VIII. Concepto de los entrevistadores</h3>
    <div class="space-y-3">
        @foreach($concepto as $i => $p)
        <div>
            <p class="text-xs text-gray-500">{{ $i + 1 }}. {{ $p['t'] }}</p>
            <p class="text-sm text-gray-800 pl-3 border-l-2 {{ $e->r('concepto', $p['k']) ? 'border-blue-200' : 'border-gray-100 text-gray-300' }} mt-0.5">
                {{ $e->r('concepto', $p['k']) ?: 'Sin respuesta' }}
            </p>
        </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-5 pt-4 border-t border-gray-100 text-sm">
        <div>
            <p class="text-[10px] uppercase text-gray-400">Viabilidad de ingreso</p>
            <span class="text-xs px-2.5 py-0.5 rounded-full font-semibold {{ $viaCls($e->viabilidad) }}">{{ $e->viabilidadTexto() }}</span>
        </div>
        <div class="md:col-span-2">
            <p class="text-[10px] uppercase text-gray-400">Compromisos</p>
            <p class="text-gray-700">{{ $e->compromisos ?: '—' }}</p>
        </div>
    </div>
</div>

@endsection
