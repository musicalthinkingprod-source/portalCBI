@extends('layouts.app-sidebar')

@section('header', 'Programación de reuniones')

@section('slot')
<style>[x-cloak] { display: none !important; }</style>
<div x-data="reunionesAnalyzer()" class="max-w-6xl mx-auto py-6 px-4 space-y-6">

    {{-- Intro --}}
    <div>
        <h1 class="text-2xl font-bold text-gray-800">🤝 Programación de reuniones</h1>
        <p class="text-sm text-gray-500 mt-1">
            Selecciona los docentes que necesitas y descubre las horas y bloques con menos
            conflictos (o del todo libres) en el ciclo académico.
        </p>
    </div>

    {{-- ── Selector de docentes ── --}}
    <div class="bg-white rounded-xl shadow p-5 space-y-4">
        <div class="flex items-center justify-between gap-3 flex-wrap">
            <div class="relative flex-1 min-w-[220px] max-w-sm">
                <input type="text" x-model="busqueda" placeholder="Buscar docente…"
                    class="w-full border border-gray-300 rounded-lg pl-9 pr-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                <span class="absolute left-3 top-2.5 text-gray-400">🔍</span>
            </div>
            <div class="flex items-center gap-3 text-sm">
                <button type="button" @click="agregarFiltrados()" x-show="busqueda.trim() !== ''"
                    class="text-indigo-600 hover:text-indigo-800 font-medium">+ Agregar los filtrados</button>
                <span class="text-gray-500">
                    <span class="font-bold text-indigo-700" x-text="seleccionados.length"></span> seleccionado(s)
                </span>
                <button type="button" @click="limpiar()" x-show="seleccionados.length > 0"
                    class="text-red-500 hover:text-red-700 underline">Limpiar</button>
            </div>
        </div>

        {{-- Chips seleccionados (arriba, para verlos siempre) --}}
        <div x-show="seleccionados.length > 0" class="flex flex-wrap gap-1.5 pb-3 border-b border-gray-100">
            <template x-for="c in seleccionados" :key="'sel-'+c">
                <button type="button" @click="toggle(c)"
                    class="inline-flex items-center gap-1.5 bg-indigo-600 text-white text-xs font-semibold px-2.5 py-1 rounded-full hover:bg-indigo-700 transition">
                    <span x-text="nombre(c)"></span>
                    <span class="text-indigo-200">✕</span>
                </button>
            </template>
        </div>

        {{-- Lista de docentes (chips seleccionables) --}}
        <div class="flex flex-wrap gap-1.5 max-h-56 overflow-y-auto">
            <template x-for="d in docentesFiltrados" :key="d.codigo">
                <button type="button" @click="toggle(d.codigo)"
                    :class="esSel(d.codigo)
                        ? 'bg-indigo-100 border-indigo-300 text-indigo-800'
                        : 'bg-gray-50 border-gray-200 text-gray-700 hover:bg-gray-100'"
                    class="inline-flex items-center gap-1.5 text-xs font-medium px-2.5 py-1.5 rounded-lg border transition">
                    <span x-show="esSel(d.codigo)" class="text-indigo-600">✓</span>
                    <span x-text="d.nombre"></span>
                </button>
            </template>
            <p x-show="docentesFiltrados.length === 0" class="text-sm text-gray-400 italic py-2">
                Ningún docente coincide con la búsqueda.
            </p>
        </div>
    </div>

    {{-- Estado vacío --}}
    <div x-show="seleccionados.length === 0"
         class="bg-indigo-50 border border-indigo-100 rounded-xl p-6 text-center text-sm text-indigo-700">
        👆 Selecciona al menos un docente para ver el análisis de horarios.
    </div>

    {{-- ── Resultados ── --}}
    <div x-show="seleccionados.length > 0" class="space-y-6" x-cloak>

        {{-- Mejores opciones --}}
        <div class="grid md:grid-cols-2 gap-4">

            {{-- Mejores horas individuales --}}
            <div class="bg-white rounded-xl shadow p-5">
                <h2 class="font-bold text-gray-800 mb-3 flex items-center gap-2">⏰ Mejores horas</h2>
                <div class="space-y-2">
                    <template x-for="s in mejoresSlots" :key="'ms-'+s.dia+'-'+s.hora">
                        <div class="flex items-center justify-between gap-3 rounded-lg px-3 py-2"
                             :class="s.conflictos === 0 ? 'bg-green-50 border border-green-200' : 'bg-gray-50'">
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-gray-800">
                                    <span x-text="s.diaLabel"></span> ·
                                    <span x-text="s.horaLabel"></span>
                                </p>
                                <p class="text-xs text-gray-400" x-text="s.rango"></p>
                            </div>
                            <span class="shrink-0 text-xs font-bold px-2.5 py-1 rounded-full"
                                  :class="s.conflictos === 0
                                      ? 'bg-green-600 text-white'
                                      : 'bg-amber-100 text-amber-700'"
                                  x-text="s.conflictos === 0 ? '✓ Todos libres' : (s.conflictos + ' con clase')">
                            </span>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Mejores bloques (2 horas) --}}
            <div class="bg-white rounded-xl shadow p-5">
                <h2 class="font-bold text-gray-800 mb-3 flex items-center gap-2">🧱 Mejores bloques (2 horas)</h2>
                <div class="space-y-2">
                    <template x-for="b in mejoresBloques" :key="'mb-'+b.dia+'-'+b.h1">
                        <div class="flex items-center justify-between gap-3 rounded-lg px-3 py-2"
                             :class="b.conflictos === 0 ? 'bg-green-50 border border-green-200' : 'bg-gray-50'">
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-gray-800">
                                    <span x-text="b.diaLabel"></span> ·
                                    <span x-text="b.h1 + 'ª – ' + b.h2 + 'ª hora'"></span>
                                </p>
                                <p class="text-xs text-gray-400" x-text="b.rango"></p>
                            </div>
                            <span class="shrink-0 text-xs font-bold px-2.5 py-1 rounded-full"
                                  :class="b.conflictos === 0
                                      ? 'bg-green-600 text-white'
                                      : 'bg-amber-100 text-amber-700'"
                                  x-text="b.conflictos === 0 ? '✓ Todos libres' : (b.conflictos + ' con clase')">
                            </span>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        {{-- Matriz completa día × hora --}}
        <div class="bg-white rounded-xl shadow overflow-hidden">
            <div class="px-5 py-3 border-b border-gray-100">
                <h2 class="font-bold text-gray-800">📊 Mapa de disponibilidad</h2>
                <p class="text-xs text-gray-400 mt-0.5">
                    Cada celda muestra cuántos de los <span x-text="seleccionados.length"></span> docentes
                    tienen clase. Haz clic para ver quién. Verde = todos libres.
                </p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm border-collapse">
                    <thead>
                        <tr class="bg-indigo-700 text-white">
                            <th class="px-3 py-2 text-left font-semibold w-28">Hora</th>
                            @foreach($dias as $d)
                            <th class="px-3 py-2 text-center font-semibold">
                                {{ $d['label'] }}
                                @if($d['fecha'])
                                    <div class="text-xs font-normal opacity-75">{{ $d['fecha'] }}</div>
                                @endif
                            </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($horas as $h)
                        <tr class="{{ $loop->even ? 'bg-gray-50' : 'bg-white' }} border-b border-gray-100">
                            <td class="px-3 py-2 whitespace-nowrap">
                                <span class="font-semibold text-indigo-700 text-xs">{{ $h['label'] }}</span><br>
                                <span class="text-gray-400 text-xs">{{ $h['rango'] }}</span>
                            </td>
                            @foreach($dias as $d)
                            <td class="px-1.5 py-1.5 text-center">
                                <button type="button"
                                    @click="detalleSlot({{ $d['num'] }}, {{ $h['num'] }})"
                                    :class="claseCelda({{ $d['num'] }}, {{ $h['num'] }})"
                                    class="w-full rounded-lg py-2 text-xs font-bold transition cursor-pointer"
                                    x-text="etiquetaCelda({{ $d['num'] }}, {{ $h['num'] }})">
                                </button>
                            </td>
                            @endforeach
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{-- Leyenda --}}
            <div class="px-5 py-3 flex flex-wrap gap-4 text-xs text-gray-500 border-t border-gray-100">
                <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-green-200 inline-block"></span>Todos libres</span>
                <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-lime-100 inline-block"></span>Pocos con clase</span>
                <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-amber-100 inline-block"></span>Varios con clase</span>
                <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-red-100 inline-block"></span>La mayoría con clase</span>
            </div>
        </div>
    </div>

    {{-- ── Modal de detalle de un slot ── --}}
    <div x-show="detalle" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm"
         @keydown.escape.window="detalle = null">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 overflow-hidden"
             @click.outside="detalle = null">
            <template x-if="detalle">
                <div>
                    <div class="bg-indigo-700 px-6 py-4 flex items-center justify-between">
                        <div>
                            <h3 class="text-white font-bold text-base" x-text="detalle.diaLabel + ' · ' + detalle.horaLabel"></h3>
                            <p class="text-indigo-200 text-xs" x-text="detalle.rango"></p>
                        </div>
                        <button @click="detalle = null" class="text-white/70 hover:text-white text-xl leading-none">&times;</button>
                    </div>
                    <div class="px-6 py-5 space-y-4 max-h-[60vh] overflow-y-auto">
                        {{-- Libres --}}
                        <div>
                            <p class="text-xs font-semibold text-green-700 mb-1.5">
                                ✓ Disponibles (<span x-text="detalle.libres.length"></span>)
                            </p>
                            <div class="flex flex-wrap gap-1.5" x-show="detalle.libres.length > 0">
                                <template x-for="c in detalle.libres" :key="'lib-'+c">
                                    <span class="text-xs bg-green-100 text-green-800 px-2 py-1 rounded-lg" x-text="nombre(c)"></span>
                                </template>
                            </div>
                            <p x-show="detalle.libres.length === 0" class="text-xs text-gray-400 italic">Ninguno está libre en este slot.</p>
                        </div>
                        {{-- Con clase --}}
                        <div>
                            <p class="text-xs font-semibold text-red-600 mb-1.5">
                                📚 Con clase (<span x-text="detalle.ocupados.length"></span>)
                            </p>
                            <div class="space-y-1.5" x-show="detalle.ocupados.length > 0">
                                <template x-for="c in detalle.ocupados" :key="'ocu-'+c">
                                    <div class="flex items-start justify-between gap-3 bg-red-50 rounded-lg px-2.5 py-1.5">
                                        <span class="text-xs font-semibold text-red-700 shrink-0" x-text="nombre(c)"></span>
                                        <span class="text-xs text-red-500 text-right" x-text="claseDe(detalle.dia, detalle.hora, c) || 'Ocupado'"></span>
                                    </div>
                                </template>
                            </div>
                            <p x-show="detalle.ocupados.length === 0" class="text-xs text-green-600 font-medium">🎉 ¡Todos disponibles en este slot!</p>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>

<script>
const _docentes     = @json($docentes);
const _ocupacion    = @json($ocupacion);
const _ocupacionDet = @json($ocupacionDet);
const _dias         = @json($dias);
const _horas        = @json($horas);

function reunionesAnalyzer() {
    return {
        docentes:      _docentes,
        ocupacion:     _ocupacion,
        ocupacionDet:  _ocupacionDet,
        dias:          _dias,
        horas:         _horas,
        bloques:       [[1, 2], [3, 4], [5, 6], [7, 8]],
        seleccionados: [],
        busqueda:      '',
        detalle:       null,

        // ── Selección ──
        get docentesFiltrados() {
            const q = this.busqueda.trim().toLowerCase();
            if (!q) return this.docentes;
            return this.docentes.filter(d =>
                d.nombre.toLowerCase().includes(q) || d.codigo.toLowerCase().includes(q));
        },
        esSel(c)  { return this.seleccionados.includes(c); },
        toggle(c) {
            const i = this.seleccionados.indexOf(c);
            if (i >= 0) this.seleccionados.splice(i, 1);
            else        this.seleccionados.push(c);
        },
        agregarFiltrados() {
            this.docentesFiltrados.forEach(d => {
                if (!this.seleccionados.includes(d.codigo)) this.seleccionados.push(d.codigo);
            });
        },
        limpiar() { this.seleccionados = []; },
        nombre(c) { const d = this.docentes.find(x => x.codigo === c); return d ? d.nombre : c; },

        // ── Ocupación ──
        ocupadosEn(dia, hora) {
            const arr = (this.ocupacion[dia] || {})[hora] || [];
            return this.seleccionados.filter(c => arr.includes(c));
        },
        libresEn(dia, hora) {
            const oc = this.ocupadosEn(dia, hora);
            return this.seleccionados.filter(c => !oc.includes(c));
        },
        nConf(dia, hora) { return this.ocupadosEn(dia, hora).length; },

        // Qué clase(s) tiene un docente en un slot
        claseDe(dia, hora, c) {
            const arr = ((this.ocupacionDet[dia] || {})[hora] || {})[c] || [];
            return arr.join(' / ');
        },

        // Conflictos únicos en un bloque de dos horas
        confBloque(dia, h1, h2) {
            const set = new Set([...this.ocupadosEn(dia, h1), ...this.ocupadosEn(dia, h2)]);
            return set.size;
        },

        // ── Celda de la matriz ──
        claseCelda(dia, hora) {
            if (this.seleccionados.length === 0) return 'bg-gray-50 text-gray-300';
            const n = this.nConf(dia, hora);
            if (n === 0) return 'bg-green-100 text-green-800 ring-1 ring-green-300 hover:bg-green-200';
            const ratio = n / this.seleccionados.length;
            if (ratio <= 0.34) return 'bg-lime-50 text-lime-700 hover:bg-lime-100';
            if (ratio <= 0.67) return 'bg-amber-50 text-amber-700 hover:bg-amber-100';
            return 'bg-red-50 text-red-600 hover:bg-red-100';
        },
        etiquetaCelda(dia, hora) {
            if (this.seleccionados.length === 0) return '·';
            const n = this.nConf(dia, hora);
            return n === 0 ? '✓' : String(n);
        },

        // ── Etiquetas ──
        diaLabel(dia)  { const d = this.dias.find(x => x.num === dia);   return d ? d.label : ('Día ' + dia); },
        horaLabel(hora){ const h = this.horas.find(x => x.num === hora); return h ? h.label : (hora + 'ª hora'); },
        horaRango(hora){ const h = this.horas.find(x => x.num === hora); return h ? h.rango : ''; },

        // ── Rankings ──
        get mejoresSlots() {
            if (this.seleccionados.length === 0) return [];
            const out = [];
            for (const d of this.dias) {
                for (const h of this.horas) {
                    out.push({
                        dia: d.num, hora: h.num,
                        diaLabel: d.label, horaLabel: h.label, rango: h.rango,
                        conflictos: this.nConf(d.num, h.num),
                    });
                }
            }
            return out.sort((a, b) => a.conflictos - b.conflictos).slice(0, 8);
        },
        get mejoresBloques() {
            if (this.seleccionados.length === 0) return [];
            const out = [];
            for (const d of this.dias) {
                for (const [h1, h2] of this.bloques) {
                    out.push({
                        dia: d.num, h1, h2, diaLabel: d.label,
                        rango: this.horaRango(h1).split('–')[0].trim() + ' – ' + this.horaRango(h2).split('–').pop().trim(),
                        conflictos: this.confBloque(d.num, h1, h2),
                    });
                }
            }
            return out.sort((a, b) => a.conflictos - b.conflictos).slice(0, 6);
        },

        // ── Detalle ──
        detalleSlot(dia, hora) {
            if (this.seleccionados.length === 0) return;
            this.detalle = {
                dia, hora,
                diaLabel:  this.diaLabel(dia),
                horaLabel: this.horaLabel(hora),
                rango:     this.horaRango(hora),
                ocupados:  this.ocupadosEn(dia, hora),
                libres:    this.libresEn(dia, hora),
            };
        },
    };
}
</script>
@endsection
