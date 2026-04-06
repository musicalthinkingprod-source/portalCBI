@extends('layouts.app-sidebar')

@section('header', 'Informe General de Cartera')

@section('slot')

    {{-- Resumen principal --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">

        <div class="bg-blue-50 border border-blue-200 rounded-xl p-5">
            <p class="text-xs text-blue-400 uppercase tracking-wide mb-1">Total Facturado</p>
            <p class="text-2xl font-bold text-blue-800">$ {{ number_format($totalFacturado, 0, ',', '.') }}</p>
        </div>

        <div class="bg-green-50 border border-green-200 rounded-xl p-5">
            <p class="text-xs text-green-400 uppercase tracking-wide mb-1">Total Recaudado</p>
            <p class="text-2xl font-bold text-green-700">$ {{ number_format($totalPagado, 0, ',', '.') }}</p>
        </div>

        <div class="bg-red-50 border border-red-200 rounded-xl p-5">
            <p class="text-xs text-red-400 uppercase tracking-wide mb-1">Total Cartera</p>
            <p class="text-2xl font-bold text-red-700">$ {{ number_format($totalCartera, 0, ',', '.') }}</p>
        </div>

        <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-5">
            <p class="text-xs text-yellow-500 uppercase tracking-wide mb-1">% Recaudo</p>
            <p class="text-2xl font-bold text-yellow-700">{{ $porcentajeRecaudo }}%</p>
            <div class="mt-2 bg-yellow-200 rounded-full h-2">
                <div class="bg-yellow-500 h-2 rounded-full" style="width: {{ min($porcentajeRecaudo, 100) }}%"></div>
            </div>
        </div>

    </div>

    {{-- Estado de estudiantes --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">

        <div class="bg-white rounded-xl shadow p-5 flex items-center gap-5">
            <div class="w-14 h-14 bg-green-100 rounded-full flex items-center justify-center text-2xl">✅</div>
            <div>
                <p class="text-3xl font-bold text-green-700">{{ $alDia }}</p>
                <p class="text-sm text-gray-500">Estudiantes al día</p>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow p-5 flex items-center gap-5">
            <div class="w-14 h-14 bg-red-100 rounded-full flex items-center justify-center text-2xl">⚠️</div>
            <div>
                <p class="text-3xl font-bold text-red-700">{{ $debiendo }}</p>
                <p class="text-sm text-gray-500">Estudiantes con saldo pendiente</p>
            </div>
        </div>

    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">

        {{-- Top deudores --}}
        <div class="bg-white rounded-xl shadow overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <h3 class="font-bold text-blue-800">Top 10 mayores saldos pendientes</h3>
                <a href="{{ route('cartera.deudores') }}"
                    class="text-xs font-semibold text-blue-700 hover:text-blue-900 hover:underline">
                    Ver lista completa →
                </a>
            </div>
            @if($topDeudores->isEmpty())
                <p class="text-sm text-gray-400 px-5 py-4">Sin deudores registrados.</p>
            @else
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                    <tr>
                        <th class="px-4 py-3 text-left">Estudiante</th>
                        <th class="px-4 py-3 text-right">Facturado</th>
                        <th class="px-4 py-3 text-right">Pagado</th>
                        <th class="px-4 py-3 text-right">Saldo</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($topDeudores as $d)
                    @php $est = $estudiantes[$d->codigo_alumno] ?? null; @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            @if($est)
                                <span class="font-medium">{{ $est->NOMBRE1 }} {{ $est->APELLIDO1 }}</span><br>
                                <span class="text-xs text-gray-400">Cód: {{ $d->codigo_alumno }}</span>
                            @else
                                <span class="text-gray-400">{{ $d->codigo_alumno }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right text-xs">$ {{ number_format($d->total_facturado, 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right text-xs text-green-700">$ {{ number_format($d->total_pagado, 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right font-bold text-red-700">$ {{ number_format($d->saldo, 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @endif
        </div>

        {{-- Facturación vs pagos por mes --}}
        <div class="bg-white rounded-xl shadow overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100">
                <h3 class="font-bold text-blue-800">Facturación vs Recaudo por mes</h3>
            </div>
            @if($porMes->isEmpty())
                <p class="text-sm text-gray-400 px-5 py-4">Sin datos por mes.</p>
            @else
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                    <tr>
                        <th class="px-4 py-3 text-left">Mes</th>
                        <th class="px-4 py-3 text-right">Facturado</th>
                        <th class="px-4 py-3 text-right">Pagado</th>
                        <th class="px-4 py-3 text-right">Diferencia</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($porMes as $m)
                    @php
                        $pagadoMes = $pagosPorMes[$m->mes]->total ?? 0;
                        $diff = $m->total - $pagadoMes;
                    @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium">{{ $m->mes }}</td>
                        <td class="px-4 py-3 text-right text-blue-700">$ {{ number_format($m->total, 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right text-green-700">$ {{ number_format($pagadoMes, 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right font-semibold {{ $diff > 0 ? 'text-red-600' : 'text-green-600' }}">
                            $ {{ number_format($diff, 0, ',', '.') }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @endif
        </div>

    </div>

@endsection
