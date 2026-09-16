@extends('layouts.app-sidebar')

@section('header', 'Uniformes · Solicitud de compra N° ' . $sol->numero)

@section('slot')

@php
    $badge = [
        'pendiente' => ['bg-amber-100 text-amber-800',    '⏳ Pendiente de autorización'],
        'aprobada'  => ['bg-emerald-100 text-emerald-800','✅ Aprobada'],
        'rechazada' => ['bg-red-100 text-red-700',        '⛔ Rechazada'],
        'comprada'  => ['bg-blue-100 text-blue-800',      '📦 Comprada'],
        'anulada'   => ['bg-gray-200 text-gray-600',      '🚫 Anulada'],
    ];
    $b = $badge[$sol->estado] ?? ['bg-gray-100 text-gray-600', $sol->estado];

    // La jefatura puede aprobar/ajustar mientras la solicitud no esté cerrada.
    $editable = $autoriza && in_array($sol->estado, ['pendiente', 'aprobada']);
    $total    = $items->sum('subtotal');
@endphp

@if(session('ok'))
<div class="mb-5 bg-green-50 border border-green-300 text-green-800 px-4 py-3 rounded-lg text-sm font-medium">{{ session('ok') }}</div>
@endif
@if($errors->any())
<div class="mb-5 bg-red-50 border border-red-300 text-red-800 px-4 py-3 rounded-lg text-sm">{{ $errors->first() }}</div>
@endif

{{-- ── Encabezado de la solicitud ── --}}
<div class="bg-white rounded-xl shadow p-5 mb-5">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <h2 class="text-xl font-bold text-gray-800">Solicitud N° {{ $sol->numero }}</h2>
                <span class="inline-block px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $b[0] }}">{{ $b[1] }}</span>
            </div>
            <p class="text-sm text-gray-500 mt-1">
                Creada el {{ \Carbon\Carbon::parse($sol->fecha)->format('d/m/Y') }}
                por <b class="text-gray-700">{{ $sol->solicitado_por ?? '—' }}</b>
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('inventario.solicitudes.imprimir', $sol->id) }}" target="_blank"
               class="bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 px-4 py-2 rounded-lg text-sm font-semibold">🖨️ Imprimir orden</a>
            @if($sol->estado === 'aprobada')
            <a href="{{ route('inventario.compras.create', ['solicitud' => $sol->id]) }}"
               class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg text-sm font-semibold">📥 Registrar compra</a>
            @endif
            @if($sol->estado === 'comprada' && $sol->compra_id)
            <a href="{{ route('inventario.compras') }}"
               class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold">Ver compras</a>
            @endif
            <a href="{{ route('inventario.solicitudes') }}"
               class="border border-gray-300 text-gray-600 hover:bg-gray-50 px-4 py-2 rounded-lg text-sm">← Volver</a>
        </div>
    </div>

    <div class="grid md:grid-cols-4 gap-4 mt-5 pt-4 border-t border-gray-100 text-sm">
        <div>
            <p class="text-xs text-gray-400 uppercase tracking-wide">Proveedor sugerido</p>
            <p class="text-gray-800 font-medium">{{ $sol->proveedor ?? '—' }}</p>
        </div>
        <div class="md:col-span-3">
            <p class="text-xs text-gray-400 uppercase tracking-wide">Justificación</p>
            <p class="text-gray-800">{{ $sol->justificacion ?: '—' }}</p>
        </div>

        @if($sol->autorizado_at)
        <div>
            <p class="text-xs text-gray-400 uppercase tracking-wide">Autorizó</p>
            <p class="text-gray-800 font-medium">{{ $sol->autorizado_por ?? '—' }}</p>
            <p class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($sol->autorizado_at)->format('d/m/Y g:i a') }}</p>
        </div>
        <div class="md:col-span-3">
            <p class="text-xs text-gray-400 uppercase tracking-wide">Nota de la administración</p>
            <p class="text-gray-800">{{ $sol->nota_autorizacion ?: '—' }}</p>
        </div>
        @endif

        @if($sol->estado === 'comprada')
        <div class="md:col-span-4 bg-blue-50 border border-blue-200 rounded-lg px-4 py-3">
            <p class="text-sm text-blue-900">
                Compra registrada por <b>{{ $sol->comprado_por ?? '—' }}</b>
                el {{ $sol->comprado_at ? \Carbon\Carbon::parse($sol->comprado_at)->format('d/m/Y') : '—' }}
                @if($sol->factura) · Factura <b>{{ $sol->factura }}</b> @endif
                @if($sol->compra_total !== null) · Total <b>${{ number_format($sol->compra_total, 0) }}</b> @endif
            </p>
        </div>
        @endif
    </div>
</div>

@if($editable)
<form method="POST" action="{{ route('inventario.solicitudes.autorizar', $sol->id) }}" id="form-aut">
@csrf
@endif

{{-- ── Detalle ── --}}
<div class="bg-white rounded-xl shadow">
    <div class="px-5 py-3 border-b border-gray-100">
        <h3 class="font-semibold text-gray-700">Referencias solicitadas</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                <tr>
                    <th class="px-4 py-2 text-left">Código</th>
                    <th class="px-4 py-2 text-left">Producto</th>
                    <th class="px-4 py-2 text-right w-28">Stock al pedir</th>
                    <th class="px-4 py-2 text-right w-24">Stock hoy</th>
                    <th class="px-4 py-2 text-right w-24">Solicitado</th>
                    <th class="px-4 py-2 text-right w-32">{{ $editable ? 'Autorizar' : 'Autorizado' }}</th>
                    <th class="px-4 py-2 text-right w-28">Costo ref.</th>
                    <th class="px-4 py-2 text-right w-32">Estimado</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($items as $it)
                <tr class="fila" data-costo="{{ (float) ($it->costo_ref ?? 0) }}">
                    <td class="px-4 py-2 font-mono text-gray-600">{{ $it->codigo }}</td>
                    <td class="px-4 py-2 text-gray-800">{{ $it->nombre }}</td>
                    <td class="px-4 py-2 text-right text-gray-500">{{ $it->stock_actual }}</td>
                    <td class="px-4 py-2 text-right font-semibold {{ $it->stock_hoy <= 0 ? 'text-red-600' : 'text-gray-700' }}">{{ $it->stock_hoy }}</td>
                    <td class="px-4 py-2 text-right text-gray-700">{{ $it->cantidad }}</td>
                    <td class="px-4 py-2 text-right">
                        @if($editable)
                        <input type="number" min="0" name="cantidades[{{ $it->id }}]" value="{{ $it->cantidad_final }}"
                               oninput="calc()" class="cant w-24 border border-gray-200 rounded px-2 py-1 text-right">
                        @else
                        <span class="cant font-semibold text-gray-800" data-v="{{ $it->cantidad_final }}">{{ $it->cantidad_final }}</span>
                        @endif
                    </td>
                    <td class="px-4 py-2 text-right text-gray-600">{{ $it->costo_ref !== null ? '$' . number_format($it->costo_ref, 0) : '—' }}</td>
                    <td class="px-4 py-2 text-right text-gray-700 sub">${{ number_format($it->subtotal, 0) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="border-t border-gray-200 bg-gray-50">
                    <td colspan="5" class="px-4 py-3 text-right font-semibold text-gray-600">Total estimado</td>
                    <td class="px-4 py-3 text-right font-semibold text-gray-700" id="tot-und">{{ $items->sum('cantidad_final') }} und</td>
                    <td></td>
                    <td class="px-4 py-3 text-right font-bold text-gray-800" id="tot-val">${{ number_format($total, 0) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

@if($editable)
    <div class="bg-white rounded-xl shadow p-5 mt-5">
        <label class="block text-xs text-gray-500 mb-1">Nota de la administración (opcional)</label>
        <input type="text" name="nota" maxlength="500" value="{{ $sol->nota_autorizacion }}"
               placeholder="Ej.: se aprueba solo lo urgente; el resto se compra en enero"
               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm mb-4">

        <div class="flex flex-wrap gap-3">
            <button type="submit" name="decision" value="aprobar"
                    class="bg-emerald-600 hover:bg-emerald-700 text-white px-5 py-2 rounded-lg text-sm font-semibold">
                ✅ {{ $sol->estado === 'aprobada' ? 'Guardar cambios' : 'Aprobar solicitud' }}
            </button>
            <button type="submit" name="decision" value="rechazar"
                    onclick="return confirm('¿Rechazar esta solicitud de compra?')"
                    class="bg-red-600 hover:bg-red-700 text-white px-5 py-2 rounded-lg text-sm font-semibold">
                ⛔ Rechazar
            </button>
        </div>
        <p class="text-xs text-gray-500 mt-3">
            Puedes ajustar las cantidades antes de aprobar. Lo que dejes en <b>0</b> no se comprará.
        </p>
    </div>
</form>
@endif

{{-- ── Anular (form aparte: no puede ir anidado) ── --}}
@if($sol->estado !== 'comprada' && $sol->estado !== 'anulada')
    @if($autoriza || ($sol->estado === 'pendiente' && $sol->solicitado_por === (auth()->user()->USER ?? null)))
    <form method="POST" action="{{ route('inventario.solicitudes.anular', $sol->id) }}" class="mt-4"
          onsubmit="return confirm('¿Anular la solicitud N° {{ $sol->numero }}?')">
        @csrf
        <button class="text-sm text-gray-500 hover:text-red-600 underline">Anular solicitud</button>
    </form>
    @endif
@endif

<script>
    function calc() {
        let und = 0, val = 0;
        document.querySelectorAll('.fila').forEach(f => {
            const el = f.querySelector('.cant');
            const c  = Number(el.value !== undefined ? el.value : el.dataset.v) || 0;
            const sub = c * (Number(f.dataset.costo) || 0);
            und += c; val += sub;
            f.querySelector('.sub').textContent = '$' + sub.toLocaleString('es-CO');
        });
        document.getElementById('tot-und').textContent = und.toLocaleString('es-CO') + ' und';
        document.getElementById('tot-val').textContent = '$' + val.toLocaleString('es-CO');
    }
</script>
@endsection
