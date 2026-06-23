@extends('layouts.app-sidebar')

@section('header', 'Aseo · Registrar compra')

@section('slot')

@if($errors->any())
<div class="mb-5 bg-red-50 border border-red-300 text-red-800 px-4 py-3 rounded-lg text-sm">{{ $errors->first() }}</div>
@endif

<form method="POST" action="{{ route('aseo.compras.store') }}">
    @csrf
    <div class="bg-white rounded-xl shadow p-5 mb-6 grid md:grid-cols-4 gap-4">
        <div>
            <label class="block text-xs text-gray-500 mb-1">Proveedor</label>
            <select name="proveedor_id" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                <option value="">— Seleccionar —</option>
                @foreach($proveedores as $pv)<option value="{{ $pv->id }}">{{ $pv->nombre }}</option>@endforeach
            </select>
        </div>
        <div><label class="block text-xs text-gray-500 mb-1">N° de documento</label><input type="text" name="documento" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"></div>
        <div><label class="block text-xs text-gray-500 mb-1">Fecha</label><input type="date" name="fecha" required value="{{ date('Y-m-d') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"></div>
        <div><label class="block text-xs text-gray-500 mb-1">Observación</label><input type="text" name="observacion" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"></div>
    </div>

    <div class="bg-white rounded-xl shadow">
        <div class="px-5 py-3 border-b border-gray-100 flex justify-between items-center">
            <h3 class="font-semibold text-gray-700">Elementos comprados</h3>
            <button type="button" onclick="agregarFila()" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded-lg text-sm font-semibold">+ Agregar línea</button>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                    <tr><th class="px-3 py-2 text-left">Elemento</th><th class="px-3 py-2 text-right w-28">Cantidad</th><th class="px-3 py-2 text-right w-36">Precio unitario</th><th class="px-3 py-2 text-right w-36">Subtotal</th><th class="w-10"></th></tr>
                </thead>
                <tbody id="items"></tbody>
                <tfoot><tr class="border-t border-gray-200"><td colspan="3" class="px-3 py-3 text-right font-semibold text-gray-600">Total</td><td class="px-3 py-3 text-right font-bold text-gray-800" id="total">$0</td><td></td></tr></tfoot>
            </table>
        </div>
    </div>

    <div class="mt-5 flex gap-3">
        <button class="bg-emerald-600 hover:bg-emerald-700 text-white px-5 py-2 rounded-lg text-sm font-semibold">Guardar compra</button>
        <a href="{{ route('aseo.compras') }}" class="px-5 py-2 rounded-lg text-sm border border-gray-300 text-gray-600 hover:bg-gray-50">Cancelar</a>
    </div>
</form>

<script>
    const ELEMENTOS = @json($elementos);
    let idx = 0;
    function opciones() { return ELEMENTOS.map(e => `<option value="${e.id}">${e.codigo} · ${e.descripcion}</option>`).join(''); }
    function agregarFila() {
        const i = idx++;
        const tr = document.createElement('tr');
        tr.className = 'border-b border-gray-100 fila';
        tr.innerHTML = `
            <td class="px-3 py-2"><select name="items[${i}][elemento_id]" required class="w-full border border-gray-200 rounded px-2 py-1"><option value="">— Elemento —</option>${opciones()}</select></td>
            <td class="px-3 py-2 text-right"><input type="number" min="1" value="1" name="items[${i}][cantidad]" oninput="calc()" class="w-24 border border-gray-200 rounded px-2 py-1 text-right"></td>
            <td class="px-3 py-2 text-right"><input type="number" min="0" step="0.01" value="0" name="items[${i}][precio]" oninput="calc()" class="w-32 border border-gray-200 rounded px-2 py-1 text-right"></td>
            <td class="px-3 py-2 text-right subtotal text-gray-700">$0</td>
            <td class="px-3 py-2 text-center"><button type="button" onclick="this.closest('tr').remove(); calc()" class="text-red-500 hover:text-red-700">✕</button></td>`;
        document.getElementById('items').appendChild(tr);
    }
    function calc() {
        let total = 0;
        document.querySelectorAll('#items .fila').forEach(f => {
            const c = Number(f.querySelector('input[name$="[cantidad]"]').value) || 0;
            const p = Number(f.querySelector('input[name$="[precio]"]').value) || 0;
            total += c * p;
            f.querySelector('.subtotal').textContent = '$' + (c * p).toLocaleString();
        });
        document.getElementById('total').textContent = '$' + total.toLocaleString();
    }
    agregarFila();
</script>
@endsection
