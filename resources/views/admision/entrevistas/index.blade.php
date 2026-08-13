@extends('layouts.app-sidebar')

@section('header', 'Entrevistas de Admisión')

@section('slot')

@php
    $isSuperAd = optional(auth()->user())->PROFILE === 'SuperAd';
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
@if(session('error'))
<div class="mb-5 bg-red-50 border border-red-300 text-red-800 px-4 py-3 rounded-lg text-sm font-medium">⚠️ {{ session('error') }}</div>
@endif

{{-- ── Encabezado ──────────────────────────────────────────────────────── --}}
<div class="bg-white rounded-xl shadow p-5 mb-6">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h2 class="text-base font-semibold text-gray-800 mb-1">Entrevista de admisión</h2>
            <p class="text-xs text-gray-500 max-w-2xl">
                Formato de entrevista familiar aplicado por orientación escolar. Al finalizarla se genera el
                <strong>balance para rectoría</strong>, que reúne el resultado del examen y el concepto de la entrevista
                en una sola hoja.
            </p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admision.index') }}"
               class="bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-semibold px-4 py-2 rounded-lg transition">📝 Exámenes</a>
            <a href="{{ route('admision.entrevistas.create') }}"
               class="bg-blue-800 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition">＋ Nueva entrevista</a>
        </div>
    </div>
</div>

{{-- ── Listado ─────────────────────────────────────────────────────────── --}}
<div class="bg-white rounded-xl shadow p-5">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
        <h2 class="text-base font-semibold text-gray-800">Entrevistas registradas</h2>
        <form method="GET" action="{{ route('admision.entrevistas.index') }}" class="flex flex-wrap gap-2">
            <input type="text" name="buscar" value="{{ request('buscar') }}" placeholder="Nombre, documento o acudiente…"
                   class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm">
            <select name="grado" class="border border-gray-300 rounded-lg px-2 py-1.5 text-sm">
                <option value="">Todos los grados</option>
                @foreach($grados as $g)
                    <option value="{{ $g['key'] }}" @selected(request('grado')===$g['key'])>{{ $g['nombre'] }}</option>
                @endforeach
            </select>
            <select name="estado" class="border border-gray-300 rounded-lg px-2 py-1.5 text-sm">
                <option value="">Todos los estados</option>
                <option value="borrador" @selected(request('estado')==='borrador')>Borrador</option>
                <option value="finalizada" @selected(request('estado')==='finalizada')>Finalizada</option>
            </select>
            <button class="bg-gray-800 hover:bg-gray-700 text-white px-4 py-1.5 rounded-lg text-sm font-semibold">Filtrar</button>
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-xs uppercase tracking-wide text-gray-400 border-b">
                    <th class="py-2 pr-3">Documento</th>
                    <th class="py-2 pr-3">Aspirante</th>
                    <th class="py-2 pr-3">Grado</th>
                    <th class="py-2 pr-3">Fecha</th>
                    <th class="py-2 pr-3 text-center">Examen</th>
                    <th class="py-2 pr-3 text-center">Avance</th>
                    <th class="py-2 pr-3 text-center">Concepto</th>
                    <th class="py-2 pr-3 text-center">Rectoría</th>
                    <th class="py-2 pr-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($entrevistas as $en)
                @php $av = $en->avance(); @endphp
                <tr class="hover:bg-gray-50">
                    <td class="py-2 pr-3 text-gray-500">{{ $en->aspirante_documento ?: '—' }}</td>
                    <td class="py-2 pr-3 font-medium text-gray-800">
                        {{ $en->aspirante_nombre }}
                        @if($en->estado === 'borrador')
                            <span class="ml-1 text-[10px] bg-gray-100 text-gray-500 px-1.5 py-0.5 rounded-full">borrador</span>
                        @endif
                    </td>
                    <td class="py-2 pr-3">{{ $en->grado_nombre ?: '—' }}</td>
                    <td class="py-2 pr-3 text-gray-500">{{ optional($en->fecha_entrevista)->format('d/m/Y') ?? $en->created_at->format('d/m/Y') }}</td>
                    <td class="py-2 pr-3 text-center">
                        @if($en->evaluacion)
                            <a href="{{ route('admision.show', $en->evaluacion) }}" class="text-blue-700 hover:underline text-xs font-semibold">
                                {{ rtrim(rtrim(number_format($en->evaluacion->porcentaje, 1), '0'), '.') }}%
                            </a>
                        @else
                            <span class="text-gray-300">—</span>
                        @endif
                    </td>
                    <td class="py-2 pr-3 text-center">
                        <div class="inline-flex items-center gap-2">
                            <span class="w-16 bg-gray-100 rounded-full h-1.5 overflow-hidden inline-block">
                                <span class="block h-full bg-blue-600 rounded-full" style="width: {{ $av['porcentaje'] }}%"></span>
                            </span>
                            <span class="text-[11px] text-gray-500">{{ $av['respondidas'] }}/{{ $av['total'] }}</span>
                        </div>
                    </td>
                    <td class="py-2 pr-3 text-center">
                        <span class="text-[11px] px-2 py-0.5 rounded-full {{ $viaCls($en->viabilidad) }}">
                            {{ $en->viabilidadTexto() }}
                        </span>
                    </td>
                    <td class="py-2 pr-3 text-center text-[11px] text-gray-600">{{ $en->decisionTexto() }}</td>
                    <td class="py-2 pr-3 text-right whitespace-nowrap">
                        <a href="{{ route('admision.entrevistas.balance', $en) }}" target="_blank"
                           class="text-green-700 hover:underline text-xs font-semibold">Balance</a>
                        <span class="text-gray-300 mx-1">·</span>
                        <a href="{{ route('admision.entrevistas.show', $en) }}"
                           class="text-blue-700 hover:underline text-xs font-semibold">Ver</a>
                        <span class="text-gray-300 mx-1">·</span>
                        <a href="{{ route('admision.entrevistas.edit', $en) }}"
                           class="text-gray-600 hover:underline text-xs font-semibold">Editar</a>
                        @if($isSuperAd)
                        <span class="text-gray-300 mx-1">·</span>
                        <form method="POST" action="{{ route('admision.entrevistas.destroy', $en) }}" class="inline"
                              onsubmit="return confirm('¿Eliminar la entrevista de {{ addslashes($en->aspirante_nombre) }}? Esta acción no se puede deshacer.');">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-red-600 hover:underline text-xs font-semibold">Borrar</button>
                        </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="9" class="py-8 text-center text-gray-400 text-sm">Aún no hay entrevistas registradas.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $entrevistas->links() }}</div>
</div>

@endsection
