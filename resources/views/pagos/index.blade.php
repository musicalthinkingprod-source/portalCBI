@extends('layouts.app-sidebar')

@section('header', 'Registro de Pagos')

@section('slot')

    {{-- Acciones --}}
    <div class="flex flex-wrap gap-3 mb-6">
        <a href="{{ route('pagos.create') }}"
            class="bg-blue-800 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition">
            ➕ Agregar pago
        </a>
        <a href="{{ route('importacion.registro_pagos.show') }}"
            class="bg-white border border-blue-800 text-blue-800 hover:bg-blue-50 text-sm font-semibold px-4 py-2 rounded-lg transition">
            📂 Importar en lote
        </a>
    </div>

    @if(session('success'))
        <div class="mb-4 p-3 bg-green-100 text-green-800 rounded-xl text-sm">
            ✅ {{ session('success') }}
        </div>
    @endif

    {{-- Tabla pagos --}}
    <div class="bg-white rounded-xl shadow overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-bold text-blue-800">Todos los pagos</h3>
            <span class="text-xs text-gray-400">{{ $pagos->total() }} registros</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                    <tr>
                        <th class="px-4 py-3 text-left">Código alumno</th>
                        <th class="px-4 py-3 text-left">Fecha</th>
                        <th class="px-4 py-3 text-left">Concepto</th>
                        <th class="px-4 py-3 text-left">Mes</th>
                        <th class="px-4 py-3 text-left">Orden</th>
                        <th class="px-4 py-3 text-right">Valor</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($pagos as $pago)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium">{{ $pago->codigo_alumno }}</td>
                        <td class="px-4 py-3">{{ $pago->fecha }}</td>
                        <td class="px-4 py-3">{{ $pago->concepto }}</td>
                        <td class="px-4 py-3">{{ $pago->mes }}</td>
                        <td class="px-4 py-3">{{ $pago->orden ?? '—' }}</td>
                        <td class="px-4 py-3 text-right font-medium text-green-700">$ {{ number_format($pago->valor, 0, ',', '.') }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-4 py-6 text-center text-gray-400 text-sm">Sin registros de pagos.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Paginación --}}
        @if($pagos->hasPages())
        <div class="px-5 py-4 border-t border-gray-100">
            {{ $pagos->links() }}
        </div>
        @endif
    </div>

@endsection
