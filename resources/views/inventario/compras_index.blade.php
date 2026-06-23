@extends('layouts.app-sidebar')

@section('header', 'Inventario · Compras')

@section('slot')

@if(session('ok'))
<div class="mb-5 bg-green-50 border border-green-300 text-green-800 px-4 py-3 rounded-lg text-sm font-medium">{{ session('ok') }}</div>
@endif

<div class="flex justify-between items-center mb-5">
    <h3 class="font-semibold text-gray-700">Últimas compras</h3>
    <a href="{{ route('inventario.compras.create') }}" class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg text-sm font-semibold">📥 Registrar compra</a>
</div>

<div class="bg-white rounded-xl shadow overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
            <tr>
                <th class="px-4 py-2 text-left">Fecha</th>
                <th class="px-4 py-2 text-left">Factura</th>
                <th class="px-4 py-2 text-left">Proveedor</th>
                <th class="px-4 py-2 text-right">Total</th>
                <th class="px-4 py-2 text-left">Observación</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($compras as $c)
            <tr>
                <td class="px-4 py-2 text-gray-600">{{ \Carbon\Carbon::parse($c->fecha)->format('d/m/Y') }}</td>
                <td class="px-4 py-2 font-mono text-gray-600">{{ $c->factura ?: '—' }}</td>
                <td class="px-4 py-2 text-gray-800">{{ $c->proveedor }}</td>
                <td class="px-4 py-2 text-right text-gray-700 font-semibold">${{ number_format($c->total, 0) }}</td>
                <td class="px-4 py-2 text-gray-500">{{ $c->observacion }}</td>
            </tr>
            @empty
            <tr><td colspan="5" class="px-4 py-6 text-center text-gray-400">Sin compras registradas.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
