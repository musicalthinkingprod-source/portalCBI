@extends('layouts.app-sidebar')

@section('header', 'Control de Planilla Ponderada')

@section('slot')

@php
    // Colores por categoría (mismos códigos que la planilla ponderada)
    $catCfg = [
        'C' => ['label' => 'Cognitivo',     'bg' => 'bg-purple-600', 'bgLight' => 'bg-purple-100', 'text' => 'text-purple-800', 'border' => 'border-purple-400'],
        'P' => ['label' => 'Procedimental', 'bg' => 'bg-blue-600',   'bgLight' => 'bg-blue-100',   'text' => 'text-blue-800',   'border' => 'border-blue-400'],
        'A' => ['label' => 'Actitudinal',   'bg' => 'bg-green-600',  'bgLight' => 'bg-green-100', 'text' => 'text-green-800', 'border' => 'border-green-400'],
    ];
@endphp

{{-- Filtros --}}
<div class="bg-white rounded-xl shadow p-5 mb-6">
    <form method="GET" action="{{ route('control.planilla') }}">
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 items-end">
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Año</label>
                <select name="anio" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @foreach([2026, 2025] as $a)
                        <option value="{{ $a }}" {{ $anio == $a ? 'selected' : '' }}>{{ $a }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Período</label>
                <select name="periodo" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @foreach([1,2,3,4] as $p)
                        <option value="{{ $p }}" {{ $periodo == $p ? 'selected' : '' }}>Período {{ $p }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Curso</label>
                <select name="curso" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">— Todos —</option>
                    @foreach($cursosDisponibles as $c)
                        <option value="{{ $c }}" {{ $curso == $c ? 'selected' : '' }}>{{ $c }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Materia</label>
                <select name="materia" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">— Todas —</option>
                    @foreach($materiasDisponibles as $m)
                        <option value="{{ $m->codigo_mat }}" {{ $materia == $m->codigo_mat ? 'selected' : '' }}>{{ $m->NOMBRE_MAT }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="mt-3 flex justify-between items-center">
            <div class="flex gap-2">
                <button type="button" id="btn-expandir" class="text-xs text-blue-700 hover:underline">⊞ Expandir todos</button>
                <span class="text-gray-300">·</span>
                <button type="button" id="btn-colapsar" class="text-xs text-blue-700 hover:underline">⊟ Colapsar todos</button>
            </div>
            <button type="submit" class="bg-blue-800 hover:bg-blue-700 text-white font-semibold text-sm px-5 py-2 rounded-lg transition">
                Consultar
            </button>
        </div>
    </form>
</div>

@if($diasGrid->isEmpty())
    <div class="bg-blue-50 border border-blue-200 text-blue-700 rounded-xl p-6 text-center text-sm">
        No hay días académicos registrados para el período <strong>{{ $periodo }}</strong> del año <strong>{{ $anio }}</strong>.
    </div>
@elseif($asignaciones->isEmpty())
    <div class="bg-yellow-50 border border-yellow-200 text-yellow-700 rounded-xl p-6 text-center text-sm">
        No hay asignaciones calificables para los filtros seleccionados.
    </div>
@else

{{-- Leyenda --}}
<div class="flex flex-wrap gap-3 mb-4 text-xs text-gray-500 items-center">
    <span>Cada celda muestra <strong>chips</strong> por categoría según las notas registradas ese día:</span>
    @foreach($catCfg as $cat => $cfg)
        <span class="inline-flex items-center gap-1.5">
            <span class="inline-flex items-center justify-center w-5 h-4 rounded-sm {{ $cfg['bg'] }} text-white text-[9px] font-bold">{{ $cat }}</span>
            {{ $cfg['label'] }}
        </span>
    @endforeach
    <span class="inline-flex items-center gap-1.5">
        <span class="w-3 h-3 rounded-sm bg-gray-100 border border-gray-300 inline-block"></span> Sin registro
    </span>
    <span class="inline-flex items-center gap-1.5">
        <span class="w-3 h-3 rounded-sm bg-gray-300 border border-gray-400 inline-block"></span> Día futuro
    </span>
</div>

@php
    $hoyStr = now()->toDateString();
    $coloresCiclo = [
        1 => 'bg-indigo-700',
        2 => 'bg-sky-700',
        3 => 'bg-teal-700',
        4 => 'bg-emerald-700',
        5 => 'bg-amber-700',
        6 => 'bg-orange-700',
        7 => 'bg-rose-700',
    ];
@endphp

<div class="bg-white rounded-xl shadow overflow-hidden">
    <div class="px-5 py-3 bg-gray-800 text-white flex items-center justify-between">
        <div>
            <h3 class="font-bold text-sm uppercase tracking-wide">
                Planillas ponderadas · Año {{ $anio }} · Período {{ $periodo }}
                @if($curso) · Curso <span class="text-blue-200">{{ $curso }}</span>@endif
                @if($materia) · <span class="text-blue-200">{{ $materiasDisponibles->firstWhere('codigo_mat', $materia)?->NOMBRE_MAT }}</span>@endif
            </h3>
            <p class="text-gray-400 text-xs mt-0.5">
                {{ $porDocente->count() }} docentes · {{ $asignaciones->count() }} asignaciones · {{ $ciclosAgrupados->count() }} ciclos · {{ $diasGrid->count() }} días académicos
            </p>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="text-xs border-collapse" style="min-width: max-content">
            <thead>
                {{-- Fila 1: Ciclo --}}
                <tr class="bg-gray-100 border-b border-gray-300">
                    <th rowspan="3" class="px-4 py-2 text-left font-semibold text-gray-700 sticky left-0 bg-gray-100 z-20 min-w-[300px] border-r border-gray-300 align-bottom">
                        Docente / Materia / Curso
                    </th>
                    @foreach($ciclosAgrupados as $cicloNum => $dias)
                        <th colspan="{{ $dias->count() }}"
                            class="px-2 py-1 text-center text-white text-xs font-bold uppercase tracking-wide border-r border-white/40 {{ $coloresCiclo[$cicloNum] ?? 'bg-gray-700' }}">
                            Ciclo {{ $cicloNum }}
                        </th>
                    @endforeach
                    <th rowspan="3" class="px-3 py-2 text-center font-semibold text-gray-700 min-w-[70px] bg-gray-200 border-l border-gray-300 align-bottom">
                        Total
                    </th>
                    <th rowspan="3" class="px-3 py-2 text-center font-semibold text-gray-700 min-w-[70px] bg-gray-200 align-bottom">
                        % Días
                    </th>
                </tr>

                {{-- Fila 2: Día académico (1-6) --}}
                <tr class="bg-gray-50 border-b border-gray-200">
                    @foreach($diasGrid as $d)
                        @php $esFuturo = $d->fecha > $hoyStr; $esHoy = $d->fecha === $hoyStr; @endphp
                        <th class="px-1 py-1 text-center text-[10px] font-semibold border-r border-gray-200 min-w-[44px]
                            {{ $esHoy ? 'bg-blue-100 text-blue-800' : ($esFuturo ? 'text-gray-400' : 'text-gray-600') }}">
                            D{{ $d->dia_ciclo }}
                        </th>
                    @endforeach
                </tr>

                {{-- Fila 3: Fecha real (DD/MM) --}}
                <tr class="bg-gray-50 border-b-2 border-gray-300">
                    @foreach($diasGrid as $d)
                        @php
                            $esFuturo = $d->fecha > $hoyStr;
                            $esHoy    = $d->fecha === $hoyStr;
                            $f        = \Carbon\Carbon::parse($d->fecha);
                        @endphp
                        <th class="px-1 pb-1 text-center text-[10px] font-normal border-r border-gray-200 min-w-[44px] leading-tight
                            {{ $esHoy ? 'bg-blue-100 text-blue-800 font-semibold' : ($esFuturo ? 'text-gray-400' : 'text-gray-500') }}"
                            title="{{ $d->fecha }}{{ $d->evento ? ' · ' . $d->evento : '' }}">
                            <span class="block">{{ $f->format('d') }}</span>
                            <span class="block opacity-70">{{ $f->format('m') }}</span>
                            @if($esHoy)
                                <span class="block text-blue-500 text-[9px]">●</span>
                            @endif
                        </th>
                    @endforeach
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-100">
                @php $totalCols = $diasGrid->count() + 2; @endphp
                @foreach($porDocente as $codDoc => $asigs)
                @php
                    $nombreDoc = $asigs->first()->NOMBRE_DOC ?? $codDoc;

                    // Totales del docente (suma sobre todas sus asignaciones y todos los días)
                    $totalDoc = 0;
                    $diasConRegDoc = 0;
                    $diasPasados   = 0;
                    foreach ($diasGrid as $d) {
                        $hayReg = false;
                        foreach ($asigs as $a) {
                            $key = $a->CODIGO_EMP . '|' . $a->CODIGO_MAT . '|' . $a->CURSO;
                            $cnt = array_sum($conteosCat[$d->fecha][$key] ?? []);
                            $totalDoc += $cnt;
                            if ($cnt > 0) $hayReg = true;
                        }
                        if ($d->fecha <= $hoyStr) {
                            $diasPasados++;
                            if ($hayReg) $diasConRegDoc++;
                        }
                    }
                    $pctDoc = $diasPasados > 0 ? round($diasConRegDoc / $diasPasados * 100) : null;
                @endphp

                {{-- Fila docente (toggle) --}}
                <tr class="docente-header bg-blue-50 hover:bg-blue-100 cursor-pointer border-t-2 border-blue-200" data-doc="{{ $codDoc }}">
                    <td class="px-3 py-2 sticky left-0 bg-blue-50 z-10 border-r border-blue-200" colspan="1">
                        <div class="flex items-center gap-2">
                            <span class="toggle-icon text-blue-700 font-bold w-4 inline-block">▶</span>
                            <div class="flex-1 min-w-0">
                                <p class="font-bold text-blue-900 text-sm truncate">{{ $nombreDoc }}</p>
                                <p class="text-blue-600 text-[10px]">{{ $codDoc }} · {{ $asigs->count() }} asignación(es)</p>
                            </div>
                        </div>
                    </td>
                    {{-- Mini-resumen del docente por día (resumen visual) --}}
                    @foreach($diasGrid as $d)
                        @php
                            $totalDia = 0;
                            $catsDia  = ['C' => 0, 'P' => 0, 'A' => 0];
                            foreach ($asigs as $a) {
                                $key = $a->CODIGO_EMP . '|' . $a->CODIGO_MAT . '|' . $a->CURSO;
                                foreach (($conteosCat[$d->fecha][$key] ?? []) as $cat => $n) {
                                    $catsDia[$cat] = ($catsDia[$cat] ?? 0) + $n;
                                    $totalDia += $n;
                                }
                            }
                            $esFuturo = $d->fecha > $hoyStr;
                        @endphp
                        <td class="p-0.5 text-center border-r border-blue-100">
                            @if($esFuturo)
                                <span class="inline-block w-9 h-4 rounded-sm bg-gray-200 border border-gray-300"></span>
                            @elseif($totalDia > 0)
                                <div class="flex justify-center gap-px" title="{{ $d->fecha }} · {{ $totalDia }} notas">
                                    @foreach(['C','P','A'] as $cat)
                                        @if(($catsDia[$cat] ?? 0) > 0)
                                            <span class="inline-block w-2 h-4 rounded-sm {{ $catCfg[$cat]['bg'] }}"></span>
                                        @endif
                                    @endforeach
                                </div>
                            @else
                                <span class="inline-block w-9 h-4 rounded-sm bg-white border border-blue-100"></span>
                            @endif
                        </td>
                    @endforeach
                    <td class="px-2 py-2 text-center font-bold text-blue-900 bg-blue-100/60 border-l border-blue-200">
                        {{ $totalDoc > 0 ? $totalDoc : '—' }}
                    </td>
                    <td class="px-2 py-2 text-center font-bold bg-blue-100/60
                        {{ $pctDoc === null ? 'text-gray-300' : ($pctDoc >= 50 ? 'text-green-700' : ($pctDoc >= 20 ? 'text-yellow-700' : 'text-red-700')) }}">
                        {{ $pctDoc !== null ? $pctDoc . '%' : '—' }}
                    </td>
                </tr>

                {{-- Filas de cada asignación del docente --}}
                @foreach($asigs as $asig)
                @php
                    $key = $asig->CODIGO_EMP . '|' . $asig->CODIGO_MAT . '|' . $asig->CURSO;
                    $totalNotas  = 0;
                    $diasConReg  = 0;
                    $diasPasadosA = 0;
                    foreach ($diasGrid as $d) {
                        $cnt = array_sum($conteosCat[$d->fecha][$key] ?? []);
                        $totalNotas += $cnt;
                        if ($d->fecha <= $hoyStr) {
                            $diasPasadosA++;
                            if ($cnt > 0) $diasConReg++;
                        }
                    }
                    $pct = $diasPasadosA > 0 ? round($diasConReg / $diasPasadosA * 100) : null;
                @endphp
                <tr class="asig-row hover:bg-gray-50" data-doc="{{ $codDoc }}" style="display:none">
                    <td class="px-4 py-1.5 sticky left-0 bg-white z-10 border-r border-gray-200">
                        <div class="flex items-center justify-between gap-2 pl-6">
                            <p class="text-gray-700 text-xs leading-tight">
                                ↳ {{ $asig->NOMBRE_MAT }} · <span class="font-semibold text-gray-800">{{ $asig->CURSO }}</span>
                            </p>
                            @if($esSuperior)
                                <a href="{{ route('notas.v2.index', ['materia' => $asig->CODIGO_MAT, 'curso' => $asig->CURSO, 'periodo' => $periodo]) }}"
                                   target="_blank"
                                   class="text-blue-700 hover:bg-blue-100 text-xs px-2 py-0.5 rounded border border-blue-200 whitespace-nowrap"
                                   title="Abrir planilla ponderada de {{ $asig->NOMBRE_DOC ?? $codDoc }} · {{ $asig->NOMBRE_MAT }} · {{ $asig->CURSO }}">
                                    🧪 Ver planilla
                                </a>
                            @endif
                        </div>
                    </td>

                    @foreach($diasGrid as $d)
                        @php
                            $cats     = $conteosCat[$d->fecha][$key] ?? [];
                            $totalDia = array_sum($cats);
                            $esFuturo = $d->fecha > $hoyStr;
                            $det      = $detalles[$d->fecha][$key] ?? [];
                            $tip      = $d->fecha;
                            if ($d->evento) $tip .= ' · ' . $d->evento;
                            if ($totalDia > 0) {
                                foreach ($cats as $catLet => $n) {
                                    $tip .= "\n[" . $catLet . '] ' . $catCfg[$catLet]['label'] . ': ' . $n;
                                    foreach (($det[$catLet] ?? []) as $r) {
                                        $tip .= "\n   • " . $r['actividad'] . ' (' . $r['cantidad'] . ')';
                                    }
                                }
                            }
                        @endphp
                        <td class="p-0.5 text-center border-r border-gray-100">
                            @if($esFuturo)
                                <span class="inline-block w-9 h-5 rounded-sm bg-gray-200 border border-gray-300"
                                    title="{{ $d->fecha }} · día futuro"></span>
                            @elseif($totalDia > 0)
                                <span class="inline-flex items-center justify-center gap-px h-5 rounded-sm" title="{{ $tip }}">
                                    @foreach(['C','P','A'] as $cat)
                                        @if(($cats[$cat] ?? 0) > 0)
                                            <span class="inline-flex items-center justify-center min-w-[12px] h-5 px-0.5 rounded-sm text-white text-[9px] font-bold {{ $catCfg[$cat]['bg'] }}">
                                                {{ $cats[$cat] }}
                                            </span>
                                        @endif
                                    @endforeach
                                </span>
                            @else
                                <span class="inline-block w-9 h-5 rounded-sm bg-gray-50 border border-gray-200"
                                    title="{{ $tip }} · sin registro"></span>
                            @endif
                        </td>
                    @endforeach

                    <td class="px-3 py-1.5 text-center font-bold text-gray-700 bg-gray-50 border-l border-gray-300">
                        {{ $totalNotas > 0 ? $totalNotas : '—' }}
                    </td>
                    <td class="px-3 py-1.5 text-center font-bold
                        {{ $pct === null ? 'text-gray-300' : ($pct >= 50 ? 'text-green-600' : ($pct >= 20 ? 'text-yellow-600' : 'text-red-600')) }}
                        bg-gray-50">
                        {{ $pct !== null ? $pct . '%' : '—' }}
                    </td>
                </tr>
                @endforeach
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@push('scripts')
<script>
    document.querySelectorAll('.docente-header').forEach(h => {
        h.addEventListener('click', e => {
            const doc = h.dataset.doc;
            const icon = h.querySelector('.toggle-icon');
            const filas = document.querySelectorAll(`tr.asig-row[data-doc="${doc}"]`);
            const colapsado = icon.textContent.trim() === '▶';
            filas.forEach(f => f.style.display = colapsado ? '' : 'none');
            icon.textContent = colapsado ? '▼' : '▶';
        });
    });
    document.getElementById('btn-expandir')?.addEventListener('click', () => {
        document.querySelectorAll('tr.asig-row').forEach(f => f.style.display = '');
        document.querySelectorAll('.docente-header .toggle-icon').forEach(i => i.textContent = '▼');
    });
    document.getElementById('btn-colapsar')?.addEventListener('click', () => {
        document.querySelectorAll('tr.asig-row').forEach(f => f.style.display = 'none');
        document.querySelectorAll('.docente-header .toggle-icon').forEach(i => i.textContent = '▶');
    });
</script>
@endpush

@endif

@endsection
