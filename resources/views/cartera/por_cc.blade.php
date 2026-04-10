@extends('layouts.app-sidebar')

@section('header', 'Cartera por CC de Facturación')

@section('slot')

    {{-- Totales generales --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="bg-blue-50 border border-blue-200 rounded-xl p-5 text-center">
            <p class="text-xs text-blue-400 uppercase tracking-wide mb-1">Total Facturado</p>
            <p class="text-2xl font-bold text-blue-800">$ {{ number_format($granTotalFacturado, 0, ',', '.') }}</p>
        </div>
        <div class="bg-green-50 border border-green-200 rounded-xl p-5 text-center">
            <p class="text-xs text-green-400 uppercase tracking-wide mb-1">Total Pagado</p>
            <p class="text-2xl font-bold text-green-700">$ {{ number_format($granTotalPagado, 0, ',', '.') }}</p>
        </div>
        <div class="bg-red-50 border border-red-200 rounded-xl p-5 text-center">
            <p class="text-xs text-red-400 uppercase tracking-wide mb-1">Saldo Total</p>
            <p class="text-2xl font-bold text-red-700">$ {{ number_format($granTotalSaldo, 0, ',', '.') }}</p>
        </div>
    </div>

    @if($porCC->isEmpty())
        <div class="bg-yellow-50 border border-yellow-200 text-yellow-700 rounded-xl p-6 text-center text-sm">
            No hay titulares de facturación registrados.
        </div>
    @else

    <div class="space-y-4">
        @foreach($porCC as $grupo)
        <div class="bg-white rounded-xl shadow overflow-hidden">

            {{-- Encabezado del titular --}}
            <div class="px-5 py-3 flex flex-wrap items-center justify-between gap-3
                {{ $grupo->totalSaldo > 0 ? 'bg-red-700' : 'bg-green-700' }} text-white">
                <div>
                    <p class="text-xs uppercase tracking-wide opacity-75">CC de facturación</p>
                    <p class="font-bold text-lg leading-tight">{{ number_format($grupo->cc, 0, ',', '.') }}</p>
                    @if($grupo->nombreTitular)
                        <p class="font-semibold text-sm mt-0.5">{{ $grupo->nombreTitular }}</p>
                    @endif
                    @if($grupo->celTitular)
                        <p class="text-xs opacity-90 mt-0.5">📞 {{ $grupo->celTitular }}</p>
                    @endif
                    <p class="text-xs opacity-75 mt-0.5">{{ $grupo->detalle->count() }} estudiante(s)</p>
                </div>
                <div class="flex gap-4 flex-wrap">
                    <div class="text-center">
                        <p class="text-xs opacity-75 uppercase">Facturado</p>
                        <p class="font-bold">$ {{ number_format($grupo->totalFacturado, 0, ',', '.') }}</p>
                    </div>
                    <div class="text-center">
                        <p class="text-xs opacity-75 uppercase">Pagado</p>
                        <p class="font-bold">$ {{ number_format($grupo->totalPagado, 0, ',', '.') }}</p>
                    </div>
                    <div class="text-center">
                        <p class="text-xs opacity-75 uppercase">Saldo</p>
                        <p class="font-bold text-lg">$ {{ number_format($grupo->totalSaldo, 0, ',', '.') }}</p>
                    </div>
                </div>
            </div>

            {{-- Detalle de estudiantes --}}
            <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 uppercase text-xs border-b border-gray-200">
                    <tr>
                        <th class="px-4 py-2 text-left">Código</th>
                        <th class="px-4 py-2 text-left">Estudiante</th>
                        <th class="px-4 py-2 text-left">Curso</th>
                        <th class="px-4 py-2 text-right">Facturado</th>
                        <th class="px-4 py-2 text-right">Pagado</th>
                        <th class="px-4 py-2 text-right">Saldo</th>
                        <th class="px-4 py-2"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($grupo->detalle as $fila)
                    @php
                        $est    = $fila->estudiante;
                        $nombre = $est
                            ? trim("{$est->APELLIDO1} {$est->APELLIDO2} {$est->NOMBRE1} {$est->NOMBRE2}")
                            : '—';
                    @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2 font-mono text-gray-500 text-xs">{{ $fila->codigo }}</td>
                        <td class="px-4 py-2 font-medium text-gray-800">{{ $nombre }}</td>
                        <td class="px-4 py-2 text-gray-500">{{ $est->CURSO ?? '—' }}</td>
                        <td class="px-4 py-2 text-right text-blue-700">$ {{ number_format($fila->facturado, 0, ',', '.') }}</td>
                        <td class="px-4 py-2 text-right text-green-700">$ {{ number_format($fila->pagado, 0, ',', '.') }}</td>
                        <td class="px-4 py-2 text-right font-semibold {{ $fila->saldo > 0 ? 'text-red-600' : 'text-gray-500' }}">
                            $ {{ number_format($fila->saldo, 0, ',', '.') }}
                        </td>
                        <td class="px-4 py-2 text-right">
                            <a href="{{ route('cartera.estudiante', $fila->codigo) }}"
                                class="text-xs bg-gray-700 hover:bg-gray-600 text-white font-semibold px-3 py-1 rounded-lg transition">
                                Ver →
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
        </div>
        @endforeach
    </div>

    @endif

@endsection
