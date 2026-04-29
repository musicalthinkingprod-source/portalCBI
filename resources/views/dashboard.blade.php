{{-- Modificado: Dashboard con tarjetas de resumen con datos reales --}}
@extends('layouts.app-sidebar')

@section('header', 'Dashboard')

@section('slot')
@php
    Carbon\Carbon::setLocale('es');

    $visColors = [
        'todos'      => ['border' => '#16a34a', 'badge_bg' => '#dcfce7', 'badge_text' => '#166534'],
        'interno'    => ['border' => '#94a3b8', 'badge_bg' => '#f1f5f9', 'badge_text' => '#475569'],
        'docentes'   => ['border' => '#3b82f6', 'badge_bg' => '#dbeafe', 'badge_text' => '#1e40af'],
        'directivas' => ['border' => '#8b5cf6', 'badge_bg' => '#ede9fe', 'badge_text' => '#5b21b6'],
        'padres'     => ['border' => '#f97316', 'badge_bg' => '#ffedd5', 'badge_text' => '#9a3412'],
    ];

    $meses = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
@endphp

<div class="flex flex-col gap-6">

    {{-- Saludo --}}
    <div>
        <h2 class="text-xl font-bold text-gray-800">Hola, {{ auth()->user()->USER }} 👋</h2>
        <p class="text-sm text-gray-500 mt-0.5">
            {{ now()->isoFormat('dddd D [de] MMMM [de] YYYY') }}
            @if($hoy && $hoy->dia_ciclo > 0)
                · <span class="text-blue-600 font-semibold">Día académico {{ $hoy->dia_ciclo }}</span>
            @endif
        </p>
    </div>

    {{-- ── Fila de KPIs ── --}}
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">

        {{-- Cartera --}}
        @if($cartera)
        <div class="bg-white rounded-xl shadow p-5 flex flex-col gap-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Cartera</span>
                <span class="text-2xl">💰</span>
            </div>
            <div>
                <div class="text-2xl font-bold text-gray-800">${{ number_format($cartera['pct'], 1) }}%</div>
                <div class="text-xs text-gray-500">% de recaudo</div>
            </div>
            <div class="w-full bg-gray-100 rounded-full h-1.5">
                <div class="bg-green-500 h-1.5 rounded-full" style="width:{{ min($cartera['pct'], 100) }}%"></div>
            </div>
            <div class="grid grid-cols-2 gap-1 text-xs text-gray-500">
                <div>Facturado: <span class="font-semibold text-gray-700">${{ number_format($cartera['facturado'] / 1000000, 1) }}M</span></div>
                <div>Recaudo: <span class="font-semibold text-green-600">${{ number_format($cartera['recaudado'] / 1000000, 1) }}M</span></div>
                <div class="col-span-2">Pendiente: <span class="font-semibold text-red-500">${{ number_format($cartera['pendiente'] / 1000000, 1) }}M</span></div>
            </div>
            <a href="{{ route('cartera.index') }}" class="text-xs text-blue-600 hover:underline mt-auto">Ver más →</a>
        </div>
        @endif

        {{-- Digitación de notas --}}
        @if($notas)
        <div class="bg-white rounded-xl shadow p-5 flex flex-col gap-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Digitación notas</span>
                <span class="text-2xl">📋</span>
            </div>
            <div>
                <div class="text-2xl font-bold text-gray-800">{{ $notas['pct'] }}%</div>
                <div class="text-xs text-gray-500">Período {{ $notas['periodo'] }}</div>
            </div>
            <div class="w-full bg-gray-100 rounded-full h-1.5">
                <div class="h-1.5 rounded-full {{ $notas['pct'] >= 80 ? 'bg-green-500' : ($notas['pct'] >= 50 ? 'bg-yellow-400' : 'bg-red-400') }}"
                    style="width:{{ $notas['pct'] }}%"></div>
            </div>
            <div class="text-xs text-gray-500">
                <span class="font-semibold text-gray-700">{{ $notas['con_notas'] }}</span> de
                <span class="font-semibold text-gray-700">{{ $notas['total'] }}</span> asignaciones con nota final
            </div>
            <a href="{{ route('notas.reporte') }}" class="text-xs text-blue-600 hover:underline mt-auto">Ver más →</a>
        </div>
        @endif

        {{-- Asistencia del día --}}
        @if($asistencia)
        <div class="bg-white rounded-xl shadow p-5 flex flex-col gap-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Asistencia hoy</span>
                <span class="text-2xl">✅</span>
            </div>
            <div>
                <div class="text-2xl font-bold text-gray-800">{{ $asistencia['pct'] }}%</div>
                <div class="text-xs text-gray-500">{{ now()->isoFormat('D MMM') }}</div>
            </div>
            <div class="w-full bg-gray-100 rounded-full h-1.5">
                <div class="bg-blue-500 h-1.5 rounded-full" style="width:{{ $asistencia['pct'] }}%"></div>
            </div>
            <div class="text-xs text-gray-500">
                <span class="font-semibold text-gray-700">{{ $asistencia['registrados'] }}</span> de
                <span class="font-semibold text-gray-700">{{ $asistencia['total'] }}</span> estudiantes registrados
            </div>
            <a href="{{ route('asistencia.reporte') }}" class="text-xs text-blue-600 hover:underline mt-auto">Ver más →</a>
        </div>
        @endif

        {{-- Calendario hoy + mañana --}}
        <div class="bg-white rounded-xl shadow p-5 flex flex-col gap-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Hoy / Mañana</span>
                <span class="text-2xl">📆</span>
            </div>
            @if($hoy && $hoy->dia_ciclo > 0)
                <div>
                    <div class="text-2xl font-bold text-blue-700">Día {{ $hoy->dia_ciclo }}</div>
                    <div class="text-xs text-gray-500">Hoy · {{ now()->isoFormat('dddd') }}</div>
                </div>
            @else
                <div>
                    <div class="text-2xl font-bold text-gray-400">—</div>
                    <div class="text-xs text-gray-400">Hoy · Día no académico</div>
                </div>
            @endif
            @if($hoy && $hoy->evento)
                <div class="text-xs bg-yellow-50 border border-yellow-200 rounded-lg px-3 py-2 text-yellow-800">
                    📌 {{ $hoy->evento }}
                </div>
            @endif

            <div class="border-t border-gray-100 pt-2">
                @if($manana && $manana->dia_ciclo > 0)
                    <div>
                        <div class="text-lg font-bold text-indigo-600">Día {{ $manana->dia_ciclo }}</div>
                        <div class="text-xs text-gray-500">Mañana · {{ now()->addDay()->isoFormat('dddd') }}</div>
                    </div>
                @else
                    <div>
                        <div class="text-lg font-bold text-gray-400">—</div>
                        <div class="text-xs text-gray-400">Mañana · Día no académico</div>
                    </div>
                @endif
                @if($manana && $manana->evento)
                    <div class="text-xs bg-yellow-50 border border-yellow-200 rounded-lg px-3 py-2 text-yellow-800 mt-2">
                        📌 {{ $manana->evento }}
                    </div>
                @endif
            </div>

            @php
                $calRoute = (str_starts_with($profile, 'DOC') || str_starts_with($profile, 'COR')) ? route('calendario.docente') : route('calendario.index');
            @endphp
            <a href="{{ $calRoute }}" class="text-xs text-blue-600 hover:underline mt-auto">Ver más →</a>
        </div>

    </div>

    {{-- ── Fila: Notas por ciclo (planilla ponderada) ── --}}
    @if($ciclosNotas && count($ciclosNotas['ciclos']) > 0)
    <div class="bg-white rounded-xl shadow p-5">
        <div class="flex items-center justify-between mb-3">
            <div>
                <h3 class="text-sm font-bold text-gray-700">Notas por ciclo · Período {{ $ciclosNotas['periodo'] }}</h3>
                <p class="text-xs text-gray-500 mt-0.5">
                    Notas registradas en planilla ponderada ·
                    <span class="font-semibold text-gray-700">{{ number_format($ciclosNotas['totalP']) }}</span> en el período
                </p>
            </div>
            <a href="{{ route('control.planilla', ['periodo' => $ciclosNotas['periodo']]) }}"
               class="text-xs text-blue-600 hover:underline">Ver detalle →</a>
        </div>

        <div class="grid grid-cols-7 gap-2">
            @foreach($ciclosNotas['ciclos'] as $c)
            @php
                $max = max($ciclosNotas['max'], 1);
                $altura = $c['futuro'] ? 0 : round(($c['total'] / $max) * 100);
                $color = $c['futuro']
                    ? 'bg-gray-200'
                    : ($c['total'] === 0
                        ? 'bg-red-300'
                        : ($c['activo'] ? 'bg-blue-500' : 'bg-green-500'));
                $inicioFmt = \Carbon\Carbon::parse($c['inicio'])->isoFormat('D MMM');
            @endphp
            <div class="flex flex-col items-center gap-1">
                <div class="text-xs font-bold {{ $c['futuro'] ? 'text-gray-400' : 'text-gray-700' }}">
                    {{ $c['total'] > 0 ? number_format($c['total']) : ($c['futuro'] ? '—' : '0') }}
                </div>
                <div class="w-full h-20 bg-gray-50 rounded flex items-end overflow-hidden"
                     title="Ciclo {{ $c['numero'] }} · desde {{ $inicioFmt }}">
                    <div class="{{ $color }} w-full transition-all"
                         style="height: {{ max($altura, $c['futuro'] ? 0 : 4) }}%"></div>
                </div>
                <div class="text-xs text-gray-500">C{{ $c['numero'] }}</div>
                @if($c['activo'])
                    <div class="text-[10px] font-semibold text-blue-600">● en curso</div>
                @elseif($c['futuro'])
                    <div class="text-[10px] text-gray-300">futuro</div>
                @endif
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- ── Fila inferior: Digitación detalle + Próximos eventos ── --}}
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">

        {{-- Próximos eventos --}}
        <div class="bg-white rounded-xl shadow p-6">
            <h3 class="text-sm font-bold text-gray-700 mb-4">Próximos eventos</h3>
            @if($proximosEventos->isEmpty())
                <p class="text-sm text-gray-400 italic">Sin eventos próximos registrados.</p>
            @else
                <ul class="flex flex-col gap-3">
                    @foreach($proximosEventos as $ev)
                    @php
                        $vc = $visColors[$ev->visibilidad] ?? $visColors['interno'];
                        $esHoy = $ev->fecha === today()->toDateString();
                    @endphp
                    <li class="flex items-start gap-3 border-l-4 pl-3"
                        style="border-color:{{ $vc['border'] }}">
                        <div class="flex-1 min-w-0">
                            <div class="text-xs font-semibold text-gray-400">
                                {{ \Carbon\Carbon::parse($ev->fecha)->isoFormat('ddd D MMM') }}
                                @if($ev->dia_ciclo > 0)
                                    · <span class="text-blue-500">Día {{ $ev->dia_ciclo }}</span>
                                @endif
                                @if($esHoy)
                                    <span class="ml-1 bg-blue-100 text-blue-700 text-[10px] font-bold px-2 py-0.5 rounded-full">HOY</span>
                                @endif
                            </div>
                            <div class="text-sm text-gray-700 mt-0.5">{{ $ev->evento }}</div>
                        </div>
                    </li>
                    @endforeach
                </ul>
            @endif
            <a href="{{ $calRoute }}" class="text-xs text-blue-600 hover:underline mt-4 block">Ver calendario completo →</a>
        </div>

        {{-- Accesos rápidos --}}
        <div class="bg-white rounded-xl shadow p-6">
            <h3 class="text-sm font-bold text-gray-700 mb-4">Accesos rápidos</h3>
            <div class="grid grid-cols-2 gap-2">
                @if($notas !== null || str_starts_with($profile, 'DOC') || str_starts_with($profile, 'COR'))
                <a href="{{ route('notas.index') }}"
                   class="flex items-center gap-2 px-3 py-3 bg-blue-50 hover:bg-blue-100 rounded-xl text-sm text-blue-800 font-medium transition">
                    📋 Notas
                </a>
                @endif
                @if(str_starts_with($profile, 'Sec') || in_array($profile, ['SuperAd','Admin']))
                <a href="{{ route('asistencia.registro') }}"
                   class="flex items-center gap-2 px-3 py-3 bg-green-50 hover:bg-green-100 rounded-xl text-sm text-green-800 font-medium transition">
                    ✅ Registrar asistencia
                </a>
                @endif
                @if($cartera !== null)
                <a href="{{ route('pagos.index') }}"
                   class="flex items-center gap-2 px-3 py-3 bg-purple-50 hover:bg-purple-100 rounded-xl text-sm text-purple-800 font-medium transition">
                    💳 Pagos
                </a>
                <a href="{{ route('facturacion.index') }}"
                   class="flex items-center gap-2 px-3 py-3 bg-orange-50 hover:bg-orange-100 rounded-xl text-sm text-orange-800 font-medium transition">
                    🧾 Facturación
                </a>
                @endif
                @if($profile === 'SuperAd')
                <a href="{{ route('asistencia-personal.reemplazos') }}"
                   class="flex items-center gap-2 px-3 py-3 bg-yellow-50 hover:bg-yellow-100 rounded-xl text-sm text-yellow-800 font-medium transition">
                    🔄 Reemplazos
                </a>
                <a href="{{ route('admin.usuarios') }}"
                   class="flex items-center gap-2 px-3 py-3 bg-gray-50 hover:bg-gray-100 rounded-xl text-sm text-gray-700 font-medium transition">
                    👤 Usuarios
                </a>
                @endif
            </div>
        </div>

    </div>

</div>
@endsection
