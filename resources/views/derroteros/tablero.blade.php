@extends('layouts.app-sidebar')
@section('header', 'Tablero de Recuperaciones')
@section('slot')

@php
    $fechaTxt = $fechaSel
        ? \Carbon\Carbon::parse($fechaSel)->locale('es')->isoFormat('dddd D [de] MMMM [de] YYYY')
        : null;
    $fechaInput = $fechaSel
        ? \Carbon\Carbon::parse($fechaSel)->format('Y-m-d')
        : '';
    $total = $cards->count();
    $cardsJson = $cards->map(function ($c) {
        return [
            'id'          => $c->id,
            'codigo_alum' => $c->codigo_alum,
            'codigo_mat'  => $c->codigo_mat,
            'nombre'      => $c->nombre,
            'curso'       => $c->curso,
            'grado'       => $c->grado,
            'materia'     => $c->materia,
            'franja'      => $c->franja,
            'docente_cod' => $c->docente_cod,
            'docente_nom' => $c->docente_nom,
        ];
    })->values();
@endphp

<div class="bg-white rounded-xl shadow p-5 mb-4">
    <form method="GET" action="{{ route('derroteros.tablero') }}" class="grid grid-cols-1 sm:grid-cols-5 gap-3 items-end">
        <div>
            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Año</label>
            <input type="number" name="anio" value="{{ $anio }}" min="2024" max="2030"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
        </div>
        <div>
            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Período</label>
            <select name="periodo" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                @foreach([1,2,3,4] as $p)
                    <option value="{{ $p }}" {{ $periodo == $p ? 'selected' : '' }}>Período {{ $p }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">
                Fecha de recuperaciones
            </label>
            <input type="date" name="fecha" value="{{ $fechaInput }}"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
        </div>
        <div class="sm:col-span-2 flex gap-2">
            <button type="submit" class="bg-blue-800 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded-lg">
                Cargar
            </button>
            @if($fechaTxt)
                <span class="flex-1 flex items-center text-sm text-gray-700 bg-blue-50 rounded-lg px-3">
                    📅 <span class="ml-2"><strong>Día a publicar:</strong> {{ ucfirst($fechaTxt) }}</span>
                </span>
            @else
                <span class="flex-1 flex items-center text-xs text-amber-700 bg-amber-50 rounded-lg px-3">
                    ⚠️ Elige la fecha que se publicará junto al horario.
                </span>
            @endif
        </div>
    </form>
</div>

@if($total === 0)
    <div class="bg-white rounded-xl shadow p-10 text-center text-gray-500">
        Sin recuperaciones pendientes para este período.
    </div>
@else
<div class="bg-white rounded-xl shadow p-4 mb-4 flex flex-wrap gap-3 items-center">
    <button id="btn-autoasignar" type="button"
        class="bg-emerald-700 hover:bg-emerald-600 text-white text-sm font-semibold px-4 py-2 rounded-lg">
        ✨ Autoasignar pendientes
    </button>
    <button id="btn-limpiar" type="button"
        class="bg-gray-200 hover:bg-gray-300 text-gray-700 text-sm font-semibold px-4 py-2 rounded-lg">
        🧹 Mover todas a "Sin asignar"
    </button>
    <button id="btn-confirmar" type="button"
        class="bg-blue-800 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded-lg">
        ✔️ Confirmar asignación
    </button>
    <div class="ml-auto text-sm text-gray-600 flex items-center gap-3">
        <span id="estado-guardado" class="text-[11px] uppercase tracking-wide text-gray-400">Sin cambios</span>
        <span>
            <span id="cont-asig" class="font-semibold text-emerald-700">0</span> asignadas
            · <span id="cont-pend" class="font-semibold text-amber-700">{{ $total }}</span> en bolsa
            · Total {{ $total }}
        </span>
    </div>
    <div id="mensaje" class="w-full text-sm hidden"></div>
</div>

<div id="grid-tablero" class="grid grid-cols-1 lg:grid-cols-[18rem_1fr] gap-4">

    {{-- ─── Bolsa "Sin asignar" ─── --}}
    <div id="bag-panel" class="bg-white rounded-xl shadow overflow-hidden self-start">
        <div class="bg-amber-100 text-amber-800 px-3 py-2 font-bold text-sm uppercase flex items-center justify-between">
            <span>Sin asignar <span id="bag-count">0</span></span>
            <button id="btn-minimizar" type="button"
                title="Minimizar / expandir"
                class="text-amber-800 hover:bg-amber-200 rounded px-2 py-0.5 text-xs font-mono">−</button>
        </div>
        <div id="bag" class="p-3 space-y-2 min-h-[6rem] max-h-[75vh] overflow-y-auto"
             data-dropzone="bag">
            {{-- Tarjetas se inyectan por JS al inicializar --}}
        </div>
    </div>

    {{-- ─── Tablero docente × franja ─── --}}
    <div class="bg-white rounded-xl shadow overflow-hidden">
        <div id="tablero-scroller" class="overflow-x-auto">
            <table class="min-w-full text-xs border-separate border-spacing-0">
                <thead class="bg-blue-900 text-white sticky top-0 z-10">
                    <tr>
                        <th class="px-3 py-2 text-left w-48 sticky left-0 bg-blue-900 z-20">Docente</th>
                        @foreach($franjas as $n => $rango)
                            <th class="px-2 py-2 text-center whitespace-nowrap">
                                <div class="font-bold">F{{ $n }}</div>
                                <div class="text-[10px] text-blue-200 font-normal">{{ $rango }}</div>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($docentes as $doc)
                        <tr class="hover:bg-gray-50">
                            <td class="px-3 py-2 font-semibold text-gray-700 border-b border-gray-200 sticky left-0 bg-white z-0">
                                {{ $doc->nombre }}
                                <div class="text-[10px] text-gray-400">{{ $doc->codigo }}</div>
                            </td>
                            @foreach($franjas as $n => $rango)
                                <td class="dropzone border-b border-l border-gray-200 align-top p-1 min-w-[10rem] transition-colors"
                                    data-dropzone="cell"
                                    data-docente="{{ $doc->codigo }}"
                                    data-franja="{{ $n }}">
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- ─── Template de tarjeta ─── --}}
<template id="tpl-card">
    <div class="card cursor-move rounded-md border px-2 py-1 text-[11px] leading-tight shadow-sm select-none"
         draggable="true">
        <div class="flex justify-between items-start">
            <span class="font-mono font-bold text-blue-800 codigo"></span>
            <span class="curso text-[10px] bg-blue-100 text-blue-700 px-1 rounded"></span>
        </div>
        <div class="nombre font-semibold text-gray-800 truncate"></div>
        <div class="materia text-gray-500 truncate"></div>
    </div>
</template>

<script>
(function() {
    const FRANJAS = @json($franjas);
    const DATA = @json($cardsJson);

    const CSRF = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const URLS = {
        guardar:     @json(route('derroteros.tablero.guardar')),
        autoasignar: @json(route('derroteros.tablero.autoasignar')),
        confirmar:   @json(route('derroteros.tablero.confirmar')),
    };
    const ANIO    = @json($anio);
    const PERIODO = @json($periodo);
    const FECHA   = @json($fechaInput);

    // Estado: key "alum_mat" -> { ...card, franja }
    const state = {};
    DATA.forEach(c => {
        const key = c.codigo_alum + '_' + c.codigo_mat;
        state[key] = { ...c };
    });

    const tpl = document.getElementById('tpl-card');

    function renderCard(item) {
        const node = tpl.content.firstElementChild.cloneNode(true);
        const key = item.codigo_alum + '_' + item.codigo_mat;
        node.dataset.key       = key;
        node.dataset.alum      = item.codigo_alum;
        node.dataset.mat       = item.codigo_mat;
        node.dataset.curso     = item.curso;
        node.dataset.grado     = item.grado;
        node.dataset.docente   = item.docente_cod || '';
        node.querySelector('.codigo').textContent  = item.codigo_alum;
        node.querySelector('.curso').textContent   = item.curso;
        node.querySelector('.nombre').textContent  = item.nombre;
        node.querySelector('.materia').textContent = item.materia;

        // Color por curso (hash simple)
        const hue = cursoHue(item.curso);
        node.style.background = `hsl(${hue} 85% 96%)`;
        node.style.borderColor = `hsl(${hue} 50% 70%)`;

        node.addEventListener('dragstart', onDragStart);
        node.addEventListener('dragend', onDragEnd);
        return node;
    }

    function cursoHue(c) {
        let h = 0;
        for (let i = 0; i < c.length; i++) h = (h * 31 + c.charCodeAt(i)) % 360;
        return h;
    }

    let dragged = null;

    function onDragStart(e) {
        dragged = e.currentTarget;
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', dragged.dataset.key);
        setTimeout(() => dragged.classList.add('opacity-40'), 0);
    }

    function onDragEnd() {
        if (dragged) dragged.classList.remove('opacity-40');
        dragged = null;
        document.querySelectorAll('.dropzone').forEach(z => z.classList.remove('bg-emerald-100','bg-red-100'));
    }

    function onDragOver(e) {
        if (!dragged) return;
        const zone = e.currentTarget;
        const tipo = zone.dataset.dropzone;
        let ok = true;

        if (tipo === 'cell') {
            const mismaFila = zone.dataset.docente === dragged.dataset.docente;
            const franja    = zone.dataset.franja;
            const alum      = dragged.dataset.alum;
            const choque = Object.values(state).some(s =>
                s.codigo_alum == alum &&
                s.franja == franja &&
                (s.codigo_alum + '_' + s.codigo_mat) !== dragged.dataset.key
            );
            ok = mismaFila && !choque;
        }

        zone.classList.toggle('bg-emerald-100', ok);
        zone.classList.toggle('bg-red-100', !ok);
        if (ok) e.preventDefault();
    }

    function onDragLeave(e) {
        e.currentTarget.classList.remove('bg-emerald-100','bg-red-100');
    }

    function onDrop(e) {
        e.preventDefault();
        const zone = e.currentTarget;
        zone.classList.remove('bg-emerald-100','bg-red-100');
        if (!dragged) return;
        const key = dragged.dataset.key;
        const tipo = zone.dataset.dropzone;

        if (tipo === 'cell') {
            if (zone.dataset.docente !== dragged.dataset.docente) return;
            const alum = dragged.dataset.alum;
            const franja = parseInt(zone.dataset.franja);
            const choque = Object.values(state).some(s =>
                s.codigo_alum == alum &&
                s.franja == franja &&
                (s.codigo_alum + '_' + s.codigo_mat) !== key
            );
            if (choque) return;
            state[key].franja = franja;
        } else {
            state[key].franja = null;
        }

        placeAll();
        autosave();
    }

    function repaint() {
        // Aviso ámbar: la celda mezcla grados distintos (6A+6B es OK, pero 6A+7A no).
        document.querySelectorAll('[data-dropzone="cell"]').forEach(cell => {
            const grados = new Set();
            cell.querySelectorAll('.card').forEach(c => grados.add(c.dataset.grado));
            cell.classList.toggle('ring-2', grados.size > 1);
            cell.classList.toggle('ring-amber-400', grados.size > 1);
        });

        // Contadores
        const cards = Object.values(state);
        const asignadas = cards.filter(c => c.franja).length;
        const pendientes = cards.length - asignadas;
        document.getElementById('cont-asig').textContent = asignadas;
        document.getElementById('cont-pend').textContent = pendientes;
        document.getElementById('bag-count').textContent = pendientes;

        // Auto-ocultar panel "Sin asignar" cuando no hay pendientes
        aplicarEstadoBolsa(pendientes);
    }

    // ─── Minimizar / ocultar bolsa "Sin asignar" ───
    // Estados: 'expanded' (panel completo) | 'collapsed' (solo cabecera) | 'hidden' (oculta, tablero full width)
    // Si el usuario minimiza manualmente, respetamos su elección mientras haya tarjetas.
    let bagManual = null; // 'collapsed' | 'expanded' | null = automático

    function aplicarEstadoBolsa(pendientes) {
        const grid = document.getElementById('grid-tablero');
        const panel = document.getElementById('bag-panel');
        const bagDiv = document.getElementById('bag');
        const btn = document.getElementById('btn-minimizar');
        if (!grid || !panel || !btn) return;

        let estado;
        if (pendientes === 0) {
            estado = 'hidden';
        } else if (bagManual === 'collapsed') {
            estado = 'collapsed';
        } else {
            estado = 'expanded';
        }

        panel.classList.toggle('hidden', estado === 'hidden');
        bagDiv.classList.toggle('hidden', estado !== 'expanded');

        if (estado === 'hidden' || estado === 'collapsed') {
            grid.classList.remove('lg:grid-cols-[18rem_1fr]');
            grid.classList.add('lg:grid-cols-1');
        } else {
            grid.classList.add('lg:grid-cols-[18rem_1fr]');
            grid.classList.remove('lg:grid-cols-1');
        }

        btn.textContent = estado === 'expanded' ? '−' : '+';
    }

    document.getElementById('btn-minimizar').addEventListener('click', () => {
        bagManual = (bagManual === 'collapsed') ? 'expanded' : 'collapsed';
        const pendientes = Object.values(state).filter(c => !c.franja).length;
        aplicarEstadoBolsa(pendientes);
    });

    // Inicializar: colocar cada tarjeta en su zona, ordenadas por curso y nombre
    function placeAll() {
        document.querySelectorAll('.dropzone').forEach(z => { z.innerHTML = ''; });
        const items = Object.values(state).slice().sort((a, b) => {
            if (a.curso !== b.curso) return a.curso.localeCompare(b.curso, 'es', {numeric: true});
            if (a.materia !== b.materia) return a.materia.localeCompare(b.materia, 'es');
            return a.nombre.localeCompare(b.nombre, 'es');
        });
        items.forEach(item => {
            const node = renderCard(item);
            if (item.franja) {
                const sel = `[data-dropzone="cell"][data-docente="${item.docente_cod||''}"][data-franja="${item.franja}"]`;
                const cell = document.querySelector(sel);
                (cell || document.getElementById('bag')).appendChild(node);
            } else {
                document.getElementById('bag').appendChild(node);
            }
        });
        repaint();
    }

    function bindDropzones() {
        document.querySelectorAll('.dropzone, #bag').forEach(z => {
            z.addEventListener('dragover', onDragOver);
            z.addEventListener('dragleave', onDragLeave);
            z.addEventListener('drop', onDrop);
        });

        // Auto-scroll horizontal mientras se arrastra cerca de los bordes
        const scroller = document.getElementById('tablero-scroller');
        if (scroller) {
            let af = null;
            const step = () => {
                if (scrollDx !== 0) {
                    scroller.scrollLeft += scrollDx;
                    af = requestAnimationFrame(step);
                } else {
                    af = null;
                }
            };
            let scrollDx = 0;
            scroller.addEventListener('dragover', (e) => {
                const rect = scroller.getBoundingClientRect();
                const edge = 80;
                if (e.clientX > rect.right - edge)      scrollDx = Math.min(20, (e.clientX - (rect.right - edge)) / 2);
                else if (e.clientX < rect.left + edge)  scrollDx = -Math.min(20, ((rect.left + edge) - e.clientX) / 2);
                else                                    scrollDx = 0;
                if (scrollDx !== 0 && af === null) af = requestAnimationFrame(step);
            });
            scroller.addEventListener('dragleave', () => { scrollDx = 0; });
            scroller.addEventListener('drop',      () => { scrollDx = 0; });
            document.addEventListener('dragend',   () => { scrollDx = 0; });
        }
    }

    function msg(texto, tipo = 'info') {
        const el = document.getElementById('mensaje');
        const colores = {
            ok:    'bg-emerald-50 text-emerald-700 border-emerald-200',
            err:   'bg-red-50 text-red-700 border-red-200',
            info:  'bg-blue-50 text-blue-700 border-blue-200',
            warn:  'bg-amber-50 text-amber-800 border-amber-200',
        };
        el.className = 'w-full text-sm border rounded-lg px-3 py-2 ' + (colores[tipo] || colores.info);
        el.textContent = texto;
        el.classList.remove('hidden');
    }

    // ─── Autoguardado debounced (solo persiste FRANJA; el horario se publica con "Confirmar") ───
    let saveTimer = null;
    let savePending = false;
    function setEstado(texto, cls) {
        const el = document.getElementById('estado-guardado');
        if (!el) return;
        el.textContent = texto;
        el.className = 'text-[11px] uppercase tracking-wide ' + cls;
    }
    async function autosave() {
        setEstado('Guardando…', 'text-amber-600');
        savePending = true;
        clearTimeout(saveTimer);
        saveTimer = setTimeout(async () => {
            const items = Object.values(state).map(s => ({
                codigo_alum: s.codigo_alum,
                codigo_mat:  s.codigo_mat,
                franja:      s.franja,
            }));
            try {
                const r = await fetch(URLS.guardar, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                    body: JSON.stringify({ anio: ANIO, periodo: PERIODO, items }),
                });
                if (!r.ok) { setEstado('Error al guardar', 'text-red-600'); return; }
                setEstado('Guardado · sin publicar', 'text-gray-500');
            } catch (e) {
                setEstado('Sin conexión', 'text-red-600');
            } finally {
                savePending = false;
            }
        }, 400);
    }
    // Aviso si intenta salir con cambios sin guardar
    window.addEventListener('beforeunload', (e) => {
        if (savePending) { e.preventDefault(); e.returnValue = ''; }
    });

    document.getElementById('btn-limpiar').addEventListener('click', () => {
        if (!confirm('¿Mover todas las tarjetas a "Sin asignar"?')) return;
        Object.values(state).forEach(s => s.franja = null);
        placeAll();
        autosave();
    });

    document.getElementById('btn-autoasignar').addEventListener('click', async () => {
        msg('Calculando asignación…', 'info');
        const fijadas = Object.values(state)
            .filter(s => s.franja)
            .map(s => ({ codigo_alum: s.codigo_alum, codigo_mat: s.codigo_mat, franja: s.franja }));

        const r = await fetch(URLS.autoasignar, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            body: JSON.stringify({ anio: ANIO, periodo: PERIODO, fijadas }),
        });
        if (!r.ok) { msg('Error al autoasignar.', 'err'); return; }
        const j = await r.json();
        j.asignadas.forEach(a => {
            const key = a.codigo_alum + '_' + a.codigo_mat;
            if (state[key]) state[key].franja = a.franja;
        });
        placeAll();
        autosave();
        const sin = j.sin_cupo.length;
        msg(
            `Asignadas ${j.asignadas.length}` + (sin ? ` · Sin cupo: ${sin} (choque con otras materias del mismo estudiante)` : ''),
            sin ? 'warn' : 'ok'
        );
    });

    document.getElementById('btn-confirmar').addEventListener('click', async () => {
        if (!FECHA) {
            msg('Selecciona primero la fecha de recuperaciones y pulsa "Cargar".', 'warn');
            return;
        }
        if (!confirm('¿Publicar la asignación? Los horarios quedarán visibles para padres y docentes.')) return;
        msg('Publicando horarios…', 'info');
        try {
            const r = await fetch(URLS.confirmar, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                body: JSON.stringify({ anio: ANIO, periodo: PERIODO, fecha: FECHA }),
            });
            if (!r.ok) { msg('Error al confirmar.', 'err'); return; }
            const j = await r.json();
            const sinF = j.sin_franja || 0;
            msg(
                `✅ Publicados ${j.publicados}` + (sinF ? ` · ${sinF} sin franja (no se publicaron)` : ''),
                sinF ? 'warn' : 'ok'
            );
            setEstado('Publicado', 'text-emerald-600');
        } catch (e) {
            msg('Sin conexión.', 'err');
        }
    });

    bindDropzones();
    placeAll();
})();
</script>

<style>
    /* Los td-dropzone deben quedar con altura uniforme por fila y aceptar
       drops en toda su área, no solo sobre las tarjetas. */
    td.dropzone:empty::before {
        content: ''; display: block; min-height: 3.5rem;
        border: 1px dashed #e5e7eb; border-radius: 4px;
    }
    .dropzone .card + .card { margin-top: 0.25rem; }
    .card:hover { transform: translateY(-1px); box-shadow: 0 2px 6px rgba(0,0,0,0.08); }
</style>
@endif

@endsection
