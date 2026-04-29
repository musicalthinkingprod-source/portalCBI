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

    @if($ciclos->isEmpty())
        <div class="bg-blue-50 border border-blue-200 text-blue-700 rounded-xl p-6 text-center text-sm">
            No hay ciclos definidos para el año <strong>{{ $anio }}</strong>.
            <a href="{{ route('ciclos.index') }}" class="underline font-semibold ml-1">Crear ciclos →</a>
        </div>
    @elseif($asignaciones->isEmpty())
        <div class="bg-yellow-50 border border-yellow-200 text-yellow-700 rounded-xl p-6 text-center text-sm">
            No hay asignaciones de docentes registradas.
        </div>
    @else

    {{-- Leyenda --}}
    <div class="flex flex-wrap gap-4 mb-4 text-xs text-gray-500 items-center">
        <span>Cada celda muestra el <strong>número de notas</strong> registradas en la planilla ponderada durante ese ciclo.</span>
        <span class="inline-flex items-center gap-1.5">
            <span class="w-4 h-4 rounded bg-green-100 border border-green-400 inline-block"></span> Registró notas
        </span>
        <span class="inline-flex items-center gap-1.5">
            <span class="w-4 h-4 rounded bg-red-100 border border-red-300 inline-block"></span> Sin registro
        </span>
        <span class="inline-flex items-center gap-1.5">
            <span class="w-4 h-4 rounded bg-gray-100 border border-gray-300 inline-block"></span> Ciclo futuro
        </span>
    </div>

    <div class="bg-white rounded-xl shadow overflow-hidden">
        <div class="px-5 py-3 bg-gray-800 text-white flex items-center justify-between">
            <div>
                <h3 class="font-bold text-sm uppercase tracking-wide">Planillas ponderadas · Año {{ $anio }} · Período {{ $periodo }}</h3>
                <p class="text-gray-400 text-xs mt-0.5">{{ $asignaciones->count() }} asignaciones · {{ $ciclos->count() }} ciclos</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="text-xs border-collapse w-full" style="min-width: max-content">
                <thead>
                    <tr class="bg-gray-100 border-b-2 border-gray-300">
                        <th class="px-4 py-3 text-left font-semibold text-gray-700 sticky left-0 bg-gray-100 z-10 min-w-[280px] border-r border-gray-300">
                            Docente / Materia / Curso
                        </th>
                        @foreach($ciclos as $ciclo)
                        @php
                            $hoy    = now()->toDateString();
                            $estado = $hoy < $ciclo->fecha_inicio ? 'futuro'
                                    : ($hoy > $ciclo->fecha_fin   ? 'pasado' : 'activo');
                        @endphp
                        <th class="px-3 py-3 text-center font-semibold text-gray-700 min-w-[100px] border-r border-gray-200
                            {{ $estado === 'activo' ? 'bg-blue-50 text-blue-800' : '' }}">
                            <span class="block">{{ $ciclo->nombre }}</span>
                            <span class="block font-normal text-gray-400 text-xs">
                                {{ \Carbon\Carbon::parse($ciclo->fecha_inicio)->format('d/m') }}
                                – {{ \Carbon\Carbon::parse($ciclo->fecha_fin)->format('d/m') }}
                            </span>
                            @if($estado === 'activo')
                                <span class="block text-blue-500 text-xs font-medium">● En curso</span>
                            @endif
                        </th>
                        @endforeach
                        <th class="px-3 py-3 text-center font-semibold text-gray-700 min-w-[80px] bg-gray-200">
                            Total notas
                        </th>
                        <th class="px-3 py-3 text-center font-semibold text-gray-700 min-w-[80px] bg-gray-200">
                            % Ciclos
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @php $docenteAnterior = null; @endphp
                    @foreach($asignaciones as $asig)
                    @php
                        $key = $asig->CODIGO_EMP . '|' . $asig->CODIGO_MAT . '|' . $asig->CURSO;
                        $totalNotas = 0;
                        foreach ($ciclos as $c) {
                            $totalNotas += $conteos[$c->id][$key] ?? 0;
                        }
                        $hoy2 = now()->toDateString();
                        $ciclosPasados = $ciclos->filter(fn($c) => $hoy2 >= $c->fecha_inicio);
                        $ciclosConReg  = $ciclosPasados->filter(fn($c) => ($conteos[$c->id][$key] ?? 0) > 0)->count();
                        $totalPasados  = $ciclosPasados->count();
                        $pct = $totalPasados > 0 ? round($ciclosConReg / $totalPasados * 100) : null;
                        $nuevaFila = $docenteAnterior !== $asig->CODIGO_EMP;
                        $docenteAnterior = $asig->CODIGO_EMP;
                    @endphp
                    <tr class="hover:bg-gray-50 {{ $nuevaFila && !$loop->first ? 'border-t-2 border-gray-300' : '' }}">
                        <td class="px-4 py-2 sticky left-0 bg-white z-10 border-r border-gray-200">
                            @if($nuevaFila)
                                <p class="font-bold text-gray-800 text-xs">{{ $asig->NOMBRE_DOC ?? $asig->CODIGO_EMP }}</p>
                            @else
                                <p class="text-gray-300 text-xs pl-2">└</p>
                            @endif
                            <p class="text-gray-500 {{ $nuevaFila ? 'pl-3' : 'pl-5' }} text-xs">
                                {{ $asig->NOMBRE_MAT }} · <span class="font-semibold text-gray-700">{{ $asig->CURSO }}</span>
                            </p>
                        </td>

                        @foreach($ciclos as $ciclo)
                        @php
                            $esFuturo = now()->toDateString() < $ciclo->fecha_inicio;
                            $count    = $conteos[$ciclo->id][$key] ?? 0;
                        @endphp
                        <td class="px-2 py-2 text-center border-r border-gray-100">
                            @if($esFuturo)
                                <span class="inline-flex items-center justify-center w-10 h-6 rounded bg-gray-100 text-gray-400 text-xs font-medium" title="Ciclo futuro">—</span>
                            @elseif($count > 0)
                                <span class="inline-flex items-center justify-center w-10 h-6 rounded bg-green-100 text-green-800 font-bold border border-green-300"
                                    title="{{ $count }} notas registradas">{{ $count }}</span>
                            @else
                                <span class="inline-flex items-center justify-center w-10 h-6 rounded bg-red-100 text-red-600 font-bold border border-red-300"
                                    title="Sin registro">0</span>
                            @endif
                        </td>
                        @endforeach

                        <td class="px-3 py-2 text-center font-bold text-gray-700 bg-gray-50">
                            {{ $totalNotas > 0 ? $totalNotas : '—' }}
                        </td>
                        <td class="px-3 py-2 text-center font-bold
                            {{ $pct === null ? 'text-gray-300' : ($pct >= 80 ? 'text-green-600' : ($pct >= 50 ? 'text-yellow-600' : 'text-red-600')) }}
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
