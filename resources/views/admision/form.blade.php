@extends('layouts.app-sidebar')

@section('header', 'Evaluación de Admisión · '.$grado['nombre'])

@section('slot')

@if($errors->any())
<div class="mb-5 bg-red-50 border border-red-300 text-red-800 px-4 py-3 rounded-lg text-sm">
    <ul class="list-disc list-inside space-y-1">
        @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
    </ul>
</div>
@endif

<div class="mb-4 flex items-center justify-between">
    <a href="{{ route('admision.index') }}" class="text-sm text-blue-700 hover:underline">← Volver</a>
    <span class="text-xs px-2 py-0.5 rounded-full {{ $grado['tipo']==='opcion_multiple' ? 'bg-blue-100 text-blue-700' : 'bg-amber-100 text-amber-700' }}">
        {{ $grado['tipo']==='opcion_multiple' ? 'Opción múltiple (A–D)' : 'Valoración cualitativa (L/P/N)' }}
    </span>
</div>

<form method="POST" action="{{ route('admision.store') }}" id="admision-form">
    @csrf
    <input type="hidden" name="grado" value="{{ $key }}">

    {{-- ── Datos del aspirante ─────────────────────────────────────────── --}}
    <div class="bg-white rounded-xl shadow p-5 mb-6">
        <h2 class="text-base font-semibold text-gray-800 mb-4">Datos del aspirante</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div class="lg:col-span-2">
                <label class="block text-xs font-medium text-gray-500 mb-1">Nombre completo *</label>
                <input type="text" name="aspirante_nombre" value="{{ old('aspirante_nombre') }}" required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Documento</label>
                <input type="text" name="aspirante_documento" value="{{ old('aspirante_documento') }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Fecha del examen</label>
                <input type="date" name="fecha_examen" value="{{ old('fecha_examen', date('Y-m-d')) }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Acudiente</label>
                <input type="text" name="acudiente" value="{{ old('acudiente') }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Teléfono</label>
                <input type="text" name="telefono" value="{{ old('telefono') }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
        </div>
    </div>

    {{-- ── Panel OMR (solo opción múltiple) — se activa en Fase B ───────── --}}
    @if($grado['tipo']==='opcion_multiple')
    @include('admision.partials.omr')
    @endif

    {{-- ── Planilla de respuestas ──────────────────────────────────────── --}}
    <div class="bg-white rounded-xl shadow p-5 mb-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-base font-semibold text-gray-800">Planilla de respuestas</h2>
            <span id="progreso" class="text-xs text-gray-400">0 / {{ $grado['total'] }} marcadas</span>
        </div>

        @if($grado['tipo']==='opcion_multiple')
            {{-- ---------- Opción múltiple: rejilla A–D por materia ---------- --}}
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-x-8 gap-y-6">
                @foreach($grado['materias'] as $mat)
                <div>
                    <h3 class="text-sm font-bold text-gray-700 border-b border-gray-200 pb-1 mb-2">{{ $mat['nombre'] }}</h3>
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-[11px] text-gray-400">
                                <th class="w-8 text-left font-medium">Nº</th>
                                @foreach(['A','B','C','D'] as $op)<th class="text-center font-medium">{{ $op }}</th>@endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($mat['preguntas'] as $q)
                            <tr class="border-t border-gray-50" title="{{ $q['enunciado'] }}">
                                <td class="py-1 text-gray-500 font-medium">{{ $q['n'] }}</td>
                                @foreach(['A','B','C','D'] as $op)
                                <td class="text-center py-1">
                                    <input type="radio" name="respuestas[{{ $q['n'] }}]" value="{{ $op }}"
                                           class="resp-radio h-4 w-4 accent-blue-700 cursor-pointer"
                                           @checked(old('respuestas.'.$q['n'])===$op)>
                                </td>
                                @endforeach
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endforeach
            </div>
        @else
            {{-- ---------- Cualitativo: enunciado + L/P/N por ítem ---------- --}}
            <p class="text-xs text-gray-500 mb-4">
                Marca cada ítem: <b>L</b> = Logrado &nbsp;·&nbsp; <b>P</b> = En proceso &nbsp;·&nbsp; <b>N</b> = No logrado.
            </p>
            <div class="space-y-6">
                @foreach($grado['materias'] as $mat)
                <div>
                    <h3 class="text-sm font-bold text-gray-700 bg-gray-50 rounded-md px-3 py-1.5 mb-2">{{ $mat['nombre'] }}</h3>
                    <div class="divide-y divide-gray-100">
                        @php
                            $estilosNivel = [
                                'L' => 'peer-checked:bg-green-600 peer-checked:border-green-600',
                                'P' => 'peer-checked:bg-amber-600 peer-checked:border-amber-600',
                                'N' => 'peer-checked:bg-red-600 peer-checked:border-red-600',
                            ];
                        @endphp
                        @foreach($mat['preguntas'] as $q)
                        <div class="flex items-start gap-3 py-2">
                            <span class="text-xs font-semibold text-gray-400 w-6 pt-0.5">{{ $q['n'] }}</span>
                            <p class="flex-1 text-sm text-gray-700">{{ $q['enunciado'] }}</p>
                            <div class="flex gap-1 pt-0.5">
                                @foreach(['L','P','N'] as $op)
                                <label class="cursor-pointer">
                                    <input type="radio" name="respuestas[{{ $q['n'] }}]" value="{{ $op }}"
                                           class="resp-radio peer sr-only" @checked(old('respuestas.'.$q['n'])===$op)>
                                    <span class="inline-flex items-center justify-center w-7 h-7 rounded-full border text-xs font-bold text-gray-500 border-gray-300 peer-checked:text-white {{ $estilosNivel[$op] }}">{{ $op }}</span>
                                </label>
                                @endforeach
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- ── Observaciones + guardar ─────────────────────────────────────── --}}
    <div class="bg-white rounded-xl shadow p-5 mb-6">
        <label class="block text-xs font-medium text-gray-500 mb-1">Observaciones (opcional)</label>
        <textarea name="observaciones" rows="2"
                  class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">{{ old('observaciones') }}</textarea>
    </div>

    <div class="flex justify-end gap-3">
        <a href="{{ route('admision.index') }}" class="px-5 py-2 rounded-lg text-sm font-semibold text-gray-600 hover:bg-gray-100">Cancelar</a>
        <button type="submit" class="bg-blue-800 hover:bg-blue-700 text-white px-6 py-2 rounded-lg text-sm font-semibold transition">
            Calificar y guardar
        </button>
    </div>
</form>

<script>
(function(){
    const total = {{ $grado['total'] }};
    const prog = document.getElementById('progreso');
    function actualizar(){
        const marcadas = new Set();
        document.querySelectorAll('.resp-radio:checked').forEach(r => {
            const m = r.name.match(/respuestas\[(\d+)\]/);
            if (m) marcadas.add(m[1]);
        });
        prog.textContent = marcadas.size + ' / ' + total + ' marcadas';
    }
    document.getElementById('admision-form').addEventListener('change', e => {
        if (e.target.classList && e.target.classList.contains('resp-radio')) actualizar();
    });
    window.admisionRefreshProgreso = actualizar;
    actualizar();
})();
</script>

@endsection
