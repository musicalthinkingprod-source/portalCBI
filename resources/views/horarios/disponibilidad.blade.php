@extends('layouts.app-sidebar')

@section('header', 'Disponibilidad docentes — ' . ($dias[$diaCiclo] ?? 'Día '.$diaCiclo))

@section('slot')
@php
    $horaInicio = [
        1=>'7:00',  2=>'7:45',
        3=>'8:50',  4=>'9:35',
        5=>'10:20', 6=>'11:05',
        7=>'12:10', 8=>'12:55',
    ];
    $horaFin = [
        1=>'7:45',  2=>'8:30',
        3=>'9:35',  4=>'10:20',
        5=>'11:05', 6=>'11:50',
        7=>'12:55', 8=>'13:40',
    ];
    $totalDocentes = $todosDocentes->count();
@endphp

<div class="max-w-5xl mx-auto py-6 px-4">

    {{-- Encabezado --}}
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div>
            <a href="{{ route('horarios.index') }}" class="text-indigo-600 text-sm hover:underline">&larr; Horarios</a>
            <h1 class="text-2xl font-bold text-gray-800 mt-1">
                Disponibilidad — <span class="text-indigo-700">{{ $dias[$diaCiclo] ?? 'Día '.$diaCiclo }}</span>
                @if($proximaFecha)
                    <span class="text-base font-normal text-gray-400 ml-2">
                        (próx. {{ $proximaFecha->locale('es')->isoFormat('ddd D MMM') }})
                    </span>
                @endif
            </h1>
        </div>

        {{-- Selector de día --}}
        <div class="flex gap-1 flex-wrap">
            @foreach($dias as $num => $label)
            <a href="{{ route('horarios.disponibilidad', ['dia' => $num]) }}"
               class="px-3 py-1.5 rounded-lg text-sm font-semibold transition
                      {{ $num === $diaCiclo
                           ? 'bg-indigo-700 text-white shadow'
                           : 'bg-white border border-gray-200 text-gray-600 hover:bg-indigo-50' }}">
                {{ $label }}
            </a>
            @endforeach
        </div>
    </div>

    {{-- Tabla por hora --}}
    <div class="space-y-4">
        @foreach($horas as $horaNum => $horaLabel)
        @php
            $libres   = $porHora[$horaNum]['libres'];
            $ocupados = $porHora[$horaNum]['ocupados'];
            $nLibres  = $libres->count();
            $pct      = $totalDocentes > 0 ? round($nLibres / $totalDocentes * 100) : 0;
        @endphp

        <div class="bg-white rounded-xl shadow overflow-hidden">
            {{-- Cabecera de hora --}}
            <div class="flex items-center justify-between px-5 py-3 bg-indigo-700 text-white">
                <div class="flex items-center gap-3">
                    <span class="font-bold">{{ $horaLabel }}</span>
                    <span class="text-indigo-200 text-sm">{{ $horaInicio[$horaNum] ?? '' }} – {{ $horaFin[$horaNum] ?? '' }}</span>
                </div>
                <div class="flex items-center gap-3 text-sm">
                    <span class="bg-white/20 rounded-full px-3 py-0.5 font-semibold">
                        {{ $nLibres }} libres
                    </span>
                    <span class="bg-white/10 rounded-full px-3 py-0.5 text-indigo-200">
                        {{ $ocupados->count() }} con clase
                    </span>
                </div>
            </div>

            {{-- Barra de progreso --}}
            <div class="h-1.5 bg-gray-100">
                <div class="h-full bg-emerald-400 transition-all"
                     style="width: {{ $pct }}%"></div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 divide-y md:divide-y-0 md:divide-x divide-gray-100">

                {{-- Libres --}}
                <div class="p-4">
                    <p class="text-xs font-semibold text-emerald-700 uppercase tracking-wide mb-2">
                        Disponibles ({{ $nLibres }})
                    </p>
                    @if($libres->isEmpty())
                        <p class="text-xs text-gray-400 italic">Todos los docentes tienen clase.</p>
                    @else
                        <div class="flex flex-wrap gap-1.5">
                            @foreach($libres as $doc)
                            <span class="bg-emerald-50 border border-emerald-200 text-emerald-800
                                         text-xs font-medium rounded-full px-2.5 py-1 whitespace-nowrap">
                                {{ $doc->NOMBRE_DOC }}
                            </span>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Con clase --}}
                <div class="p-4">
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2">
                        Con clase ({{ $ocupados->count() }})
                    </p>
                    @if($ocupados->isEmpty())
                        <p class="text-xs text-gray-400 italic">Ningún docente tiene clase.</p>
                    @else
                        <div class="flex flex-col gap-1">
                            @foreach($ocupados as $doc)
                            <div class="flex items-baseline gap-2">
                                <span class="text-xs font-semibold text-gray-700 whitespace-nowrap">{{ $doc['nombre'] }}</span>
                                <span class="text-xs text-gray-400 truncate" title="{{ $doc['clases'] }}">{{ $doc['clases'] }}</span>
                            </div>
                            @endforeach
                        </div>
                    @endif
                </div>

            </div>
        </div>
        @endforeach
    </div>

</div>
@endsection
