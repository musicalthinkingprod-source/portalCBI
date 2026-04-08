@extends('layouts.app-sidebar')

@section('header', 'Informe de Seguimiento Cartera')

@section('slot')

@php
    $tipos = [
        'Llamada'  => ['color' => 'bg-blue-100 text-blue-800',    'icono' => '📞'],
        'Acuerdo'  => ['color' => 'bg-green-100 text-green-800',   'icono' => '🤝'],
        'WhatsApp' => ['color' => 'bg-emerald-100 text-emerald-800','icono' => '💬'],
        'Email'    => ['color' => 'bg-purple-100 text-purple-800', 'icono' => '✉️'],
        'Visita'   => ['color' => 'bg-orange-100 text-orange-800', 'icono' => '🏠'],
        'Suspensión' => ['color' => 'bg-red-100 text-red-800',      'icono' => '🚫'],
        'Nota'     => ['color' => 'bg-gray-100 text-gray-700',     'icono' => '📝'],
    ];
@endphp

    {{-- Tarjetas resumen por tipo --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 mb-6">
        @foreach($tipos as $tipo => $cfg)
        @php $count = $totalesPorTipo[$tipo]->total ?? 0; @endphp
        <div class="bg-white rounded-xl shadow px-4 py-3 flex flex-col items-center gap-1">
            <span class="text-xl">{{ $cfg['icono'] }}</span>
            <span class="text-lg font-bold text-gray-800">{{ $count }}</span>
            <span class="text-xs font-semibold px-2 py-0.5 rounded-full {{ $cfg['color'] }}">{{ $tipo }}</span>
        </div>
        @endforeach
    </div>

    {{-- Filtros --}}
    <div class="bg-white rounded-xl shadow p-5 mb-6">
        <form method="GET" action="{{ route('cartera.seguimiento.informe') }}" class="flex flex-wrap gap-4 items-end">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Código alumno</label>
                <input type="number" name="codigo_alumno" value="{{ request('codigo_alumno') }}"
                    placeholder="Ej: 1023"
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm w-32 focus:outline-none focus:ring-2 focus:ring-blue-400">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Tipo de gestión</label>
                <select name="tipo"
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                    <option value="">Todos</option>
                    @foreach(array_keys($tipos) as $t)
                        <option value="{{ $t }}" {{ request('tipo') === $t ? 'selected' : '' }}>{{ $t }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Desde</label>
                <input type="date" name="fecha_desde" value="{{ request('fecha_desde') }}"
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Hasta</label>
                <input type="date" name="fecha_hasta" value="{{ request('fecha_hasta') }}"
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Usuario</label>
                <input type="text" name="usuario" value="{{ request('usuario') }}"
                    placeholder="Nombre de usuario"
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm w-40 focus:outline-none focus:ring-2 focus:ring-blue-400">
            </div>
            <div class="flex gap-2">
                <button type="submit"
                    class="bg-blue-800 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition">
                    Filtrar
                </button>
                @if(request()->hasAny(['codigo_alumno','tipo','fecha_desde','fecha_hasta','usuario']))
                    <a href="{{ route('cartera.seguimiento.informe') }}"
                        class="bg-gray-100 hover:bg-gray-200 text-gray-600 text-sm font-semibold px-4 py-2 rounded-lg transition">
                        Limpiar
                    </a>
                @endif
            </div>
        </form>
    </div>

    {{-- Tabla de registros --}}
    <div class="bg-white rounded-xl shadow overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-bold text-gray-700">Gestiones de seguimiento</h3>
            <span class="text-xs text-gray-400">{{ $registros->total() }} registros</span>
        </div>

        @if($registros->isEmpty())
            <div class="px-5 py-12 text-center text-gray-400 text-sm">
                Sin registros para los filtros seleccionados.
            </div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                    <tr>
                        <th class="px-4 py-3 text-left">Fecha</th>
                        <th class="px-4 py-3 text-left">Estudiante</th>
                        <th class="px-4 py-3 text-left">Curso</th>
                        <th class="px-4 py-3 text-left">Tipo</th>
                        <th class="px-4 py-3 text-left">Descripción / Anotación</th>
                        <th class="px-4 py-3 text-left">Registrado por</th>
                        <th class="px-4 py-3 text-left">Ver estudiante</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($registros as $r)
                    @php
                        $badge = $tipos[$r->tipo]['color'] ?? 'bg-gray-100 text-gray-700';
                        $icono = $tipos[$r->tipo]['icono'] ?? '📝';
                    @endphp
                    <tr class="hover:bg-gray-50 align-top">
                        <td class="px-4 py-3 text-gray-500 whitespace-nowrap">
                            {{ \Carbon\Carbon::parse($r->created_at)->format('d/m/Y') }}
                            <span class="block text-xs text-gray-400">{{ \Carbon\Carbon::parse($r->created_at)->format('H:i') }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="font-medium text-gray-800">{{ trim($r->nombre) ?: '—' }}</span>
                            <span class="block text-xs text-gray-400">Cód. {{ $r->codigo_alumno }}</span>
                        </td>
                        <td class="px-4 py-3 text-gray-600">{{ $r->curso ?? '—' }}</td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <span class="inline-flex items-center gap-1 text-xs font-semibold px-2 py-1 rounded-full {{ $badge }}">
                                {{ $icono }} {{ $r->tipo }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-gray-700 max-w-sm">
                            <p class="whitespace-pre-wrap text-sm leading-snug">{{ $r->nota }}</p>
                        </td>
                        <td class="px-4 py-3 text-gray-500 whitespace-nowrap text-xs">{{ $r->usuario ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <a href="{{ route('cartera.estudiante', $r->codigo_alumno) }}"
                                class="text-xs text-blue-700 hover:underline font-medium whitespace-nowrap">
                                Ver cartera →
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($registros->hasPages())
        <div class="px-5 py-4 border-t border-gray-100">
            {{ $registros->links() }}
        </div>
        @endif
        @endif
    </div>

@endsection
