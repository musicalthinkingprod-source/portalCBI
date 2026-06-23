@extends('layouts.app-sidebar')

@section('header', 'Inventario · Ventas y dotaciones')

@section('slot')

@if(session('ok'))
<div class="mb-5 bg-green-50 border border-green-300 text-green-800 px-4 py-3 rounded-lg text-sm font-medium">{{ session('ok') }}</div>
@endif

<div class="flex justify-between items-center mb-5">
    <h3 class="font-semibold text-gray-700">Últimos movimientos</h3>
    <a href="{{ route('inventario.ventas.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold">🧾 Nueva venta</a>
</div>

<div class="bg-white rounded-xl shadow overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
            <tr>
                <th class="px-4 py-2 text-left">N°</th>
                <th class="px-4 py-2 text-left">Fecha</th>
                <th class="px-4 py-2 text-left">Tipo</th>
                <th class="px-4 py-2 text-left">Destinatario</th>
                <th class="px-4 py-2 text-right">Total</th>
                <th class="px-4 py-2 text-center">Estado</th>
                <th class="px-4 py-2"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($ventas as $v)
            <tr class="{{ $v->estado === 'anulada' ? 'opacity-50' : '' }}">
                <td class="px-4 py-2 font-mono text-gray-700">{{ $v->numero }}</td>
                <td class="px-4 py-2 text-gray-600">{{ \Carbon\Carbon::parse($v->fecha)->format('d/m/Y') }}</td>
                <td class="px-4 py-2">
                    @if($v->tipo === 'dotacion')
                    <span class="inline-block px-2 py-0.5 rounded text-xs bg-purple-100 text-purple-800">Dotación docente</span>
                    @else
                    <span class="inline-block px-2 py-0.5 rounded text-xs bg-blue-100 text-blue-800">Venta</span>
                    @endif
                </td>
                <td class="px-4 py-2 text-gray-800">
                    {{ $v->tipo === 'dotacion' ? ($v->docente ?? '—') : ($v->estudiante ?: 'Cód. '.$v->estudiante_codigo) }}
                </td>
                <td class="px-4 py-2 text-right font-semibold text-gray-700">${{ number_format($v->total, 0) }}</td>
                <td class="px-4 py-2 text-center">
                    @if($v->estado === 'anulada')
                    <span class="text-red-600 text-xs font-semibold">Anulada</span>
                    @else
                    <span class="text-green-600 text-xs font-semibold">Activa</span>
                    @endif
                </td>
                <td class="px-4 py-2 text-right">
                    @if($v->estado !== 'anulada')
                    <form method="POST" action="{{ route('inventario.ventas.anular', $v->id) }}" onsubmit="return confirm('¿Anular este documento? El stock se devolverá.')">
                        @csrf
                        <button class="text-red-500 hover:underline text-xs">Anular</button>
                    </form>
                    @endif
                </td>
            </tr>
            @empty
            <tr><td colspan="7" class="px-4 py-6 text-center text-gray-400">Sin movimientos registrados.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
