@extends('layouts.app-sidebar')

@section('header', 'Agenda Estudiantil Virtual · Configuración')

@section('slot')

@php
    $ambitoLabel = ['academico' => 'Académico', 'convivencia' => 'Convivencia', 'general' => 'General'];
    $colores = ['blue', 'indigo', 'red', 'green', 'amber', 'gray'];
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

<div class="mb-4">
    <a href="{{ route('bitacora.index') }}" class="text-sm text-blue-700 hover:text-blue-900 font-medium">← Volver al registro</a>
</div>

{{-- ── Categorías ───────────────────────────────────────────────────────── --}}
<div class="bg-white rounded-xl shadow p-5 mb-8">
    <h2 class="text-base font-bold text-gray-800 mb-1">🏷️ Categorías / motivos</h2>
    <p class="text-xs text-gray-400 mb-4">El <strong>ámbito</strong> define qué perfil puede usar la categoría:
        Coordinación Académica (COR001) usa <em>Académico</em> y <em>General</em>; Coordinación de Convivencia (COR002) usa
        <em>Convivencia</em> y <em>General</em>; SuperAdmin usa todas.</p>

    {{-- Nueva categoría --}}
    <form method="POST" action="{{ route('bitacora.categorias.store') }}" class="flex gap-3 items-end flex-wrap mb-5 pb-5 border-b border-gray-100">
        @csrf
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Nombre</label>
            <input type="text" name="nombre" required maxlength="100" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Ámbito</label>
            <select name="ambito" required class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                @foreach($ambitoLabel as $val => $lbl)
                <option value="{{ $val }}">{{ $lbl }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Color</label>
            <select name="color" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                @foreach($colores as $col)
                <option value="{{ $col }}">{{ ucfirst($col) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Prioridad</label>
            <select name="prioridad" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                <option value="normal">Normal</option>
                <option value="alta">Alta</option>
            </select>
        </div>
        <label class="text-xs text-gray-600 flex items-center gap-1 pb-2">
            <input type="checkbox" name="docentes" value="1"> Disponible para docentes
        </label>
        <label class="text-xs text-gray-600 flex items-center gap-1 pb-2">
            <input type="checkbox" name="unica" value="1"> Registro único (no se repite)
        </label>
        <label class="text-xs text-gray-600 flex items-center gap-1 pb-2">
            <input type="checkbox" name="tarea" value="1"> Categoría de tarea
        </label>
        <button type="submit" class="bg-blue-700 hover:bg-blue-800 text-white px-5 py-2 rounded-lg text-sm font-semibold transition">＋ Agregar</button>
    </form>

    <table class="w-full text-sm">
        <thead>
            <tr class="bg-gray-50 border-b border-gray-200 text-left">
                <th class="px-3 py-2 text-xs font-semibold text-gray-500 uppercase">Categoría</th>
                <th class="px-3 py-2 text-xs font-semibold text-gray-500 uppercase">Ámbito</th>
                <th class="px-3 py-2 text-xs font-semibold text-gray-500 uppercase">Docentes</th>
                <th class="px-3 py-2 text-xs font-semibold text-gray-500 uppercase">Única</th>
                <th class="px-3 py-2 text-xs font-semibold text-gray-500 uppercase">Tarea</th>
                <th class="px-3 py-2 text-xs font-semibold text-gray-500 uppercase">Prioridad</th>
                <th class="px-3 py-2 text-xs font-semibold text-gray-500 uppercase">Estado</th>
                <th class="px-3 py-2 text-xs font-semibold text-gray-500 uppercase w-32"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($categorias as $cat)
            <tr x-data="{ edit: false }">
                <td class="px-3 py-2">
                    <span x-show="!edit" class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold {{ $badge($cat->color) }}">{{ $cat->nombre }}</span>
                    <form x-show="edit" x-cloak method="POST" action="{{ route('bitacora.categorias.update', $cat->id) }}" id="cat-{{ $cat->id }}" class="flex gap-2 flex-wrap items-center">
                        @csrf @method('PUT')
                        <input type="text" name="nombre" value="{{ $cat->nombre }}" required maxlength="100" class="border border-gray-300 rounded-lg px-2 py-1 text-xs">
                        <select name="ambito" class="border border-gray-300 rounded-lg px-2 py-1 text-xs">
                            @foreach($ambitoLabel as $val => $lbl)
                            <option value="{{ $val }}" @selected($cat->ambito === $val)>{{ $lbl }}</option>
                            @endforeach
                        </select>
                        <select name="color" class="border border-gray-300 rounded-lg px-2 py-1 text-xs">
                            @foreach($colores as $col)
                            <option value="{{ $col }}" @selected($cat->color === $col)>{{ ucfirst($col) }}</option>
                            @endforeach
                        </select>
                        <select name="prioridad" class="border border-gray-300 rounded-lg px-2 py-1 text-xs">
                            <option value="normal" @selected(($cat->prioridad ?? 'normal') === 'normal')>Prioridad normal</option>
                            <option value="alta" @selected(($cat->prioridad ?? 'normal') === 'alta')>Prioridad alta</option>
                        </select>
                        <label class="text-xs text-gray-600 flex items-center gap-1">
                            <input type="checkbox" name="docentes" value="1" @checked($cat->docentes)> Docentes
                        </label>
                        <label class="text-xs text-gray-600 flex items-center gap-1">
                            <input type="checkbox" name="unica" value="1" @checked($cat->unica)> Única
                        </label>
                        <label class="text-xs text-gray-600 flex items-center gap-1">
                            <input type="checkbox" name="tarea" value="1" @checked($cat->tarea ?? false)> Tarea
                        </label>
                        <label class="text-xs text-gray-600 flex items-center gap-1">
                            <input type="checkbox" name="activo" value="1" @checked($cat->activo)> Activa
                        </label>
                    </form>
                </td>
                <td class="px-3 py-2 text-gray-600">{{ $ambitoLabel[$cat->ambito] ?? $cat->ambito }}</td>
                <td class="px-3 py-2">{!! $cat->docentes ? '<span class="text-green-600 text-xs font-semibold">Sí</span>' : '<span class="text-gray-300 text-xs">—</span>' !!}</td>
                <td class="px-3 py-2">{!! $cat->unica ? '<span class="text-amber-600 text-xs font-semibold">Sí</span>' : '<span class="text-gray-300 text-xs">—</span>' !!}</td>
                <td class="px-3 py-2">{!! ($cat->tarea ?? false) ? '<span class="text-indigo-600 text-xs font-semibold">Sí</span>' : '<span class="text-gray-300 text-xs">—</span>' !!}</td>
                <td class="px-3 py-2">
                    @if(($cat->prioridad ?? 'normal') === 'alta')
                        <span class="text-red-600 text-xs font-bold">Alta</span>
                    @else
                        <span class="text-gray-400 text-xs">Normal</span>
                    @endif
                </td>
                <td class="px-3 py-2">
                    @if($cat->activo)
                        <span class="text-green-600 text-xs font-semibold">Activa</span>
                    @else
                        <span class="text-gray-400 text-xs">Inactiva</span>
                    @endif
                </td>
                <td class="px-3 py-2 whitespace-nowrap">
                    <button type="button" x-show="!edit" @click="edit=true" class="text-blue-600 hover:text-blue-800 text-xs font-semibold">Editar</button>
                    <button type="submit" form="cat-{{ $cat->id }}" x-show="edit" x-cloak class="text-blue-700 text-xs font-semibold">Guardar</button>
                    <button type="button" x-show="edit" x-cloak @click="edit=false" class="text-gray-500 text-xs ml-1">Cancelar</button>
                    <form method="POST" action="{{ route('bitacora.categorias.destroy', $cat->id) }}" class="inline" x-show="!edit"
                          onsubmit="return confirm('¿Eliminar la categoría? Si tiene observaciones asociadas solo se desactivará.');">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-red-500 hover:text-red-700 text-xs font-semibold ml-2">Eliminar</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr><td colspan="8" class="px-3 py-6 text-center text-gray-400 text-sm">No hay categorías.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- ── Plantillas ───────────────────────────────────────────────────────── --}}
<div class="bg-white rounded-xl shadow p-5">
    <h2 class="text-base font-bold text-gray-800 mb-1">💬 Plantillas de texto rápido</h2>
    <p class="text-xs text-gray-400 mb-4">Frases para autocompletar la observación. Si asocias una plantilla a una categoría, solo aparece al elegir esa categoría; sin categoría, aparece siempre.</p>

    <form method="POST" action="{{ route('bitacora.plantillas.store') }}" class="flex gap-3 items-end flex-wrap mb-5 pb-5 border-b border-gray-100">
        @csrf
        <div class="flex-1 min-w-[260px]">
            <label class="block text-xs font-medium text-gray-500 mb-1">Texto</label>
            <input type="text" name="texto" required maxlength="8000" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="Ej: El estudiante mostró un excelente desempeño durante el período.">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Categoría (opcional)</label>
            <select name="categoria_id" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                <option value="">Todas</option>
                @foreach($categorias as $cat)
                <option value="{{ $cat->id }}">{{ $cat->nombre }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="bg-blue-700 hover:bg-blue-800 text-white px-5 py-2 rounded-lg text-sm font-semibold transition">＋ Agregar</button>
    </form>

    <table class="w-full text-sm">
        <thead>
            <tr class="bg-gray-50 border-b border-gray-200 text-left">
                <th class="px-3 py-2 text-xs font-semibold text-gray-500 uppercase">Texto</th>
                <th class="px-3 py-2 text-xs font-semibold text-gray-500 uppercase w-44">Categoría</th>
                <th class="px-3 py-2 text-xs font-semibold text-gray-500 uppercase w-32"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($plantillas as $p)
            <tr x-data="{ edit: false }">
                <td class="px-3 py-2 text-gray-700">
                    <span x-show="!edit">{{ $p->texto }}</span>
                    <form x-show="edit" x-cloak method="POST" action="{{ route('bitacora.plantillas.update', $p->id) }}" id="pl-{{ $p->id }}" class="flex gap-2 flex-wrap items-center">
                        @csrf @method('PUT')
                        <input type="text" name="texto" value="{{ $p->texto }}" required maxlength="8000" class="flex-1 min-w-[240px] border border-gray-300 rounded-lg px-2 py-1 text-xs">
                        <select name="categoria_id" class="border border-gray-300 rounded-lg px-2 py-1 text-xs">
                            <option value="">Todas</option>
                            @foreach($categorias as $cat)
                            <option value="{{ $cat->id }}" @selected($p->categoria_id === $cat->id)>{{ $cat->nombre }}</option>
                            @endforeach
                        </select>
                        <label class="text-xs text-gray-600 flex items-center gap-1">
                            <input type="checkbox" name="activo" value="1" @checked($p->activo)> Activa
                        </label>
                    </form>
                </td>
                <td class="px-3 py-2 text-gray-600">{{ $p->categoria_nombre ?: 'Todas' }}</td>
                <td class="px-3 py-2 whitespace-nowrap">
                    <button type="button" x-show="!edit" @click="edit=true" class="text-blue-600 hover:text-blue-800 text-xs font-semibold">Editar</button>
                    <button type="submit" form="pl-{{ $p->id }}" x-show="edit" x-cloak class="text-blue-700 text-xs font-semibold">Guardar</button>
                    <button type="button" x-show="edit" x-cloak @click="edit=false" class="text-gray-500 text-xs ml-1">Cancelar</button>
                    <form method="POST" action="{{ route('bitacora.plantillas.destroy', $p->id) }}" class="inline" x-show="!edit"
                          onsubmit="return confirm('¿Eliminar la plantilla?');">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-red-500 hover:text-red-700 text-xs font-semibold ml-2">Eliminar</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr><td colspan="3" class="px-3 py-6 text-center text-gray-400 text-sm">No hay plantillas.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

@endsection
