@extends('layouts.padres')

@section('header', 'Inicio')

@section('slot')

@if(session('aviso'))
    <div class="mb-5 p-3 bg-yellow-100 border border-yellow-300 text-yellow-800 rounded-xl text-sm flex items-center gap-2">
        🔒 {{ session('aviso') }}
    </div>
@endif

@php
    $nombreCompleto = trim(
        ($estudiante->NOMBRE1 ?? '') . ' ' .
        ($estudiante->NOMBRE2 ?? '') . ' ' .
        ($estudiante->APELLIDO1 ?? '') . ' ' .
        ($estudiante->APELLIDO2 ?? '')
    );
    $diasNombre = [1=>'Día 1',2=>'Día 2',3=>'Día 3',4=>'Día 4',5=>'Día 5',6=>'Día 6'];
@endphp

{{-- Bienvenida --}}
<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-800">Hola, bienvenido</h2>
    <p class="text-gray-500 text-sm mt-0.5">
        Consultando información de <strong class="text-gray-700">{{ $nombreCompleto }}</strong>
    </p>
</div>

{{-- Fila 1: Día académico + Período/Ciclo --}}
<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4 mb-4">

    {{-- Día académico hoy --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
        <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-3">Hoy · {{ now()->locale('es')->isoFormat('dddd D [de] MMMM') }}</p>
        @if($diaCicloHoy)
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-xl bg-blue-600 flex items-center justify-center text-white text-xl font-bold shrink-0">
                    {{ $diaCicloHoy }}
                </div>
                <div>
                    <p class="text-lg font-bold text-gray-800">{{ $diasNombre[$diaCicloHoy] ?? 'Día '.$diaCicloHoy }}</p>
                    <p class="text-xs text-blue-600 font-medium">Día académico activo</p>
                </div>
            </div>
        @else
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-xl bg-gray-200 flex items-center justify-center text-gray-400 text-2xl shrink-0">
                    🏠
                </div>
                <div>
                    <p class="text-base font-semibold text-gray-500">Sin clases hoy</p>
                    <p class="text-xs text-gray-400">No es un día académico</p>
                </div>
            </div>
        @endif
    </div>

    {{-- Mañana --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
        <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-3">Mañana · {{ now()->addDay()->locale('es')->isoFormat('dddd D [de] MMMM') }}</p>
        @if($diaCicloManana)
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-xl bg-indigo-100 flex items-center justify-center text-indigo-600 text-xl font-bold shrink-0">
                    {{ $diaCicloManana }}
                </div>
                <div>
                    <p class="text-lg font-bold text-gray-800">{{ $diasNombre[$diaCicloManana] ?? 'Día '.$diaCicloManana }}</p>
                    <p class="text-xs text-indigo-600 font-medium">Día académico</p>
                </div>
            </div>
        @else
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-xl bg-gray-200 flex items-center justify-center text-gray-400 text-2xl shrink-0">
                    🏠
                </div>
                <div>
                    <p class="text-base font-semibold text-gray-500">Día no académico</p>
                </div>
            </div>
        @endif
    </div>

    {{-- Período y ciclo --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 sm:col-span-2 xl:col-span-1">
        <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-3">Año académico {{ date('Y') }}</p>
        @if($periodo && $ciclo)
            <div class="flex items-center gap-5">
                <div class="text-center">
                    <p class="text-3xl font-black text-blue-700">{{ $periodo }}</p>
                    <p class="text-xs text-gray-400 uppercase tracking-wide">Período</p>
                </div>
                <div class="w-px h-10 bg-gray-200"></div>
                <div class="text-center">
                    <p class="text-3xl font-black text-indigo-600">{{ $ciclo }}</p>
                    <p class="text-xs text-gray-400 uppercase tracking-wide">Ciclo</p>
                </div>
                <div class="ml-auto">
                    <div class="flex gap-1 flex-wrap justify-end">
                        @for($c = 1; $c <= 7; $c++)
                            <div class="w-5 h-5 rounded-full text-xs flex items-center justify-center font-bold
                                {{ $c < $ciclo ? 'bg-blue-600 text-white' : ($c === $ciclo ? 'bg-indigo-500 text-white ring-2 ring-indigo-200' : 'bg-gray-100 text-gray-400') }}">
                                {{ $c }}
                            </div>
                        @endfor
                    </div>
                    <p class="text-xs text-gray-400 text-right mt-1">ciclos del período</p>
                </div>
            </div>
        @else
            <p class="text-sm text-gray-400">No se ha iniciado ningún período</p>
        @endif
    </div>

</div>

{{-- Fila 2: Horarios (hoy + mañana si aplica) --}}
@php
    $tarjetasHorario = [];
    if ($diaCicloHoy) {
        $tarjetasHorario[] = [
            'titulo'      => 'Horario de hoy — ' . ($diasNombre[$diaCicloHoy] ?? 'Día '.$diaCicloHoy),
            'fecha'       => now()->locale('es')->isoFormat('dddd D [de] MMMM [de] YYYY'),
            'horario'     => $horarioHoy,
            'icoColor'    => 'text-blue-600',
            'horaBadge'   => 'bg-blue-50 text-blue-600',
        ];
    }
    if ($diaCicloManana) {
        $tarjetasHorario[] = [
            'titulo'      => 'Horario de mañana — ' . ($diasNombre[$diaCicloManana] ?? 'Día '.$diaCicloManana),
            'fecha'       => now()->addDay()->locale('es')->isoFormat('dddd D [de] MMMM [de] YYYY'),
            'horario'     => $horarioManana,
            'icoColor'    => 'text-indigo-600',
            'horaBadge'   => 'bg-indigo-50 text-indigo-600',
        ];
    }
@endphp
@if(!empty($tarjetasHorario))
<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4">
    @foreach($tarjetasHorario as $t)
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center gap-2">
            <span class="{{ $t['icoColor'] }} text-lg">📚</span>
            <div>
                <h3 class="font-semibold text-gray-800 text-sm">{{ $t['titulo'] }}</h3>
                <p class="text-xs text-gray-400">{{ $t['fecha'] }}</p>
            </div>
        </div>
        @if($t['horario']->isNotEmpty())
            <div class="divide-y divide-gray-50">
                @foreach($t['horario'] as $clase)
                    <div class="flex items-center gap-4 px-5 py-3 hover:bg-gray-50 transition">
                        <div class="w-7 h-7 rounded-full {{ $t['horaBadge'] }} text-xs font-bold flex items-center justify-center shrink-0">
                            {{ $clase->HORA }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-800 truncate">{{ $clase->NOMBRE_MAT ?? '—' }}</p>
                            @if($clase->NOMBRE_DOC)
                                <p class="text-xs text-gray-400 truncate">{{ $clase->NOMBRE_DOC }}</p>
                            @endif
                        </div>
                        <span class="text-xs text-gray-300">Hora {{ $clase->HORA }}</span>
                    </div>
                @endforeach
            </div>
        @else
            <div class="px-5 py-6 text-center text-sm text-gray-400">
                Sin clases registradas en el horario para este día
            </div>
        @endif
    </div>
    @endforeach
</div>
@endif

{{-- Horario completo (colapsable) --}}
@if(!empty($gridCompleto))
@php
    $horaInicio = [1=>'7:00',2=>'7:45',3=>'8:50',4=>'9:35',5=>'10:20',6=>'11:05',7=>'12:10',8=>'12:55'];
    $horaFin    = [1=>'7:45',2=>'8:30',3=>'9:35',4=>'10:20',5=>'11:05',6=>'11:50',7=>'12:55',8=>'13:40'];
    $descansos  = [2=>'8:30–8:50', 6=>'11:50–12:10'];
    $bloques    = [[1,2],[3,4],[5,6],[7,8]];

    // Merge: dos horas consecutivas con la misma materia → rowspan
    $merge = [];
    foreach ($bloques as [$h1,$h2]) {
        foreach ($diasConDatos as $d) {
            $c1 = $gridCompleto[$h1][$d]['materia'] ?? '';
            $c2 = $gridCompleto[$h2][$d]['materia'] ?? '';
            $merge[$h1][$d] = ($c1 !== '' && $c1 !== '—' && $c1 === $c2);
        }
    }
@endphp
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 mb-4" x-data="{ abierto: false }">
    <button @click="abierto = !abierto"
            class="w-full px-5 py-4 flex items-center justify-between text-left hover:bg-gray-50 transition rounded-2xl">
        <div class="flex items-center gap-2">
            <span class="text-blue-500 text-lg">🗓</span>
            <span class="font-semibold text-gray-800 text-sm">Horario completo del curso {{ $estudiante->CURSO ?? '' }}</span>
        </div>
        <svg class="w-4 h-4 text-gray-400 transition-transform duration-200"
             :class="abierto ? 'rotate-180' : ''"
             fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
    </button>

    <div x-show="abierto" x-transition class="border-t border-gray-100">
        <div class="overflow-x-auto">
            <table class="w-full text-xs border-collapse" style="min-width:520px">
                <thead>
                    <tr>
                        <th class="bg-blue-700 text-white px-3 py-2 text-left font-semibold w-24">Hora</th>
                        @foreach($diasConDatos as $d)
                        @php
                            $esHoy  = $diaCicloHoy === $d;
                            $prox   = $proximaFecha[$d] ?? null;
                        @endphp
                        <th class="px-2 py-2 text-center font-semibold {{ $esHoy ? 'bg-indigo-600 text-white' : 'bg-blue-700 text-white' }}">
                            Día {{ $d }}
                            @if($prox)
                                <div class="font-normal opacity-75 text-[10px] mt-0.5 leading-tight">
                                    {{ $prox->locale('es')->isoFormat('ddd D MMM') }}
                                </div>
                            @endif
                            @if($esHoy)
                                <div class="text-[9px] font-bold uppercase tracking-wide mt-0.5 opacity-90">hoy</div>
                            @endif
                        </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($bloques as [$h1,$h2])

                    {{-- Hora 1 del bloque --}}
                    <tr class="border-b border-gray-100 hover:bg-gray-50">
                        <td class="px-3 py-3 bg-gray-50 font-semibold text-blue-700 whitespace-nowrap text-xs">
                            {{ $h1 }}ª<br>
                            <span class="text-gray-400 font-normal">{{ $horaInicio[$h1] }}–{{ $horaFin[$h1] }}</span>
                        </td>
                        @foreach($diasConDatos as $d)
                        @php
                            $esHoy  = $diaCicloHoy === $d;
                            $merged = $merge[$h1][$d] ?? false;
                            $mat    = $gridCompleto[$h1][$d]['materia'] ?? null;
                            $doc    = $gridCompleto[$h1][$d]['docente'] ?? null;
                        @endphp
                        <td @if($merged) rowspan="2" @endif
                            class="px-2 py-3 text-center align-middle {{ $esHoy ? 'bg-indigo-50' : '' }} {{ $merged ? 'border-b-0' : '' }}">
                            @if($mat && $mat !== '—')
                                <div class="inline-flex flex-col items-center gap-1 rounded-xl px-3 py-2 w-full {{ $esHoy ? 'bg-indigo-100' : 'bg-blue-50' }}">
                                    <span class="font-bold {{ $esHoy ? 'text-indigo-700' : 'text-blue-700' }} leading-snug text-center text-xs">{{ $mat }}</span>
                                    @if($doc)
                                        <span class="text-gray-500 leading-tight text-center" style="font-size:10px">{{ $doc }}</span>
                                    @endif
                                </div>
                            @else
                                <span class="text-gray-200">—</span>
                            @endif
                        </td>
                        @endforeach
                    </tr>

                    {{-- Hora 2 del bloque --}}
                    <tr class="border-b border-gray-100 hover:bg-gray-50">
                        <td class="px-3 py-3 bg-gray-50 font-semibold text-blue-700 whitespace-nowrap text-xs">
                            {{ $h2 }}ª<br>
                            <span class="text-gray-400 font-normal">{{ $horaInicio[$h2] }}–{{ $horaFin[$h2] }}</span>
                        </td>
                        @foreach($diasConDatos as $d)
                        @if(!($merge[$h1][$d] ?? false))
                        @php
                            $esHoy = $diaCicloHoy === $d;
                            $mat   = $gridCompleto[$h2][$d]['materia'] ?? null;
                            $doc   = $gridCompleto[$h2][$d]['docente'] ?? null;
                        @endphp
                        <td class="px-2 py-3 text-center align-middle {{ $esHoy ? 'bg-indigo-50' : '' }}">
                            @if($mat && $mat !== '—')
                                <div class="inline-flex flex-col items-center gap-1 rounded-xl px-3 py-2 w-full {{ $esHoy ? 'bg-indigo-100' : 'bg-blue-50' }}">
                                    <span class="font-bold {{ $esHoy ? 'text-indigo-700' : 'text-blue-700' }} leading-snug text-center text-xs">{{ $mat }}</span>
                                    @if($doc)
                                        <span class="text-gray-500 leading-tight text-center" style="font-size:10px">{{ $doc }}</span>
                                    @endif
                                </div>
                            @else
                                <span class="text-gray-200">—</span>
                            @endif
                        </td>
                        @endif
                        @endforeach
                    </tr>

                    {{-- Descanso --}}
                    @if(isset($descansos[$h2]))
                    <tr>
                        <td colspan="{{ count($diasConDatos) + 1 }}"
                            class="px-3 py-1.5 text-center text-gray-400 bg-amber-50 border-y border-amber-100"
                            style="font-size:10px">
                            ☕ Descanso · {{ $descansos[$h2] }}
                        </td>
                    </tr>
                    @endif

                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

{{-- Fila 3: Info del estudiante --}}
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 mb-4">
    <div class="px-5 py-4 border-b border-gray-100">
        <h3 class="font-semibold text-gray-800 text-sm flex items-center gap-2">
            <span class="text-blue-500">👤</span> Datos del estudiante
        </h3>
    </div>
    <div class="px-5 py-4 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-x-6 gap-y-3 text-sm">
        <div>
            <p class="text-xs text-gray-400 uppercase tracking-wide">Código</p>
            <p class="font-semibold text-gray-800">{{ $estudiante->CODIGO }}</p>
        </div>
        <div>
            <p class="text-xs text-gray-400 uppercase tracking-wide">Curso</p>
            <p class="font-semibold text-gray-800">{{ $estudiante->CURSO ?? '—' }}</p>
        </div>
        @if($estudiante->GRADO)
        <div>
            <p class="text-xs text-gray-400 uppercase tracking-wide">Grado</p>
            <p class="font-semibold text-gray-800">{{ $estudiante->GRADO }}</p>
        </div>
        @endif
        @if($estudiante->SEDE)
        <div>
            <p class="text-xs text-gray-400 uppercase tracking-wide">Sede</p>
            <p class="font-semibold text-gray-800">{{ $estudiante->SEDE }}</p>
        </div>
        @endif
        @if($estudiante->ENTRADA)
        <div>
            <p class="text-xs text-gray-400 uppercase tracking-wide">Entrada</p>
            <p class="font-semibold text-gray-800">{{ $estudiante->ENTRADA }}</p>
        </div>
        @endif
        @if($estudiante->SALIDA)
        <div>
            <p class="text-xs text-gray-400 uppercase tracking-wide">Salida</p>
            <p class="font-semibold text-gray-800">{{ $estudiante->SALIDA }}</p>
        </div>
        @endif
        @if($estudiante->EPS)
        <div>
            <p class="text-xs text-gray-400 uppercase tracking-wide">EPS</p>
            <p class="font-semibold text-gray-800">{{ $estudiante->EPS }}</p>
        </div>
        @endif
        @if($estudiante->RH)
        <div>
            <p class="text-xs text-gray-400 uppercase tracking-wide">Tipo de sangre</p>
            <p class="font-semibold text-red-600">{{ $estudiante->RH }}</p>
        </div>
        @endif
        @if($estudiante->FECH_NACIMIENTO)
        <div>
            <p class="text-xs text-gray-400 uppercase tracking-wide">Fecha de nacimiento</p>
            <p class="font-semibold text-gray-800">{{ \Carbon\Carbon::parse($estudiante->FECH_NACIMIENTO)->locale('es')->isoFormat('D [de] MMMM [de] YYYY') }}</p>
        </div>
        @endif
        @if($estudiante->ACUDIENTE)
        <div>
            <p class="text-xs text-gray-400 uppercase tracking-wide">Acudiente</p>
            <p class="font-semibold text-gray-800">{{ $estudiante->ACUDIENTE }}</p>
        </div>
        @endif
    </div>
</div>

{{-- Alerta de deuda --}}
@if($bloqueado)
<div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-2xl flex items-start gap-3">
    <span class="text-2xl">⚠️</span>
    <div>
        <p class="font-semibold text-red-700 text-sm">Saldo pendiente de pago</p>
        <p class="text-red-600 text-sm">Tienes un saldo de <strong>$ {{ number_format($saldo, 0, ',', '.') }}</strong>. Algunos módulos están bloqueados hasta regularizar el pago.</p>
    </div>
</div>
@endif

{{-- Fila 4: Módulos por sección --}}
@php
    $secciones = collect($modulos)->groupBy('seccion');
    $iconoSeccion = [
        'Académico'       => '🎓',
        'Comunicaciones'  => '📣',
        'Financiero'      => '💳',
        'Contacto'        => '📞',
    ];
@endphp

<div class="space-y-6">
    @foreach($secciones as $nombreSeccion => $mods)
    <div>
        <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-3 flex items-center gap-1.5">
            <span>{{ $iconoSeccion[$nombreSeccion] ?? '' }}</span>
            {{ $nombreSeccion }}
        </p>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
            @foreach($mods as $mod)
                @if($mod['activo'])
                    <a href="{{ route($mod['route']) }}"
                       class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 flex flex-col items-center gap-2 hover:border-blue-300 hover:shadow-md transition group text-center">
                        <span class="text-3xl">{{ $mod['icon'] }}</span>
                        <span class="text-xs font-semibold text-gray-700 group-hover:text-blue-700 leading-tight">{{ $mod['label'] }}</span>
                    </a>
                @else
                    <div class="bg-gray-50 rounded-2xl border border-gray-100 p-4 flex flex-col items-center gap-2 cursor-not-allowed opacity-60 text-center">
                        <span class="text-3xl grayscale">{{ $mod['icon'] }}</span>
                        <span class="text-xs font-semibold text-gray-400 leading-tight">{{ $mod['label'] }}</span>
                        <span class="text-xs text-gray-400">
                            @if($mod['requiere_pago'] && $bloqueado) Saldo pendiente @else No disponible @endif
                        </span>
                    </div>
                @endif
            @endforeach
        </div>
    </div>
    @endforeach
</div>

@endsection
