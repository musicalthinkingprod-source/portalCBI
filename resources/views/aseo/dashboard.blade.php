@extends('layouts.app-sidebar')

@section('header', 'Informe aseo')

@section('slot')

@if(session('ok'))
<div class="mb-5 bg-green-50 border border-green-300 text-green-800 px-4 py-3 rounded-lg text-sm font-medium">{{ session('ok') }}</div>
@endif

<div class="flex flex-wrap gap-3 mb-6">
    <a href="{{ route('aseo.salidas.create') }}" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold">📤 Entregar a dependencia</a>
    <a href="{{ route('aseo.compras.create') }}" class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg text-sm font-semibold">📥 Registrar compra</a>
    <a href="{{ route('aseo.elementos') }}" class="inline-flex items-center gap-2 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 px-4 py-2 rounded-lg text-sm font-semibold">🧴 Elementos</a>
    <a href="{{ route('aseo.dependencias') }}" class="inline-flex items-center gap-2 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 px-4 py-2 rounded-lg text-sm font-semibold">🏢 Dependencias</a>
</div>

<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <div class="bg-white rounded-xl shadow p-4"><p class="text-xs text-gray-500 uppercase tracking-wide">Referencias</p><p class="text-2xl font-bold text-gray-800">{{ number_format($resumen['referencias']) }}</p></div>
    <div class="bg-white rounded-xl shadow p-4"><p class="text-xs text-gray-500 uppercase tracking-wide">Unidades en stock</p><p class="text-2xl font-bold text-gray-800">{{ number_format($resumen['unidades']) }}</p></div>
    <div class="bg-white rounded-xl shadow p-4"><p class="text-xs text-gray-500 uppercase tracking-wide">Stock bajo (≤2)</p><p class="text-2xl font-bold text-amber-600">{{ number_format($resumen['bajos']) }}</p></div>
    <div class="bg-white rounded-xl shadow p-4"><p class="text-xs text-gray-500 uppercase tracking-wide">Agotados</p><p class="text-2xl font-bold text-red-600">{{ number_format($resumen['agotados']) }}</p></div>
</div>

<div class="bg-white rounded-xl shadow mb-8">
    <div class="px-5 py-3 border-b border-gray-100"><h3 class="font-semibold text-gray-700">Para revisar / comprar — menor stock primero</h3></div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                <tr><th class="px-4 py-2 text-left">Código</th><th class="px-4 py-2 text-left">Elemento</th><th class="px-4 py-2 text-left">Presentación</th><th class="px-4 py-2 text-right">Stock</th></tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($porComprar as $e)
                <tr class="{{ $e->stock <= 0 ? 'bg-red-50' : ($e->stock <= 2 ? 'bg-amber-50' : '') }}">
                    <td class="px-4 py-2 font-mono text-gray-600">{{ $e->codigo }}</td>
                    <td class="px-4 py-2 text-gray-800">{{ $e->descripcion }}</td>
                    <td class="px-4 py-2 text-gray-500">{{ $e->presentacion }}</td>
                    <td class="px-4 py-2 text-right font-semibold {{ $e->stock <= 0 ? 'text-red-600' : ($e->stock <= 2 ? 'text-amber-600' : 'text-gray-700') }}">{{ $e->stock }}</td>
                </tr>
                @empty
                <tr><td colspan="4" class="px-4 py-6 text-center text-gray-400">Aún no hay elementos de aseo.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="bg-white rounded-xl shadow">
    <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
        <h3 class="font-semibold text-gray-700">Inventario de aseo</h3>
        <input type="text" id="filtro" placeholder="Buscar…" class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm w-48">
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm" id="tabla-inv">
            <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                <tr><th class="px-4 py-2 text-left">Código</th><th class="px-4 py-2 text-left">Elemento</th><th class="px-4 py-2 text-left">Presentación</th><th class="px-4 py-2 text-right">Stock</th></tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($elementos as $e)
                <tr class="fila">
                    <td class="px-4 py-2 font-mono text-gray-600">{{ $e->codigo }}</td>
                    <td class="px-4 py-2 text-gray-800">{{ $e->descripcion }}</td>
                    <td class="px-4 py-2 text-gray-500">{{ $e->presentacion }}</td>
                    <td class="px-4 py-2 text-right font-semibold {{ $e->stock <= 0 ? 'text-red-600' : ($e->stock <= 2 ? 'text-amber-600' : 'text-gray-700') }}">{{ $e->stock }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<script>
    document.getElementById('filtro').addEventListener('input', function () {
        const q = this.value.toLowerCase();
        document.querySelectorAll('#tabla-inv .fila').forEach(f => { f.style.display = f.textContent.toLowerCase().includes(q) ? '' : 'none'; });
    });
</script>
@endsection
