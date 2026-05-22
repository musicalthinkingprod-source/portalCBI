@extends('layouts.app-sidebar')

@section('header', 'Resumen de Asignaciones')

@section('slot')

<div class="bg-white rounded-xl shadow overflow-hidden">

    {{-- ── Cabecera + tabs ─────────────────────────────────────────── --}}
    <div class="px-5 py-3 bg-blue-800 text-white">
        <h3 class="font-bold text-sm uppercase tracking-wide">Quién le da clases a quién</h3>
        <p class="text-blue-300 text-xs mt-0.5">
            Vista de solo lectura. Filtra por curso o por docente para ver las asignaciones registradas.
        </p>
    </div>

    <div class="flex border-b border-gray-200 bg-gray-50">
        <button type="button" data-tab="por-curso"
            class="tab-btn flex-1 px-5 py-3 text-sm font-semibold text-blue-800 border-b-2 border-blue-800 bg-white">
            📚 Por curso
        </button>
        <button type="button" data-tab="por-docente"
            class="tab-btn flex-1 px-5 py-3 text-sm font-semibold text-gray-500 border-b-2 border-transparent hover:text-blue-700">
            👩‍🏫 Por docente
        </button>
    </div>

    {{-- ═══════════════════════════════════════════════════════
         TAB: POR CURSO
    ═══════════════════════════════════════════════════════ --}}
    <div data-tab-panel="por-curso" class="tab-panel">

        <div class="p-5 border-b border-gray-100 bg-gray-50 flex flex-wrap gap-3 items-end">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs text-gray-500 mb-1">Filtrar por curso</label>
                <select id="filtro-curso"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">— Todos los cursos —</option>
                    @foreach($cursos as $c)
                        <option value="{{ $c }}">{{ $c }}</option>
                    @endforeach
                </select>
            </div>
            <div class="text-xs text-gray-500">
                {{ $porCurso->count() }} curso(s) · {{ $porCurso->flatten(1)->count() }} asignación(es)
            </div>
        </div>

        <div class="p-5 space-y-6">
            @forelse($porCurso as $curso => $items)
                <div class="bloque-curso border border-gray-200 rounded-lg overflow-hidden"
                     data-curso="{{ $curso }}">
                    <div class="px-4 py-2 bg-blue-50 border-b border-blue-100 flex justify-between items-center">
                        <span class="font-bold text-blue-900">Curso {{ $curso }}</span>
                        <span class="text-xs text-blue-700">{{ $items->count() }} asignación(es)</span>
                    </div>
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                            <tr>
                                <th class="px-4 py-2 text-left">Materia</th>
                                <th class="px-4 py-2 text-left">Docente</th>
                                <th class="px-4 py-2 text-center w-20">IHS</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($items as $a)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-2">{{ $a->NOMBRE_MAT }}</td>
                                <td class="px-4 py-2">
                                    {{ $a->NOMBRE_DOC }}
                                    <span class="text-xs text-gray-400">({{ $a->CODIGO_EMP }})</span>
                                    @if($a->DOC_ESTADO && $a->DOC_ESTADO !== 'ACTIVO')
                                        <span class="ml-1 text-xs text-red-600">[{{ $a->DOC_ESTADO }}]</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2 text-center font-mono">{{ $a->IHS ?: '—' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @empty
                <div class="text-sm text-gray-400 text-center py-6">No hay asignaciones registradas.</div>
            @endforelse
            <div id="curso-vacio" class="hidden text-sm text-gray-400 text-center py-6">
                Ningún curso coincide con el filtro.
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════
         TAB: POR DOCENTE
    ═══════════════════════════════════════════════════════ --}}
    <div data-tab-panel="por-docente" class="tab-panel hidden">

        <div class="p-5 border-b border-gray-100 bg-gray-50 flex flex-wrap gap-3 items-end">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs text-gray-500 mb-1">Filtrar por docente</label>
                <select id="filtro-docente"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">— Todos los docentes —</option>
                    @foreach($docentes as $d)
                        <option value="{{ $d->CODIGO_EMP }}">
                            {{ $d->NOMBRE_DOC }} ({{ $d->CODIGO_EMP }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="text-xs text-gray-500">
                {{ $porDocente->count() }} docente(s) · {{ $porDocente->flatten(1)->count() }} asignación(es)
            </div>
        </div>

        <div class="p-5 space-y-6">
            @forelse($porDocente as $codigoEmp => $items)
                @php $primero = $items->first(); @endphp
                <div class="bloque-docente border border-gray-200 rounded-lg overflow-hidden"
                     data-docente="{{ $codigoEmp }}">
                    <div class="px-4 py-2 bg-indigo-50 border-b border-indigo-100 flex justify-between items-center">
                        <span class="font-bold text-indigo-900">
                            {{ $primero->NOMBRE_DOC }}
                            <span class="text-xs font-normal text-indigo-600">({{ $codigoEmp }})</span>
                            @if($primero->DOC_ESTADO && $primero->DOC_ESTADO !== 'ACTIVO')
                                <span class="ml-1 text-xs text-red-600">[{{ $primero->DOC_ESTADO }}]</span>
                            @endif
                        </span>
                        <span class="text-xs text-indigo-700">
                            {{ $items->count() }} asignación(es) ·
                            IHS total: {{ $items->sum(fn($x) => (int) $x->IHS) }}
                        </span>
                    </div>
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                            <tr>
                                <th class="px-4 py-2 text-left w-24">Curso</th>
                                <th class="px-4 py-2 text-left">Materia</th>
                                <th class="px-4 py-2 text-center w-20">IHS</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($items as $a)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-2 font-semibold">{{ $a->CURSO }}</td>
                                <td class="px-4 py-2">{{ $a->NOMBRE_MAT }}</td>
                                <td class="px-4 py-2 text-center font-mono">{{ $a->IHS ?: '—' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @empty
                <div class="text-sm text-gray-400 text-center py-6">No hay asignaciones registradas.</div>
            @endforelse
            <div id="docente-vacio" class="hidden text-sm text-gray-400 text-center py-6">
                Ningún docente coincide con el filtro.
            </div>
        </div>
    </div>

</div>

<script>
(function () {
    const tabButtons = document.querySelectorAll('.tab-btn');
    const tabPanels  = document.querySelectorAll('[data-tab-panel]');

    tabButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const target = btn.dataset.tab;

            tabButtons.forEach(b => {
                const isActive = b.dataset.tab === target;
                b.classList.toggle('text-blue-800', isActive);
                b.classList.toggle('border-blue-800', isActive);
                b.classList.toggle('bg-white', isActive);
                b.classList.toggle('text-gray-500', !isActive);
                b.classList.toggle('border-transparent', !isActive);
            });

            tabPanels.forEach(p => {
                p.classList.toggle('hidden', p.dataset.tabPanel !== target);
            });
        });
    });

    const filtroCurso = document.getElementById('filtro-curso');
    if (filtroCurso) {
        filtroCurso.addEventListener('change', () => {
            const val = filtroCurso.value;
            let visibles = 0;
            document.querySelectorAll('.bloque-curso').forEach(b => {
                const match = !val || b.dataset.curso === val;
                b.classList.toggle('hidden', !match);
                if (match) visibles++;
            });
            document.getElementById('curso-vacio').classList.toggle('hidden', visibles > 0);
        });
    }

    const filtroDocente = document.getElementById('filtro-docente');
    if (filtroDocente) {
        filtroDocente.addEventListener('change', () => {
            const val = filtroDocente.value;
            let visibles = 0;
            document.querySelectorAll('.bloque-docente').forEach(b => {
                const match = !val || b.dataset.docente === val;
                b.classList.toggle('hidden', !match);
                if (match) visibles++;
            });
            document.getElementById('docente-vacio').classList.toggle('hidden', visibles > 0);
        });
    }
})();
</script>

@endsection
