@extends('layouts.app-sidebar')

@section('header', 'Agenda Estudiantil Virtual · Consultar agenda')

@section('slot')

@php
    $badge = fn($color) => match ($color) {
        'blue'   => 'bg-blue-100 text-blue-800',
        'indigo' => 'bg-indigo-100 text-indigo-800',
        'red'    => 'bg-red-100 text-red-800',
        'green'  => 'bg-green-100 text-green-800',
        'amber'  => 'bg-amber-100 text-amber-800',
        default  => 'bg-gray-100 text-gray-700',
    };
@endphp

@if(session('ok'))
<div class="mb-5 bg-green-50 border border-green-300 text-green-800 px-4 py-3 rounded-lg text-sm font-medium">✅ {{ session('ok') }}</div>
@endif
@if(session('error'))
<div class="mb-5 bg-red-50 border border-red-300 text-red-800 px-4 py-3 rounded-lg text-sm font-medium">⚠️ {{ session('error') }}</div>
@endif
@if($errors->any())
<div class="mb-5 bg-red-50 border border-red-300 text-red-800 px-4 py-3 rounded-lg text-sm font-medium">⚠️ {{ $errors->first() }}</div>
@endif

<div class="mb-4 flex items-center gap-4">
    <a href="{{ route('bitacora.index') }}" class="text-sm text-blue-700 hover:text-blue-900 font-medium">← Volver al registro</a>
</div>

{{-- Selección del estudiante --}}
<div class="bg-white rounded-xl shadow p-5 mb-6">
    <h2 class="text-base font-bold text-gray-800 mb-1">🔎 Consultar la agenda de un estudiante</h2>
    <p class="text-xs text-gray-400 mb-4">
        @if($esSuperior)
        Ves <strong>toda</strong> la agenda de cualquier estudiante.
        @elseif($cursoDir)
        Eres director de grupo de <strong>{{ $cursoDir }}</strong>: de tus estudiantes ves <strong>toda</strong> la agenda; de los demás, solo tus propias anotaciones.
        @else
        Ves <strong>tus propias</strong> anotaciones sobre el estudiante consultado.
        @endif
    </p>

    <div class="flex gap-6 flex-wrap">
        <form method="GET" action="{{ route('bitacora.consulta') }}" class="flex gap-3 items-end">
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Por curso</label>
                <select name="curso_form" onchange="this.form.submit()"
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <option value="">-- Curso --</option>
                    @foreach($cursos as $c)
                    <option value="{{ $c }}" @selected($cursoForm === $c)>{{ $c }}</option>
                    @endforeach
                </select>
            </div>
        </form>
        <form method="GET" action="{{ route('bitacora.consulta') }}" class="flex gap-3 items-end">
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Por código</label>
                <input type="number" name="codigo" value="{{ request('codigo') }}" placeholder="Ej: 17000"
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm w-36">
            </div>
            <button type="submit" class="bg-blue-800 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold">Buscar</button>
        </form>
    </div>

    @if($estudiantes->isNotEmpty())
    <div class="mt-4 flex flex-wrap gap-2">
        @foreach($estudiantes as $est)
        @php $nom = preg_replace('/\s+/', ' ', trim(implode(' ', array_filter([$est->APELLIDO1, $est->APELLIDO2, $est->NOMBRE1, $est->NOMBRE2])))); @endphp
        <a href="{{ route('bitacora.consulta', ['curso_form' => $cursoForm, 'codigo' => $est->CODIGO]) }}"
           class="text-xs border rounded-lg px-2.5 py-1.5 transition {{ (int)request('codigo') === (int)$est->CODIGO ? 'bg-blue-700 text-white border-blue-700' : 'border-gray-200 text-gray-700 hover:bg-gray-50' }}">
            <span class="font-mono">{{ $est->CODIGO }}</span> · {{ $nom }}
        </a>
        @endforeach
    </div>
    @endif
</div>

@if($estudiante)
@php $nomEst = preg_replace('/\s+/', ' ', trim(implode(' ', array_filter([$estudiante->NOMBRE1, $estudiante->NOMBRE2, $estudiante->APELLIDO1, $estudiante->APELLIDO2])))); @endphp
<div class="mb-4">
    <h3 class="text-lg font-bold text-gray-800">{{ $nomEst }}</h3>
    <p class="text-sm text-gray-500">Código <span class="font-mono">{{ $estudiante->CODIGO }}</span> · Curso {{ $estudiante->CURSO }}
        @unless($puedeVerTodo)<span class="text-amber-600">· (solo tus anotaciones)</span>@endunless
    </p>
</div>

@if($entradas->isEmpty())
<div class="bg-white rounded-xl shadow p-8 text-center text-gray-400 text-sm">No hay anotaciones para mostrar.</div>
@else
<div class="space-y-3">
    @foreach($entradas as $e)
    <div class="bg-white rounded-xl shadow p-4" x-data="{ hilo: false }">
        <div class="flex items-center justify-between flex-wrap gap-2 mb-1.5">
            <div class="flex items-center gap-2 flex-wrap">
                <span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold {{ $badge($e->categoria_color) }}">{{ $e->categoria }}</span>
                @if(($e->prioridad ?? 'normal') === 'alta')
                <span class="inline-block px-2 py-0.5 rounded-full text-xs font-bold bg-red-600 text-white">⚠ Alta</span>
                @endif
                @if(!empty($e->acknowledged_at))
                <span class="text-[11px] font-semibold text-green-700">✓ Leída por la familia</span>
                @endif
            </div>
            <span class="text-xs text-gray-400">{{ \Carbon\Carbon::parse($e->fecha)->locale('es')->isoFormat('D MMM YYYY') }}</span>
        </div>
        @if(!empty($e->materia))
        <p class="text-xs font-semibold text-gray-500 mb-0.5">📘 {{ $e->materia }}</p>
        @endif
        <p class="text-sm text-gray-700 whitespace-pre-line">{{ $e->observacion }}</p>
        @if(!empty($e->registrado_nombre) || !empty($e->registrado_por))
        <p class="text-xs text-gray-400 mt-1">Por: {{ $e->registrado_nombre ?: $e->registrado_por }}</p>
        @endif

        @php $hiloE = $comentarios[$e->id] ?? collect(); @endphp
        <div class="mt-2">
            <button type="button" @click="hilo=!hilo" class="text-[11px] font-semibold text-blue-600 hover:text-blue-800">
                💬 Hilo<span class="text-gray-400">{{ $hiloE->count() ? ' ('.$hiloE->count().')' : '' }}</span>
            </button>
            <div x-show="hilo" x-cloak>
                @include('bitacora._hilo', ['entradaId' => $e->id, 'hilo' => $hiloE, 'miUser' => $miUser])
            </div>
        </div>
    </div>
    @endforeach
</div>
@endif
@endif

@endsection
