@extends('layouts.app-sidebar')

@section('header', 'Aseo · Solicitudes de compra')

@section('slot')

@php
    $badge = [
        'pendiente' => ['bg-amber-100 text-amber-800',    '⏳ Pendiente'],
        'aprobada'  => ['bg-emerald-100 text-emerald-800','✅ Aprobada'],
        'rechazada' => ['bg-red-100 text-red-700',        '⛔ Rechazada'],
        'comprada'  => ['bg-blue-100 text-blue-800',      '📦 Comprada'],
        'anulada'   => ['bg-gray-200 text-gray-600',      '🚫 Anulada'],
    ];
@endphp

@if(session('ok'))
<div class="mb-5 bg-green-50 border border-green-300 text-green-800 px-4 py-3 rounded-lg text-sm font-medium">{{ session('ok') }}</div>
@endif
@if($errors->any())
<div class="mb-5 bg-red-50 border border-red-300 text-red-800 px-4 py-3 rounded-lg text-sm">{{ $errors->first() }}</div>
@endif

<div class="flex flex-wrap gap-3 mb-6">
    <a href="{{ route('aseo.solicitudes.create') }}" class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg text-sm font-semibold">
        🛒 Nueva solicitud
    </a>
    <a href="{{ route('aseo.dashboard') }}" class="inline-flex items-center gap-2 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 px-4 py-2 rounded-lg text-sm font-semibold">
        📊 Informe aseo
    </a>
</div>

<div class="bg-white rounded-xl shadow">
    <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
        <h3 class="font-semibold text-gray-700">Historial de solicitudes</h3>
        <input type="text" id="filtro" placeholder="Buscar…" class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm w-48">
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm" id="tabla">
            <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                <tr>
                    <th class="px-4 py-2 text-left w-20">N°</th>
                    <th class="px-4 py-2 text-left w-28">Fecha</th>
                    <th class="px-4 py-2 text-left">Solicitó</th>
                    <th class="px-4 py-2 text-left">Proveedor sugerido</th>
                    <th class="px-4 py-2 text-right w-24">Elementos</th>
                    <th class="px-4 py-2 text-right w-24">Unidades</th>
                    <th class="px-4 py-2 text-left w-32">Estado</th>
                    <th class="px-4 py-2 text-right w-28"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($solicitudes as $s)
                @php $b = $badge[$s->estado] ?? ['bg-gray-100 text-gray-600', $s->estado]; @endphp
                <tr class="fila hover:bg-gray-50">
                    <td class="px-4 py-2 font-mono font-semibold text-gray-700">{{ $s->numero }}</td>
                    <td class="px-4 py-2 text-gray-600">{{ \Carbon\Carbon::parse($s->fecha)->format('d/m/Y') }}</td>
                    <td class="px-4 py-2 text-gray-700">{{ $s->solicitado_por ?? '—' }}</td>
                    <td class="px-4 py-2 text-gray-600">{{ $s->proveedor ?? '—' }}</td>
                    <td class="px-4 py-2 text-right text-gray-700">{{ $s->n_items }}</td>
                    <td class="px-4 py-2 text-right font-semibold text-gray-800">{{ (int) $s->n_unidades }}</td>
                    <td class="px-4 py-2">
                        <span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold {{ $b[0] }}">{{ $b[1] }}</span>
                        @if($s->estado === 'comprada' && $s->documento)
                        <div class="text-xs text-gray-400 mt-0.5">Doc. {{ $s->documento }}</div>
                        @endif
                    </td>
                    <td class="px-4 py-2 text-right">
                        <a href="{{ route('aseo.solicitudes.ver', $s->id) }}" class="text-blue-600 hover:underline font-semibold">Ver</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="px-4 py-8 text-center text-gray-400">
                    Aún no hay solicitudes de compra de aseo.
                    <a href="{{ route('aseo.solicitudes.create') }}" class="text-blue-600 underline">Crea la primera</a>.
                </td></tr>
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
