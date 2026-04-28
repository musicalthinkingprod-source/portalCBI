@extends('layouts.app-sidebar')

@section('header', 'Informe General de Notas')

@section('slot')

<div class="max-w-7xl mx-auto">

    <p class="text-sm text-gray-500 mb-4">Año lectivo <strong>{{ $anio }}</strong> — basado en la nota final entregada por período.</p>

    {{-- Filtros --}}
    <form method="GET" action="{{ route('informes.notas') }}"
          class="bg-white rounded-2xl shadow p-5 mb-6 grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4">

        <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">Período</label>
            <select name="periodo" class="w-full border-gray-300 rounded-lg text-sm">
                <option value="acum" @selected($filtros['periodo'] === 'acum')>Acumulado año</option>
                @foreach([1,2,3,4] as $p)
                    <option value="{{ $p }}" @selected((string)$filtros['periodo'] === (string)$p)>Período {{ $p }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">Sede</label>
            <select name="sede" class="w-full border-gray-300 rounded-lg text-sm">
                <option value="">— Todas —</option>
                @foreach($opciones['sedes'] as $s)
                    <option value="{{ $s }}" @selected($filtros['sede']===$s)>{{ $s }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">Grado</label>
            <select name="grado" class="w-full border-gray-300 rounded-lg text-sm">
                <option value="">— Todos —</option>
                @foreach($opciones['grados'] as $g)
                    <option value="{{ $g }}" @selected($filtros['grado']===$g)>{{ $g }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">Curso</label>
            <select name="curso" class="w-full border-gray-300 rounded-lg text-sm">
                <option value="">— Todos —</option>
                @foreach($opciones['cursos'] as $c)
                    <option value="{{ $c }}" @selected($filtros['curso']===$c)>{{ $c }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">Docente</label>
            <select name="docente" class="w-full border-gray-300 rounded-lg text-sm">
                <option value="">— Todos —</option>
                @foreach($opciones['docentes'] as $d)
                    <option value="{{ $d->CODIGO_DOC }}" @selected($filtros['docente']===$d->CODIGO_DOC)>
                        {{ $d->NOMBRE_DOC }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">Asignatura</label>
            <select name="codigo_mat" class="w-full border-gray-300 rounded-lg text-sm">
                <option value="">— Todas —</option>
                @foreach($opciones['materias'] as $m)
                    <option value="{{ $m->CODIGO_MAT }}" @selected((string)$filtros['codigo_mat']===(string)$m->CODIGO_MAT)>
                        {{ $m->NOMBRE_MAT }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">Área</label>
            <select name="area" class="w-full border-gray-300 rounded-lg text-sm">
                <option value="">— Todas —</option>
                @foreach($opciones['areas'] as $a)
                    <option value="{{ $a }}" @selected((string)$filtros['area']===(string)$a)>{{ $a }}</option>
                @endforeach
            </select>
        </div>

        <div class="md:col-span-3 lg:col-span-4 grid grid-cols-2 md:grid-cols-4 gap-4 pt-3 border-t border-gray-100">
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Agrupar por</label>
                <select name="g1" class="w-full border-gray-300 rounded-lg text-sm">
                    @foreach($agrupaciones as $k => $a)
                        <option value="{{ $k }}" @selected($filtros['g1']===$k)>{{ $a['label'] }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Sub-agrupar (opcional)</label>
                <select name="g2" class="w-full border-gray-300 rounded-lg text-sm">
                    <option value="">— Ninguna —</option>
                    @foreach($agrupaciones as $k => $a)
                        <option value="{{ $k }}" @selected($filtros['g2']===$k)>{{ $a['label'] }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Métrica destacada</label>
                <select name="metrica" class="w-full border-gray-300 rounded-lg text-sm">
                    @foreach($metricas as $k => $lab)
                        <option value="{{ $k }}" @selected($filtros['metrica']===$k)>{{ $lab }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Ordenar por</label>
                <select name="orden" class="w-full border-gray-300 rounded-lg text-sm">
                    <option value="metrica_desc" @selected($filtros['orden']==='metrica_desc')>Métrica (mayor→menor)</option>
                    <option value="metrica_asc"  @selected($filtros['orden']==='metrica_asc')>Métrica (menor→mayor)</option>
                    <option value="grupo_asc"    @selected($filtros['orden']==='grupo_asc')>Grupo (A→Z)</option>
                    <option value="grupo_desc"   @selected($filtros['orden']==='grupo_desc')>Grupo (Z→A)</option>
                </select>
            </div>
        </div>

        <div class="md:col-span-3 lg:col-span-4 flex justify-end gap-2">
            <a href="{{ route('informes.notas') }}"
               class="px-4 py-2 text-sm font-semibold text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-lg">
                Limpiar
            </a>
            <button type="submit"
                    class="px-5 py-2 text-sm font-semibold text-white bg-blue-700 hover:bg-blue-800 rounded-lg shadow">
                Generar informe
            </button>
        </div>
    </form>

    {{-- Resumen --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-xl shadow p-4">
            <p class="text-xs text-gray-400 uppercase">Filas</p>
            <p class="text-2xl font-bold text-gray-800">{{ $resultados['total'] }}</p>
        </div>
        <div class="bg-white rounded-xl shadow p-4">
            <p class="text-xs text-gray-400 uppercase">Promedio global</p>
            <p class="text-2xl font-bold {{ ($resultados['globalProm'] ?? 0) >= 3 ? 'text-green-700' : 'text-red-600' }}">
                {{ $resultados['globalProm'] !== null ? number_format($resultados['globalProm'], 2) : '—' }}
            </p>
        </div>
        <div class="bg-white rounded-xl shadow p-4">
            <p class="text-xs text-gray-400 uppercase">Aprobados (≥ 3.0)</p>
            <p class="text-2xl font-bold text-blue-800">
                {{ $resultados['globalAprob'] !== null ? number_format($resultados['globalAprob'], 1) . '%' : '—' }}
            </p>
        </div>
    </div>

    {{-- Tabla de resultados --}}
    @if($resultados['rows']->isEmpty())
        <div class="bg-yellow-50 border border-yellow-200 text-yellow-700 rounded-xl p-4 text-sm">
            No hay notas que coincidan con los filtros seleccionados.
        </div>
    @else
        @php
            $g1Key   = $filtros['g1'];
            $g2Key   = $filtros['g2'] ?: null;
            $g1Label = $agrupaciones[$g1Key]['label'];
            $g2Label = $g2Key ? $agrupaciones[$g2Key]['label'] : null;

            $colMetrica = match($filtros['metrica']) {
                'aprobados' => 'aprobados_pct',
                'min'       => 'nota_min',
                'max'       => 'nota_max',
                'desv'      => 'desv',
                'cantidad'  => 'cantidad',
                default     => 'promedio',
            };
            $labelMetrica = $metricas[$filtros['metrica']];
        @endphp

        <div class="bg-white rounded-2xl shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-blue-800 text-white text-xs uppercase tracking-wide">
                        <tr>
                            @if($g1Key === 'estudiante')
                                <th class="px-3 py-2 text-left">Código</th>
                                <th class="px-3 py-2 text-left">Estudiante</th>
                                <th class="px-3 py-2 text-left">Curso</th>
                            @else
                                <th class="px-3 py-2 text-left">{{ $g1Label }}</th>
                            @endif

                            @if($g2Key === 'estudiante')
                                <th class="px-3 py-2 text-left">Sub-código</th>
                                <th class="px-3 py-2 text-left">Sub-{{ $g2Label }}</th>
                            @elseif($g2Key)
                                <th class="px-3 py-2 text-left">{{ $g2Label }}</th>
                            @endif

                            <th class="px-3 py-2 text-center bg-blue-900">{{ $labelMetrica }}</th>
                            <th class="px-3 py-2 text-center">Promedio</th>
                            <th class="px-3 py-2 text-center">% Aprob.</th>
                            <th class="px-3 py-2 text-center">Mín</th>
                            <th class="px-3 py-2 text-center">Máx</th>
                            <th class="px-3 py-2 text-center">Desv.</th>
                            <th class="px-3 py-2 text-center"># Notas</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($resultados['rows'] as $r)
                        @php
                            $prom    = (float) $r->promedio;
                            $colorP  = $prom >= 4 ? 'text-green-700' : ($prom >= 3 ? 'text-blue-700' : 'text-red-600');
                            $aprob   = (float) $r->aprobados_pct;
                            $colorA  = $aprob >= 80 ? 'text-green-700' : ($aprob >= 50 ? 'text-yellow-600' : 'text-red-600');
                            $valMet  = $r->{$colMetrica};
                        @endphp
                        <tr class="hover:bg-blue-50/50">
                            @if($g1Key === 'estudiante')
                                <td class="px-3 py-2 text-gray-700 font-mono text-xs">{{ $r->g1_codigo }}</td>
                                <td class="px-3 py-2 text-gray-800">{{ $r->g1_label }}</td>
                                <td class="px-3 py-2 text-gray-500 text-xs">{{ $r->g1_extra }}</td>
                            @else
                                <td class="px-3 py-2 text-gray-800 font-medium">{{ $r->g1_label ?? $r->g1_codigo }}</td>
                            @endif

                            @if($g2Key === 'estudiante')
                                <td class="px-3 py-2 text-gray-700 font-mono text-xs">{{ $r->g2_codigo }}</td>
                                <td class="px-3 py-2 text-gray-700">{{ $r->g2_label }}</td>
                            @elseif($g2Key)
                                <td class="px-3 py-2 text-gray-700">{{ $r->g2_label ?? $r->g2_codigo }}</td>
                            @endif

                            <td class="px-3 py-2 text-center bg-blue-50 font-bold">
                                {{ $filtros['metrica'] === 'aprobados' ? number_format((float)$valMet, 1) . '%' : number_format((float)$valMet, 2) }}
                            </td>
                            <td class="px-3 py-2 text-center font-bold {{ $colorP }}">{{ number_format($prom, 2) }}</td>
                            <td class="px-3 py-2 text-center font-semibold {{ $colorA }}">{{ number_format($aprob, 1) }}%</td>
                            <td class="px-3 py-2 text-center text-gray-700">{{ number_format((float)$r->nota_min, 2) }}</td>
                            <td class="px-3 py-2 text-center text-gray-700">{{ number_format((float)$r->nota_max, 2) }}</td>
                            <td class="px-3 py-2 text-center text-gray-700">{{ $r->desv !== null ? number_format((float)$r->desv, 2) : '—' }}</td>
                            <td class="px-3 py-2 text-center text-gray-500">{{ $r->cantidad }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

</div>

@endsection
