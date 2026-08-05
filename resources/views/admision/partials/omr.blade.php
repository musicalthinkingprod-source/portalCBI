{{-- ── Lector OMR (hoja escaneada → auto-relleno de la planilla) ──────────── --}}
<div class="bg-white rounded-xl shadow p-5 mb-6">
    <div class="flex items-center justify-between mb-1">
        <h2 class="text-base font-semibold text-gray-800">Lectura automática de hoja escaneada (OMR)</h2>
        <a href="{{ route('admision.hoja', ['grado' => $key]) }}" target="_blank"
           class="text-xs text-blue-700 hover:underline">🖨️ Imprimir hoja en blanco</a>
    </div>
    <p class="text-xs text-gray-500 mb-3">
        Sube el <b>PDF o imagen</b> escaneado de la hoja de respuestas del aspirante. El sistema detecta las marcas
        y rellena la planilla de abajo. <b>Revisa siempre</b> el resultado antes de guardar.
    </p>

    <div class="flex flex-wrap items-center gap-3">
        <input type="file" id="omr-file" accept="application/pdf,image/*"
               class="text-sm file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-blue-800 file:text-white file:cursor-pointer">
        <span id="omr-status" class="text-sm text-gray-500"></span>
    </div>

    {{-- Vista previa + resumen (ocultos hasta procesar) --}}
    <div id="omr-result" class="mt-4 hidden">
        <div class="flex flex-wrap gap-4 items-start">
            <canvas id="omr-canvas" class="border border-gray-200 rounded-lg max-w-[240px] h-auto"></canvas>
            <div class="flex-1 min-w-[220px]">
                <div class="grid grid-cols-3 gap-2 mb-2">
                    <div class="bg-green-50 rounded-lg p-2 text-center"><div id="omr-marcadas" class="text-lg font-bold text-green-700">0</div><div class="text-[10px] text-gray-500 uppercase">Detectadas</div></div>
                    <div class="bg-amber-50 rounded-lg p-2 text-center"><div id="omr-dudosas" class="text-lg font-bold text-amber-700">0</div><div class="text-[10px] text-gray-500 uppercase">Dudosas</div></div>
                    <div class="bg-gray-50 rounded-lg p-2 text-center"><div id="omr-blancas" class="text-lg font-bold text-gray-600">0</div><div class="text-[10px] text-gray-500 uppercase">En blanco</div></div>
                </div>
                <p id="omr-dudosas-list" class="text-xs text-amber-700"></p>
                <p class="text-[11px] text-gray-400 mt-2">Las preguntas dudosas se resaltaron en la planilla para tu revisión.</p>
            </div>
        </div>
    </div>
</div>

@once
@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
@endpush
@endonce

@push('scripts')
<script>
(function () {
    const LAYOUT = @json($omr);
    const fileInput = document.getElementById('omr-file');
    const statusEl  = document.getElementById('omr-status');
    if (!fileInput) return;

    const DARK_T = 140;      // umbral de gris para "pixel oscuro"
    const MARK_MIN = 0.28;   // fracción mínima de disco lleno para contar como marca
    const AMBIG = 0.14;      // diferencia mínima entre 1ª y 2ª opción para no ser "dudosa"

    fileInput.addEventListener('change', async function () {
        const file = this.files[0];
        if (!file) return;
        statusEl.textContent = 'Procesando…';
        try {
            const canvas = await fileToCanvas(file);
            procesar(canvas);
        } catch (e) {
            console.error(e);
            statusEl.textContent = '⚠️ No se pudo leer el archivo: ' + e.message;
        }
    });

    async function fileToCanvas(file) {
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d', { willReadFrequently: true });
        if (file.type === 'application/pdf') {
            if (!window.pdfjsLib) throw new Error('No se cargó pdf.js (¿sin conexión?)');
            pdfjsLib.GlobalWorkerOptions.workerSrc =
                'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
            const buf = await file.arrayBuffer();
            const pdf = await pdfjsLib.getDocument({ data: buf }).promise;
            const page = await pdf.getPage(1);
            const base = page.getViewport({ scale: 1 });
            const scale = Math.min(2200 / base.width, 3.0); // resolución de trabajo
            const vp = page.getViewport({ scale });
            canvas.width = vp.width; canvas.height = vp.height;
            await page.render({ canvasContext: ctx, viewport: vp }).promise;
        } else {
            const img = await loadImage(URL.createObjectURL(file));
            const scale = Math.min(2200 / img.width, 3.0);
            canvas.width = Math.round(img.width * scale);
            canvas.height = Math.round(img.height * scale);
            ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
        }
        return canvas;
    }

    function loadImage(src) {
        return new Promise((res, rej) => {
            const img = new Image();
            img.onload = () => res(img); img.onerror = rej; img.src = src;
        });
    }

    function procesar(canvas) {
        const W = canvas.width, H = canvas.height;
        const ctx = canvas.getContext('2d', { willReadFrequently: true });
        const data = ctx.getImageData(0, 0, W, H).data;
        const gray = (x, y) => {
            const i = (y * W + x) * 4;
            return (data[i] * 0.299 + data[i + 1] * 0.587 + data[i + 2] * 0.114);
        };

        // 1) Detectar los 4 fiduciales = centroide de píxeles oscuros en cada esquina
        const fid = {
            tl: centroid(gray, W, H, 0, 0, 0.14, 0.10),
            tr: centroid(gray, W, H, 0.86, 0, 0.14, 0.10),
            bl: centroid(gray, W, H, 0, 0.90, 0.14, 0.10),
            br: centroid(gray, W, H, 0.86, 0.90, 0.14, 0.10),
        };
        for (const k in fid) if (!fid[k]) { statusEl.textContent = '⚠️ No se detectaron las 4 marcas de esquina. Verifica el escaneo.'; return; }

        // Referencia en mm de los fiduciales del layout
        const F = LAYOUT.fid;
        const x0 = F.tl[0], x1 = F.tr[0], y0 = F.tl[1], y1 = F.bl[1];

        // Mapeo bilineal mm → pixel usando las 4 esquinas detectadas
        function mmToPx(mx, my) {
            const u = (mx - x0) / (x1 - x0);
            const v = (my - y0) / (y1 - y0);
            const px = (1 - u) * (1 - v) * fid.tl.x + u * (1 - v) * fid.tr.x + (1 - u) * v * fid.bl.x + u * v * fid.br.x;
            const py = (1 - u) * (1 - v) * fid.tl.y + u * (1 - v) * fid.tr.y + (1 - u) * v * fid.bl.y + u * v * fid.br.y;
            return [px, py];
        }

        // px por mm (para el radio de muestreo)
        const dxpx = Math.hypot(fid.tr.x - fid.tl.x, fid.tr.y - fid.tl.y);
        const pxPerMm = dxpx / (x1 - x0);
        const sampleR = Math.max(3, LAYOUT.bubbleR * pxPerMm * 0.75);

        // 2) Medir oscuridad de cada burbuja
        const fill = {}; // n -> {A,B,C,D}
        for (const b of LAYOUT.bubbles) {
            const [px, py] = mmToPx(b.x, b.y);
            (fill[b.n] = fill[b.n] || {})[b.opt] = darkFraction(gray, W, H, px, py, sampleR);
        }

        // 3) Decidir respuesta por pregunta
        const detectadas = [], dudosas = [], blancas = [];
        const resultado = {};
        for (const n in fill) {
            const ops = fill[n];
            const orden = Object.entries(ops).sort((a, b) => b[1] - a[1]);
            const [op1, v1] = orden[0], v2 = orden[1] ? orden[1][1] : 0;
            if (v1 < MARK_MIN) { blancas.push(+n); continue; }
            resultado[n] = op1;
            if (v1 - v2 < AMBIG) dudosas.push(+n);
            else detectadas.push(+n);
        }

        aplicar(resultado, dudosas);
        pintarPreview(canvas, mmToPx, resultado, dudosas);
        resumen(detectadas.length, dudosas, blancas.length);
        statusEl.textContent = '✅ Lectura completada. Revisa y ajusta lo necesario.';
    }

    // Centroide de píxeles oscuros dentro de una ventana relativa (rx,ry, rw,rh en fracción)
    function centroid(gray, W, H, rx, ry, rw, rh) {
        const x0 = Math.floor(rx * W), y0 = Math.floor(ry * H);
        const x1 = Math.floor((rx + rw) * W), y1 = Math.floor((ry + rh) * H);
        let sx = 0, sy = 0, n = 0;
        for (let y = y0; y < y1; y += 2)
            for (let x = x0; x < x1; x += 2)
                if (gray(x, y) < DARK_T) { sx += x; sy += y; n++; }
        if (n < 20) return null;
        return { x: sx / n, y: sy / n };
    }

    // Fracción de píxeles oscuros dentro de un disco
    function darkFraction(gray, W, H, cx, cy, r) {
        let dark = 0, tot = 0;
        const r2 = r * r, step = Math.max(1, Math.round(r / 6));
        for (let dy = -r; dy <= r; dy += step)
            for (let dx = -r; dx <= r; dx += step) {
                if (dx * dx + dy * dy > r2) continue;
                const x = Math.round(cx + dx), y = Math.round(cy + dy);
                if (x < 0 || y < 0 || x >= W || y >= H) continue;
                tot++; if (gray(x, y) < DARK_T) dark++;
            }
        return tot ? dark / tot : 0;
    }

    // Marca los radios del formulario y resalta las preguntas dudosas
    function aplicar(resultado, dudosas) {
        // limpiar resaltados previos
        document.querySelectorAll('tr.omr-dudosa').forEach(tr => tr.classList.remove('omr-dudosa', 'bg-amber-50'));
        for (const n in resultado) {
            const radio = document.querySelector(`input[name="respuestas[${n}]"][value="${resultado[n]}"]`);
            if (radio) { radio.checked = true; }
        }
        dudosas.forEach(n => {
            const radio = document.querySelector(`input[name="respuestas[${n}]"]`);
            const tr = radio && radio.closest('tr');
            if (tr) tr.classList.add('omr-dudosa', 'bg-amber-50');
        });
        // refrescar contador de progreso del formulario
        if (window.admisionRefreshProgreso) window.admisionRefreshProgreso();
    }

    function pintarPreview(src, mmToPx, resultado, dudosas) {
        const out = document.getElementById('omr-canvas');
        const maxW = 240, scale = maxW / src.width;
        out.width = maxW; out.height = src.height * scale;
        const c = out.getContext('2d');
        c.drawImage(src, 0, 0, out.width, out.height);
        const dud = new Set(dudosas);
        for (const b of LAYOUT.bubbles) {
            if (resultado[b.n] !== b.opt) continue;
            const [px, py] = mmToPx(b.x, b.y);
            c.beginPath();
            c.arc(px * scale, py * scale, 4, 0, 6.283);
            c.lineWidth = 1.5;
            c.strokeStyle = dud.has(b.n) ? '#d97706' : '#16a34a';
            c.stroke();
        }
        document.getElementById('omr-result').classList.remove('hidden');
    }

    function resumen(nMarc, dudosas, nBlanc) {
        document.getElementById('omr-marcadas').textContent = nMarc + dudosas.length;
        document.getElementById('omr-dudosas').textContent = dudosas.length;
        document.getElementById('omr-blancas').textContent = nBlanc;
        const list = document.getElementById('omr-dudosas-list');
        list.textContent = dudosas.length
            ? '⚠️ Revisa las preguntas: ' + dudosas.sort((a, b) => a - b).join(', ')
            : '';
    }
})();
</script>
@endpush
