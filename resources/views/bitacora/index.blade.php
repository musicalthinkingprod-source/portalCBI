@extends('layouts.app-sidebar')

@section('header', 'Bitácora del Estudiante · Registro')

@section('slot')

@php
    // Clases literales para que Tailwind no las purgue.
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
<div class="mb-5 bg-green-50 border border-green-300 text-green-800 px-4 py-3 rounded-lg text-sm font-medium">
    ✅ {{ session('ok') }}
</div>
@endif
@if(session('error'))
<div class="mb-5 bg-red-50 border border-red-300 text-red-800 px-4 py-3 rounded-lg text-sm font-medium">
    ⚠️ {{ session('error') }}
</div>
@endif
@if($errors->any())
<div class="mb-5 bg-red-50 border border-red-300 text-red-800 px-4 py-3 rounded-lg text-sm font-medium">
    ⚠️ {{ $errors->first() }}
</div>
@endif

{{-- ── Nueva observación ────────────────────────────────────────────────── --}}
<div class="bg-white rounded-xl shadow p-5 mb-6">
    <div class="flex items-center justify-between flex-wrap gap-2 mb-4">
        <h2 class="text-base font-bold text-gray-800">📖 Nueva observación</h2>
        @unless($esDocente)
        <a href="{{ route('bitacora.masiva') }}" class="text-sm font-semibold text-blue-700 hover:text-blue-900 border border-blue-200 rounded-lg px-3 py-1.5">📋 Carga masiva por curso</a>
        @endunless
    </div>

    {{-- Paso 1: cargar estudiantes del curso (recarga GET) --}}
    <form method="GET" action="{{ route('bitacora.index') }}" class="flex gap-3 items-end flex-wrap mb-4 pb-4 border-b border-gray-100">
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Curso</label>
            <select name="curso_form" onchange="this.form.submit()"
                class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                <option value="">-- Selecciona un curso --</option>
                @foreach($cursos as $c)
                <option value="{{ $c }}" @selected($cursoForm === $c)>{{ $c }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit"
            class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm font-semibold transition">
            Cargar estudiantes
        </button>
    </form>

    @if($estudiantes->isEmpty())
        <p class="text-sm text-gray-400">Selecciona un curso para listar sus estudiantes y registrar una observación.</p>
    @elseif($categorias->isEmpty())
        <p class="text-sm text-amber-600">No tienes categorías disponibles para tu perfil. Pide al administrador que configure la bitácora.</p>
    @else
        <form method="POST" action="{{ route('bitacora.store') }}"
              x-data="{
                  categoria: '',
                  texto: '',
                  alumno: '',
                  plantillas: @js($plantillas),
                  historial: @js($historialPorEstudiante ?? []),
                  filtradas() { return this.plantillas.filter(p => !p.categoria_id || String(p.categoria_id) === String(this.categoria)); },
                  histSel() { return this.historial[this.alumno] || []; },
                  colorClass(c) { return ({blue:'bg-blue-100 text-blue-800',indigo:'bg-indigo-100 text-indigo-800',red:'bg-red-100 text-red-800',green:'bg-green-100 text-green-800',amber:'bg-amber-100 text-amber-800'})[c] || 'bg-gray-100 text-gray-700'; },
                  aplicar(e) {
                      const p = this.plantillas.find(x => String(x.id) === String(e.target.value));
                      if (p) this.texto = this.texto ? (this.texto + '\n' + p.texto) : p.texto;
                      e.target.value = '';
                  }
              }">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Estudiante</label>
                    <select name="codigo_alumno" x-model="alumno" required
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                        <option value="">-- Selecciona --</option>
                        @foreach($estudiantes as $est)
                        @php $nom = preg_replace('/\s+/', ' ', trim(implode(' ', array_filter([$est->NOMBRE1, $est->NOMBRE2, $est->APELLIDO1, $est->APELLIDO2])))); @endphp
                        <option value="{{ $est->CODIGO }}">{{ $est->CODIGO }} — {{ $nom }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Fecha</label>
                    <input type="date" name="fecha" value="{{ now()->toDateString() }}" required
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Motivo / Categoría</label>
                    <select name="categoria_id" x-model="categoria" required
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                        <option value="">-- Selecciona --</option>
                        @foreach($categorias as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                @if($esDocente)
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Materia (de aula)</label>
                    <select name="codigo_mat" required
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                        <option value="">-- Selecciona --</option>
                        @foreach($materias as $mat)
                        <option value="{{ $mat->codigo_mat }}">{{ $mat->nombre_mat }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
            </div>

            <div class="mb-3">
                <div class="flex items-center justify-between mb-1">
                    <label class="block text-xs font-medium text-gray-500">Observación</label>
                    <template x-if="filtradas().length">
                        <select @change="aplicar($event)"
                            class="border border-gray-200 rounded-lg px-2 py-1 text-xs text-gray-600 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                            <option value="">＋ Insertar plantilla…</option>
                            <template x-for="p in filtradas()" :key="p.id">
                                <option :value="p.id" x-text="p.texto.length > 70 ? p.texto.slice(0,70)+'…' : p.texto"></option>
                            </template>
                        </select>
                    </template>
                </div>
                <textarea name="observacion" x-model="texto" rows="4" required maxlength="8000"
                    placeholder="Escribe la observación para el padre…"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"></textarea>
            </div>

            {{-- Registros anteriores del estudiante seleccionado --}}
            <div class="mb-4" x-show="alumno" x-cloak>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">📚 Registros anteriores del estudiante</p>
                <template x-if="histSel().length">
                    <div class="space-y-2 max-h-64 overflow-y-auto border border-gray-100 rounded-lg p-2 bg-gray-50">
                        <template x-for="(h, i) in histSel()" :key="i">
                            <div class="bg-white border border-gray-100 rounded-lg p-2.5">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-xs px-2 py-0.5 rounded-full font-semibold" :class="colorClass(h.color)" x-text="h.categoria"></span>
                                    <span class="text-xs text-gray-400" x-text="h.fecha"></span>
                                </div>
                                <p class="text-xs font-semibold text-gray-500 mb-0.5" x-show="h.materia" x-text="'📘 ' + h.materia"></p>
                                <p class="text-xs text-gray-700 whitespace-pre-line" x-text="h.observacion"></p>
                            </div>
                        </template>
                    </div>
                </template>
                <template x-if="!histSel().length">
                    <p class="text-xs text-gray-400">Sin registros anteriores para este estudiante.</p>
                </template>
            </div>

            <button type="submit"
                class="bg-blue-700 hover:bg-blue-800 text-white px-6 py-2.5 rounded-lg text-sm font-semibold transition shadow">
                💾 Registrar observación
            </button>
        </form>
    @endif
</div>

{{-- ── Filtros + listado ────────────────────────────────────────────────── --}}
<div class="bg-white rounded-xl shadow p-5 mb-4">
    <form method="GET" action="{{ route('bitacora.index') }}" class="flex gap-3 items-end flex-wrap">
        @if($cursoForm)<input type="hidden" name="curso_form" value="{{ $cursoForm }}">@endif
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Curso</label>
            <select name="f_curso" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                <option value="">Todos</option>
                @foreach($cursos as $c)
                <option value="{{ $c }}" @selected($fCurso === $c)>{{ $c }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Código estudiante</label>
            <input type="number" name="f_codigo" value="{{ $fCodigo }}" placeholder="Ej: 17000"
                class="border border-gray-300 rounded-lg px-3 py-2 text-sm w-32">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Categoría</label>
            <select name="f_categoria" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                <option value="">Todas</option>
                @foreach($todasCategorias as $cat)
                <option value="{{ $cat->id }}" @selected((int)$fCategoria === $cat->id)>{{ $cat->nombre }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="bg-blue-800 hover:bg-blue-700 text-white px-5 py-2 rounded-lg text-sm font-semibold transition">
            Filtrar
        </button>
        <a href="{{ route('bitacora.index', ['curso_form' => $cursoForm]) }}"
           class="text-sm text-gray-500 hover:text-gray-700 px-2 py-2">Limpiar</a>
    </form>
</div>

<div class="bg-white rounded-xl shadow overflow-hidden">
    <table class="w-full text-sm">
        <thead>
            <tr class="bg-gray-50 border-b border-gray-200 text-left">
                <th class="px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide w-20">Código</th>
                <th class="px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Estudiante</th>
                <th class="px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide w-20">Curso</th>
                <th class="px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide w-28">Fecha</th>
                <th class="px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Motivo</th>
                <th class="px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Observación</th>
                <th class="px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide w-24"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($entradas as $e)
            <tr class="hover:bg-gray-50 align-top" x-data="{ edit: false }">
                <td class="px-4 py-3 font-mono text-gray-500 text-xs pt-4">{{ $e->codigo_alumno }}</td>
                <td class="px-4 py-3 pt-4 font-medium text-gray-800">{{ $e->nombre_alumno ?: '—' }}</td>
                <td class="px-4 py-3 pt-4 text-gray-600">{{ $e->CURSO ?: '—' }}</td>
                <td class="px-4 py-3 pt-4 text-gray-600 whitespace-nowrap">{{ \Carbon\Carbon::parse($e->fecha)->locale('es')->isoFormat('D MMM YYYY') }}</td>
                <td class="px-4 py-3 pt-4">
                    <span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold {{ $badge($e->categoria_color) }}">{{ $e->categoria }}</span>
                </td>
                <td class="px-4 py-3 text-gray-700">
                    @if(!empty($e->materia))
                    <div x-show="!edit" class="text-xs font-semibold text-gray-500 mb-0.5">📘 {{ $e->materia }}</div>
                    @endif
                    <div x-show="!edit" class="whitespace-pre-line">{{ $e->observacion }}</div>
                    @if(!empty($e->registrado_nombre) || !empty($e->registrado_por))
                    <div x-show="!edit" class="text-xs text-gray-400 mt-1">Por: {{ $e->registrado_nombre ?: $e->registrado_por }}</div>
                    @endif
                    {{-- Formulario de edición inline --}}
                    <form x-show="edit" x-cloak method="POST" action="{{ route('bitacora.update', $e->id) }}" class="space-y-2">
                        @csrf @method('PUT')
                        <div class="flex gap-2 flex-wrap">
                            <select name="categoria_id" required class="border border-gray-300 rounded-lg px-2 py-1 text-xs">
                                @foreach($todasCategorias as $cat)
                                <option value="{{ $cat->id }}" @selected($cat->id === $e->categoria_id)>{{ $cat->nombre }}</option>
                                @endforeach
                            </select>
                            <input type="date" name="fecha" value="{{ \Carbon\Carbon::parse($e->fecha)->toDateString() }}" required
                                class="border border-gray-300 rounded-lg px-2 py-1 text-xs">
                            @if($esDocente)
                            <select name="codigo_mat" required class="border border-gray-300 rounded-lg px-2 py-1 text-xs">
                                @foreach($materias as $mat)
                                <option value="{{ $mat->codigo_mat }}" @selected((int)$e->codigo_mat === (int)$mat->codigo_mat)>{{ $mat->nombre_mat }}</option>
                                @endforeach
                            </select>
                            @endif
                        </div>
                        <textarea name="observacion" rows="3" required maxlength="8000"
                            class="w-full border border-gray-300 rounded-lg px-2 py-1 text-xs">{{ $e->observacion }}</textarea>
                        <div class="flex gap-2">
                            <button type="submit" class="bg-blue-700 hover:bg-blue-800 text-white px-3 py-1 rounded-lg text-xs font-semibold">Guardar</button>
                            <button type="button" @click="edit=false" class="text-gray-500 text-xs">Cancelar</button>
                        </div>
                    </form>
                </td>
                <td class="px-4 py-3 pt-4 whitespace-nowrap">
                    <button type="button" @click="edit=!edit" class="text-blue-600 hover:text-blue-800 text-xs font-semibold">Editar</button>
                    <form method="POST" action="{{ route('bitacora.destroy', $e->id) }}" class="inline"
                          onsubmit="return confirm('¿Eliminar esta observación?');">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-red-500 hover:text-red-700 text-xs font-semibold ml-2">Eliminar</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="px-4 py-8 text-center text-gray-400 text-sm">No hay observaciones registradas con estos filtros.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@endsection
