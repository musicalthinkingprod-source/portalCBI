@extends('layouts.app-sidebar')

@section('header', 'Inventario · Precios y costos')

@section('slot')

@if(session('ok'))
<div class="mb-5 bg-green-50 border border-green-300 text-green-800 px-4 py-3 rounded-lg text-sm font-medium">{{ session('ok') }}</div>
@endif
@if($errors->any())
<div class="mb-5 bg-red-50 border border-red-300 text-red-800 px-4 py-3 rounded-lg text-sm">{{ $errors->first() }}</div>
@endif

<div class="bg-white rounded-xl shadow">
    <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
        <div>
            <h3 class="font-semibold text-gray-700">Lista de precios</h3>
            <p class="text-xs text-gray-400">El precio de venta se digita según la resolución de costos. El último costo de compra es solo de referencia.</p>
        </div>
        <input type="text" id="filtro" placeholder="Buscar…" class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm w-48">
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm" id="tabla">
            <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                <tr>
                    <th class="px-3 py-2 text-left">Código</th>
                    <th class="px-3 py-2 text-left">Prenda</th>
                    <th class="px-3 py-2 text-right">Último costo</th>
                    <th class="px-3 py-2 text-right w-40">Precio de venta</th>
                    <th class="px-3 py-2 text-right">Ganancia</th>
                    <th class="px-3 py-2 text-right">Margen</th>
                    <th class="px-3 py-2"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($productos as $p)
                <tr class="fila">
                    <form method="POST" action="{{ route('inventario.precios.update', $p->id) }}">
                        @csrf @method('PUT')
                        <td class="px-3 py-2 font-mono text-gray-600">{{ $p->codigo }}</td>
                        <td class="px-3 py-2 text-gray-800">{{ $p->nombre }}</td>
                        <td class="px-3 py-2 text-right text-gray-600">
                            {{ $p->ultimo_costo !== null ? '$'.number_format($p->ultimo_costo, 0) : '—' }}
                        </td>
                        <td class="px-3 py-2 text-right">
                            <input type="number" step="0.01" min="0" name="precio_venta" value="{{ $p->precio_venta }}"
                                   class="w-32 border border-gray-300 rounded px-2 py-1 text-right">
                        </td>
                        <td class="px-3 py-2 text-right font-semibold {{ $p->ganancia === null ? 'text-gray-400' : ($p->ganancia < 0 ? 'text-red-600' : 'text-emerald-600') }}">
                            {{ $p->ganancia !== null ? '$'.number_format($p->ganancia, 0) : '—' }}
                        </td>
                        <td class="px-3 py-2 text-right {{ $p->margen === null ? 'text-gray-400' : ($p->margen < 0 ? 'text-red-600' : 'text-gray-600') }}">
                            {{ $p->margen !== null ? $p->margen.'%' : '—' }}
                        </td>
                        <td class="px-3 py-2 text-right"><button class="text-blue-600 hover:underline text-xs font-semibold">Guardar</button></td>
                    </form>
                </tr>
                @empty
                <tr><td colspan="7" class="px-4 py-6 text-center text-gray-400">Sin productos todavía.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<script>
    document.getElementById('filtro').addEventListener('input', function () {
        const q = this.value.toLowerCase();
        document.querySelectorAll('#tabla .fila').forEach(f => {
            f.style.display = f.textContent.toLowerCase().includes(q) ? '' : 'none';
        });
    });
</script>
@endsection
