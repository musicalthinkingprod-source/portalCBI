@extends('layouts.app-sidebar')

@section('header', 'Informe de Digitación de Planillas')

@section('slot')

    {{-- Filtros --}}
    <div class="bg-white rounded-xl shadow p-5 mb-6">
        <form method="GET" action="{{ route('ciclos.informe') }}" class="flex flex-wrap gap-4 items-end">
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Año</label>
                <input type="number" name="anio" value="{{ $anio }}" min="2020" max="2099"
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm w-28 focus:outline-none focus:ring-2 focus:ring-blue-400">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Período</label>
                <select name="periodo"
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                    @foreach([1,2,3,4] as $p)
                        <option value="{{ $p }}" {{ $periodo == $p ? 'selected' : '' }}>Período {{ $p }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <button type="submit"
                    class="bg-blue-800 hover:bg-blue-700 text-white text-sm font-semibold px-5 py-2 rounded-lg transition">
                    Consultar
                </button>
            </div>
            <div class="ml-auto self-end">
                <a href="{{ route('ciclos.index') }}"
                    class="text-sm text-blue-700 hover:underline">← Control de digitación</a>
            </div>
        </form>
    </div>

    @if($diasGrid->isEmpty())
        <div class="bg-blue-50 border border-blue-200 text-blue-700 rounded-xl p-6 text-center text-sm">
            No hay días académicos registrados para el período <strong>{{ $periodo }}</strong> del año <strong>{{ $anio }}</strong>.
        </div>
    @elseif($asignaciones->isEmpty())
        <div class="bg-yellow-50 border border-yellow-200 text-yellow-700 rounded-xl p-6 text-center text-sm">
            No hay asignaciones de docentes registradas.
        </div>
    @else

    {{-- Leyenda --}}
    <div class="flex flex-wrap gap-4 mb-4 text-xs text-gray-500 items-center">
        <span>Cada celda muestra las <strong>notas registradas</strong> ese día académico (intensidad por cantidad).</span>
        <span class="inline-flex items-center gap-1.5">
            <span class="w-3 h-3 rounded-sm bg-emerald-200 border border-emerald-400 inline-block"></span> 1–4
        </span>
        <span class="inline-flex items-center gap-1.5">
            <span class="w-3 h-3 rounded-sm bg-emerald-400 border border-emerald-600 inline-block"></span> 5–14
        </span>
        <span class="inline-flex items-center gap-1.5">
            <span class="w-3 h-3 rounded-sm bg-emerald-600 border border-emerald-800 inline-block"></span> 15+
        </span>
        <span class="inline-flex items-center gap-1.5">
            <span class="w-3 h-3 rounded-sm bg-gray-100 border border-gray-300 inline-block"></span> Sin registro
        </span>
        <span class="inline-flex items-center gap-1.5">
            <span class="w-3 h-3 rounded-sm bg-gray-300 border border-gray-400 inline-block"></span> Día futuro
        </span>
    </div>

    @php
        $hoyStr = now()->toDateString();
        // Colores por ciclo (alternados para distinguir bloques)
        $coloresCiclo = [
            1 => 'bg-indigo-700',
            2 => 'bg-sky-700',
            3 => 'bg-teal-700',
            4 => 'bg-emerald-700',
            5 => 'bg-amber-700',
            6 => 'bg-orange-700',
            7 => 'bg-rose-700',
        ];
        $intensidad = function (int $n): string {
            if ($n <= 0)  return 'bg-gray-50 text-gray-300 border border-gray-200';
            if ($n < 5)   return 'bg-emerald-200 text-emerald-900 border border-emerald-400';
            if ($n < 15)  return 'bg-emerald-400 text-white border border-emerald-600 font-semibold';
            return 'bg-emerald-600 text-white border border-emerald-800 font-bold';
        };
    @endphp

    <div class="bg-white rounded-xl shadow overflow-hidden">
        <div class="px-5 py-3 bg-gray-800 text-white flex items-center justify-between">
            <div>
                <h3 class="font-bold text-sm uppercase tracking-wide">Planillas ponderadas · Año {{ $anio }} · Período {{ $periodo }}</h3>
                <p class="text-gray-400 text-xs mt-0.5">
                    {{ $asignaciones->count() }} asignaciones · {{ $ciclosAgrupados->count() }} ciclos · {{ $diasGrid->count() }} días académicos
                </p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="text-xs border-collapse" style="min-width: max-content">
                <thead>
                    {{-- Fila 1: Ciclo --}}
                    <tr class="bg-gray-100 border-b border-gray-300">
                        <th rowspan="3" class="px-4 py-2 text-left font-semibold text-gray-700 sticky left-0 bg-gray-100 z-20 min-w-[280px] border-r border-gray-300 align-bottom">
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
                            <th class="px-1 py-1 text-center text-[10px] font-semibold border-r border-gray-200 min-w-[36px]
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
                            <th class="px-1 pb-1 text-center text-[10px] font-normal border-r border-gray-200 min-w-[36px] leading-tight
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
                    @php $docenteAnterior = null; @endphp
                    @foreach($asignaciones as $asig)
                    @php
                        $key = $asig->CODIGO_EMP . '|' . $asig->CODIGO_MAT . '|' . $asig->CURSO;
                        $totalNotas    = 0;
                        $diasConReg    = 0;
                        $diasPasados   = 0;
                        foreach ($diasGrid as $d) {
                            $cnt = $conteos[$d->fecha][$key] ?? 0;
                            $totalNotas += $cnt;
                            if ($d->fecha <= $hoyStr) {
                                $diasPasados++;
                                if ($cnt > 0) $diasConReg++;
                            }
                        }
                        $pct = $diasPasados > 0 ? round($diasConReg / $diasPasados * 100) : null;
                        $nuevaFila = $docenteAnterior !== $asig->CODIGO_EMP;
                        $docenteAnterior = $asig->CODIGO_EMP;
                    @endphp
                    <tr class="hover:bg-blue-50/30 {{ $nuevaFila && !$loop->first ? 'border-t-2 border-gray-300' : '' }}">
                        <td class="px-4 py-1.5 sticky left-0 bg-white z-10 border-r border-gray-200 hover:bg-blue-50/30">
                            @if($nuevaFila)
                                <p class="font-bold text-gray-800 text-xs">{{ $asig->NOMBRE_DOC ?? $asig->CODIGO_EMP }}</p>
                            @else
                                <span class="text-gray-300 text-xs">└</span>
                            @endif
                            <p class="text-gray-500 {{ $nuevaFila ? 'pl-3' : 'pl-5' }} text-xs leading-tight">
                                {{ $asig->NOMBRE_MAT }} · <span class="font-semibold text-gray-700">{{ $asig->CURSO }}</span>
                            </p>
                        </td>

                        @foreach($diasGrid as $d)
                            @php
                                $cnt      = $conteos[$d->fecha][$key] ?? 0;
                                $esFuturo = $d->fecha > $hoyStr;
                            @endphp
                            <td class="p-0.5 text-center border-r border-gray-100">
                                @if($esFuturo)
                                    <span class="inline-block w-7 h-5 rounded-sm bg-gray-200 border border-gray-300"
                                        title="{{ $d->fecha }} · día futuro"></span>
                                @elseif($cnt > 0)
                                    <span class="inline-flex items-center justify-center w-7 h-5 rounded-sm text-[10px] {{ $intensidad($cnt) }}"
                                        title="{{ $d->fecha }} · {{ $cnt }} nota{{ $cnt === 1 ? '' : 's' }}{{ $d->evento ? ' · ' . $d->evento : '' }}">
                                        {{ $cnt }}
                                    </span>
                                @else
                                    <span class="inline-block w-7 h-5 rounded-sm {{ $intensidad(0) }}"
                                        title="{{ $d->fecha }} · sin registro"></span>
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
                </tbody>
            </table>
        </div>
    </div>

    @endif

@endsection
