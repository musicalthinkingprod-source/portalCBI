@extends('layouts.app-sidebar')

@section('header', 'Inventario · Productos (uniformes)')

@section('slot')

@if(session('ok'))
<div class="mb-5 bg-green-50 border border-green-300 text-green-800 px-4 py-3 rounded-lg text-sm font-medium">{{ session('ok') }}</div>
@endif
@if($errors->any())
<div class="mb-5 bg-red-50 border border-red-300 text-red-800 px-4 py-3 rounded-lg text-sm">{{ $errors->first() }}</div>
@endif

<div class="grid lg:grid-cols-3 gap-6">

    {{-- Alta de producto --}}
    <div class="bg-white rounded-xl shadow p-5 h-fit">
        <h3 class="font-semibold text-gray-700 mb-4">Nuevo producto</h3>
        <form method="POST" action="{{ route('inventario.productos.store') }}" class="space-y-3">
            @csrf
            <div>
                <label class="block text-xs text-gray-500 mb-1">Código (código de barras)</label>
                <input type="number" name="codigo" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" value="{{ old('codigo') }}">
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Nombre / talla</label>
                <input type="text" name="nombre" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" value="{{ old('nombre') }}">
            </div>
            <p class="text-xs text-gray-400">El precio de venta se asigna en «Precios y costos» (Admin/SuperAd), según la resolución de costos.</p>
            <button class="w-full bg-blue-600 hover:bg-blue-700 text-white rounded-lg py-2 text-sm font-semibold">Guardar</button>
        </form>
    </div>

    {{-- Listado / edición --}}
    <div class="lg:col-span-2 bg-white rounded-xl shadow">
        <div class="px-5 py-3 border-b border-gray-100">
            <h3 class="font-semibold text-gray-700">Catálogo ({{ $productos->count() }})</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                    <tr>
                        <th class="px-3 py-2 text-left">Código</th>
                        <th class="px-3 py-2 text-left">Nombre</th>
                        <th class="px-3 py-2 text-right">Precio</th>
                        <th class="px-3 py-2 text-right">Stock</th>
                        <th class="px-3 py-2 text-center">Activo</th>
                        <th class="px-3 py-2"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($productos as $p)
                    <tr>
                        <form method="POST" action="{{ route('inventario.productos.update', $p->id) }}">
                            @csrf @method('PUT')
                            <td class="px-3 py-2"><input type="number" name="codigo" value="{{ $p->codigo }}" class="w-20 border border-gray-200 rounded px-2 py-1 font-mono"></td>
                            <td class="px-3 py-2"><input type="text" name="nombre" value="{{ $p->nombre }}" class="w-full border border-gray-200 rounded px-2 py-1"></td>
                            <td class="px-3 py-2 text-right text-gray-700">${{ number_format($p->precio_venta, 0) }}</td>
                            <td class="px-3 py-2 text-right font-semibold {{ $p->stock <= 0 ? 'text-red-600' : ($p->stock <= 2 ? 'text-amber-600' : 'text-gray-700') }}">{{ $p->stock }}</td>
                            <td class="px-3 py-2 text-center"><input type="checkbox" name="activo" value="1" {{ $p->activo ? 'checked' : '' }}></td>
                            <td class="px-3 py-2 text-right"><button class="text-blue-600 hover:underline text-xs font-semibold">Guardar</button></td>
                        </form>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="px-4 py-6 text-center text-gray-400">Sin productos todavía.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
