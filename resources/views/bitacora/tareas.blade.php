@extends('layouts.app-sidebar')

@section('header', 'Agenda Estudiantil Virtual · Tarea a un curso/grupo')

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

{{-- Paso 1: elegir la asignación (materia + curso/grupo) y la fecha --}}
<div class="bg-white rounded-xl shadow p-5 mb-6">
    <h2 class="text-base font-bold text-gray-800 mb-1">📝 Dejar una tarea a un curso/grupo</h2>
    <p class="text-xs text-gray-400 mb-4">Elige la <strong>materia y curso/grupo</strong> (incluye grupos de proyecto y subgrupos de Artes/Música). Escribes <strong>una sola vez</strong> la tarea y se registra igual para todos los estudiantes del grupo; cada familia confirma su lectura de forma independiente.</p>

    @if($asignaciones->isEmpty())
    <p class="text-sm text-amber-600">No tienes asignaciones registradas en el sistema.</p>
    @elseif($categorias->isEmpty())
    <p class="text-sm text-amber-600">No hay categorías de tarea configuradas. Pide al administrador que marque una categoría como "Tarea".</p>
    @else
    <form method="GET" action="{{ route('bitacora.tareas') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
        <div class="md:col-span-2">
            <label class="block text-xs font-medium text-gray-500 mb-1">Materia · Curso / Grupo</label>
            <select name="asignacion" onchange="this.form.submit()" required
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                <option value="">-- Selecciona --</option>
                @foreach($asignaciones as $a)
                @php $val = $a->codigo_mat.'|'.$a->curso; @endphp
                <option value="{{ $val }}" @selected($asignacionSel === $val)>{{ $a->nombre_mat }} · {{ $a->curso }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Fecha</label>
            <input type="date" name="fecha" value="{{ $fecha }}" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
        </div>
    </form>
    @endif
</div>

@if($asignacionValida && $estudiantes->isEmpty())
<div class="bg-white rounded-xl shadow p-8 text-center text-gray-400 text-sm">El grupo seleccionado no tiene estudiantes matriculados.</div>
@elseif($asignacionValida && $estudiantes->isNotEmpty())

<form method="POST" action="{{ route('bitacora.tareas.guardar') }}"
      x-data="{
          categoria: '{{ $categorias->count() === 1 ? $categorias->first()->id : '' }}',
          texto: '',
          plantillas: @js($plantillas),
          filtradas() { return this.plantillas.filter(p => !p.categoria_id || String(p.categoria_id) === String(this.categoria)); },
          aplicar(e) {
              const p = this.plantillas.find(x => String(x.id) === String(e.target.value));
              if (p) this.texto = this.texto ? (this.texto + '\n' + p.texto) : p.texto;
              e.target.value = '';
          }
      }">
    @csrf
    <input type="hidden" name="asignacion" value="{{ $asignacionSel }}">
    <input type="hidden" name="fecha" value="{{ $fecha }}">

    <div class="bg-white rounded-xl shadow p-5 mb-5">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Categoría</label>
                <select name="categoria_id" x-model="categoria" required
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <option value="">-- Selecciona --</option>
                    @foreach($categorias as $cat)
                    <option value="{{ $cat->id }}">{{ $cat->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end">
                <p class="text-xs text-gray-500">Se aplicará a <strong>{{ $estudiantes->count() }}</strong> estudiante(s) ·
                    {{ \Carbon\Carbon::parse($fecha)->locale('es')->isoFormat('D MMM YYYY') }}</p>
            </div>
        </div>

        <div>
            <div class="flex items-center justify-between mb-1">
                <label class="block text-xs font-medium text-gray-500">Tarea (texto único para todo el grupo)</label>
                <template x-if="filtradas().length">
                    <select @change="aplicar($event)"
                        class="border border-gray-200 rounded-lg px-2 py-1 text-xs text-gray-600">
                        <option value="">＋ Insertar plantilla…</option>
                        <template x-for="p in filtradas()" :key="p.id">
                            <option :value="p.id" x-text="p.texto.length > 70 ? p.texto.slice(0,70)+'…' : p.texto"></option>
                        </template>
                    </select>
                </template>
            </div>
            <textarea name="observacion" x-model="texto" rows="4" required maxlength="8000"
                placeholder="Describe la tarea para el grupo…"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"></textarea>
        </div>
    </div>

    {{-- Lista del grupo (solo lectura): la tarea se asigna a todos --}}
    <div class="bg-white rounded-xl shadow overflow-hidden mb-5">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-200 text-left">
                    <th class="px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide w-24">Código</th>
                    <th class="px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Estudiante</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($estudiantes as $est)
                @php $nom = preg_replace('/\s+/', ' ', trim(implode(' ', array_filter([$est->NOMBRE1, $est->NOMBRE2, $est->APELLIDO1, $est->APELLIDO2])))); @endphp
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-mono text-gray-500 text-xs">{{ $est->CODIGO }}</td>
                    <td class="px-4 py-3 font-medium text-gray-800">{{ $nom }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <button type="submit" class="bg-blue-700 hover:bg-blue-800 text-white px-6 py-2.5 rounded-lg text-sm font-semibold transition shadow">
        💾 Registrar tarea al grupo
    </button>
</form>

@endif

@endsection
