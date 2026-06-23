@extends('layouts.app-sidebar')

@section('header', 'Aseo · Entrega a dependencia')

@section('slot')

@if($errors->any())
<div class="mb-5 bg-red-50 border border-red-300 text-red-800 px-4 py-3 rounded-lg text-sm">{{ $errors->first() }}</div>
@endif

<form method="POST" action="{{ route('aseo.salidas.store') }}">
    @csrf
    <div class="bg-white rounded-xl shadow p-5 mb-6 grid md:grid-cols-3 gap-4">
        <div>
            <label class="block text-xs text-gray-500 mb-1">Dependencia</label>
            <select name="dependencia_id" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                <option value="">— Seleccionar —</option>
                @foreach($dependencias as $d)<option value="{{ $d->id }}">{{ $d->nombre }}</option>@endforeach
            </select>
        </div>
        <div><label class="block text-xs text-gray-500 mb-1">Fecha</label><input type="date" name="fecha" required value="{{ date('Y-m-d') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"></div>
        <div><label class="block text-xs text-gray-500 mb-1">Observación</label><input type="text" name="observacion" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"></div>
    </div>

    <div class="bg-white rounded-xl shadow">
        <div class="px-5 py-3 border-b border-gray-100 flex justify-between items-center">
            <h3 class="font-semibold text-gray-700">Elementos entregados</h3>
            <button type="button" onclick="agregarFila()" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded-lg text-sm font-semibold">+ Agregar línea</button>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                    <tr><th class="px-3 py-2 text-left">Elemento</th><th class="px-3 py-2 text-right w-28">Cantidad</th><th class="w-10"></th></tr>
                </thead>
                <tbody id="items"></tbody>
            </table>
        </div>
    </div>

    <div class="mt-5 flex gap-3">
        <button class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-lg text-sm font-semibold">Guardar entrega</button>
        <a href="{{ route('aseo.salidas') }}" class="px-5 py-2 rounded-lg text-sm border border-gray-300 text-gray-600 hover:bg-gray-50">Cancelar</a>
    </div>
</form>

<script>
    const ELEMENTOS = @json($elementos);
    let idx = 0;
    function opciones() { return ELEMENTOS.map(e => `<option value="${e.id}">${e.codigo} · ${e.descripcion}${e.presentacion ? ' ('+e.presentacion+')' : ''}</option>`).join(''); }
    function agregarFila() {
        const i = idx++;
        const tr = document.createElement('tr');
        tr.className = 'border-b border-gray-100';
        tr.innerHTML = `
            <td class="px-3 py-2"><select name="items[${i}][elemento_id]" required class="w-full border border-gray-200 rounded px-2 py-1"><option value="">— Elemento —</option>${opciones()}</select></td>
            <td class="px-3 py-2 text-right"><input type="number" min="1" value="1" name="items[${i}][cantidad]" class="w-24 border border-gray-200 rounded px-2 py-1 text-right"></td>
            <td class="px-3 py-2 text-center"><button type="button" onclick="this.closest('tr').remove()" class="text-red-500 hover:text-red-700">✕</button></td>`;
        document.getElementById('items').appendChild(tr);
    }
    agregarFila();
</script>
@endsection
