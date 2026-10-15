@extends('layouts.app-sidebar')

@section('header', 'Informe aseo')

@section('slot')

@if(session('ok'))
<div class="mb-5 bg-green-50 border border-green-300 text-green-800 px-4 py-3 rounded-lg text-sm font-medium">{{ session('ok') }}</div>
@endif

{{-- Accesos rápidos --}}
<div class="flex flex-wrap gap-3 mb-5">
    <a href="{{ route('aseo.informe.imprimir', ['minimo' => $minimo, 'objetivo' => $objetivo, 'dias' => $dias]) }}" target="_blank"
       class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-semibold">
        🖨️ Imprimir informe
    </a>
    <a href="{{ route('aseo.solicitudes.create', ['minimo' => $minimo, 'objetivo' => $objetivo, 'dias' => $dias]) }}"
       class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg text-sm font-semibold">
        🛒 Solicitar compra
    </a>
    <a href="{{ route('aseo.solicitudes') }}"
       class="inline-flex items-center gap-2 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 px-4 py-2 rounded-lg text-sm font-semibold">
        📋 Solicitudes
        @if($pendientes > 0)
        <span class="bg-amber-500 text-white text-xs px-1.5 rounded-full">{{ $pendientes }}</span>
        @endif
    </a>
    <a href="{{ route('aseo.salidas.create') }}" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold">📤 Entregar a dependencia</a>
    <a href="{{ route('aseo.compras.create') }}" class="inline-flex items-center gap-2 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 px-4 py-2 rounded-lg text-sm font-semibold">📥 Registrar compra</a>
    <a href="{{ route('aseo.elementos') }}" class="inline-flex items-center gap-2 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 px-4 py-2 rounded-lg text-sm font-semibold">🧴 Elementos</a>
    <a href="{{ route('aseo.dependencias') }}" class="inline-flex items-center gap-2 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 px-4 py-2 rounded-lg text-sm font-semibold">🏢 Dependencias</a>
</div>

{{-- Criterio de reposición --}}
<form method="GET" class="bg-white rounded-xl shadow px-5 py-3 mb-6 flex flex-wrap items-end gap-4 text-sm">
    <div>
        <label class="block text-xs text-gray-500 mb-1">Stock mínimo (alerta)</label>
        <input type="number" name="minimo" min="0" max="99" value="{{ $minimo }}" class="w-24 border border-gray-300 rounded-lg px-3 py-1.5">
    </div>
    <div>
        <label class="block text-xs text-gray-500 mb-1">Objetivo por elemento</label>
        <input type="number" name="objetivo" min="1" max="999" value="{{ $objetivo }}" class="w-24 border border-gray-300 rounded-lg px-3 py-1.5">
    </div>
    <div>
        <label class="block text-xs text-gray-500 mb-1">Consumo (días)</label>
        <input type="number" name="dias" min="7" max="730" value="{{ $dias }}" class="w-24 border border-gray-300 rounded-lg px-3 py-1.5">
    </div>
    <button class="bg-gray-700 hover:bg-gray-800 text-white px-4 py-1.5 rounded-lg text-sm font-semibold">Aplicar</button>
    <p class="text-xs text-gray-500 ml-auto max-w-md">
        Se marca como <b>por reponer</b> todo lo que tenga {{ $minimo }} unidad(es) o menos; la cantidad sugerida completa hasta
        {{ $objetivo }} unidades y el <b>consumo</b> es lo entregado a dependencias en los últimos {{ $dias }} días.
    </p>
</form>

{{-- Tarjetas resumen --}}
<div class="grid grid-cols-2 lg:grid-cols-6 gap-4 mb-8">
    <div class="bg-white rounded-xl shadow p-4">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Referencias</p>
        <p class="text-2xl font-bold text-gray-800">{{ number_format($resumen['referencias']) }}</p>
    </div>
    <div class="bg-white rounded-xl shadow p-4">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Unidades en stock</p>
        <p class="text-2xl font-bold text-gray-800">{{ number_format($resumen['unidades']) }}</p>
    </div>
    <div class="bg-white rounded-xl shadow p-4">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Stock bajo (≤{{ $minimo }})</p>
        <p class="text-2xl font-bold text-amber-600">{{ number_format($resumen['bajos']) }}</p>
    </div>
    <div class="bg-white rounded-xl shadow p-4">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Agotados</p>
        <p class="text-2xl font-bold text-red-600">{{ number_format($resumen['agotados']) }}</p>
    </div>
    <div class="bg-white rounded-xl shadow p-4">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Consumo ({{ $dias }} días)</p>
        <p class="text-2xl font-bold text-gray-800">{{ number_format($resumen['consumo']) }}</p>
    </div>
    <div class="bg-white rounded-xl shadow p-4">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Reposición estimada</p>
        <p class="text-2xl font-bold text-emerald-700">${{ number_format($resumen['costo_reposicion'], 0) }}</p>
    </div>
</div>

{{-- Sin precios de compra todavía: el costo estimado no se puede calcular. --}}
@if($resumen['sin_costo'] === $resumen['referencias'] && $resumen['referencias'] > 0)
<div class="mb-6 bg-amber-50 border border-amber-300 text-amber-900 px-4 py-3 rounded-lg text-sm">
    <b>Sin precios de compra registrados.</b> El inventario de aseo se cargó sin valores, así que la reposición estimada
    aparece en $0. Al registrar una compra con sus precios unitarios, el informe empezará a calcular el costo.
</div>
@endif

{{-- Para comprar --}}
<div class="bg-white rounded-xl shadow mb-8">
    <div class="px-5 py-3 border-b border-gray-100 flex flex-wrap items-center justify-between gap-3">
        <h3 class="font-semibold text-gray-700">Para revisar / comprar — menor stock primero</h3>
        <a href="{{ route('aseo.solicitudes.create', ['minimo' => $minimo, 'objetivo' => $objetivo, 'dias' => $dias]) }}"
           class="text-sm font-semibold text-emerald-700 hover:underline">Solicitar compra de estos elementos →</a>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                <tr>
                    <th class="px-4 py-2 text-left">Código</th>
                    <th class="px-4 py-2 text-left">Elemento</th>
                    <th class="px-4 py-2 text-left">Presentación</th>
                    <th class="px-4 py-2 text-right">Stock</th>
                    <th class="px-4 py-2 text-right">Consumo</th>
                    <th class="px-4 py-2 text-right">Sugerido</th>
                    <th class="px-4 py-2 text-right">Último costo</th>
                    <th class="px-4 py-2 text-right">Costo estimado</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($porComprar as $e)
                <tr class="{{ $e->stock <= 0 ? 'bg-red-50' : ($e->stock <= $minimo ? 'bg-amber-50' : '') }}">
                    <td class="px-4 py-2 font-mono text-gray-600">{{ $e->codigo }}</td>
                    <td class="px-4 py-2 text-gray-800">{{ $e->descripcion }}</td>
                    <td class="px-4 py-2 text-gray-500">{{ $e->presentacion }}</td>
                    <td class="px-4 py-2 text-right font-semibold {{ $e->stock <= 0 ? 'text-red-600' : ($e->stock <= $minimo ? 'text-amber-600' : 'text-gray-700') }}">{{ $e->stock }}</td>
                    <td class="px-4 py-2 text-right text-gray-500">{{ $e->consumo }}</td>
                    <td class="px-4 py-2 text-right font-semibold text-gray-800">{{ $e->sugerido }}</td>
                    <td class="px-4 py-2 text-right text-gray-600">{{ $e->ultimo_costo !== null ? '$' . number_format($e->ultimo_costo, 0) : '—' }}</td>
                    <td class="px-4 py-2 text-right text-gray-700">{{ $e->ultimo_costo !== null ? '$' . number_format($e->sugerido * $e->ultimo_costo, 0) : '—' }}</td>
                </tr>
                @empty
                <tr><td colspan="8" class="px-4 py-6 text-center text-gray-400">Aún no hay elementos de aseo. <a href="{{ route('aseo.elementos') }}" class="text-blue-600 underline">Crea el catálogo</a>.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Consumo por dependencia --}}
@if($porDependencia->count())
<div class="bg-white rounded-xl shadow mb-8">
    <div class="px-5 py-3 border-b border-gray-100">
        <h3 class="font-semibold text-gray-700">Consumo por dependencia — últimos {{ $dias }} días</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                <tr>
                    <th class="px-4 py-2 text-left">Dependencia</th>
                    <th class="px-4 py-2 text-right w-32">Entregas</th>
                    <th class="px-4 py-2 text-right w-32">Unidades</th>
                    <th class="px-4 py-2 text-left">Participación</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @php $totalDep = $porDependencia->sum('unidades'); @endphp
                @foreach($porDependencia as $d)
                @php $pct = $totalDep > 0 ? $d->unidades * 100 / $totalDep : 0; @endphp
                <tr>
                    <td class="px-4 py-2 text-gray-800">{{ $d->nombre }}</td>
                    <td class="px-4 py-2 text-right text-gray-600">{{ number_format($d->entregas) }}</td>
                    <td class="px-4 py-2 text-right font-semibold text-gray-800">{{ number_format($d->unidades) }}</td>
                    <td class="px-4 py-2">
                        <div class="flex items-center gap-2">
                            <div class="flex-1 bg-gray-100 rounded-full h-2 max-w-xs overflow-hidden">
                                <div class="bg-teal-500 h-2 rounded-full" style="width: {{ number_format($pct, 1) }}%"></div>
                            </div>
                            <span class="text-xs text-gray-500 w-12 text-right">{{ number_format($pct, 1) }}%</span>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- Inventario completo --}}
<div class="bg-white rounded-xl shadow">
    <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
        <h3 class="font-semibold text-gray-700">Inventario de aseo</h3>
        <input type="text" id="filtro" placeholder="Buscar…" class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm w-48">
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm" id="tabla-inv">
            <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                <tr>
                    <th class="px-4 py-2 text-left">Código</th>
                    <th class="px-4 py-2 text-left">Elemento</th>
                    <th class="px-4 py-2 text-left">Presentación</th>
                    <th class="px-4 py-2 text-right">Stock</th>
                    <th class="px-4 py-2 text-right">Consumo</th>
                    <th class="px-4 py-2 text-right">Valor al costo</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($elementos as $e)
                <tr class="fila">
                    <td class="px-4 py-2 font-mono text-gray-600">{{ $e->codigo }}</td>
                    <td class="px-4 py-2 text-gray-800">{{ $e->descripcion }}</td>
                    <td class="px-4 py-2 text-gray-500">{{ $e->presentacion }}</td>
                    <td class="px-4 py-2 text-right font-semibold {{ $e->stock <= 0 ? 'text-red-600' : ($e->stock <= $minimo ? 'text-amber-600' : 'text-gray-700') }}">{{ $e->stock }}</td>
                    <td class="px-4 py-2 text-right text-gray-500">{{ $e->consumo }}</td>
                    <td class="px-4 py-2 text-right text-gray-700">${{ number_format($e->valor_costo, 0) }}</td>
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
