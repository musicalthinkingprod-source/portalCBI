@extends('layouts.app-sidebar')

@section('header', 'Inventario · Nueva venta')

@section('slot')

{{-- Aviso flotante de confirmación de escaneo --}}
<div id="toast" class="hidden fixed top-6 left-1/2 -translate-x-1/2 z-[3000] px-6 py-4 rounded-xl shadow-2xl text-white text-lg font-bold pointer-events-none"></div>
<style>
    @keyframes flashok { 0% { background-color: #bbf7d0; } 100% { background-color: transparent; } }
    .flash-ok { animation: flashok 1.2s ease-out; }
</style>

@if($errors->any())
<div class="mb-5 bg-red-50 border border-red-300 text-red-800 px-4 py-3 rounded-lg text-sm">{{ $errors->first() }}</div>
@endif

<form method="POST" action="{{ route('inventario.ventas.store') }}" id="form-venta">
    @csrf
    <input type="hidden" name="tipo" id="tipo" value="venta">

    <div class="grid lg:grid-cols-3 gap-6">

        {{-- Columna izquierda: destinatario + escáner --}}
        <div class="space-y-6">

            {{-- Tipo de movimiento --}}
            <div class="bg-white rounded-xl shadow p-5">
                <div class="flex gap-2 mb-4">
                    <button type="button" id="btn-venta" onclick="setTipo('venta')" class="flex-1 py-2 rounded-lg text-sm font-semibold bg-blue-600 text-white">Venta a estudiante</button>
                    <button type="button" id="btn-dotacion" onclick="setTipo('dotacion')" class="flex-1 py-2 rounded-lg text-sm font-semibold bg-gray-100 text-gray-600">Dotación a docente</button>
                </div>

                {{-- Destinatario: estudiante --}}
                <div id="box-estudiante">
                    <label class="block text-xs text-gray-500 mb-1">Código del estudiante</label>
                    <div class="flex gap-2">
                        <input type="number" id="estudiante_codigo_input" class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="Ej: 25039">
                        <button type="button" onclick="buscarEstudiante()" class="bg-gray-700 text-white px-3 rounded-lg text-sm">Buscar</button>
                    </div>
                    <input type="hidden" name="estudiante_codigo" id="estudiante_codigo">
                    <p id="estudiante_nombre" class="mt-2 text-sm text-gray-700 font-medium"></p>
                </div>

                {{-- Destinatario: docente --}}
                <div id="box-docente" class="hidden">
                    <label class="block text-xs text-gray-500 mb-1">Docente (Ed. Física)</label>
                    <select name="empleado_id" id="empleado_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">— Seleccionar —</option>
                        @foreach($docentes as $d)
                        <option value="{{ $d->id }}">{{ $d->nombre }}@if($d->cargo) · {{ $d->cargo }}@endif</option>
                        @endforeach
                    </select>
                    <p class="mt-2 text-xs text-purple-700">La dotación no tiene costo (total $0).</p>
                </div>
            </div>

            {{-- Escáner por cámara --}}
            <div class="bg-white rounded-xl shadow p-5">
                <div class="flex items-center justify-between mb-2">
                    <label class="block text-xs text-gray-500">📷 Escáner por cámara</label>
                    <button type="button" id="btn-cam" onclick="toggleCam()" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded-lg text-xs font-semibold">Encender cámara</button>
                </div>

                <div id="cam-wrap" class="hidden">
                    <video id="cam" class="w-full rounded-lg bg-black aspect-video" muted playsinline></video>
                    <div class="mt-2 flex items-center gap-2">
                        <select id="cam-select" onchange="cambiarCamara()" class="flex-1 border border-gray-300 rounded-lg px-2 py-1.5 text-xs"></select>
                    </div>
                </div>

                <p id="scan-msg" class="mt-2 text-sm"></p>

                {{-- Respaldo manual --}}
                <label class="block text-xs text-gray-500 mt-3 mb-1">O digita / usa lector USB</label>
                <input type="text" id="scan" autocomplete="off" class="w-full border border-gray-300 focus:border-blue-500 rounded-lg px-3 py-2 text-base font-mono" placeholder="Código…">
                <p class="mt-1 text-xs text-gray-400">La cámara necesita permiso del navegador y conexión segura (HTTPS o localhost).</p>
            </div>

            <div>
                <label class="block text-xs text-gray-500 mb-1">Fecha</label>
                <input type="date" name="fecha" value="{{ date('Y-m-d') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
        </div>

        {{-- Columna derecha: recibo --}}
        <div class="lg:col-span-2 bg-white rounded-xl shadow flex flex-col">
            <div class="px-5 py-3 border-b border-gray-100">
                <h3 class="font-semibold text-gray-700">Recibo</h3>
            </div>
            <div class="overflow-x-auto flex-1">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                        <tr>
                            <th class="px-3 py-2 text-left">Código</th>
                            <th class="px-3 py-2 text-left">Producto</th>
                            <th class="px-3 py-2 text-right w-24">Cant.</th>
                            <th class="px-3 py-2 text-right w-32 col-precio">Precio</th>
                            <th class="px-3 py-2 text-right w-32 col-precio">Subtotal</th>
                            <th class="px-3 py-2 w-10"></th>
                        </tr>
                    </thead>
                    <tbody id="items">
                        <tr id="vacio"><td colspan="6" class="px-4 py-8 text-center text-gray-400">Escanea o digita un código para agregar productos.</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="border-t border-gray-100 px-5 py-4 space-y-2">
                <div class="flex justify-end items-center gap-3 col-precio">
                    <span class="text-sm text-gray-500">Subtotal</span>
                    <span id="subtotal" class="w-32 text-right font-semibold text-gray-700">$0</span>
                </div>
                <div class="flex justify-end items-center gap-3 col-precio">
                    <span class="text-sm text-gray-500">Descuento (automático)</span>
                    <span id="descuento-lbl" class="w-32 text-right text-emerald-600 font-medium">- $0</span>
                </div>
                <div class="flex justify-end items-center gap-3">
                    <span class="text-base font-semibold text-gray-700">TOTAL</span>
                    <span id="total" class="w-32 text-right text-xl font-bold text-gray-900">$0</span>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-5 flex gap-3">
        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2.5 rounded-lg text-sm font-semibold">Guardar</button>
        <a href="{{ route('inventario.ventas') }}" class="px-5 py-2.5 rounded-lg text-sm border border-gray-300 text-gray-600 hover:bg-gray-50">Cancelar</a>
    </div>
</form>

<script src="https://unpkg.com/@zxing/library@0.20.0/umd/index.min.js"></script>
<script>
    const API_PROD = "{{ route('inventario.api.producto') }}";
    const API_EST  = "{{ route('inventario.api.estudiante') }}";
    const METAS    = @json($metasCompleto);   // talla => precio meta del uniforme completo

    // Clasifica una prenda por su nombre: {tipo, talla}.
    function clasificar(nombre) {
        let n = nombre.toLowerCase()
            .replace(/á/g,'a').replace(/é/g,'e').replace(/í/g,'i').replace(/ó/g,'o').replace(/ú/g,'u').replace(/ñ/g,'n');
        const mt = n.match(/t\s*-?\s*(xl|\d+|s|m|l)\s*$/);
        const talla = mt ? mt[1].toUpperCase() : null;
        let tipo = null;
        if (n.startsWith('chaqueta sudadera'))      tipo = 'chaqueta';
        else if (n.startsWith('pantalon sudadera')) tipo = 'pantalon';
        else if (n.startsWith('camiseta'))          tipo = 'camiseta';
        else if (n.startsWith('pantaloneta'))       tipo = 'pantaloneta';
        return { tipo, talla };
    }

    // Descuento por uniforme completo (réplica de la lógica del servidor).
    function calcularDescuento() {
        const req = ['chaqueta','pantalon','camiseta','pantaloneta'];
        const porTalla = {};
        Object.values(carrito).forEach(it => {
            const { tipo, talla } = clasificar(it.nombre);
            if (!tipo || !talla) return;
            (porTalla[talla] = porTalla[talla] || {})[tipo] = { precio: it.precio, cant: it.cantidad };
        });
        let desc = 0;
        for (const talla in porTalla) {
            const g = porTalla[talla];
            if (!req.every(t => g[t]) || !(talla in METAS)) continue;
            const conjuntos = Math.min(g.chaqueta.cant, g.pantalon.cant, g.camiseta.cant, g.pantaloneta.cant);
            const suma = g.chaqueta.precio + g.pantalon.precio + g.camiseta.precio + g.pantaloneta.precio;
            const porConj = suma - Number(METAS[talla]);
            if (conjuntos >= 1 && porConj > 0) desc += conjuntos * porConj;
        }
        return desc;
    }
    let tipo = 'venta';
    let idx = 0;
    const carrito = {};   // producto_id => {datos, cantidad}

    // ── Tipo de movimiento ──
    function setTipo(t) {
        tipo = t;
        document.getElementById('tipo').value = t;
        const venta = t === 'venta';
        document.getElementById('btn-venta').className    = 'flex-1 py-2 rounded-lg text-sm font-semibold ' + (venta ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-600');
        document.getElementById('btn-dotacion').className = 'flex-1 py-2 rounded-lg text-sm font-semibold ' + (!venta ? 'bg-purple-600 text-white' : 'bg-gray-100 text-gray-600');
        document.getElementById('box-estudiante').classList.toggle('hidden', !venta);
        document.getElementById('box-docente').classList.toggle('hidden', venta);
        // En dotación se ocultan precios (sin costo).
        document.querySelectorAll('.col-precio').forEach(e => e.classList.toggle('hidden', !venta));
        render();
    }

    // ── Buscar estudiante por código ──
    async function buscarEstudiante() {
        const cod = document.getElementById('estudiante_codigo_input').value.trim();
        const el = document.getElementById('estudiante_nombre');
        if (!cod) return;
        const r = await fetch(`${API_EST}?codigo=${encodeURIComponent(cod)}`);
        if (!r.ok) { el.textContent = '⚠ Estudiante no encontrado'; el.className = 'mt-2 text-sm text-red-600 font-medium'; document.getElementById('estudiante_codigo').value = ''; return; }
        const e = await r.json();
        document.getElementById('estudiante_codigo').value = e.codigo;
        el.textContent = `✓ ${e.nombre}` + (e.grado ? ` (${e.grado})` : '');
        el.className = 'mt-2 text-sm text-green-700 font-medium';
    }
    document.getElementById('estudiante_codigo_input').addEventListener('keydown', e => {
        if (e.key === 'Enter') { e.preventDefault(); buscarEstudiante(); }
    });

    // ── Escáner ──
    const scan = document.getElementById('scan');
    scan.addEventListener('keydown', async e => {
        if (e.key !== 'Enter') return;
        e.preventDefault();
        const cod = scan.value.trim();
        scan.value = '';
        if (!cod) return;
        await agregarPorCodigo(cod);
    });

    // ── Feedback de escaneo: sonido + aviso visual ──
    let audioCtx = null;
    function beep(ok) {
        try {
            audioCtx = audioCtx || new (window.AudioContext || window.webkitAudioContext)();
            if (audioCtx.state === 'suspended') audioCtx.resume();
            const o = audioCtx.createOscillator(), g = audioCtx.createGain();
            o.connect(g); g.connect(audioCtx.destination);
            o.type = 'square';
            o.frequency.value = ok ? 880 : 200;
            g.gain.value = 0.12;
            o.start();
            o.stop(audioCtx.currentTime + (ok ? 0.12 : 0.35));
        } catch (e) {}
    }
    let toastT = null;
    function toast(texto, ok) {
        const t = document.getElementById('toast');
        t.textContent = texto;
        t.className = 'fixed top-6 left-1/2 -translate-x-1/2 z-[3000] px-6 py-4 rounded-xl shadow-2xl text-white text-lg font-bold pointer-events-none ' + (ok ? 'bg-green-600' : 'bg-red-600');
        clearTimeout(toastT);
        toastT = setTimeout(() => t.classList.add('hidden'), 1600);
    }
    function resaltarFila(id) {
        const fila = document.querySelector(`#items tr[data-pid="${id}"]`);
        if (fila) { fila.classList.add('flash-ok'); setTimeout(() => fila.classList.remove('flash-ok'), 1200); }
    }

    async function agregarPorCodigo(cod) {
        const msg = document.getElementById('scan-msg');
        const r = await fetch(`${API_PROD}?codigo=${encodeURIComponent(cod)}`);
        if (!r.ok) {
            msg.textContent = `⚠ Código ${cod} no encontrado`; msg.className = 'mt-2 text-sm text-red-600';
            beep(false); toast(`✗ Código ${cod} no encontrado`, false);
            return;
        }
        const p = await r.json();
        if (carrito[p.id]) {
            carrito[p.id].cantidad++;
        } else {
            carrito[p.id] = { id: p.id, codigo: p.codigo, nombre: p.nombre, precio: Number(p.precio_venta), stock: p.stock, cantidad: 1 };
        }
        const cant = carrito[p.id].cantidad;
        msg.textContent = `✓ ${p.nombre} — cantidad: ${cant} (stock ${p.stock})`;
        msg.className = 'mt-2 text-sm text-green-700 font-semibold';
        beep(true);
        toast(`✓ ${p.nombre}  ×${cant}`, true);
        render();
        resaltarFila(p.id);
    }

    // ── Render del carrito ──
    function render() {
        const tbody = document.getElementById('items');
        const ids = Object.keys(carrito);
        if (ids.length === 0) {
            tbody.innerHTML = '<tr id="vacio"><td colspan="6" class="px-4 py-8 text-center text-gray-400">Escanea o digita un código para agregar productos.</td></tr>';
            calc(); return;
        }
        const venta = tipo === 'venta';
        let i = 0, html = '';
        ids.forEach(id => {
            const it = carrito[id];
            const precio = venta ? it.precio : 0;
            html += `<tr class="border-b border-gray-100" data-pid="${it.id}">
                <td class="px-3 py-2 font-mono text-gray-600">${it.codigo}
                    <input type="hidden" name="items[${i}][producto_id]" value="${it.id}"></td>
                <td class="px-3 py-2 text-gray-800">${it.nombre}</td>
                <td class="px-3 py-2 text-right">
                    <input type="number" min="1" value="${it.cantidad}" onchange="setCant(${it.id}, this.value)" name="items[${i}][cantidad]" class="w-20 border border-gray-200 rounded px-2 py-1 text-right">
                </td>
                <td class="px-3 py-2 text-right col-precio text-gray-700">$${precio.toLocaleString()}</td>
                <td class="px-3 py-2 text-right col-precio text-gray-700 sub">$${(precio * it.cantidad).toLocaleString()}</td>
                <td class="px-3 py-2 text-center"><button type="button" onclick="quitar(${it.id})" class="text-red-500 hover:text-red-700">✕</button></td>
            </tr>`;
            i++;
        });
        tbody.innerHTML = html;
        document.querySelectorAll('.col-precio').forEach(e => e.classList.toggle('hidden', !venta));
        calc();
    }

    function setCant(id, val) { carrito[id].cantidad = Math.max(1, parseInt(val) || 1); render(); }
    function quitar(id) { delete carrito[id]; render(); }

    function calc() {
        const venta = tipo === 'venta';
        let subtotal = 0;
        // El precio es fijo (viene del producto); el cálculo usa el carrito.
        Object.keys(carrito).forEach(id => {
            if (venta) subtotal += carrito[id].precio * carrito[id].cantidad;
        });
        // Descuento por uniforme completo (mismo cálculo que el servidor).
        const desc = venta ? calcularDescuento() : 0;
        const total = Math.max(0, subtotal - desc);
        document.getElementById('subtotal').textContent = '$' + subtotal.toLocaleString();
        document.getElementById('descuento-lbl').textContent = '- $' + desc.toLocaleString();
        document.getElementById('total').textContent = '$' + (venta ? total : 0).toLocaleString();
    }

    // Validación mínima antes de enviar
    document.getElementById('form-venta').addEventListener('submit', e => {
        if (Object.keys(carrito).length === 0) { e.preventDefault(); alert('Agrega al menos un producto.'); return; }
        if (tipo === 'venta' && !document.getElementById('estudiante_codigo').value) { e.preventDefault(); alert('Busca y selecciona el estudiante.'); return; }
        if (tipo === 'dotacion' && !document.getElementById('empleado_id').value) { e.preventDefault(); alert('Selecciona el docente.'); return; }
    });

    // ── Escáner por cámara (ZXing) ──
    let codeReader = null;
    let camActiva = false;
    let ultimoCodigo = '';
    let ultimoMomento = 0;

    async function toggleCam() {
        if (camActiva) { detenerCam(); return; }
        const msg = document.getElementById('scan-msg');
        if (typeof ZXing === 'undefined') { msg.textContent = '⚠ No se pudo cargar la librería de escaneo (revisa la conexión).'; msg.className = 'mt-2 text-sm text-red-600'; return; }
        try {
            codeReader = new ZXing.BrowserMultiFormatReader();
            const dispositivos = await codeReader.listVideoInputDevices();
            if (!dispositivos.length) { msg.textContent = '⚠ No se detectó ninguna cámara.'; msg.className = 'mt-2 text-sm text-red-600'; return; }

            const sel = document.getElementById('cam-select');
            sel.innerHTML = dispositivos.map((d, i) => `<option value="${d.deviceId}">${d.label || ('Cámara ' + (i + 1))}</option>`).join('');
            // Preferir cámara trasera si el nombre lo indica.
            const trasera = dispositivos.find(d => /back|trasera|rear|environment/i.test(d.label));
            const deviceId = trasera ? trasera.deviceId : dispositivos[dispositivos.length - 1].deviceId;
            sel.value = deviceId;

            document.getElementById('cam-wrap').classList.remove('hidden');
            document.getElementById('btn-cam').textContent = 'Apagar cámara';
            document.getElementById('btn-cam').className = 'bg-red-600 hover:bg-red-700 text-white px-3 py-1.5 rounded-lg text-xs font-semibold';
            camActiva = true;
            iniciarLectura(deviceId);
            msg.textContent = 'Apunta la cámara al código de barras…';
            msg.className = 'mt-2 text-sm text-gray-500';
        } catch (e) {
            msg.textContent = '⚠ No se pudo acceder a la cámara: ' + (e.message || e);
            msg.className = 'mt-2 text-sm text-red-600';
        }
    }

    function iniciarLectura(deviceId) {
        codeReader.decodeFromVideoDevice(deviceId, 'cam', (result, err) => {
            if (!result) return;
            const cod = result.getText().trim();
            const ahora = Date.now();
            // Evitar lecturas repetidas del mismo código en ráfaga.
            if (cod === ultimoCodigo && (ahora - ultimoMomento) < 1500) return;
            ultimoCodigo = cod; ultimoMomento = ahora;
            agregarPorCodigo(cod);
        });
    }

    function cambiarCamara() {
        if (!camActiva || !codeReader) return;
        codeReader.reset();
        iniciarLectura(document.getElementById('cam-select').value);
    }

    function detenerCam() {
        if (codeReader) { codeReader.reset(); codeReader = null; }
        camActiva = false;
        document.getElementById('cam-wrap').classList.add('hidden');
        document.getElementById('btn-cam').textContent = 'Encender cámara';
        document.getElementById('btn-cam').className = 'bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded-lg text-xs font-semibold';
    }

    // Liberar la cámara al salir de la página.
    window.addEventListener('beforeunload', () => { if (codeReader) codeReader.reset(); });

    scan.focus();
</script>
@endsection
