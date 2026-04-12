@php
    $diasNombre  = [1=>'Día 1',2=>'Día 2',3=>'Día 3',4=>'Día 4',5=>'Día 5',6=>'Día 6'];
    $diaCicloHoy = \App\Models\Horario::diaCicloHoy();
@endphp

<div class="bg-white rounded-2xl shadow-sm border {{ $doc['es_propio'] ? 'border-blue-200' : 'border-gray-100' }} p-5 flex flex-col gap-3">

    {{-- Encabezado --}}
    <div class="flex items-start gap-3">
        <div class="w-10 h-10 rounded-xl {{ $doc['es_propio'] ? 'bg-blue-600' : 'bg-gray-200' }} flex items-center justify-center text-lg shrink-0">
            {{ $doc['es_propio'] ? '👤' : '👤' }}
        </div>
        <div class="min-w-0">
            <p class="font-bold text-gray-800 leading-tight text-sm">{{ $doc['nombre'] }}</p>
            @if($doc['es_propio'] && $doc['materias']->isNotEmpty())
                <p class="text-xs text-blue-600 font-medium mt-0.5">
                    ★ {{ $doc['materias']->implode(' · ') }}
                </p>
            @else
                <p class="text-xs text-gray-400 mt-0.5">Otro docente</p>
            @endif
        </div>
    </div>

    {{-- Slots de atención --}}
    <div class="space-y-2">
        @foreach($doc['slots'] as $slot)
        @php
            $prox    = $proximaFecha[$slot['dia']] ?? null;
            $esHoy   = $diaCicloHoy === $slot['dia'];
            $inicio  = $horaInicio[$slot['hora']] ?? '—';
            $fin     = $horaFin[$slot['hora']]    ?? '—';
        @endphp
        <div class="flex items-center gap-3 rounded-xl px-3 py-2 {{ $esHoy ? 'bg-indigo-50 border border-indigo-200' : 'bg-gray-50' }}">
            <div class="text-center shrink-0 w-10">
                <p class="text-xs font-bold {{ $esHoy ? 'text-indigo-700' : 'text-blue-700' }}">{{ $diasNombre[$slot['dia']] ?? 'Día '.$slot['dia'] }}</p>
                @if($esHoy)
                    <span class="text-[9px] font-bold uppercase text-indigo-500 leading-none">hoy</span>
                @endif
            </div>
            <div class="w-px h-6 bg-gray-200 shrink-0"></div>
            <div class="flex-1 min-w-0">
                <p class="text-xs font-semibold {{ $esHoy ? 'text-indigo-800' : 'text-gray-700' }}">
                    {{ $inicio }} – {{ $fin }}
                </p>
                @if($prox)
                    <p class="text-[10px] text-gray-400 leading-tight">
                        {{ $prox->locale('es')->isoFormat('ddd D [de] MMM') }}
                    </p>
                @endif
            </div>
            @if($esHoy)
                <span class="text-[10px] font-bold text-indigo-600 shrink-0">Hoy</span>
            @endif
        </div>
        @endforeach
    </div>

</div>
