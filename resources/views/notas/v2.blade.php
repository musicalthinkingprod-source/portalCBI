@extends('layouts.app-sidebar')

@section('header', 'Planilla Ponderada')

@section('slot')

@php
    $categorias = [
        'C' => ['label' => 'Cognitivo',     'peso' => '20%', 'color' => 'purple',
                'bg' => 'bg-purple-700', 'badge' => 'bg-purple-100 text-purple-800', 'ring' => 'focus:ring-purple-400'],
        'P' => ['label' => 'Procedimental', 'peso' => '70%', 'color' => 'blue',
                'bg' => 'bg-blue-700', 'badge' => 'bg-blue-100 text-blue-800', 'ring' => 'focus:ring-blue-400'],
        'A' => ['label' => 'Actitudinal',   'peso' => '10%', 'color' => 'green',
                'bg' => 'bg-green-700', 'badge' => 'bg-green-100 text-green-800', 'ring' => 'focus:ring-green-400'],
    ];
    $columnasPorCat = ['C' => collect(), 'P' => collect(), 'A' => collect()];
    foreach ($columnas as $col) {
        $columnasPorCat[$col->categoria][] = $col;
    }

    // Detectar modo por categoría: 'decimal' si algún peso < 1, 'entero' si algún peso > 1, null si indefinido
    $modoPorCat = [];
    $pesosPorCat = []; // para JS
    foreach (['P','C','A'] as $cat) {
        $ps = collect($columnasPorCat[$cat])->map(fn($c) => $c->peso)->filter()->map(fn($p) => (float)$p);
        $pesosPorCat[$cat] = $ps->values()->toArray();
        if ($ps->contains(fn($p) => $p < 1))       $modoPorCat[$cat] = 'decimal';
        elseif ($ps->contains(fn($p) => $p > 1))   $modoPorCat[$cat] = 'entero';
        else                                         $modoPorCat[$cat] = null;
    }
@endphp

    {{-- Selector --}}
    <div class="bg-white rounded-xl shadow p-5 mb-6">
        <form method="GET" action="{{ route('notas.v2.index') }}" id="form-filtros">
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 items-end">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Materia</label>
                    <select name="materia" id="sel-materia" data-remember="notasv2_materia"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">— Selecciona —</option>
                        @foreach($materias as $mat)
                            <option value="{{ $mat->CODIGO_MAT }}" {{ $matSelec == $mat->CODIGO_MAT ? 'selected' : '' }}>
                                {{ $mat->NOMBRE_MAT }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Curso</label>
                    <select name="curso" id="sel-curso" data-remember="notasv2_curso"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                        {{ !$matSelec ? 'disabled' : '' }}>
                        <option value="">— Selecciona —</option>
                        @foreach($cursosDisponibles as $c)
                            <option value="{{ $c->CURSO }}" {{ $cursoSelec == $c->CURSO ? 'selected' : '' }}>
                                {{ $c->CURSO }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Período</label>
                    <select name="periodo" data-remember="notasv2_periodo"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @foreach([1,2,3,4] as $p)
                            <option value="{{ $p }}" {{ $periodo == $p ? 'selected' : '' }}>Período {{ $p }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <button type="submit"
                        class="w-full bg-blue-800 hover:bg-blue-700 text-white font-semibold text-sm px-4 py-2 rounded-lg transition">
                        Cargar planilla
                    </button>
                </div>
            </div>
        </form>
    </div>

    @if(session('success'))
        <div class="mb-4 p-3 bg-green-100 text-green-800 rounded-xl text-sm">✅ {{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-4 p-3 bg-red-100 text-red-800 rounded-xl text-sm">⚠️ {{ session('error') }}</div>
    @endif
    @if(session('error_entrega'))
        <div class="mb-4 p-3 bg-red-100 text-red-800 rounded-xl text-sm">⚠️ {{ session('error_entrega') }}</div>
    @endif

    @if($matSelec && $cursoSelec)

    {{-- Leyenda de pesos --}}
    <div class="flex flex-wrap gap-3 mb-2">
        @foreach($categorias as $cat => $cfg)
        <span class="inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-1.5 rounded-full {{ $cfg['badge'] }}">
            {{ $cfg['label'] }} · {{ $cfg['peso'] }}
        </span>
        @endforeach
        <span class="text-xs text-gray-400 self-center ml-1">Nota final = P×0.70 + C×0.20 + A×0.10</span>
    </div>

    {{-- Tip: uso de porcentajes --}}
    <div class="mb-4 text-xs text-gray-500 bg-gray-50 border border-gray-200 rounded-lg px-4 py-2 leading-relaxed">
        💡 <strong>Dos formas de ponderar — escoge una sola por categoría:</strong><br>
        <span class="ml-4">① <strong>Por número de notas</strong>: ingresa enteros (1, 2, 3…). Una nota con peso 2 vale el doble que una con peso 1.</span><br>
        <span class="ml-4">② <strong>Por porcentaje</strong>: ingresa decimales entre 0 y 1 (p. ej. <strong>0.500</strong> para 50 %, <strong>0.300</strong> para 30 %, <strong>0.200</strong> para 20 %). La suma debe ser exactamente <strong>1</strong>.</span><br>
        <span class="ml-4 text-orange-600 font-semibold">⚠ No se pueden mezclar los dos modos dentro de la misma categoría.</span>
    </div>

    {{-- Formularios para agregar columnas --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-5">
        @foreach($categorias as $cat => $cfg)
        <div class="bg-white rounded-xl shadow p-4">
            <p class="text-xs font-bold text-gray-600 uppercase tracking-wide mb-3">
                + Nueva actividad · <span class="{{ $cfg['badge'] }} px-1.5 py-0.5 rounded text-xs">{{ $cfg['label'] }} ({{ $cfg['peso'] }})</span>
            </p>
            <form method="POST" action="{{ route('notas.v2.columna.store') }}" class="form-agregar-col">
                @csrf
                <input type="hidden" name="codigo_mat" value="{{ $matSelec }}">
                <input type="hidden" name="curso"      value="{{ $cursoSelec }}">
                <input type="hidden" name="periodo"    value="{{ $periodo }}">
                <input type="hidden" name="categoria"  value="{{ $cat }}">
                <div class="flex gap-2 items-end">
                    <div class="flex-1">
                        <label class="block text-xs text-gray-500 mb-1">Nombre de la actividad</label>
                        <input type="text" name="nombre_actividad" required
                            placeholder="Ej: Taller #1, Quiz, Exposición…"
                            class="w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 {{ $cfg['ring'] }}">
                    </div>
                    <div class="w-20">
                        <label class="block text-xs text-gray-500 mb-1" title="Peso relativo. Dejar vacío = igual peso que las demás">
                            Peso <span class="text-gray-400">(?)</span>
                        </label>
                        <input type="number" name="peso" min="0.1" max="5" step="0.001"
                            placeholder="1"
                            title="Peso relativo para ponderación. Ej: 2 = doble peso. Vacío = igual."
                            class="w-full border border-gray-300 rounded-lg px-2 py-1.5 text-sm focus:outline-none focus:ring-2 {{ $cfg['ring'] }}">
                    </div>
                    <button type="submit"
                        class="{{ $cfg['bg'] }} hover:opacity-90 text-white text-xs font-semibold px-3 py-1.5 rounded-lg transition whitespace-nowrap">
                        Agregar
                    </button>
                </div>
            </form>
        </div>
        @endforeach
    </div>

    @if($estudiantes->isEmpty())
        <div class="bg-yellow-50 border border-yellow-200 text-yellow-700 rounded-xl p-4 text-sm">
            No hay estudiantes matriculados en el curso <strong>{{ $cursoSelec }}</strong>.
        </div>
    @elseif($columnas->isEmpty())
        <div class="bg-blue-50 border border-blue-200 text-blue-700 rounded-xl p-5 text-sm text-center">
            Aún no hay actividades registradas para este período. Agrega una columna arriba para comenzar.
        </div>
    @else

    {{-- Planilla --}}
    <form method="POST" action="{{ route('notas.v2.guardar') }}" id="form-planilla">
        @csrf

        <div class="bg-white rounded-xl shadow overflow-hidden">
            {{-- Encabezado --}}
            <div class="px-5 py-3 bg-blue-800 text-white flex items-center justify-between">
                <div>
                    <h3 class="font-bold text-sm uppercase tracking-wide">{{ $materiaNombre }} · {{ $cursoSelec }} · Período {{ $periodo }}</h3>
                    <p class="text-blue-300 text-xs mt-0.5">{{ $estudiantes->count() }} estudiantes · {{ $columnas->count() }} actividades</p>
                </div>
                <button type="submit"
                    class="bg-white text-blue-800 hover:bg-blue-50 font-semibold text-xs px-4 py-1.5 rounded-lg transition">
                    💾 Guardar planilla
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="text-xs border-collapse" style="min-width: max-content">
                    {{-- Fila 1: grupos de categoría --}}
                    <thead>
                        <tr class="border-b border-gray-200">
                            <th class="px-4 py-2 text-left bg-gray-50 sticky left-0 z-10 border-r border-gray-200 min-w-[240px]" rowspan="2">
                                Código · Estudiante
                            </th>
                            @foreach($categorias as $cat => $cfg)
                                @php
                                    $cols = $columnasPorCat[$cat];
                                    $sumaPesos = collect($cols)->sum(fn($c) => (float)($c->peso ?? 1));
                                    $sumaPesosStr = number_format($sumaPesos, 3, '.', '');
                                    // Si todos los pesos son 1 (valor por defecto) la suma es irrelevante
                                    $todosDefault = collect($cols)->every(fn($c) => $c->peso === null);
                                @endphp
                                @if($cols->count() > 0)
                                <th colspan="{{ $cols->count() + 1 }}"
                                    class="px-3 py-2 text-center text-white text-xs font-bold uppercase tracking-wide {{ $cfg['bg'] }} border-r border-white/30">
                                    {{ $cfg['label'] }} · {{ $cfg['peso'] }}
                                    @if(!$todosDefault)
                                        <span class="ml-1 font-normal opacity-80">
                                            (Σ pesos = {{ $sumaPesosStr }}{{ abs($sumaPesos - 1) < 0.001 ? ' ✓' : '' }})
                                        </span>
                                    @endif
                                </th>
                                @endif
                            @endforeach
                            <th class="px-3 py-2 text-center bg-gray-800 text-white text-xs font-bold uppercase tracking-wide">
                                Nota Final
                            </th>
                        </tr>

                        {{-- Fila 2: nombres de actividades --}}
                        <tr class="bg-gray-50 border-b border-gray-200">
                            @foreach($categorias as $cat => $cfg)
                                @php $cols = $columnasPorCat[$cat]; @endphp
                                @foreach($cols as $col)
                                <th class="px-2 py-2 text-center font-semibold text-gray-600 min-w-[90px] border-r border-gray-200">
                                    <div class="flex flex-col items-center gap-1">
                                        <span class="{{ $cfg['badge'] }} px-1.5 py-0.5 rounded text-xs font-bold">{{ $cat }}</span>
                                        <span class="text-xs text-gray-700 max-w-[100px] leading-tight text-center">{{ $col->nombre_actividad }}</span>
                                        {{-- Peso editable --}}
                                        <div class="flex items-center gap-1 mt-0.5" title="Peso ponderado (1 = igual, 2 = doble, etc.)">
                                            <span class="text-gray-400 text-xs">×</span>
                                            <input type="number"
                                                value="{{ $col->peso ?? '' }}"
                                                placeholder="1"
                                                min="0.1" max="5" step="0.001"
                                                class="peso-col-input w-14 text-center border border-gray-300 rounded px-1 py-0.5 text-xs focus:outline-none focus:ring-1 {{ $cfg['ring'] }}"
                                                data-col-id="{{ $col->id }}"
                                                data-cat="{{ $cat }}"
                                                data-peso="{{ $col->peso ?? 1 }}"
                                                title="Peso de esta actividad (dejar vacío = igual que las demás)">
                                        </div>
                                        <button type="button"
                                            onclick="eliminarColumna({{ $col->id }}, '{{ addslashes($col->nombre_actividad) }}')"
                                            class="text-red-400 hover:text-red-600 text-xs leading-none" title="Eliminar columna">✕</button>
                                    </div>
                                </th>
                                @endforeach
                                @if($cols->count() > 0)
                                {{-- Promedio ponderado de categoría --}}
                                <th class="px-2 py-2 text-center font-bold {{ $cfg['badge'] }} min-w-[64px] border-r border-gray-300">
                                    Prom {{ $cat }}
                                </th>
                                @endif
                            @endforeach
                        </tr>
                    </thead>

                    {{-- Filas de estudiantes --}}
                    <tbody class="divide-y divide-gray-100">
                        @foreach($estudiantes as $est)
                        <tr class="hover:bg-gray-50" data-codigo="{{ $est->CODIGO }}">
                            <td class="px-4 py-2 font-medium text-gray-800 sticky left-0 bg-white border-r border-gray-200 z-10 whitespace-nowrap">
                                <span class="text-gray-400 font-mono text-xs mr-1">{{ $est->CODIGO }}</span>
                                {{ $est->APELLIDO1 }} {{ $est->APELLIDO2 }} {{ $est->NOMBRE1 }}
                            </td>

                            @foreach($categorias as $cat => $cfg)
                                @php $cols = $columnasPorCat[$cat]; @endphp
                                @foreach($cols as $col)
                                @php $val = $notasMap[$col->id][$est->CODIGO] ?? ''; @endphp
                                <td class="px-1 py-1 text-center border-r border-gray-100">
                                    <input type="number"
                                        name="notas[{{ $col->id }}][{{ $est->CODIGO }}]"
                                        value="{{ $val }}"
                                        min="0" max="10" step="0.001"
                                        placeholder="—"
                                        data-cat="{{ $cat }}"
                                        data-peso="{{ $col->peso ?? 1 }}"
                                        class="nota-input w-16 text-center border border-gray-300 rounded px-1 py-1 text-xs focus:outline-none focus:ring-2 {{ $cfg['ring'] }}
                                            {{ $val !== '' && $val < 6 ? 'text-red-600 font-semibold' : ($val !== '' ? 'text-green-700 font-semibold' : '') }}">
                                </td>
                                @endforeach

                                @if($cols->count() > 0)
                                <td class="px-2 py-1 text-center border-r border-gray-200">
                                    <span class="prom-cat font-semibold text-xs" data-cat="{{ $cat }}">
                                        @php
                                            $todosCols = collect($cols)->map(fn($c) => ['nota' => $notasMap[$c->id][$est->CODIGO] ?? null, 'peso' => (float)($c->peso ?? 1)]);
                                            $conNota   = $todosCols->filter(fn($v) => $v['nota'] !== null);
                                            if ($conNota->isNotEmpty()) {
                                                if ($modoPorCat[$cat] === 'decimal') {
                                                    // Σ(nota × porcentaje) directo
                                                    echo number_format($conNota->sum(fn($v) => $v['nota'] * $v['peso']), 1);
                                                } else {
                                                    // Promedio ponderado: Σ(nota×peso) / Σ(todos los pesos)
                                                    $sumTotalPeso = $todosCols->sum('peso');
                                                    echo number_format($conNota->sum(fn($v) => $v['nota'] * $v['peso']) / $sumTotalPeso, 1);
                                                }
                                            } else {
                                                echo '—';
                                            }
                                        @endphp
                                    </span>
                                </td>
                                @endif
                            @endforeach

                            {{-- Nota final --}}
                            <td class="px-3 py-1 text-center">
                                @php
                                    $notaFinal = null;
                                    $sumaTotal = 0;
                                    $pesoTotal = 0;
                                    foreach ($categorias as $cat => $cfg) {
                                        $colsCat = $columnasPorCat[$cat];
                                        if ($colsCat->count() === 0) continue;
                                        $todosCat = collect($colsCat)->map(fn($c) => ['nota' => $notasMap[$c->id][$est->CODIGO] ?? null, 'peso' => (float)($c->peso ?? 1)]);
                                        $conNotaCat = $todosCat->filter(fn($v) => $v['nota'] !== null);
                                        if ($conNotaCat->isNotEmpty()) {
                                            if ($modoPorCat[$cat] === 'decimal') {
                                                $promCat = $conNotaCat->sum(fn($v) => $v['nota'] * $v['peso']);
                                            } else {
                                                $sumTotalPesoCat = $todosCat->sum('peso');
                                                $promCat = $conNotaCat->sum(fn($v) => $v['nota'] * $v['peso']) / $sumTotalPesoCat;
                                            }
                                            $pesoCat  = ['P' => 0.70, 'C' => 0.20, 'A' => 0.10][$cat];
                                            $sumaTotal += $promCat * $pesoCat;
                                        }
                                    }
                                    if ($sumaTotal > 0) $notaFinal = round($sumaTotal, 1);
                                @endphp
                                <span class="nota-final font-bold text-sm
                                    {{ $notaFinal !== null && $notaFinal < 6 ? 'text-red-600' : ($notaFinal !== null ? 'text-green-700' : 'text-gray-300') }}">
                                    {{ $notaFinal !== null ? number_format($notaFinal, 1) : '—' }}
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="px-5 py-3 border-t border-gray-100 flex items-center justify-between gap-3">
                <button type="submit"
                    class="bg-blue-800 hover:bg-blue-700 text-white font-semibold text-sm px-6 py-2 rounded-lg transition">
                    💾 Guardar planilla
                </button>
                @php
                    $ventana = $fechaEntrega ?? null;
                @endphp
                @if($entregaActiva)
                    <button type="button" onclick="confirmarEntrega()"
                        class="bg-emerald-700 hover:bg-emerald-600 text-white font-semibold text-sm px-6 py-2 rounded-lg transition">
                        📤 Entregar notas
                    </button>
                @else
                    <div class="flex flex-col items-end gap-1">
                        <button type="button" disabled
                            class="bg-gray-300 text-gray-500 font-semibold text-sm px-6 py-2 rounded-lg cursor-not-allowed"
                            title="La ventana de entrega no está abierta">
                            📤 Entregar notas
                        </button>
                        @if($ventana)
                            <span class="text-xs text-gray-400">
                                Habilitado del {{ \Carbon\Carbon::parse($ventana->INICIO)->format('d/m H:i') }}
                                al {{ \Carbon\Carbon::parse($ventana->FIN)->format('d/m H:i') }}
                            </span>
                        @else
                            <span class="text-xs text-gray-400">Ventana N{{ $periodo }} no configurada</span>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </form>

    <form id="form-entregar" method="POST" action="{{ route('notas.v2.entregar') }}" style="display:none">
        @csrf
        <input type="hidden" name="codigo_mat" value="{{ $matSelec }}">
        <input type="hidden" name="curso"      value="{{ $cursoSelec }}">
        <input type="hidden" name="periodo"    value="{{ $periodo }}">
    </form>

    @endif {{-- columnas.isEmpty --}}
    @endif {{-- matSelec && cursoSelec --}}

    @if(!$matSelec || !$cursoSelec)
    <div class="bg-gray-50 border border-gray-200 text-gray-500 rounded-xl p-6 text-center text-sm">
        Selecciona materia, curso y período para cargar la planilla.
    </div>
    @endif

@endsection

{{-- Formularios ocultos externos (fuera del form principal para evitar anidamiento) --}}
<form id="form-delete-col" method="POST" style="display:none">
    @csrf
    @method('DELETE')
</form>

<form id="form-update-peso" method="POST" style="display:none">
    @csrf
    @method('PATCH')
    <input id="peso-input-hidden" name="peso">
</form>

@push('scripts')
<script>
    // ── Eliminar columna ─────────────────────────────────────────────────────
    const baseUrlColumna = '{{ url("notas-v2/columna") }}';

    function eliminarColumna(id, nombre) {
        if (!confirm(`¿Eliminar la actividad «${nombre}» y todas sus notas?`)) return;
        const form = document.getElementById('form-delete-col');
        form.action = baseUrlColumna + '/' + id;
        form.submit();
    }

    // ── Actualizar peso de columna ───────────────────────────────────────────
    document.querySelectorAll('.peso-col-input').forEach(input => {
        function guardarPeso() {
            const colId = input.dataset.colId;
            const form  = document.getElementById('form-update-peso');
            form.action = baseUrlColumna + '/' + colId + '/peso';
            document.getElementById('peso-input-hidden').value = input.value;
            form.submit();
        }
        function intentarGuardar() {
            if (input.value === '') { guardarPeso(); return; }
            const nuevoPeso  = parseFloat(input.value);
            const pesoActual = parseFloat(input.dataset.peso) || 1;
            if (isNaN(nuevoPeso)) return;
            const cat   = input.dataset.cat;
            // Para edición: excluir el peso actual de la lista de existentes
            const sinEste = (pesosPorCat[cat] || []).filter(p => Math.abs(p - pesoActual) > 0.0001);
            const modo    = detectarModo(sinEste);
            let error = null;
            if (modo === 'decimal') {
                if (nuevoPeso >= 1) {
                    error = 'Esta categoría usa porcentajes decimales (0–1). El valor debe ser menor a 1.';
                } else {
                    const suma = sinEste.reduce((a,b) => a+b, 0) + nuevoPeso;
                    if (suma > 1.0001) error = `Los demás porcentajes suman ${sinEste.reduce((a,b)=>a+b,0).toFixed(3)}. Este valor lo haría superar 1.`;
                }
            } else if (modo === 'entero') {
                if (!Number.isInteger(nuevoPeso) || nuevoPeso < 1) {
                    error = 'Esta categoría usa pesos por número de notas (enteros ≥ 1). No se permiten decimales.';
                }
            }
            if (error) { alert(error); input.value = pesoActual; return; }
            if (Math.abs(nuevoPeso - pesoActual) > 0.0001) guardarPeso();
        }
        input.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); intentarGuardar(); } });
        input.addEventListener('blur', function () { intentarGuardar(); });
    });

    // ── Selector materia/curso ────────────────────────────────────────────────
    const mapaMaterias = @json($mapaMateriasCursos);
    const selMateria   = document.getElementById('sel-materia');
    const selCurso     = document.getElementById('sel-curso');

    function actualizarCursos() {
        const mat = selMateria.value;
        selCurso.innerHTML = '<option value="">— Selecciona —</option>';
        if (!mat || !mapaMaterias[mat]) { selCurso.disabled = true; return; }
        mapaMaterias[mat].forEach(c => {
            const opt = document.createElement('option');
            opt.value = c; opt.textContent = c;
            selCurso.appendChild(opt);
        });
        selCurso.disabled = false;
    }
    if (selMateria) selMateria.addEventListener('change', () => { actualizarCursos(); selCurso.value = ''; });

    // ── Recalcular promedios ponderados en tiempo real ───────────────────────
    const PESOS_CAT   = { P: 0.70, C: 0.20, A: 0.10 };
    const pesosPorCat = @json($pesosPorCat ?? []);  // pesos explícitos por categoría

    function detectarModo(pesos) {
        // 'decimal' si algún peso < 1; 'entero' si algún peso > 1; null si indefinido
        if (pesos.some(p => p < 1)) return 'decimal';
        if (pesos.some(p => p > 1)) return 'entero';
        return null;
    }

    function calcPromCat(inputs) {
        const all    = inputs.map(i => ({ v: parseFloat(i.value), p: parseFloat(i.dataset.peso) || 1, filled: i.value !== '' }));
        const filled = all.filter(i => i.filled && !isNaN(i.v));
        if (filled.length === 0) return null;
        const modo = detectarModo(all.map(i => i.p));
        if (modo === 'decimal') {
            // Σ(nota × porcentaje) — suma directa
            return filled.reduce((a, b) => a + b.v * b.p, 0);
        } else {
            // Promedio ponderado: Σ(nota×peso) / Σ(TODOS los pesos)
            const totalPeso = all.reduce((a, b) => a + b.p, 0);
            return filled.reduce((a, b) => a + b.v * b.p, 0) / totalPeso;
        }
    }

    function recalcularFila(row) {
        const catProms = {};

        ['P','C','A'].forEach(cat => {
            const inputs = [...row.querySelectorAll(`.nota-input[data-cat="${cat}"]`)];
            if (inputs.length === 0) return;
            const prom = calcPromCat(inputs);
            catProms[cat] = prom;

            const promCell = row.querySelector(`.prom-cat[data-cat="${cat}"]`);
            if (promCell) {
                promCell.textContent = prom !== null ? prom.toFixed(1) : '—';
                promCell.className = 'prom-cat font-semibold text-xs ' +
                    (prom !== null ? (prom < 6 ? 'text-red-600' : 'text-green-700') : 'text-gray-400');
            }
        });

        // Nota final: suma directa sin normalizar (P×0.70 + C×0.20 + A×0.10)
        let suma = 0, tieneDatos = false;
        Object.entries(catProms).forEach(([cat, prom]) => {
            if (prom !== null) {
                suma      += prom * PESOS_CAT[cat];
                tieneDatos = true;
            }
        });

        const finalCell = row.querySelector('.nota-final');
        if (finalCell) {
            if (tieneDatos) {
                const final = Math.round(suma * 10) / 10;
                finalCell.textContent = final.toFixed(1);
                finalCell.className = 'nota-final font-bold text-sm ' +
                    (final < 6 ? 'text-red-600' : 'text-green-700');
            } else {
                finalCell.textContent = '—';
                finalCell.className = 'nota-final font-bold text-sm text-gray-300';
            }
        }
    }

    // ── Entregar notas ───────────────────────────────────────────────────────
    function confirmarEntrega() {
        if (!confirm('¿Entregar las notas definitivas a NOTAS_2026?\n\nSe verificará que todos los estudiantes tengan nota en cada actividad.\nLas notas existentes serán sobreescritas.')) return;
        document.getElementById('form-entregar').submit();
    }

    // ── Validar modo al agregar/editar peso ─────────────────────────────────
    function validarPeso(cat, nuevoPeso, colIdExcluir) {
        // Regla base: si el valor es mayor a 1 debe ser entero
        if (nuevoPeso > 1 && !Number.isInteger(nuevoPeso)) {
            return 'Si el peso es mayor a 1 debe ser un número entero (sin decimales).';
        }

        const existentes = (pesosPorCat[cat] || []);
        const modo = detectarModo(existentes);

        if (modo === 'decimal') {
            if (nuevoPeso >= 1) {
                return 'Esta categoría ya usa porcentajes decimales (0–1). El valor debe ser menor a 1.';
            }
            const suma = existentes.reduce((a, b) => a + b, 0) + nuevoPeso;
            if (suma > 1.0001) {
                return `Los porcentajes de esta categoría ya suman ${existentes.reduce((a,b)=>a+b,0).toFixed(3)}. Agregar ${nuevoPeso.toFixed(3)} excedería 1.`;
            }
        } else if (modo === 'entero') {
            if (!Number.isInteger(nuevoPeso) || nuevoPeso < 1) {
                return 'Esta categoría usa pesos por número de notas (enteros ≥ 1). No se permiten decimales.';
            }
        }
        return null; // ok
    }

    // Validar al enviar formulario de nueva actividad
    document.querySelectorAll('.form-agregar-col').forEach(form => {
        form.addEventListener('submit', function(e) {
            const pesoInput = this.querySelector('input[name="peso"]');
            const cat       = this.querySelector('input[name="categoria"]').value;
            if (!pesoInput || pesoInput.value === '') return;
            const nuevoPeso = parseFloat(pesoInput.value);
            if (isNaN(nuevoPeso)) return;
            const error = validarPeso(cat, nuevoPeso, null);
            if (error) {
                e.preventDefault();
                alert(error);
            }
        });
    });

    // Validar al editar peso en encabezado de columna
    document.querySelectorAll('.peso-col-input').forEach(input => {
        input._originalValidate = function() {
            const val = this.value;
            if (val === '') return true; // vacío = peso por defecto, ok
            const nuevoPeso = parseFloat(val);
            if (isNaN(nuevoPeso)) return true;
            const cat   = this.dataset.cat;
            const colId = this.dataset.colId;
            const error = validarPeso(cat, nuevoPeso, colId);
            if (error) {
                alert(error);
                this.value = this.dataset.peso; // revertir al valor anterior
                return false;
            }
            return true;
        };
    });

    // Colorear input y recalcular al cambiar
    document.querySelectorAll('.nota-input').forEach(input => {
        input.addEventListener('input', function () {
            const v = parseFloat(this.value);
            if (this.value !== '') {
                this.classList.toggle('text-red-600',   v < 6);
                this.classList.toggle('text-green-700', v >= 6);
                this.classList.add('font-semibold');
            } else {
                this.classList.remove('text-red-600','text-green-700','font-semibold');
            }
            recalcularFila(this.closest('tr'));
        });
    });
</script>
@endpush
