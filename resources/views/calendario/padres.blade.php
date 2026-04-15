@extends('layouts.padres')

@section('header', 'Calendario Académico')

@section('slot')
@php
    Carbon\Carbon::setLocale('es');
    $meses  = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
    $hoyStr = now()->toDateString();
    $mesPrev = $mes === 1 ? ['mes' => 12, 'anio' => $anio - 1] : ['mes' => $mes - 1, 'anio' => $anio];
    $mesSig  = $mes === 12 ? ['mes' => 1,  'anio' => $anio + 1] : ['mes' => $mes + 1, 'anio' => $anio];
@endphp

<div class="max-w-4xl mx-auto space-y-6">

    {{-- Hoy --}}
    @if($hoy && $hoy->dia_ciclo > 0)
    <div class="bg-white rounded-xl shadow p-4 flex items-center gap-5">
        <div class="flex flex-col items-center justify-center bg-blue-700 text-white rounded-xl w-20 h-20 shrink-0">
            <span class="text-3xl font-black leading-none">D{{ $hoy->dia_ciclo }}</span>
            <span class="text-xs mt-1 opacity-80">Hoy</span>
        </div>
        <div>
            <p class="text-xs text-gray-400 uppercase tracking-widest">{{ \Carbon\Carbon::parse($hoy->fecha)->isoFormat('dddd D [de] MMMM') }}</p>
            <p class="text-lg font-semibold text-gray-800">Día académico {{ $hoy->dia_ciclo }} de 6</p>
            @foreach($eventosHoy as $evH)
                <p class="text-sm text-blue-700 mt-0.5">{{ $evH->evento }}</p>
            @endforeach
        </div>
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Grilla --}}
        <div class="lg:col-span-2 bg-white rounded-xl shadow p-5">
            <div class="flex items-center justify-between mb-4">
                <a href="{{ route('padres.calendario', $mesPrev) }}"
                   class="text-gray-400 hover:text-blue-700 transition text-lg font-bold px-2">‹</a>
                <h2 class="text-base font-semibold text-blue-900">{{ $meses[$mes - 1] }} {{ $anio }}</h2>
                <a href="{{ route('padres.calendario', $mesSig) }}"
                   class="text-gray-400 hover:text-blue-700 transition text-lg font-bold px-2">›</a>
            </div>

            <div class="grid grid-cols-7 text-center text-xs font-semibold text-gray-400 uppercase mb-1">
                @foreach(['Lun','Mar','Mié','Jue','Vie','Sáb','Dom'] as $d)
                    <div class="py-1">{{ $d }}</div>
                @endforeach
            </div>

            @php
                $primerDia = $inicio->dayOfWeekIso;
                $diasEnMes = $inicio->daysInMonth;
            @endphp

            <div class="grid grid-cols-7 gap-1">
                @for($i = 1; $i < $primerDia; $i++)<div></div>@endfor

                @for($d = 1; $d <= $diasEnMes; $d++)
                    @php
                        $fechaStr      = \Carbon\Carbon::create($anio, $mes, $d)->toDateString();
                        $entrada       = $diasMes[$fechaStr] ?? null;
                        $esHoy         = $fechaStr === $hoyStr;
                        $eventosDelDia = $eventosPorFecha[$fechaStr] ?? collect();
                        $tieneEvento   = $eventosDelDia->isNotEmpty();
                    @endphp
                    <div class="relative rounded-lg p-1 text-center text-xs min-h-[52px] flex flex-col items-center justify-start pt-1
                        {{ $esHoy ? 'bg-blue-700 text-white' : ($entrada && $entrada->dia_ciclo > 0 ? 'bg-gray-50 hover:bg-blue-50' : 'text-gray-300') }}
                        {{ $tieneEvento ? 'ring-1 ring-yellow-400' : '' }}">
                        <span class="font-semibold text-sm leading-none">{{ $d }}</span>
                        @if($entrada && $entrada->dia_ciclo > 0)
                            <span class="mt-0.5 text-[10px] font-bold {{ $esHoy ? 'text-blue-200' : 'text-blue-600' }}">D{{ $entrada->dia_ciclo }}</span>
                        @endif
                        @foreach($eventosDelDia->take(2) as $evCell)
                            <span class="mt-0.5 text-[9px] leading-tight {{ $esHoy ? 'text-yellow-200' : 'text-yellow-700' }} font-medium w-full overflow-hidden" style="display:-webkit-box;-webkit-line-clamp:1;-webkit-box-orient:vertical;overflow:hidden;" title="{{ $evCell->evento }}">{{ $evCell->evento }}</span>
                        @endforeach
                        @if($eventosDelDia->count() > 2)
                            <span class="text-[8px] text-gray-400">+{{ $eventosDelDia->count()-2 }} más</span>
                        @endif
                    </div>
                @endfor
            </div>

            <div class="mt-3 flex flex-wrap gap-3 text-xs text-gray-500">
                <span class="flex items-center gap-1"><span class="w-3 h-3 rounded bg-blue-700 inline-block"></span> Hoy</span>
                <span class="flex items-center gap-1"><span class="w-3 h-3 rounded bg-gray-50 border border-gray-200 inline-block"></span> Día académico</span>
                <span class="flex items-center gap-1"><span class="ring-1 ring-yellow-400 rounded w-3 h-3 inline-block"></span> Con evento</span>
            </div>
        </div>

        {{-- Próximos eventos --}}
        <div class="bg-white rounded-xl shadow p-5">
            <h3 class="text-sm font-semibold text-blue-900 mb-3">Próximos 30 días</h3>
            @if($proximosEventos->isEmpty())
                <p class="text-xs text-gray-400 italic">Sin eventos próximos.</p>
            @else
                <ul class="space-y-3">
                    @foreach($proximosEventos as $ev)
                    <li class="border-l-2 border-yellow-400 pl-3">
                        <p class="text-xs text-gray-400">
                            {{ \Carbon\Carbon::parse($ev->fecha)->isoFormat('ddd D MMM') }}
                            @if($ev->dia_ciclo > 0)· <span class="font-semibold text-blue-600">D{{ $ev->dia_ciclo }}</span>@endif
                        </p>
                        <p class="text-sm text-gray-800 leading-snug">{{ $ev->evento }}</p>
                    </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</div>
@endsection
