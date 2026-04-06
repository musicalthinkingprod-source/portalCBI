@extends('layouts.padres')

@section('header', 'Asistencia')

@section('slot')

    {{-- Filtro de fechas --}}
    <div class="bg-white rounded-xl shadow p-5 mb-6">
        <form method="GET" action="{{ route('padres.asistencia') }}" class="flex flex-wrap gap-4 items-end">
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Desde</label>
                <input type="date" name="desde" value="{{ $desde }}"
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Hasta</label>
                <input type="date" name="hasta" value="{{ $hasta }}" max="{{ today()->format('Y-m-d') }}"
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <button type="submit"
                class="bg-blue-800 hover:bg-blue-700 text-white font-semibold text-sm px-4 py-2 rounded-lg transition">
                Filtrar
            </button>
        </form>
    </div>

    {{-- Tarjetas resumen --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
        <div class="bg-white rounded-xl shadow p-4 text-center">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">Presentes</p>
            <p class="text-3xl font-bold text-green-600">{{ $resumen['presentes'] }}</p>
            <p class="text-xs text-gray-400 mt-1">de {{ $resumen['total'] }} días</p>
        </div>
        <div class="bg-white rounded-xl shadow p-4 text-center">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">Ausentes</p>
            <p class="text-3xl font-bold {{ $resumen['ausentes'] > 0 ? 'text-red-600' : 'text-gray-400' }}">{{ $resumen['ausentes'] }}</p>
        </div>
        <div class="bg-white rounded-xl shadow p-4 text-center">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">Excusas</p>
            <p class="text-3xl font-bold {{ $resumen['excusas'] > 0 ? 'text-yellow-500' : 'text-gray-400' }}">{{ $resumen['excusas'] }}</p>
        </div>
        <div class="bg-white rounded-xl shadow p-4 text-center">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">Sal. Anticipada</p>
            <p class="text-3xl font-bold {{ $resumen['salidas_anticipadas'] > 0 ? 'text-purple-600' : 'text-gray-400' }}">{{ $resumen['salidas_anticipadas'] }}</p>
        </div>
    </div>

    {{-- Tarjetas de faltas --}}
    @if($resumen['retardos'] > 0 || $resumen['falta_carnet'] > 0 || $resumen['falta_uniforme'] > 0 || $resumen['falta_presentacion'] > 0)
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
        <div class="bg-orange-50 border border-orange-100 rounded-xl p-3 text-center">
            <p class="text-xs font-semibold text-orange-400 uppercase tracking-wide mb-1">⏰ Retardos</p>
            <p class="text-2xl font-bold text-orange-500">{{ $resumen['retardos'] }}</p>
        </div>
        <div class="bg-red-50 border border-red-100 rounded-xl p-3 text-center">
            <p class="text-xs font-semibold text-red-400 uppercase tracking-wide mb-1">🪪 Sin carnet</p>
            <p class="text-2xl font-bold text-red-500">{{ $resumen['falta_carnet'] }}</p>
        </div>
        <div class="bg-red-50 border border-red-100 rounded-xl p-3 text-center">
            <p class="text-xs font-semibold text-red-400 uppercase tracking-wide mb-1">👔 Sin uniforme</p>
            <p class="text-2xl font-bold text-red-500">{{ $resumen['falta_uniforme'] }}</p>
        </div>
        <div class="bg-red-50 border border-red-100 rounded-xl p-3 text-center">
            <p class="text-xs font-semibold text-red-400 uppercase tracking-wide mb-1">🚩 Presentación</p>
            <p class="text-2xl font-bold text-red-500">{{ $resumen['falta_presentacion'] }}</p>
        </div>
    </div>
    @endif

    {{-- Historial --}}
    <div class="bg-white rounded-xl shadow overflow-hidden">
        <div class="px-5 py-3 bg-blue-800 text-white">
            <h3 class="font-bold text-sm uppercase tracking-wide">Historial de asistencia</h3>
            <p class="text-blue-300 text-xs mt-0.5">
                Del {{ \Carbon\Carbon::parse($desde)->format('d/m/Y') }}
                al {{ \Carbon\Carbon::parse($hasta)->format('d/m/Y') }}
            </p>
        </div>

        @if($registros->isEmpty())
            <div class="px-5 py-8 text-center text-gray-400 text-sm">
                Sin registros de asistencia para el período seleccionado.
            </div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                    <tr>
                        <th class="px-4 py-3 text-left">Fecha</th>
                        <th class="px-4 py-3 text-center w-28">Estado</th>
                        <th class="px-4 py-3 text-center w-20">Retardo</th>
                        <th class="px-4 py-3 text-center w-24">Sin carnet</th>
                        <th class="px-4 py-3 text-center w-28">Sin uniforme</th>
                        <th class="px-4 py-3 text-center w-28">Presentación</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($registros as $r)
                    @php
                        $badgeColor = match($r->ASISTENCIA) {
                            'P'  => 'bg-green-100 text-green-700',
                            'A'  => 'bg-red-100 text-red-700',
                            'EX' => 'bg-yellow-100 text-yellow-700',
                            'SA' => 'bg-purple-100 text-purple-700',
                            default => 'bg-gray-100 text-gray-500'
                        };
                        $badgeLabel = match($r->ASISTENCIA) {
                            'P'  => 'Presente',
                            'A'  => 'Ausente',
                            'EX' => 'Excusa',
                            'SA' => 'Sal. Anticipada',
                            default => $r->ASISTENCIA
                        };
                    @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2 font-medium">
                            {{ \Carbon\Carbon::parse($r->FECHA)->locale('es')->isoFormat('dddd D [de] MMMM') }}
                        </td>
                        <td class="px-4 py-2 text-center">
                            <span class="inline-block {{ $badgeColor }} font-semibold text-xs px-2 py-0.5 rounded-full">
                                {{ $badgeLabel }}
                            </span>
                        </td>
                        <td class="px-4 py-2 text-center text-sm">
                            {{ $r->RETARDO ? '⏰' : '—' }}
                        </td>
                        <td class="px-4 py-2 text-center text-sm">
                            {{ $r->CARNET ? '🪪' : '—' }}
                        </td>
                        <td class="px-4 py-2 text-center text-sm">
                            {{ $r->UNIFORME ? '👔' : '—' }}
                        </td>
                        <td class="px-4 py-2 text-center text-sm">
                            {{ $r->PRESENTACION ? '🚩' : '—' }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

@endsection
