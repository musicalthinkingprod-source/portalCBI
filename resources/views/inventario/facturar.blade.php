@extends('layouts.app-sidebar')

@section('header', 'Inventario · Uniformes a facturar')

@section('slot')

@if(session('ok'))
<div class="mb-5 bg-green-50 border border-green-300 text-green-800 px-4 py-3 rounded-lg text-sm font-medium">{{ session('ok') }}</div>
@endif

<form method="POST" action="{{ route('inventario.facturar.guardar') }}">
    @csrf

    <div class="bg-white rounded-xl shadow mb-6">
        <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
            <div>
                <h3 class="font-semibold text-gray-700">Ventas pendientes de facturar</h3>
                <p class="text-xs text-gray-400">Una vez facturadas, ya no se pueden cancelar: solo se admite cambio par.</p>
            </div>
            <button class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold">Facturar seleccionadas</button>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                    <tr>
                        <th class="px-3 py-2 text-center"><input type="checkbox" onclick="document.querySelectorAll('.chk').forEach(c=>c.checked=this.checked)"></th>
                        <th class="px-4 py-2 text-left">Código</th>
                        <th class="px-4 py-2 text-left">N°</th>
                        <th class="px-4 py-2 text-left">Fecha</th>
                        <th class="px-4 py-2 text-left">Estudiante</th>
                        <th class="px-4 py-2 text-right">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($ventas as $v)
                    <tr>
                        <td class="px-3 py-2 text-center"><input type="checkbox" class="chk" name="ids[]" value="{{ $v->id }}" checked></td>
                        <td class="px-4 py-2 font-mono text-gray-500 text-xs">{{ $v->estudiante_codigo }}</td>
                        <td class="px-4 py-2 font-mono text-gray-700">{{ $v->numero }}</td>
                        <td class="px-4 py-2 text-gray-600">{{ \Carbon\Carbon::parse($v->fecha)->format('d/m/Y') }}</td>
                        <td class="px-4 py-2 text-gray-800">{{ $v->estudiante ?: '—' }}</td>
                        <td class="px-4 py-2 text-right font-semibold text-gray-700">${{ number_format($v->total, 0) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">No hay ventas pendientes de facturar. 🎉</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</form>

{{-- Sección: ventas ya facturadas --}}
<div class="bg-white rounded-xl shadow">
    <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between cursor-pointer select-none"
         onclick="document.getElementById('tabla-facturadas').classList.toggle('hidden')">
        <div>
            <h3 class="font-semibold text-gray-700">Ventas facturadas
                <span class="ml-2 text-xs font-normal bg-gray-100 text-gray-500 px-2 py-0.5 rounded-full">{{ $facturadas->count() }}</span>
            </h3>
            <p class="text-xs text-gray-400">Historial de ventas ya procesadas. Haz clic para mostrar/ocultar.</p>
        </div>
        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
    </div>
    <div id="tabla-facturadas" class="hidden overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                <tr>
                    <th class="px-4 py-2 text-left">Código</th>
                    <th class="px-4 py-2 text-left">N°</th>
                    <th class="px-4 py-2 text-left">Fecha venta</th>
                    <th class="px-4 py-2 text-left">Estudiante</th>
                    <th class="px-4 py-2 text-right">Total</th>
                    <th class="px-4 py-2 text-left">Facturada el</th>
                    <th class="px-4 py-2 text-left">Por</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($facturadas as $v)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-2 font-mono text-gray-500 text-xs">{{ $v->estudiante_codigo }}</td>
                    <td class="px-4 py-2 font-mono text-gray-700">{{ $v->numero }}</td>
                    <td class="px-4 py-2 text-gray-600">{{ \Carbon\Carbon::parse($v->fecha)->format('d/m/Y') }}</td>
                    <td class="px-4 py-2 text-gray-800">{{ $v->estudiante ?: '—' }}</td>
                    <td class="px-4 py-2 text-right font-semibold text-gray-700">${{ number_format($v->total, 0) }}</td>
                    <td class="px-4 py-2 text-gray-500">{{ $v->facturada_at ? \Carbon\Carbon::parse($v->facturada_at)->format('d/m/Y H:i') : '—' }}</td>
                    <td class="px-4 py-2 text-gray-500">{{ $v->facturada_por ?? '—' }}</td>
                </tr>
                @empty
                <tr><td colspan="7" class="px-4 py-8 text-center text-gray-400">Aún no hay ventas facturadas.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
