@extends('layouts.app-sidebar')

@section('header', 'Reporte de Admisión · '.$evaluacion->aspirante_nombre)

@section('slot')

@php
    $isSuperAd = optional(auth()->user())->PROFILE === 'SuperAd';
    $res  = $evaluacion->resultados;
    $tot  = $res['total'] ?? [];
    $esMc = $evaluacion->tipo === 'opcion_multiple';
    $fmt  = fn($p) => rtrim(rtrim(number_format((float)$p, 1), '0'), '.');
    $barColor = fn($p) => $p >= 70 ? 'bg-green-500' : ($p >= 50 ? 'bg-amber-500' : 'bg-red-500');
    $nivelCls = fn($n) => match($n) {
        'Superior','Alto' => 'bg-green-100 text-green-700',
        'Básico'          => 'bg-amber-100 text-amber-700',
        'Bajo'            => 'bg-red-100 text-red-700',
        default           => 'bg-gray-100 text-gray-500',
    };
@endphp

@if(session('success'))
<div class="mb-5 bg-green-50 border border-green-300 text-green-800 px-4 py-3 rounded-lg text-sm font-medium">✅ {{ session('success') }}</div>
@endif

{{-- ── Barra de acciones ───────────────────────────────────────────────── --}}
<div class="flex flex-wrap items-center justify-between gap-3 mb-6">
    <a href="{{ route('admision.index') }}" class="text-sm text-blue-700 hover:underline">← Volver al listado</a>
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('admision.imprimir', $evaluacion) }}" target="_blank"
           class="bg-blue-800 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition">🖨️ Imprimir</a>
        @if($isSuperAd)
        <a href="{{ route('admision.edit', $evaluacion) }}"
           class="bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-semibold px-4 py-2 rounded-lg transition">✏️ Editar respuestas</a>
        <form method="POST" action="{{ route('admision.recalificar', $evaluacion) }}" class="inline"
              onsubmit="return confirm('¿Recalificar este reporte con la clave vigente?');">
            @csrf
            <button class="bg-green-700 hover:bg-green-600 text-white text-sm font-semibold px-4 py-2 rounded-lg transition">↻ Recalificar</button>
        </form>
        <form method="POST" action="{{ route('admision.destroy', $evaluacion) }}" class="inline"
              onsubmit="return confirm('¿Eliminar la evaluación de {{ addslashes($evaluacion->aspirante_nombre) }}? Esta acción no se puede deshacer.');">
            @csrf @method('DELETE')
            <button class="bg-red-50 hover:bg-red-100 text-red-700 text-sm font-semibold px-4 py-2 rounded-lg transition">🗑 Borrar</button>
        </form>
        @endif
    </div>
</div>

{{-- ── Datos del aspirante ─────────────────────────────────────────────── --}}
<div class="bg-white rounded-xl shadow p-5 mb-6">
    <div class="flex items-start justify-between mb-4">
        <div>
            <h2 class="text-lg font-bold text-gray-800">{{ $evaluacion->aspirante_nombre }}</h2>
            <p class="text-sm text-gray-500">Ingreso a {{ $evaluacion->grado_nombre }}</p>
        </div>
        <div class="text-right">
            <span class="text-xs px-2 py-0.5 rounded-full {{ $esMc ? 'bg-blue-100 text-blue-700' : 'bg-amber-100 text-amber-700' }}">
                {{ $esMc ? 'Opción múltiple' : 'Valoración cualitativa' }}
            </span>
            <span class="text-xs ml-1 px-2.5 py-0.5 rounded-full {{ $nivelCls($tot['nivel'] ?? '') }} font-semibold">{{ $tot['nivel'] ?? '—' }}</span>
        </div>
    </div>
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4 text-sm">
        <div><p class="text-[10px] uppercase text-gray-400">Documento</p><p class="font-medium text-gray-700">{{ $evaluacion->aspirante_documento ?: '—' }}</p></div>
        <div><p class="text-[10px] uppercase text-gray-400">Fecha examen</p><p class="font-medium text-gray-700">{{ optional($evaluacion->fecha_examen)->format('d/m/Y') ?? '—' }}</p></div>
        <div><p class="text-[10px] uppercase text-gray-400">Acudiente</p><p class="font-medium text-gray-700">{{ $evaluacion->acudiente ?: '—' }}</p></div>
        <div><p class="text-[10px] uppercase text-gray-400">Teléfono</p><p class="font-medium text-gray-700">{{ $evaluacion->telefono ?: '—' }}</p></div>
        <div><p class="text-[10px] uppercase text-gray-400">Evaluó</p><p class="font-medium text-gray-700">{{ $evaluacion->evaluado_por ?: '—' }}</p></div>
        <div><p class="text-[10px] uppercase text-gray-400">Registrado</p><p class="font-medium text-gray-700">{{ $evaluacion->created_at->format('d/m/Y') }}</p></div>
    </div>
</div>

{{-- ── Resultado global (tarjetas) ─────────────────────────────────────── --}}
<div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
    @if($esMc)
    <div class="bg-white rounded-xl shadow p-4 text-center">
        <p class="text-2xl font-bold text-gray-800">{{ $tot['correctas'] ?? 0 }}<span class="text-base text-gray-400">/{{ $tot['items'] ?? 0 }}</span></p>
        <p class="text-[11px] text-gray-400 uppercase">Aciertos</p>
    </div>
    @endif
    <div class="bg-white rounded-xl shadow p-4 text-center">
        <p class="text-2xl font-bold text-gray-800">{{ $fmt($tot['porcentaje'] ?? 0) }}%</p>
        <p class="text-[11px] text-gray-400 uppercase">Desempeño</p>
    </div>
    <div class="bg-white rounded-xl shadow p-4 text-center">
        <p class="text-2xl font-bold {{ $nivelCls($tot['nivel'] ?? '') }} inline-block px-3 rounded-lg">{{ $tot['nivel'] ?? '—' }}</p>
        <p class="text-[11px] text-gray-400 uppercase mt-1">Nivel global</p>
    </div>
    <div class="bg-white rounded-xl shadow p-4 text-center">
        <p class="text-2xl font-bold text-gray-800">{{ count($res['materias'] ?? []) }}</p>
        <p class="text-[11px] text-gray-400 uppercase">Materias</p>
    </div>
</div>

{{-- ── Resultados por materia ──────────────────────────────────────────── --}}
<div class="bg-white rounded-xl shadow p-5 mb-6">
    <h3 class="text-base font-semibold text-gray-800 mb-4">Resultados por materia</h3>
    <div class="space-y-3">
        @foreach($res['materias'] as $m)
        <div class="flex items-center gap-3">
            <div class="w-40 shrink-0">
                <p class="text-sm font-medium text-gray-700">{{ $m['nombre'] }}</p>
                <p class="text-[11px] text-gray-400">
                    @if($esMc){{ $m['correctas'] }}/{{ $m['items'] }} correctas
                    @else L {{ $m['niveles']['L'] }} · P {{ $m['niveles']['P'] }} · N {{ $m['niveles']['N'] }}@endif
                </p>
            </div>
            <div class="flex-1 bg-gray-100 rounded-full h-3 overflow-hidden">
                <div class="{{ $barColor($m['porcentaje']) }} h-full rounded-full" style="width: {{ $m['porcentaje'] }}%"></div>
            </div>
            <span class="w-12 text-right text-sm font-bold text-gray-700">{{ $fmt($m['porcentaje']) }}%</span>
            <span class="w-20 text-center text-[11px] px-2 py-0.5 rounded-full {{ $nivelCls($m['nivel']) }} font-semibold">{{ $m['nivel'] }}</span>
        </div>
        @endforeach
    </div>
</div>

{{-- ── Detalle de respuestas (solo opción múltiple) ────────────────────── --}}
@if($esMc)
<div class="bg-white rounded-xl shadow p-5 mb-6">
    <div class="flex items-center justify-between mb-3">
        <h3 class="text-base font-semibold text-gray-800">Detalle de respuestas</h3>
        <div class="flex gap-3 text-[11px] text-gray-500">
            <span class="flex items-center gap-1"><span class="w-3 h-3 rounded bg-green-600 inline-block"></span> Correcta</span>
            <span class="flex items-center gap-1"><span class="w-3 h-3 rounded bg-red-600 inline-block"></span> Incorrecta</span>
            <span class="flex items-center gap-1"><span class="w-3 h-3 rounded bg-gray-300 inline-block"></span> En blanco</span>
        </div>
    </div>
    <div class="flex flex-wrap gap-1.5">
        @foreach($grado['materias'] as $mat)
            @foreach($mat['preguntas'] as $q)
                @php
                    $marca = $evaluacion->respuestas[$q['n']] ?? ($evaluacion->respuestas[(string)$q['n']] ?? null);
                    $ok = $marca !== null && strtoupper($marca) === strtoupper($q['correcta']);
                    $cls = $marca === null ? 'bg-gray-300 text-gray-600' : ($ok ? 'bg-green-600 text-white' : 'bg-red-600 text-white');
                @endphp
                <div class="w-7 h-7 rounded flex items-center justify-center text-[11px] font-bold {{ $cls }}"
                     title="Pregunta {{ $q['n'] }} · Correcta: {{ $q['correcta'] }}{{ $marca ? ' · Marcó: '.$marca : ' · En blanco' }}">{{ $q['n'] }}</div>
            @endforeach
        @endforeach
    </div>
</div>
@endif

@if($evaluacion->observaciones)
<div class="bg-white rounded-xl shadow p-5 mb-6">
    <p class="text-[11px] uppercase text-gray-400 mb-1">Observaciones</p>
    <p class="text-sm text-gray-700">{{ $evaluacion->observaciones }}</p>
</div>
@endif

@endsection
