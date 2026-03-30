@extends('layouts.app-sidebar')

@section('header', 'Facturación')

@section('slot')

    {{-- Acciones --}}
    <div class="flex flex-wrap gap-3 mb-6">
        <a href="{{ route('facturacion.create') }}"
            class="bg-blue-800 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition">
            ➕ Agregar factura
        </a>
        <a href="{{ route('importacion.facturacion.show') }}"
            class="bg-white border border-blue-800 text-blue-800 hover:bg-blue-50 text-sm font-semibold px-4 py-2 rounded-lg transition">
            📂 Importar en lote
        </a>
    </div>

    @if(session('success'))
        <div class="mb-4 p-3 bg-green-100 text-green-800 rounded-xl text-sm">
            ✅ {{ session('success') }}
        </div>
    @endif

    {{-- Tabla --}}
    <div class="bg-white rounded-xl shadow overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-bold text-blue-800">Todos los registros</h3>
            <span class="text-xs text-gray-400">{{ $facturas->total() }} registros</span>
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
                        <th class="px-4 py-3 text-left">Cód. Concepto</th>
                        <th class="px-4 py-3 text-left">Centro Costos</th>
                        <th class="px-4 py-3 text-right">Valor</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($facturas as $f)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium">{{ $f->codigo_alumno }}</td>
                        <td class="px-4 py-3">{{ $f->fecha }}</td>
                        <td class="px-4 py-3">{{ $f->concepto }}</td>
                        <td class="px-4 py-3">{{ $f->mes }}</td>
                        <td class="px-4 py-3">{{ $f->orden ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $f->codigo_concepto ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $f->centro_costos ?? '—' }}</td>
                        <td class="px-4 py-3 text-right font-medium text-blue-800">$ {{ number_format($f->valor, 0, ',', '.') }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-4 py-6 text-center text-gray-400 text-sm">Sin registros de facturación.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($facturas->hasPages())
        <div class="px-5 py-4 border-t border-gray-100">
            {{ $facturas->links() }}
        </div>
        @endif
    </div>

@endsection
