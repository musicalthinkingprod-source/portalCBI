@extends('layouts.app-sidebar')

@section('header', 'Inventario · Devoluciones y cambios')

@section('slot')

@if(session('ok'))
<div class="mb-5 bg-green-50 border border-green-300 text-green-800 px-4 py-3 rounded-lg text-sm font-medium">{{ session('ok') }}</div>
@endif
@if($errors->any())
<div class="mb-5 bg-red-50 border border-red-300 text-red-800 px-4 py-3 rounded-lg text-sm">{{ $errors->first() }}</div>
@endif

{{-- Buscar venta --}}
<div class="bg-white rounded-xl shadow p-5 mb-6">
    <label class="block text-xs text-gray-500 mb-1">Número de venta / recibo</label>
    <div class="flex gap-2 max-w-sm">
        <input type="number" id="numero" class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="Ej: 1">
        <button type="button" onclick="buscar()" class="bg-gray-700 text-white px-4 rounded-lg text-sm">Buscar</button>
    </div>
    <p id="busca-msg" class="mt-2 text-sm"></p>
</div>

{{-- Detalle / acciones --}}
<div id="panel" class="hidden">
    <div class="bg-white rounded-xl shadow p-5 mb-6">
        <div class="flex items-center justify-between mb-3">
            <h3 class="font-semibold text-gray-700">Venta N° <span id="v-numero"></span> — <span id="v-fecha"></span></h3>
            <span id="v-estado" class="text-xs font-semibold px-2 py-1 rounded"></span>
        </div>

        <table class="w-full text-sm mb-2">
            <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                <tr>
                    <th class="px-3 py-2 text-left">Código</th>
                    <th class="px-3 py-2 text-left">Prenda</th>
                    <th class="px-3 py-2 text-right">Precio</th>
                    <th class="px-3 py-2 text-right">Comprado</th>
                    <th class="px-3 py-2 text-right">Ya devuelto</th>
                    <th class="px-3 py-2 text-right w-28">Devolver</th>
                </tr>
            </thead>
            <tbody id="v-items"></tbody>
        </table>
    </div>

    <form method="POST" action="{{ route('inventario.cambios.guardar') }}" id="form-cambio">
        @csrf
        <input type="hidden" name="venta_id" id="venta_id">
        <input type="hidden" name="accion" id="accion" value="cambio">

        {{-- Prendas nuevas (solo para cambio) --}}
        <div class="bg-white rounded-xl shadow mb-6" id="box-nuevos">
            <div class="px-5 py-3 border-b border-gray-100 flex justify-between items-center">
                <h3 class="font-semibold text-gray-700">Prendas que se lleva (cambio)</h3>
                <button type="button" onclick="agregarNuevo()" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded-lg text-sm font-semibold">+ Agregar prenda</button>
            </div>
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                    <tr><th class="px-3 py-2 text-left">Prenda</th><th class="px-3 py-2 text-right w-24">Cant.</th><th class="px-3 py-2 text-right w-32">Precio</th><th class="w-10"></th></tr>
                </thead>
                <tbody id="nuevos"></tbody>
            </table>
        </div>

        <div class="bg-white rounded-xl shadow p-5 mb-6">
            <div class="flex justify-between text-sm mb-1"><span class="text-gray-500">Valor devuelto</span><span id="r-dev" class="font-medium">$0</span></div>
            <div class="flex justify-between text-sm mb-1"><span class="text-gray-500">Valor nuevo</span><span id="r-nue" class="font-medium">$0</span></div>
            <div class="flex justify-between text-base font-semibold border-t border-gray-100 pt-2 mt-2">
                <span id="r-dif-lbl">Diferencia</span><span id="r-dif">$0</span>
            </div>
            <p id="r-aviso" class="mt-2 text-xs"></p>
            <div class="mt-3">
                <label class="block text-xs text-gray-500 mb-1">Motivo (opcional)</label>
                <input type="text" name="motivo" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
        </div>

        <div class="flex gap-3">
            <button type="submit" id="btn-cambio" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-lg text-sm font-semibold">Registrar cambio</button>
            <button type="button" id="btn-cancelar" onclick="cancelarVenta()" class="bg-red-600 hover:bg-red-700 text-white px-5 py-2.5 rounded-lg text-sm font-semibold hidden">Cancelar venta (reembolso total)</button>
        </div>
    </form>
</div>

<script>
    const API_VENTA = "{{ route('inventario.api.venta') }}";
    const PRODUCTOS = @json($productos);
    let venta = null;

    async function buscar() {
        const num = document.getElementById('numero').value.trim();
        const msg = document.getElementById('busca-msg');
        if (!num) return;
        const r = await fetch(`${API_VENTA}?numero=${encodeURIComponent(num)}`);
        if (!r.ok) { msg.textContent = '⚠ No se encontró la venta.'; msg.className = 'mt-2 text-sm text-red-600'; document.getElementById('panel').classList.add('hidden'); return; }
        venta = await r.json();
        msg.textContent = '';
        render();
    }

    function render() {
        document.getElementById('panel').classList.remove('hidden');
        document.getElementById('venta_id').value = venta.id;
        document.getElementById('v-numero').textContent = venta.numero;
        document.getElementById('v-fecha').textContent = venta.fecha;

        const est = document.getElementById('v-estado');
        if (venta.estado === 'anulada') {
            est.textContent = 'ANULADA'; est.className = 'text-xs font-semibold px-2 py-1 rounded bg-gray-200 text-gray-600';
        } else if (venta.facturada) {
            est.textContent = 'FACTURADA — solo cambio par'; est.className = 'text-xs font-semibold px-2 py-1 rounded bg-purple-100 text-purple-800';
        } else {
            est.textContent = 'SIN FACTURAR — admite cancelación y cambios'; est.className = 'text-xs font-semibold px-2 py-1 rounded bg-amber-100 text-amber-800';
        }

        // Items de la venta con campo "devolver"
        document.getElementById('v-items').innerHTML = venta.items.map((it, i) => `
            <tr class="border-b border-gray-100">
                <td class="px-3 py-2 font-mono text-gray-600">${it.codigo}</td>
                <td class="px-3 py-2 text-gray-800">${it.nombre}</td>
                <td class="px-3 py-2 text-right text-gray-600">$${Number(it.precio_venta).toLocaleString()}</td>
                <td class="px-3 py-2 text-right">${it.cantidad}</td>
                <td class="px-3 py-2 text-right text-gray-500">${it.devuelto}</td>
                <td class="px-3 py-2 text-right">
                    <input type="number" min="0" max="${it.disponible}" value="0" data-pid="${it.producto_id}" data-precio="${it.precio_venta}"
                           class="dev w-20 border border-gray-200 rounded px-2 py-1 text-right" oninput="calc()">
                </td>
            </tr>`).join('');

        // Botones según estado
        const puedeCancelar = !venta.facturada && venta.estado === 'activa';
        document.getElementById('btn-cancelar').classList.toggle('hidden', !puedeCancelar);
        document.getElementById('nuevos').innerHTML = '';
        calc();
    }

    function opciones() {
        return PRODUCTOS.map(p => `<option value="${p.id}" data-precio="${p.precio_venta}">${p.codigo} · ${p.nombre} ($${Number(p.precio_venta).toLocaleString()})</option>`).join('');
    }
    let idx = 0;
    function agregarNuevo() {
        const i = idx++;
        const tr = document.createElement('tr');
        tr.className = 'border-b border-gray-100 filaN';
        tr.innerHTML = `
            <td class="px-3 py-2"><select name="nuevos[${i}][producto_id]" onchange="calc()" class="w-full border border-gray-200 rounded px-2 py-1"><option value="">— Prenda —</option>${opciones()}</select></td>
            <td class="px-3 py-2 text-right"><input type="number" min="1" value="1" name="nuevos[${i}][cantidad]" oninput="calc()" class="w-20 border border-gray-200 rounded px-2 py-1 text-right"></td>
            <td class="px-3 py-2 text-right precioN text-gray-600">$0</td>
            <td class="px-3 py-2 text-center"><button type="button" onclick="this.closest('tr').remove(); calc()" class="text-red-500">✕</button></td>`;
        document.getElementById('nuevos').appendChild(tr);
    }

    function calc() {
        // Valor devuelto
        let dev = 0;
        document.querySelectorAll('.dev').forEach(inp => {
            const c = Number(inp.value) || 0;
            dev += c * Number(inp.dataset.precio);
        });
        // Valor nuevo
        let nue = 0;
        document.querySelectorAll('#nuevos .filaN').forEach(f => {
            const sel = f.querySelector('select');
            const cant = Number(f.querySelector('input[name$="[cantidad]"]').value) || 0;
            const precio = sel.value ? Number(sel.options[sel.selectedIndex].dataset.precio) : 0;
            f.querySelector('.precioN').textContent = '$' + (precio * cant).toLocaleString();
            nue += precio * cant;
        });
        const dif = nue - dev;
        document.getElementById('r-dev').textContent = '$' + dev.toLocaleString();
        document.getElementById('r-nue').textContent = '$' + nue.toLocaleString();
        document.getElementById('r-dif').textContent = (dif < 0 ? '-$' : '$') + Math.abs(dif).toLocaleString();

        const aviso = document.getElementById('r-aviso');
        if (dif > 0) { aviso.textContent = 'Se cobra la diferencia al cliente.'; aviso.className = 'mt-2 text-xs text-blue-700'; }
        else if (dif < 0) { aviso.textContent = 'Se devuelve la diferencia al cliente.'; aviso.className = 'mt-2 text-xs text-emerald-700'; }
        else { aviso.textContent = 'Cambio par (sin diferencia).'; aviso.className = 'mt-2 text-xs text-gray-500'; }

        // Si está facturada, solo cambio par.
        const btn = document.getElementById('btn-cambio');
        if (venta && venta.facturada && Math.abs(dif) > 0) {
            btn.disabled = true; btn.classList.add('opacity-50', 'cursor-not-allowed');
            aviso.textContent = 'Venta facturada: solo se permite cambio par (mismo valor).'; aviso.className = 'mt-2 text-xs text-red-600';
        } else {
            btn.disabled = false; btn.classList.remove('opacity-50', 'cursor-not-allowed');
        }
    }

    function cancelarVenta() {
        if (!confirm('¿Cancelar la venta completa? Se reembolsa el total y las prendas reingresan al stock.')) return;
        document.getElementById('accion').value = 'cancelar';
        document.getElementById('form-cambio').submit();
    }

    // Al enviar el cambio, marcar la acción y volcar los devueltos (>0) como inputs.
    document.getElementById('form-cambio').addEventListener('submit', function (e) {
        if (document.getElementById('accion').value === 'cancelar') return; // cancelar va directo
        document.getElementById('accion').value = 'cambio';
        // limpiar hidden previos
        this.querySelectorAll('.devhidden').forEach(x => x.remove());
        let j = 0, hayDev = false;
        document.querySelectorAll('.dev').forEach(inp => {
            const c = Number(inp.value) || 0;
            if (c > 0) {
                hayDev = true;
                this.insertAdjacentHTML('beforeend',
                    `<input type="hidden" class="devhidden" name="devueltos[${j}][producto_id]" value="${inp.dataset.pid}">
                     <input type="hidden" class="devhidden" name="devueltos[${j}][cantidad]" value="${c}">`);
                j++;
            }
        });
        const hayNue = document.querySelectorAll('#nuevos .filaN').length > 0;
        if (!hayDev || !hayNue) { e.preventDefault(); alert('Indica al menos una prenda a devolver y una prenda nueva.'); }
    });
</script>
@endsection
