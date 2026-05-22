@extends('layouts.app-sidebar')

@section('header', 'Salvavidas — Links de Google Sites')

@section('slot')

<div class="bg-white rounded-xl shadow overflow-hidden">

    <div class="px-5 py-3 bg-blue-800 text-white">
        <h3 class="font-bold text-sm uppercase tracking-wide">Índice de Google Sites por materia</h3>
        <p class="text-blue-300 text-xs mt-0.5">
            Acceso directo al contenido de cada Google Site. Materias de Preescolar (PE) usan automáticamente el código sin el 100.
        </p>
    </div>

    {{-- ── Filtros ──────────────────────────────────────────────── --}}
    <div class="p-5 border-b border-gray-100 bg-gray-50 flex flex-wrap gap-3 items-end">
        <div class="flex-1 min-w-[240px]">
            <label class="block text-xs text-gray-500 mb-1">Filtrar por materia</label>
            <select id="filtro-materia"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">— Todas las materias —</option>
                @foreach($materias as $m)
                    <option value="{{ $m->codigo_mat }}">
                        {{ $m->nombre }} ({{ $m->codigo_mat }}){{ $m->es_pe ? ' · PE' : '' }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="flex items-center gap-2">
            <input type="checkbox" id="solo-pe" class="rounded border-gray-300">
            <label for="solo-pe" class="text-sm text-gray-600">Solo materias PE</label>
        </div>
        <div class="text-xs text-gray-500">
            {{ $materias->count() }} materia(s) · {{ $materias->sum(fn($m) => $m->links->count()) }} link(s)
        </div>
    </div>

    {{-- ── Lista de materias ────────────────────────────────────── --}}
    <div class="p-5 space-y-5">
        @forelse($materias as $m)
            <div class="bloque-materia border border-gray-200 rounded-lg overflow-hidden"
                 data-materia="{{ $m->codigo_mat }}"
                 data-pe="{{ $m->es_pe ? '1' : '0' }}">
                <div class="px-4 py-2 bg-blue-50 border-b border-blue-100 flex justify-between items-center">
                    <span class="font-bold text-blue-900">
                        {{ $m->nombre }}
                        <span class="text-xs font-normal text-blue-600">
                            (CODIGO_MAT {{ $m->codigo_mat }}@if($m->es_pe) → URL usa {{ $m->cod_site }}@endif)
                        </span>
                        @if($m->es_pe)
                            <span class="ml-1 px-1.5 py-0.5 text-xs bg-amber-100 text-amber-800 rounded">PE</span>
                        @endif
                    </span>
                    <span class="text-xs text-blue-700">{{ $m->links->count() }} link(s)</span>
                </div>
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                        <tr>
                            <th class="px-4 py-2 text-left w-32">Grado / Curso(s)</th>
                            <th class="px-4 py-2 text-left">URL del Google Site</th>
                            <th class="px-4 py-2 text-center w-24">Abrir</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($m->links as $l)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2 font-semibold">
                                {{ implode(', ', $l->cursos) }}
                                @if(count($l->cursos) > 1 || $l->cursos[0] !== $l->grado)
                                    <span class="text-xs text-gray-400">(grado {{ $l->grado }})</span>
                                @endif
                            </td>
                            <td class="px-4 py-2">
                                <code class="text-xs text-gray-700 break-all">{{ $l->url }}</code>
                            </td>
                            <td class="px-4 py-2 text-center">
                                <a href="{{ $l->url }}" target="_blank" rel="noopener"
                                   class="inline-flex items-center gap-1 px-2 py-1 text-xs font-semibold text-white bg-blue-700 hover:bg-blue-800 rounded transition">
                                    ↗ Abrir
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @empty
            <div class="text-sm text-gray-400 text-center py-6">No hay materias con asignaciones registradas.</div>
        @endforelse
        <div id="vacio" class="hidden text-sm text-gray-400 text-center py-6">
            Ninguna materia coincide con los filtros.
        </div>
    </div>
</div>

<script>
(function () {
    const filtroMateria = document.getElementById('filtro-materia');
    const soloPe        = document.getElementById('solo-pe');
    const bloques       = document.querySelectorAll('.bloque-materia');
    const vacio         = document.getElementById('vacio');

    function aplicar() {
        const valMat = filtroMateria.value;
        const soloPeChk = soloPe.checked;
        let visibles = 0;
        bloques.forEach(b => {
            const matchMat = !valMat || b.dataset.materia === valMat;
            const matchPe  = !soloPeChk || b.dataset.pe === '1';
            const visible  = matchMat && matchPe;
            b.classList.toggle('hidden', !visible);
            if (visible) visibles++;
        });
        vacio.classList.toggle('hidden', visibles > 0);
    }

    filtroMateria.addEventListener('change', aplicar);
    soloPe.addEventListener('change', aplicar);
})();
</script>

@endsection
