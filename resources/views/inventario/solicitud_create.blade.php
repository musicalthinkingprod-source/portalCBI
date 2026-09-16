@extends('layouts.app-sidebar')

@section('header', 'Uniformes · Solicitar compra')

@section('slot')

@php
    // Referencias que llegan marcadas por defecto (las que están por reponer).
    $marcados = $porComprar->pluck('id')->all();
@endphp

@if($errors->any())
<div class="mb-5 bg-red-50 border border-red-300 text-red-800 px-4 py-3 rounded-lg text-sm">{{ $errors->first() }}</div>
@endif

<div class="mb-5 bg-blue-50 border border-blue-200 text-blue-900 px-4 py-3 rounded-lg text-sm">
    @if($autoriza)
        Estás creando la orden como <b>administración</b>: queda <b>aprobada de inmediato</b> y las secretarías podrán registrar la compra.
    @else
        La solicitud se envía a la <b>administración</b> para su autorización. Cuando la aprueben, podrás registrar la compra desde aquí mismo.
    @endif
    Vienen marcadas las referencias con stock ≤ {{ $minimo }} y una cantidad sugerida para llegar a {{ $objetivo }} unidades por referencia.
</div>

<form method="POST" action="{{ route('inventario.solicitudes.store') }}" id="form-sol">
    @csrf

    <div class="bg-white rounded-xl shadow p-5 mb-5 grid md:grid-cols-3 gap-4">
        <div>
            <label class="block text-xs text-gray-500 mb-1">Proveedor sugerido (opcional)</label>
            <select name="proveedor_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                <option value="">— Sin definir —</option>
                @foreach($proveedores as $pv)
                <option value="{{ $pv->id }}">{{ $pv->nombre }}</option>
                @endforeach
            </select>
        </div>
        <div class="md:col-span-2">
            <label class="block text-xs text-gray-500 mb-1">Justificación / observaciones</label>
            <input type="text" name="justificacion" maxlength="500" placeholder="Ej.: reposición para matrículas de enero"
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
        </div>
    </div>

    <div class="bg-white rounded-xl shadow">
        <div class="px-5 py-3 border-b border-gray-100 flex flex-wrap items-center gap-3 justify-between">
            <h3 class="font-semibold text-gray-700">Referencias a comprar</h3>
            <div class="flex items-center gap-2">
                <input type="text" id="filtro" placeholder="Buscar referencia…" class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm w-52">
                <button type="button" onclick="marcar(true)"  class="text-xs px-3 py-1.5 rounded-lg border border-gray-300 text-gray-600 hover:bg-gray-50">Marcar visibles</button>
                <button type="button" onclick="marcar(false)" class="text-xs px-3 py-1.5 rounded-lg border border-gray-300 text-gray-600 hover:bg-gray-50">Desmarcar visibles</button>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm" id="tabla">
                <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                    <tr>
                        <th class="px-3 py-2 w-10"></th>
                        <th class="px-3 py-2 text-left">Código</th>
                        <th class="px-3 py-2 text-left">Producto</th>
                        <th class="px-3 py-2 text-right w-20">Stock</th>
                        <th class="px-3 py-2 text-right w-28">Cantidad</th>
                        <th class="px-3 py-2 text-right w-32">Último costo</th>
                        <th class="px-3 py-2 text-right w-32">Estimado</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($productos as $i => $p)
                    @php $on = in_array($p->id, $marcados); @endphp
                    <tr class="fila {{ $on ? '' : 'opacity-60' }}" data-costo="{{ (float) ($p->ultimo_costo ?? 0) }}">
                        <td class="px-3 py-2 text-center">
                            <input type="checkbox" class="chk w-4 h-4" {{ $on ? 'checked' : '' }} onchange="toggleFila(this)">
                        </td>
                        <td class="px-3 py-2 font-mono text-gray-600">{{ $p->codigo }}</td>
                        <td class="px-3 py-2 text-gray-800">{{ $p->nombre }}</td>
                        <td class="px-3 py-2 text-right font-semibold {{ $p->stock <= 0 ? 'text-red-600' : ($p->stock <= $minimo ? 'text-amber-600' : 'text-gray-700') }}">{{ $p->stock }}</td>
                        <td class="px-3 py-2 text-right">
                            <input type="hidden" name="items[{{ $i }}][producto_id]" value="{{ $p->id }}" {{ $on ? '' : 'disabled' }}>
                            <input type="number" min="1" value="{{ max(1, $p->sugerido) }}" name="items[{{ $i }}][cantidad]"
                                   oninput="calc()" {{ $on ? '' : 'disabled' }}
                                   class="cant w-24 border border-gray-200 rounded px-2 py-1 text-right">
                        </td>
                        <td class="px-3 py-2 text-right text-gray-600">
                            {{ $p->ultimo_costo !== null ? '$' . number_format($p->ultimo_costo, 0) : '—' }}
                        </td>
                        <td class="px-3 py-2 text-right text-gray-700 sub">$0</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="border-t border-gray-200 bg-gray-50">
                        <td colspan="4" class="px-3 py-3 text-right font-semibold text-gray-600">Total estimado</td>
                        <td class="px-3 py-3 text-right font-semibold text-gray-700" id="tot-unidades">0 und</td>
                        <td></td>
                        <td class="px-3 py-3 text-right font-bold text-gray-800" id="tot-valor">$0</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <p class="text-xs text-gray-500 mt-3">
        El valor estimado usa el último precio de compra registrado de cada referencia; es solo una referencia para la autorización,
        el precio real se digita al registrar la factura.
    </p>

    <div class="mt-5 flex gap-3">
        <button class="bg-emerald-600 hover:bg-emerald-700 text-white px-5 py-2 rounded-lg text-sm font-semibold">
            {{ $autoriza ? '✅ Crear orden aprobada' : '📤 Enviar a autorización' }}
        </button>
        <a href="{{ route('inventario.dashboard', ['minimo' => $minimo, 'objetivo' => $objetivo]) }}"
           class="px-5 py-2 rounded-lg text-sm border border-gray-300 text-gray-600 hover:bg-gray-50">Cancelar</a>
    </div>
</form>

<script>
    function filas() { return document.querySelectorAll('#tabla .fila'); }

    function toggleFila(chk) {
        const f = chk.closest('tr');
        f.querySelectorAll('input[name^="items"]').forEach(i => i.disabled = !chk.checked);
        f.classList.toggle('opacity-60', !chk.checked);
        calc();
    }

    function marcar(valor) {
        filas().forEach(f => {
            if (f.style.display === 'none') return;   // solo las visibles según el filtro
            const chk = f.querySelector('.chk');
            chk.checked = valor;
            toggleFila(chk);
        });
    }

    function calc() {
        let und = 0, val = 0;
        filas().forEach(f => {
            const on = f.querySelector('.chk').checked;
            const c  = Number(f.querySelector('.cant').value) || 0;
            const costo = Number(f.dataset.costo) || 0;
            const sub = on ? c * costo : 0;
            if (on) und += c;
            val += sub;
            f.querySelector('.sub').textContent = '$' + sub.toLocaleString('es-CO');
        });
        document.getElementById('tot-unidades').textContent = und.toLocaleString('es-CO') + ' und';
        document.getElementById('tot-valor').textContent = '$' + val.toLocaleString('es-CO');
    }

    document.getElementById('filtro').addEventListener('input', function () {
        const q = this.value.toLowerCase();
        filas().forEach(f => {
            f.style.display = f.textContent.toLowerCase().includes(q) ? '' : 'none';
        });
    });

    document.getElementById('form-sol').addEventListener('submit', function (e) {
        const n = [...filas()].filter(f => f.querySelector('.chk').checked).length;
        if (n === 0) {
            e.preventDefault();
            alert('Marca al menos una referencia para solicitar.');
        }
    });

    calc();
</script>
@endsection
