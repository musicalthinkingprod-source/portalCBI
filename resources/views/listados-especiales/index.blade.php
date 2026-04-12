@extends('layouts.app-sidebar')

@section('header', 'Listados Especiales')

@section('slot')

@if(session('success'))
<div class="mb-4 bg-green-100 border border-green-400 text-green-800 px-4 py-3 rounded">
    {{ session('success') }}
</div>
@endif
@if($errors->any())
<div class="mb-4 bg-red-100 border border-red-400 text-red-800 px-4 py-3 rounded">
    @foreach($errors->all() as $e)<p>{{ $e }}</p>@endforeach
</div>
@endif

{{-- TABS --}}
<div class="mb-6 border-b border-gray-200">
    <nav class="-mb-px flex space-x-1">
        <a href="{{ route('listados.index', ['tab' => 'proyectos']) }}"
           class="px-5 py-3 border-b-2 font-medium text-sm transition
                  {{ $tab === 'proyectos' ? 'border-blue-600 text-blue-700' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
            🔬 Proyectos
        </a>
        <a href="{{ route('listados.index', ['tab' => 'musica']) }}"
           class="px-5 py-3 border-b-2 font-medium text-sm transition
                  {{ $tab === 'musica' ? 'border-blue-600 text-blue-700' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
            🎵 Música y Artes
        </a>
    </nav>
</div>


{{-- ═══════════════════════ TAB PROYECTOS ═══════════════════════ --}}
@if($tab === 'proyectos')

@php
    $totalAsignados = array_sum(array_map(fn($v) => $v->count(), $estudiantesProyecto));
    $sinDocente = $gruposProyecto->filter(fn($g) => !$g->CODIGO_DOC)->count();
@endphp

{{-- Barra superior: estadísticas + Crear grupo --}}
<div class="flex flex-wrap items-start justify-between gap-4 mb-5">

    {{-- Stats --}}
    <div class="flex flex-wrap gap-4 text-sm text-gray-600 pt-1">
        <span>Grupos: <strong>{{ $gruposProyecto->count() }}</strong></span>
        <span>Asignados: <strong>{{ $totalAsignados }}</strong></span>
        <span class="text-orange-600">Sin proyecto: <strong>{{ $sinProyecto->count() }}</strong></span>
        @if($sinDocente > 0)
        <span class="text-red-600">Sin docente: <strong>{{ $sinDocente }}</strong></span>
        @endif
    </div>

    {{-- Formulario crear grupo --}}
    <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-4 min-w-[320px]">
        <p class="text-xs font-semibold text-gray-700 mb-3 flex items-center gap-1">
            ➕ Crear nuevo grupo de proyecto
        </p>
        <form method="POST" action="{{ route('listados.grupo.crear') }}"
              class="flex flex-wrap items-end gap-2">
            @csrf
            <div>
                <label class="block text-xs text-gray-500 mb-1">Nombre del grupo</label>
                <input type="text" name="grupo"
                       value="{{ old('grupo', $siguienteGrupo) }}"
                       placeholder="GP14"
                       pattern="GP\d+"
                       title="Formato: GP seguido de número (GP1, GP14…)"
                       class="border border-gray-300 rounded px-2 py-1.5 text-sm w-24 uppercase">
            </div>
            <div class="flex-1 min-w-[160px]">
                <label class="block text-xs text-gray-500 mb-1">Docente (opcional)</label>
                <select name="codigo_doc"
                        class="w-full border border-gray-300 rounded px-2 py-1.5 text-sm">
                    <option value="">— Sin asignar aún —</option>
                    @foreach($docentesActivos as $doc)
                    <option value="{{ $doc->CODIGO_DOC }}">{{ $doc->NOMBRE_DOC }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit"
                    class="bg-blue-600 hover:bg-blue-700 text-white text-sm px-4 py-1.5 rounded h-[34px]">
                Crear
            </button>
        </form>
    </div>
</div>

{{-- Panel sin proyecto (asignación masiva) --}}
@if($sinProyecto->count() > 0)
<div class="bg-orange-50 border border-orange-200 rounded-lg mb-5 overflow-hidden">
    <button type="button" onclick="toggleGrupo('panel-sin-proyecto')"
            class="w-full flex items-center justify-between px-5 py-3 text-left hover:bg-orange-100 transition">
        <span class="text-sm font-semibold text-orange-800">
            ⚠️ {{ $sinProyecto->count() }} estudiante(s) sin proyecto — clic para asignar
        </span>
        <span id="chevron-panel-sin-proyecto" class="text-orange-500 transition-transform">▼</span>
    </button>
    <div id="panel-sin-proyecto" class="hidden border-t border-orange-200 p-4">

        <div class="mb-3 flex items-center gap-2">
            <input type="text" id="filtro-sin-proyecto"
                   placeholder="Filtrar por nombre o curso..."
                   oninput="filtrarFilas('filtro-sin-proyecto', 'tabla-sin-proyecto')"
                   class="border border-gray-300 rounded px-3 py-1.5 text-sm w-64">
            <span class="text-xs text-gray-400">Filtra la lista de abajo</span>
        </div>

        <div class="overflow-x-auto max-h-[420px] overflow-y-auto">
            <table id="tabla-sin-proyecto" class="w-full text-sm">
                <thead class="sticky top-0 bg-orange-50">
                    <tr class="text-xs text-gray-500 border-b border-orange-200">
                        <th class="text-left py-1.5 font-medium pr-4">Estudiante</th>
                        <th class="text-center py-1.5 font-medium w-16">Curso</th>
                        <th class="text-right py-1.5 font-medium">Asignar a proyecto</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($sinProyecto as $sp)
                    <tr class="border-b border-orange-100 hover:bg-orange-50"
                        data-fila="{{ strtolower($sp->APELLIDO1 . ' ' . $sp->APELLIDO2 . ' ' . $sp->NOMBRE1 . ' ' . $sp->NOMBRE2 . ' ' . $sp->CURSO) }}">
                        <td class="py-1.5 pr-4">
                            {{ $sp->APELLIDO1 }} {{ $sp->APELLIDO2 }}, {{ $sp->NOMBRE1 }} {{ $sp->NOMBRE2 }}
                        </td>
                        <td class="text-center py-1.5">
                            <span class="text-xs bg-gray-100 px-2 py-0.5 rounded">{{ $sp->CURSO }}</span>
                        </td>
                        <td class="text-right py-1.5">
                            <form method="POST" action="{{ route('listados.proyecto.asignar') }}"
                                  class="inline-flex items-center gap-1">
                                @csrf
                                <input type="hidden" name="codigo_alum" value="{{ $sp->CODIGO }}">
                                <select name="grupo"
                                        class="text-xs border border-gray-300 rounded px-1.5 py-0.5 bg-white">
                                    @foreach($gruposProyecto as $gp)
                                    <option value="{{ $gp->grupo }}">
                                        {{ $gp->grupo }}{{ $gp->NOMBRE_DOC ? ' — ' . explode(' ', $gp->NOMBRE_DOC)[0] . ' ' . (explode(' ', $gp->NOMBRE_DOC)[count(explode(' ', $gp->NOMBRE_DOC))-1] ?? '') : '' }}
                                    </option>
                                    @endforeach
                                </select>
                                <button type="submit"
                                        class="text-xs bg-blue-600 hover:bg-blue-700 text-white px-2 py-0.5 rounded">
                                    Asignar
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@else
<div class="bg-green-50 border border-green-200 rounded-lg p-3 mb-5">
    <p class="text-sm font-semibold text-green-800">✅ Todos los estudiantes matriculados tienen proyecto asignado.</p>
</div>
@endif

{{-- Lista de grupos --}}
@forelse($gruposProyecto as $gp)
@php
    $ests  = $estudiantesProyecto[$gp->grupo] ?? collect();
    $count = $ests->count();
    $sinDoc = !$gp->CODIGO_DOC;
@endphp
<div class="bg-white rounded-lg shadow mb-3 overflow-hidden">

    <button type="button" onclick="toggleGrupo('gp-{{ $gp->grupo }}')"
            class="w-full flex items-center justify-between px-5 py-4 text-left hover:bg-gray-50 transition">
        <div class="flex items-center gap-4 min-w-0">
            <span class="shrink-0 inline-block bg-blue-600 text-white text-xs font-bold px-3 py-1 rounded-full min-w-[48px] text-center">
                {{ $gp->grupo }}
            </span>
            @if($sinDoc)
                <span class="text-orange-500 text-sm italic">Sin docente asignado</span>
            @else
                <span class="font-medium text-gray-800 truncate">{{ $gp->NOMBRE_DOC }}</span>
                <span class="text-xs text-gray-400 shrink-0">({{ $gp->CODIGO_DOC }})</span>
            @endif
        </div>
        <div class="flex items-center gap-3 shrink-0 ml-4">
            <span class="text-sm text-gray-500">{{ $count }} est.</span>
            @if($count === 0)
            <form method="POST" action="{{ route('listados.grupo.eliminar') }}" class="inline"
                  onclick="event.stopPropagation()">
                @csrf
                <input type="hidden" name="grupo" value="{{ $gp->grupo }}">
                <button type="submit"
                        onclick="return confirm('¿Eliminar el grupo {{ $gp->grupo }}? (está vacío)')"
                        class="text-xs bg-red-100 text-red-600 hover:bg-red-200 px-2 py-0.5 rounded">
                    Eliminar
                </button>
            </form>
            @endif
            <span id="chevron-gp-{{ $gp->grupo }}" class="text-gray-400 transition-transform duration-200">▼</span>
        </div>
    </button>

    <div id="gp-{{ $gp->grupo }}" class="hidden border-t border-gray-100">
        <div class="p-4">

            {{-- Asignar / cambiar docente --}}
            <div class="mb-4 p-3 {{ $sinDoc ? 'bg-orange-50 border border-orange-200' : 'bg-gray-50' }} rounded">
                <p class="text-xs font-semibold {{ $sinDoc ? 'text-orange-700' : 'text-gray-600' }} mb-2">
                    {{ $sinDoc ? '⚠️ Asignar docente' : '🔄 Cambiar docente' }}
                </p>
                <form method="POST" action="{{ route('listados.docente.asignar') }}"
                      class="flex flex-wrap items-center gap-2">
                    @csrf
                    <input type="hidden" name="grupo" value="{{ $gp->grupo }}">
                    <select name="codigo_doc"
                            class="border border-gray-300 rounded px-2 py-1.5 text-sm flex-1 min-w-[180px]">
                        <option value="">— Seleccionar docente —</option>
                        @foreach($docentesActivos as $doc)
                        <option value="{{ $doc->CODIGO_DOC }}"
                                {{ $gp->CODIGO_DOC === $doc->CODIGO_DOC ? 'selected' : '' }}>
                            {{ $doc->NOMBRE_DOC }}
                        </option>
                        @endforeach
                    </select>
                    <button type="submit"
                            class="{{ $sinDoc ? 'bg-orange-600 hover:bg-orange-700' : 'bg-gray-600 hover:bg-gray-700' }} text-white text-sm px-4 py-1.5 rounded">
                        {{ $sinDoc ? 'Asignar' : 'Cambiar' }}
                    </button>
                </form>
            </div>

            {{-- Lista de estudiantes del grupo --}}
            @if($ests->isEmpty())
            <p class="text-sm text-gray-400 italic mb-3">Grupo vacío. Los estudiantes se asignan desde el panel naranja de arriba.</p>
            @else
            <table class="w-full text-sm mb-4">
                <thead>
                    <tr class="text-xs text-gray-500 border-b">
                        <th class="text-left py-1 font-medium">Estudiante</th>
                        <th class="text-center py-1 font-medium w-16">Curso</th>
                        <th class="text-right py-1 font-medium">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($ests as $est)
                    <tr class="border-b border-gray-50 hover:bg-gray-50">
                        <td class="py-1.5">
                            {{ $est->APELLIDO1 }} {{ $est->APELLIDO2 }},
                            {{ $est->NOMBRE1 }} {{ $est->NOMBRE2 }}
                        </td>
                        <td class="text-center py-1.5">
                            <span class="text-xs bg-gray-100 px-2 py-0.5 rounded">{{ $est->CURSO_ALUM }}</span>
                        </td>
                        <td class="text-right py-1.5">
                            <div class="flex items-center justify-end gap-2">
                                @if($gruposProyecto->count() > 1)
                                <form method="POST" action="{{ route('listados.proyecto.asignar') }}"
                                      class="inline-flex items-center gap-1">
                                    @csrf
                                    <input type="hidden" name="codigo_alum" value="{{ $est->CODIGO }}">
                                    <select name="grupo"
                                            class="text-xs border border-gray-300 rounded px-1.5 py-0.5 bg-white">
                                        @foreach($gruposProyecto as $otro)
                                            @if($otro->grupo !== $gp->grupo)
                                            <option value="{{ $otro->grupo }}">{{ $otro->grupo }}</option>
                                            @endif
                                        @endforeach
                                    </select>
                                    <button type="submit"
                                            class="text-xs bg-blue-100 text-blue-700 hover:bg-blue-200 px-2 py-0.5 rounded">
                                        Mover
                                    </button>
                                </form>
                                @endif
                                <form method="POST" action="{{ route('listados.quitar') }}" class="inline">
                                    @csrf
                                    <input type="hidden" name="codigo_alum" value="{{ $est->CODIGO }}">
                                    <input type="hidden" name="grupo" value="{{ $gp->grupo }}">
                                    <input type="hidden" name="tab" value="proyectos">
                                    <button type="submit"
                                            onclick="return confirm('¿Quitar a este estudiante de {{ $gp->grupo }}?')"
                                            class="text-xs bg-red-100 text-red-700 hover:bg-red-200 px-2 py-0.5 rounded">
                                        Quitar
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @endif

        </div>
    </div>
</div>
@empty
<div class="bg-yellow-50 border border-yellow-300 text-yellow-800 p-4 rounded text-sm">
    No hay grupos de proyecto. Usa el formulario de arriba para crear el primero.
</div>
@endforelse


{{-- ═══════════════════════ TAB MÚSICA Y ARTES ═══════════════════════ --}}
@else

<div class="bg-white rounded-lg shadow p-4 mb-6">
    <form method="GET" action="{{ route('listados.index') }}" class="flex flex-wrap items-center gap-3">
        <input type="hidden" name="tab" value="musica">
        <label class="text-sm font-medium text-gray-700">Curso (grado 7 en adelante):</label>
        <select name="curso_musica" onchange="this.form.submit()"
                class="border border-gray-300 rounded px-3 py-1.5 text-sm">
            <option value="">— Seleccionar —</option>
            @foreach($todosLosCursos as $c)
            <option value="{{ $c }}" {{ $cursoMusica === $c ? 'selected' : '' }}>Grado {{ $c }}</option>
            @endforeach
        </select>
        <span class="text-xs text-gray-400">Sufijo <strong>-1</strong> = Artes · <strong>-2</strong> = Música</span>
    </form>
</div>

@if($cursoMusica)
@php
    $grupoArtes  = $cursoMusica . '-1';
    $grupoMusica = $cursoMusica . '-2';
    $totalCurso  = $estudiantesArtes->count() + $estudiantesMusica->count() + $sinAsignarMusica->count();
@endphp

<div class="mb-4 flex flex-wrap gap-6 text-sm text-gray-600">
    <span>Curso: <strong>{{ $cursoMusica }}</strong></span>
    <span>Total: <strong>{{ $totalCurso }}</strong></span>
    <span class="text-purple-700">🎨 Artes: <strong>{{ $estudiantesArtes->count() }}</strong></span>
    <span class="text-indigo-700">🎵 Música: <strong>{{ $estudiantesMusica->count() }}</strong></span>
    @if($sinAsignarMusica->count() > 0)
    <span class="text-orange-600">⚠️ Sin asignar: <strong>{{ $sinAsignarMusica->count() }}</strong></span>
    @endif
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-4">

    {{-- ARTES --}}
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="bg-purple-600 text-white px-4 py-3">
            <h3 class="font-semibold text-sm">🎨 Artes ({{ $grupoArtes }})</h3>
            <p class="text-xs text-purple-200">{{ $estudiantesArtes->count() }} estudiantes</p>
        </div>
        <div class="p-3 space-y-1.5 max-h-[520px] overflow-y-auto">
            @forelse($estudiantesArtes as $est)
            <div class="flex items-center justify-between text-xs bg-purple-50 rounded px-2 py-1.5">
                <span class="truncate mr-2">{{ $est->APELLIDO1 }} {{ $est->APELLIDO2 }}, {{ $est->NOMBRE1 }}</span>
                <div class="flex gap-1 shrink-0">
                    <form method="POST" action="{{ route('listados.musica.asignar') }}">
                        @csrf
                        <input type="hidden" name="codigo_alum" value="{{ $est->CODIGO }}">
                        <input type="hidden" name="curso" value="{{ $cursoMusica }}">
                        <input type="hidden" name="tipo" value="2">
                        <button type="submit" title="Mover a Música"
                                class="bg-indigo-100 text-indigo-700 hover:bg-indigo-200 px-1.5 py-0.5 rounded">→🎵</button>
                    </form>
                    <form method="POST" action="{{ route('listados.quitar') }}">
                        @csrf
                        <input type="hidden" name="codigo_alum" value="{{ $est->CODIGO }}">
                        <input type="hidden" name="grupo" value="{{ $grupoArtes }}">
                        <input type="hidden" name="tab" value="musica">
                        <input type="hidden" name="curso_musica" value="{{ $cursoMusica }}">
                        <button type="submit" title="Quitar"
                                onclick="return confirm('¿Quitar del listado de artes?')"
                                class="bg-red-100 text-red-600 hover:bg-red-200 px-1.5 py-0.5 rounded">✕</button>
                    </form>
                </div>
            </div>
            @empty
            <p class="text-xs text-gray-400 italic p-2">Sin estudiantes.</p>
            @endforelse
        </div>
    </div>

    {{-- SIN ASIGNAR --}}
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="bg-gray-500 text-white px-4 py-3">
            <h3 class="font-semibold text-sm">Sin asignar</h3>
            <p class="text-xs text-gray-200">{{ $sinAsignarMusica->count() }} estudiantes</p>
        </div>
        <div class="p-3 space-y-1.5 max-h-[520px] overflow-y-auto">
            @forelse($sinAsignarMusica as $est)
            <div class="flex items-center justify-between text-xs bg-gray-50 rounded px-2 py-1.5">
                <span class="truncate mr-2">{{ $est->APELLIDO1 }} {{ $est->APELLIDO2 }}, {{ $est->NOMBRE1 }}</span>
                <div class="flex gap-1 shrink-0">
                    <form method="POST" action="{{ route('listados.musica.asignar') }}">
                        @csrf
                        <input type="hidden" name="codigo_alum" value="{{ $est->CODIGO }}">
                        <input type="hidden" name="curso" value="{{ $cursoMusica }}">
                        <input type="hidden" name="tipo" value="1">
                        <button type="submit" title="Asignar a Artes"
                                class="bg-purple-100 text-purple-700 hover:bg-purple-200 px-1.5 py-0.5 rounded">🎨</button>
                    </form>
                    <form method="POST" action="{{ route('listados.musica.asignar') }}">
                        @csrf
                        <input type="hidden" name="codigo_alum" value="{{ $est->CODIGO }}">
                        <input type="hidden" name="curso" value="{{ $cursoMusica }}">
                        <input type="hidden" name="tipo" value="2">
                        <button type="submit" title="Asignar a Música"
                                class="bg-indigo-100 text-indigo-700 hover:bg-indigo-200 px-1.5 py-0.5 rounded">🎵</button>
                    </form>
                </div>
            </div>
            @empty
            <p class="text-xs text-green-600 italic p-2">✅ Todos asignados.</p>
            @endforelse
        </div>
    </div>

    {{-- MÚSICA --}}
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="bg-indigo-600 text-white px-4 py-3">
            <h3 class="font-semibold text-sm">🎵 Música ({{ $grupoMusica }})</h3>
            <p class="text-xs text-indigo-200">{{ $estudiantesMusica->count() }} estudiantes</p>
        </div>
        <div class="p-3 space-y-1.5 max-h-[520px] overflow-y-auto">
            @forelse($estudiantesMusica as $est)
            <div class="flex items-center justify-between text-xs bg-indigo-50 rounded px-2 py-1.5">
                <span class="truncate mr-2">{{ $est->APELLIDO1 }} {{ $est->APELLIDO2 }}, {{ $est->NOMBRE1 }}</span>
                <div class="flex gap-1 shrink-0">
                    <form method="POST" action="{{ route('listados.musica.asignar') }}">
                        @csrf
                        <input type="hidden" name="codigo_alum" value="{{ $est->CODIGO }}">
                        <input type="hidden" name="curso" value="{{ $cursoMusica }}">
                        <input type="hidden" name="tipo" value="1">
                        <button type="submit" title="Mover a Artes"
                                class="bg-purple-100 text-purple-700 hover:bg-purple-200 px-1.5 py-0.5 rounded">→🎨</button>
                    </form>
                    <form method="POST" action="{{ route('listados.quitar') }}">
                        @csrf
                        <input type="hidden" name="codigo_alum" value="{{ $est->CODIGO }}">
                        <input type="hidden" name="grupo" value="{{ $grupoMusica }}">
                        <input type="hidden" name="tab" value="musica">
                        <input type="hidden" name="curso_musica" value="{{ $cursoMusica }}">
                        <button type="submit" title="Quitar"
                                onclick="return confirm('¿Quitar del listado de música?')"
                                class="bg-red-100 text-red-600 hover:bg-red-200 px-1.5 py-0.5 rounded">✕</button>
                    </form>
                </div>
            </div>
            @empty
            <p class="text-xs text-gray-400 italic p-2">Sin estudiantes.</p>
            @endforelse
        </div>
    </div>

</div>

@else
<div class="bg-blue-50 border border-blue-200 text-blue-700 p-5 rounded-lg text-sm">
    Selecciona un curso para ver y gestionar sus listados de Música y Artes.
    <br><span class="text-xs text-blue-500 mt-1 block">Los grados 1–6 toman música y artes juntas (sin listado especial).</span>
</div>
@endif

@endif {{-- fin tabs --}}

@endsection

@push('scripts')
<script>
function toggleGrupo(id) {
    const el = document.getElementById(id);
    const ch = document.getElementById('chevron-' + id);
    if (!el) return;
    const oculto = el.classList.contains('hidden');
    el.classList.toggle('hidden', !oculto);
    if (ch) ch.style.transform = oculto ? 'rotate(180deg)' : '';
}

function filtrarFilas(inputId, tablaId) {
    const texto = document.getElementById(inputId).value.toLowerCase();
    const filas = document.querySelectorAll('#' + tablaId + ' tbody tr');
    filas.forEach(tr => {
        const dato = tr.getAttribute('data-fila') || '';
        tr.style.display = dato.includes(texto) ? '' : 'none';
    });
}
</script>
@endpush
