@extends('layouts.app-sidebar')

@section('header', 'Informe de Digitación de Notas')

@section('slot')

    <div class="flex items-center justify-between mb-6">
        <p class="text-sm text-gray-500">Año lectivo <strong>{{ $anio }}</strong></p>
        <a href="{{ route('notas.index') }}"
            class="bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-semibold px-4 py-2 rounded-lg transition">
            ← Volver a notas
        </a>
    </div>

    @php
        $docentesInactivos = array_filter($docentes, fn($d) => ($d['estado'] ?? 'ACTIVO') !== 'ACTIVO');
    @endphp
    @if(!empty($docentesInactivos))
    <div class="mb-6 p-4 bg-red-50 border border-red-300 rounded-xl flex items-start justify-between gap-4">
        <div>
            <p class="font-semibold text-red-700 text-sm">
                ⚠️ {{ count($docentesInactivos) }} docente(s) INACTIVO(s) con asignaciones pendientes
            </p>
            <ul class="mt-1 text-xs text-red-600 list-disc list-inside space-y-0.5">
                @foreach($docentesInactivos as $di)
                <li>{{ $di['nombre'] }} ({{ $di['codigo'] }})</li>
                @endforeach
            </ul>
        </div>
        <a href="{{ route('admin.asignaciones') }}"
            class="shrink-0 bg-red-600 hover:bg-red-700 text-white text-xs font-semibold px-4 py-2 rounded-lg transition">
            Reasignar carga →
        </a>
    </div>
    @endif

    @if(empty($docentes))
        <div class="bg-yellow-50 border border-yellow-200 text-yellow-700 rounded-xl p-4 text-sm">
            No hay asignaciones registradas para docentes activos.
        </div>
    @else

    {{-- Tarjetas resumen --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        @php
            $totDoc = count($docentes);
            $completosPorPeriodo = [1=>0, 2=>0, 3=>0, 4=>0];
            foreach ($docentes as $d) {
                for ($p = 1; $p <= 4; $p++) {
                    if ($d['periodos'][$p]['esperadas'] > 0 &&
                        $d['periodos'][$p]['ingresadas'] >= $d['periodos'][$p]['esperadas']) {
                        $completosPorPeriodo[$p]++;
                    }
                }
            }
        @endphp
        @foreach([1,2,3,4] as $p)
        <div class="bg-white rounded-xl shadow p-4 text-center">
            <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Período {{ $p }}</p>
            <p class="text-2xl font-bold text-blue-800">
                {{ $completosPorPeriodo[$p] }}<span class="text-base font-normal text-gray-400">/{{ $totDoc }}</span>
            </p>
            <p class="text-xs text-gray-500 mt-1">docentes completos</p>
        </div>
        @endforeach
    </div>

    {{-- Botones expandir/colapsar todo --}}
    <div class="flex justify-end gap-2 mb-3">
        <button onclick="toggleTodos(true)"
            class="text-xs text-blue-700 hover:underline">Expandir todo</button>
        <span class="text-gray-300 text-xs">|</span>
        <button onclick="toggleTodos(false)"
            class="text-xs text-blue-700 hover:underline">Colapsar todo</button>
    </div>

    {{-- Tabla unificada --}}
    <div class="bg-white rounded-xl shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm border-collapse">
                <thead>
                    <tr class="bg-blue-800 text-white text-xs uppercase tracking-wide">
                        <th class="px-4 py-3 text-left border-r border-blue-700 w-48">Docente</th>
                        <th class="px-4 py-3 text-left border-r border-blue-700 w-24">Código</th>
                        <th class="px-4 py-3 text-left border-r border-blue-700">Materia</th>
                        <th class="px-4 py-3 text-center border-r border-blue-700 w-20">Curso</th>
                        <th class="px-4 py-3 text-center border-r border-blue-700 w-20">Alumnos</th>
                        <th class="px-4 py-3 text-center border-r border-blue-700 w-28">Período 1</th>
                        <th class="px-4 py-3 text-center border-r border-blue-700 w-28">Período 2</th>
                        <th class="px-4 py-3 text-center border-r border-blue-700 w-28">Período 3</th>
                        <th class="px-4 py-3 text-center w-28">Período 4</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($docentes as $doc)
                @php
                    $numAsig  = count($doc['asignaciones']);
                    $idGrupo  = 'grp-' . $loop->index;
                @endphp

                @php $inactivo = ($doc['estado'] ?? 'ACTIVO') !== 'ACTIVO'; @endphp

                {{-- Fila resumen del docente (siempre visible) --}}
                <tr class="{{ $inactivo ? 'bg-red-50 border-b-2 border-red-200' : 'bg-blue-50 border-b-2 border-blue-200' }} cursor-pointer select-none"
                    onclick="toggleGrupo('{{ $idGrupo }}', this)">

                    <td class="px-4 py-2 border-r {{ $inactivo ? 'border-red-200' : 'border-blue-200' }} font-bold {{ $inactivo ? 'text-red-700' : 'text-blue-900' }}">
                        <span class="toggle-icon mr-1 {{ $inactivo ? 'text-red-400' : 'text-blue-400' }} text-xs">▶</span>
                        {{ $doc['nombre'] }}
                        @if($inactivo)
                            <span class="ml-2 px-1.5 py-0.5 rounded text-xs font-semibold bg-red-100 text-red-600">{{ strtoupper($doc['estado']) }}</span>
                        @endif
                    </td>
                    <td class="px-4 py-2 border-r {{ $inactivo ? 'border-red-200' : 'border-blue-200' }} text-gray-500 text-xs font-mono">
                        {{ $doc['codigo'] }}
                    </td>
                    <td class="px-4 py-2 border-r {{ $inactivo ? 'border-red-200' : 'border-blue-200' }} text-xs text-gray-500 italic" colspan="3">
                        {{ $numAsig }} asignación(es)
                    </td>

                    @foreach([1,2,3,4] as $p)
                    @php
                        $tEsp  = $doc['periodos'][$p]['esperadas'];
                        $tIng  = $doc['periodos'][$p]['ingresadas'];
                        $tFalt = $tEsp - $tIng;
                        $tPct  = $tEsp > 0 ? round(($tIng / $tEsp) * 100) : 0;
                    @endphp
                    <td class="px-4 py-2 text-center {{ $p < 4 ? 'border-r border-blue-200' : '' }}">
                        @if($tEsp == 0)
                            <span class="text-gray-300 text-xs">—</span>
                        @else
                            <div class="flex flex-col items-center gap-0.5">
                                <span class="text-xs font-bold {{ $tFalt <= 0 ? 'text-green-700' : 'text-red-600' }}">
                                    {{ $tIng }}/{{ $tEsp }}
                                </span>
                                <div class="w-16 bg-gray-200 rounded-full h-1.5">
                                    <div class="h-1.5 rounded-full {{ $tPct >= 100 ? 'bg-green-500' : ($tPct >= 50 ? 'bg-yellow-400' : 'bg-red-400') }}"
                                         style="width:{{ $tPct }}%"></div>
                                </div>
                            </div>
                        @endif
                    </td>
                    @endforeach
                </tr>

                {{-- Filas de asignaciones (colapsables, ocultas por defecto) --}}
                @foreach($doc['asignaciones'] as $asig)
                <tr class="{{ $idGrupo }} hidden border-b border-gray-100 hover:bg-gray-50">
                    <td class="px-4 py-2 border-r border-gray-200 border-l-4 border-l-blue-300"></td>
                    <td class="px-4 py-2 border-r border-gray-200"></td>
                    <td class="px-4 py-2 border-r border-gray-200 font-medium">{{ $asig['materia'] }}</td>
                    <td class="px-4 py-2 text-center border-r border-gray-200">{{ $asig['curso'] }}</td>
                    <td class="px-4 py-2 text-center text-gray-500 border-r border-gray-200">{{ $asig['estudiantes'] }}</td>

                    @foreach([1,2,3,4] as $p)
                    @php
                        $esp  = $asig['periodos'][$p]['esperadas'];
                        $ing  = $asig['periodos'][$p]['ingresadas'];
                        $falt = $esp - $ing;
                    @endphp
                    <td class="px-4 py-2 text-center {{ $p < 4 ? 'border-r border-gray-200' : '' }}">
                        @if($esp == 0)
                            <span class="text-gray-300 text-xs">—</span>
                        @elseif($falt <= 0)
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-green-100 text-green-700 text-xs font-semibold">
                                ✓ {{ $ing }}
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 text-xs whitespace-nowrap">
                                <span class="font-semibold {{ $ing > 0 ? 'text-yellow-600' : 'text-gray-400' }}">{{ $ing }}</span>
                                <span class="text-gray-400">/{{ $esp }}</span>
                                <span class="px-1 py-0.5 rounded bg-red-100 text-red-600 font-bold">-{{ $falt }}</span>
                            </span>
                        @endif
                    </td>
                    @endforeach
                </tr>
                @endforeach

                @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @endif

    {{-- ══ Sección: Observaciones 2026 ══ --}}
    <div class="mt-10 mb-2">
        <h2 class="text-base font-bold text-gray-700">Diligenciamiento de Observaciones 2026</h2>
        <p class="text-xs text-gray-400 mt-0.5">Un registro por estudiante por período (máximo = total alumnos del curso)</p>
    </div>

    @if(empty($observacionesReport))
        <div class="bg-yellow-50 border border-yellow-200 text-yellow-700 rounded-xl p-4 text-sm">
            No hay directores de grupo asignados.
        </div>
    @else

    {{-- Tarjetas resumen observaciones --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        @php
            $totDir = count($observacionesReport);
            $obsCompletos = [1=>0, 2=>0, 3=>0, 4=>0];
            foreach ($observacionesReport as $d) {
                for ($p = 1; $p <= 4; $p++) {
                    if ($d['periodos'][$p]['esperadas'] > 0 &&
                        $d['periodos'][$p]['ingresadas'] >= $d['periodos'][$p]['esperadas']) {
                        $obsCompletos[$p]++;
                    }
                }
            }
        @endphp
        @foreach([1,2,3,4] as $p)
        <div class="bg-white rounded-xl shadow p-4 text-center">
            <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Período {{ $p }}</p>
            <p class="text-2xl font-bold text-purple-700">
                {{ $obsCompletos[$p] }}<span class="text-base font-normal text-gray-400">/{{ $totDir }}</span>
            </p>
            <p class="text-xs text-gray-500 mt-1">directores completos</p>
        </div>
        @endforeach
    </div>

    <div class="bg-white rounded-xl shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm border-collapse">
                <thead>
                    <tr class="bg-purple-800 text-white text-xs uppercase tracking-wide">
                        <th class="px-4 py-3 text-left border-r border-purple-700 w-48">Director de grupo</th>
                        <th class="px-4 py-3 text-left border-r border-purple-700 w-24">Código</th>
                        <th class="px-4 py-3 text-center border-r border-purple-700 w-16">Curso</th>
                        <th class="px-4 py-3 text-center border-r border-purple-700 w-20">Alumnos</th>
                        <th class="px-4 py-3 text-center border-r border-purple-700 w-28">Período 1</th>
                        <th class="px-4 py-3 text-center border-r border-purple-700 w-28">Período 2</th>
                        <th class="px-4 py-3 text-center border-r border-purple-700 w-28">Período 3</th>
                        <th class="px-4 py-3 text-center w-28">Período 4</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($observacionesReport as $dir)
                    @php $inactivo = ($dir['estado'] ?? 'ACTIVO') !== 'ACTIVO'; @endphp
                    <tr class="{{ $inactivo ? 'bg-red-50' : 'bg-white' }} hover:bg-gray-50 transition">
                        <td class="px-4 py-3 font-medium {{ $inactivo ? 'text-red-700' : 'text-gray-800' }}">
                            {{ $dir['nombre'] }}
                            @if($inactivo)
                                <span class="ml-1 px-1.5 py-0.5 rounded text-xs font-semibold bg-red-100 text-red-600">{{ strtoupper($dir['estado']) }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-xs font-mono text-gray-500 border-l border-gray-100">{{ $dir['codigo'] }}</td>
                        <td class="px-4 py-3 text-center font-semibold text-purple-700">{{ $dir['curso'] }}</td>
                        <td class="px-4 py-3 text-center text-gray-500">{{ $dir['periodos'][1]['esperadas'] }}</td>
                        @foreach([1,2,3,4] as $p)
                        @php
                            $esp  = $dir['periodos'][$p]['esperadas'];
                            $ing  = $dir['periodos'][$p]['ingresadas'];
                            $falt = $esp - $ing;
                            $pct  = $esp > 0 ? round(($ing / $esp) * 100) : 0;
                        @endphp
                        <td class="px-4 py-3 text-center {{ $p < 4 ? 'border-r border-gray-100' : '' }}">
                            @if($esp == 0)
                                <span class="text-gray-300 text-xs">—</span>
                            @elseif($falt <= 0)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-green-100 text-green-700 text-xs font-semibold">
                                    ✓ {{ $ing }}
                                </span>
                            @else
                                <div class="flex flex-col items-center gap-0.5">
                                    <span class="text-xs font-bold text-red-600">{{ $ing }}/{{ $esp }}</span>
                                    <div class="w-16 bg-gray-200 rounded-full h-1.5">
                                        <div class="h-1.5 rounded-full {{ $pct >= 100 ? 'bg-green-500' : ($pct >= 50 ? 'bg-yellow-400' : 'bg-red-400') }}"
                                             style="width:{{ $pct }}%"></div>
                                    </div>
                                </div>
                            @endif
                        </td>
                        @endforeach
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @endif

@endsection

@push('scripts')
<script>
    function toggleGrupo(id, fila) {
        const rows  = document.querySelectorAll(`tr.${id}`);
        const icon  = fila.querySelector('.toggle-icon');
        const visible = [...rows].some(r => !r.classList.contains('hidden'));

        rows.forEach(r => r.classList.toggle('hidden', visible));
        icon.textContent = visible ? '▶' : '▼';
    }

    function toggleTodos(expandir) {
        document.querySelectorAll('tr[onclick]').forEach(fila => {
            const match = fila.getAttribute('onclick').match(/'(grp-\d+)'/);
            if (!match) return;
            const id   = match[1];
            const rows = document.querySelectorAll(`tr.${id}`);
            const icon = fila.querySelector('.toggle-icon');
            rows.forEach(r => r.classList.toggle('hidden', !expandir));
            icon.textContent = expandir ? '▼' : '▶';
        });
    }
</script>
@endpush
