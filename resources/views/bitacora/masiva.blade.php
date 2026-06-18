@extends('layouts.app-sidebar')

@section('header', 'Bitácora del Estudiante · Carga masiva por curso')

@section('slot')

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
    <a href="{{ route('bitacora.index') }}" class="text-sm text-blue-700 hover:text-blue-900 font-medium">← Registro individual</a>
</div>

{{-- Paso 1: elegir curso, fecha, categoría y plantilla por defecto --}}
<div class="bg-white rounded-xl shadow p-5 mb-6">
    <h2 class="text-base font-bold text-gray-800 mb-1">📋 Carga masiva por curso</h2>
    <p class="text-xs text-gray-400 mb-4">Elige el curso, la fecha, la categoría y una plantilla por defecto (ej. "buena"). La lista se precarga con esa plantilla y solo editas las excepciones. Guardar de nuevo sobre la misma fecha/categoría <strong>actualiza, no duplica</strong>. Dejar un campo en blanco <strong>quita</strong> la observación de ese estudiante.</p>

    <form method="GET" action="{{ route('bitacora.masiva') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end"
          x-data="{
              cat: '{{ $categoriaId ?: '' }}',
              plant: '{{ $plantillaId ?: '' }}',
              plantillas: @js($plantillas),
              filtradas() { return this.plantillas.filter(p => !p.categoria_id || String(p.categoria_id) === String(this.cat)); },
              recorta(t) { return t.length > 60 ? t.slice(0, 60) + '…' : t; }
          }">
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Curso</label>
            <select name="curso" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                <option value="">-- Selecciona --</option>
                @foreach($cursos as $c)
                <option value="{{ $c }}" @selected($curso === $c)>{{ $c }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Fecha</label>
            <input type="date" name="fecha" value="{{ $fecha }}" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Categoría</label>
            <select name="categoria_id" x-model="cat" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                <option value="">-- Selecciona --</option>
                @foreach($categorias as $cat)
                <option value="{{ $cat->id }}">{{ $cat->nombre }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Plantilla por defecto</label>
            <select name="plantilla_id" x-model="plant" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                <option value="">(Sin plantilla / texto en blanco)</option>
                <template x-for="p in filtradas()" :key="p.id">
                    <option :value="p.id" x-text="recorta(p.texto)"></option>
                </template>
            </select>
        </div>
        <div class="md:col-span-4">
            <button type="submit" class="bg-blue-800 hover:bg-blue-700 text-white px-5 py-2 rounded-lg text-sm font-semibold transition">Cargar lista del curso</button>
        </div>
    </form>
</div>

@if($curso && !$categoriaValida)
<div class="bg-white rounded-xl shadow p-8 text-center text-amber-600 text-sm">Selecciona una categoría válida para tu perfil.</div>
@elseif($estudiantes->isEmpty() && $curso)
<div class="bg-white rounded-xl shadow p-8 text-center text-gray-400 text-sm">No hay estudiantes matriculados en el curso {{ $curso }}.</div>
@elseif($estudiantes->isNotEmpty())

<form method="POST" action="{{ route('bitacora.masiva.guardar') }}"
      x-data="{ def: @js($textoDefault) }">
    @csrf
    <input type="hidden" name="curso" value="{{ $curso }}">
    <input type="hidden" name="fecha" value="{{ $fecha }}">
    <input type="hidden" name="categoria_id" value="{{ $categoriaId }}">

    <div class="flex items-center justify-between flex-wrap gap-2 mb-3">
        <p class="text-sm text-gray-600">Curso <strong>{{ $curso }}</strong> · {{ $estudiantes->count() }} estudiante(s) · {{ \Carbon\Carbon::parse($fecha)->locale('es')->isoFormat('D MMM YYYY') }}</p>
        @if($textoDefault !== '')
        <button type="button" @click="document.querySelectorAll('textarea[data-obs]').forEach(t => { if(!t.value.trim()) t.value = def; })"
            class="text-xs text-blue-700 hover:text-blue-900 font-semibold border border-blue-200 rounded-lg px-3 py-1.5">
            Rellenar vacíos con la plantilla
        </button>
        @endif
    </div>

    <div class="bg-white rounded-xl shadow overflow-hidden mb-5">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-200 text-left">
                    <th class="px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide w-24">Código</th>
                    <th class="px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Estudiante</th>
                    <th class="px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Observación</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($estudiantes as $est)
                @php
                    $nom = preg_replace('/\s+/', ' ', trim(implode(' ', array_filter([$est->NOMBRE1, $est->NOMBRE2, $est->APELLIDO1, $est->APELLIDO2]))));
                @endphp
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-mono text-gray-500 text-xs align-top pt-4">{{ $est->CODIGO }}</td>
                    <td class="px-4 py-3 align-top pt-4 font-medium text-gray-800">{{ $nom }}</td>
                    <td class="px-4 py-3">
                        <textarea name="obs[{{ $est->CODIGO }}]" data-obs rows="2" maxlength="8000"
                            placeholder="(en blanco = sin observación)"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">{{ $prefill[$est->CODIGO] ?? '' }}</textarea>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="flex items-center gap-3">
        <button type="submit" class="bg-blue-700 hover:bg-blue-800 text-white px-6 py-2.5 rounded-lg text-sm font-semibold transition shadow">
            💾 Guardar carga masiva
        </button>
        <p class="text-xs text-gray-400">Los campos en blanco quitarán la observación de esa fecha/categoría.</p>
    </div>
</form>

@endif

@endsection
