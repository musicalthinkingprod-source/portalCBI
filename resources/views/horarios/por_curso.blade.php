@extends('layouts.app-sidebar')

@section('header', 'Horario — Curso ' . $cursoActual)

@section('slot')
<div class="max-w-6xl mx-auto py-6 px-4">

    {{-- Encabezado y selector --}}
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
            <a href="{{ route('horarios.index') }}" class="text-blue-600 text-sm hover:underline">&larr; Horarios</a>
            <h1 class="text-2xl font-bold text-gray-800 mt-1">
                Horario — Curso <span class="text-blue-700">{{ $cursoActual }}</span>
            </h1>
        </div>

        <form action="{{ route('horarios.por_curso') }}" method="GET" class="flex items-center gap-2">
            <select name="curso" onchange="this.form.submit()"
                class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                @foreach($cursos as $c)
                    <option value="{{ $c }}" {{ $c === $cursoActual ? 'selected' : '' }}>{{ $c }}</option>
                @endforeach
            </select>
        </form>
    </div>

    @if(empty($diasConDatos))
        <div class="bg-yellow-50 border border-yellow-200 text-yellow-700 rounded-lg p-4 text-sm">
            No hay horario registrado para el curso <strong>{{ $cursoActual }}</strong>.
        </div>
    @else
        {{-- Grilla --}}
        <div class="bg-white rounded-xl shadow overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="bg-blue-700 text-white">
                        <th class="px-4 py-3 text-left font-semibold w-24">Hora</th>
                        @foreach($diasConDatos as $diaNum)
                            @php $esHoy = ($diaCicloHoy === $diaNum); @endphp
                            <th class="px-4 py-3 text-center font-semibold {{ $esHoy ? 'bg-yellow-400 text-gray-900' : '' }}">
                                {{ $dias[$diaNum] ?? 'Día '.$diaNum }}
                                @if(isset($proximaFecha[$diaNum]))
                                    <div class="text-xs font-normal opacity-80 mt-0.5">
                                        {{ $proximaFecha[$diaNum]->locale('es')->isoFormat('D MMM') }}
                                    </div>
                                @endif
                                @if($esHoy)
                                    <div class="text-xs font-bold mt-0.5">HOY</div>
                                @endif
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($horas as $horaNum => $horaLabel)
                        @php
                            $tieneContenido = collect($diasConDatos)->some(fn($d) => isset($grid[$horaNum][$d]));
                        @endphp
                        @if($tieneContenido)
                        <tr class="{{ $loop->even ? 'bg-gray-50' : 'bg-white' }} border-b border-gray-100 hover:bg-blue-50 transition">
                            <td class="px-4 py-3 font-medium text-gray-500 whitespace-nowrap">
                                {{ $horaLabel }}
                            </td>
                            @foreach($diasConDatos as $diaNum)
                                @php $celda = $grid[$horaNum][$diaNum] ?? null; @endphp
                                <td class="px-4 py-3 text-center">
                                    @if($celda)
                                        <div class="font-semibold text-gray-800">{{ $celda['materia'] }}</div>
                                        @if($celda['docente'])
                                            <div class="text-xs text-blue-600 mt-0.5">
                                                <a href="{{ route('horarios.por_docente', ['docente' => $celda['codigo_emp']]) }}"
                                                   class="hover:underline">
                                                    {{ $celda['docente'] }}
                                                </a>
                                            </div>
                                        @else
                                            <div class="text-xs text-gray-400 mt-0.5">Sin docente asignado</div>
                                        @endif
                                    @else
                                        <span class="text-gray-300">—</span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>

        <p class="text-xs text-gray-400 mt-3">
            Días con datos: {{ implode(', ', array_map(fn($d) => $dias[$d] ?? $d, $diasConDatos)) }}
        </p>
    @endif

</div>
@endsection
