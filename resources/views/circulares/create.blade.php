@extends('layouts.app-sidebar')

@section('header', 'Nueva Circular — ' . $numero)

@section('slot')

@php $esSuperAd = auth()->user()->PROFILE === 'SuperAd'; @endphp

<form method="POST" action="{{ route('circulares.store') }}">
    @csrf

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Columna izquierda: datos generales --}}
        <div class="lg:col-span-1 space-y-4">
            <div class="bg-white rounded-xl shadow p-5 space-y-4">
                <h2 class="text-sm font-semibold text-gray-700 border-b pb-2">Datos de la circular</h2>

                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Número <span class="text-red-500">*</span></label>
                    <input type="text" name="numero" value="{{ old('numero', $numero) }}"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-mono text-blue-800 @error('numero') border-red-400 @enderror">
                    @error('numero') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Fecha <span class="text-red-500">*</span></label>
                    <input type="date" name="fecha" value="{{ old('fecha', date('Y-m-d')) }}"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm @error('fecha') border-red-400 @enderror">
                    @error('fecha') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Asunto <span class="text-red-500">*</span></label>
                    <input type="text" name="asunto" value="{{ old('asunto') }}" placeholder="Ej: Jornada pedagógica"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm @error('asunto') border-red-400 @enderror">
                    @error('asunto') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Dirigido a <span class="text-red-500">*</span></label>
                    <input type="text" name="dirigido_a" value="{{ old('dirigido_a') }}" placeholder="Ej: Padres de familia"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm @error('dirigido_a') border-red-400 @enderror">
                    @error('dirigido_a') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Firmado por <span class="text-red-500">*</span></label>
                    @if($esSuperAd)
                        <input type="text" name="emitido_por" value="{{ old('emitido_por', 'Luz Ángela Vega Buenahora') }}"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm @error('emitido_por') border-red-400 @enderror">
                    @else
                        <input type="hidden" name="emitido_por" value="Luz Ángela Vega Buenahora">
                        <p class="px-3 py-2 text-sm bg-gray-50 border border-gray-200 rounded-lg text-gray-700">Luz Ángela Vega Buenahora</p>
                    @endif
                    @error('emitido_por') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Cargo del firmante</label>
                    @if($esSuperAd)
                        <input type="text" name="cargo" value="{{ old('cargo', 'Directora General') }}"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm @error('cargo') border-red-400 @enderror">
                    @else
                        <input type="hidden" name="cargo" value="Directora General">
                        <p class="px-3 py-2 text-sm bg-gray-50 border border-gray-200 rounded-lg text-gray-700">Directora General</p>
                    @endif
                    @error('cargo') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Estado</label>
                    @if($esSuperAd)
                        <select name="estado" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option value="borrador" @selected(old('estado', 'borrador') === 'borrador')>Borrador</option>
                            <option value="publicada" @selected(old('estado') === 'publicada')>Publicada</option>
                        </select>
                    @else
                        <input type="hidden" name="estado" value="borrador">
                        <p class="px-3 py-2 text-sm bg-yellow-50 border border-yellow-200 rounded-lg text-yellow-700 text-xs">
                            🔒 Solo la rectora puede publicar circulares
                        </p>
                    @endif
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Enlace (Google Drive)</label>
                    <input type="url" name="link" value="{{ old('link') }}" placeholder="https://drive.google.com/..."
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm @error('link') border-red-400 @enderror">
                    @error('link') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Visible para</label>
                    <p class="text-xs text-gray-400 mb-2">Sin selección = todos los grados</p>
                    @php
                        $todosGrados = [
                            'Preescolar' => ['PJ' => 'PreJardín', 'J' => 'Jardín', 'T' => 'Transición'],
                            'Primaria'   => ['1' => '1°', '2' => '2°', '3' => '3°', '4' => '4°', '5' => '5°'],
                            'Bachillerato' => ['6' => '6°', '7' => '7°', '8' => '8°', '9' => '9°', '10' => '10°', '11' => '11°'],
                        ];
                        $selGrados = old('grados', []);
                    @endphp
                    @foreach($todosGrados as $nivel => $opciones)
                    <p class="text-xs text-gray-400 mt-2 mb-1">{{ $nivel }}</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach($opciones as $val => $etiqueta)
                        <label class="flex items-center gap-1.5 cursor-pointer">
                            <input type="checkbox" name="grados[]" value="{{ $val }}"
                                {{ in_array((string)$val, array_map('strval', $selGrados)) ? 'checked' : '' }}
                                class="rounded border-gray-300 text-blue-700">
                            <span class="text-sm text-gray-700">{{ $etiqueta }}</span>
                        </label>
                        @endforeach
                    </div>
                    @endforeach
                    @error('grados') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="flex gap-3">
                <button type="submit"
                    class="flex-1 bg-blue-800 hover:bg-blue-700 text-white py-2 rounded-lg text-sm font-semibold transition">
                    Guardar
                </button>
                <a href="{{ route('circulares.index') }}"
                    class="flex-1 text-center border border-gray-300 text-gray-600 py-2 rounded-lg text-sm hover:bg-gray-50 transition">
                    Cancelar
                </a>
            </div>
        </div>

        {{-- Columna derecha: editor --}}
        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl shadow p-5">
                <h2 class="text-sm font-semibold text-gray-700 border-b pb-2 mb-3">Contenido de la circular <span class="text-xs font-normal text-gray-400">(opcional si hay enlace a Drive)</span></h2>

                <details class="mb-3 text-xs text-gray-600 bg-blue-50 border border-blue-200 rounded-lg p-3">
                    <summary class="cursor-pointer font-semibold text-blue-800">¿Cómo insertar imágenes, documentos o videos?</summary>
                    <div class="mt-2 space-y-2">
                        <ol class="list-decimal ml-5 space-y-2">
                            <li>
                                <b>Imagen (recomendado):</b> usa el botón <b>🖼️ Insertar imagen</b> del editor o simplemente <b>arrastra la imagen</b> al contenido.
                                <p class="mt-1 text-[11px] text-blue-800">La imagen se comprime automáticamente (máx. 1200 px, ~300 KB) y queda incrustada en la circular y en el PDF. El peso total del contenido no debe pasar los 2 MB.</p>
                            </li>
                            <li>
                                <b>Documento (PDF, Word, etc.) desde Drive:</b> usa <b>Insertar HTML</b> (&lt;/&gt;) con:
                                <pre class="bg-white border border-blue-100 rounded p-2 mt-1 text-[11px] overflow-x-auto">&lt;iframe src="https://drive.google.com/file/d/ID_ARCHIVO/preview" width="640" height="480"&gt;&lt;/iframe&gt;</pre>
                                <p class="mt-1 text-[11px] text-amber-700">⚠️ Los iframes solo se ven en el portal; en el PDF aparecen como un enlace "Ver en Drive".</p>
                            </li>
                            <li><b>Video de YouTube/Vimeo:</b> usa el botón <b>Insertar medio</b> y pega la URL. (Solo se ve en el portal, no en el PDF.)</li>
                        </ol>
                    </div>
                </details>

                @error('contenido') <p class="text-red-500 text-xs mb-2">{{ $message }}</p> @enderror

                <div id="editor" style="min-height: 420px;">{!! old('contenido') !!}</div>
                <input type="hidden" name="contenido" id="contenido-input">

                <div id="peso-barra" class="mt-3 text-xs flex items-center gap-3">
                    <div class="flex-1 h-2 bg-gray-200 rounded overflow-hidden">
                        <div id="peso-progreso" class="h-full bg-blue-600 transition-all" style="width:0%"></div>
                    </div>
                    <span id="peso-texto" class="text-gray-600 font-mono whitespace-nowrap">0 KB / 2 MB</span>
                </div>
                <p id="peso-alerta" class="hidden mt-1 text-xs text-red-600">⚠️ El contenido supera los 2 MB. Elimina o reemplaza imágenes antes de guardar.</p>
            </div>
        </div>

    </div>
</form>

<script src="https://cdn.ckeditor.com/ckeditor5/41.4.2/super-build/ckeditor.js"></script>
<style>
    #editor { min-height: 420px; border: 1px solid #e5e7eb; border-radius: 6px; padding: 8px; }
    .ck-editor__editable { min-height: 420px !important; }
    .ck-content { font-family: Arial, Helvetica, sans-serif; font-size: 13.5px; line-height: 1.7; }
    .ck-content img { max-width: 100%; height: auto; }
    .ck-content table { border-collapse: collapse; }
    .ck-content table td, .ck-content table th { border: 1px solid #ccc; padding: 6px 10px; }
</style>
<script>
    let ckEditor;
    const EditorClass = (window.CKEDITOR && window.CKEDITOR.ClassicEditor) || window.ClassicEditor;
    if (!EditorClass) {
        document.getElementById('editor').innerHTML =
            '<div style="color:#b91c1c;padding:12px;">No se pudo cargar el editor CKEditor. Revisa tu conexión a internet.</div>';
    } else {
    // Adapter que comprime imágenes a JPEG base64 antes de insertarlas (sin subir al servidor).
    function Base64CompressAdapterPlugin(editor) {
        editor.plugins.get('FileRepository').createUploadAdapter = (loader) => ({
            upload: () => loader.file.then(file => new Promise((resolve, reject) => {
                if (!file || !file.type || !file.type.startsWith('image/')) {
                    reject('Solo se permiten imágenes.'); return;
                }
                const MAX_DIM = 1200;
                const TARGET_BYTES = 400 * 1024;
                const reader = new FileReader();
                reader.onload = () => {
                    const img = new Image();
                    img.onload = () => {
                        let w = img.naturalWidth, h = img.naturalHeight;
                        if (w > MAX_DIM || h > MAX_DIM) {
                            const r = Math.min(MAX_DIM / w, MAX_DIM / h);
                            w = Math.round(w * r); h = Math.round(h * r);
                        }
                        const canvas = document.createElement('canvas');
                        canvas.width = w; canvas.height = h;
                        const ctx = canvas.getContext('2d');
                        ctx.fillStyle = '#ffffff';
                        ctx.fillRect(0, 0, w, h);
                        ctx.drawImage(img, 0, 0, w, h);
                        let dataUrl = '';
                        for (const q of [0.75, 0.65, 0.55, 0.45, 0.35]) {
                            dataUrl = canvas.toDataURL('image/jpeg', q);
                            if (dataUrl.length * 0.75 <= TARGET_BYTES) break;
                        }
                        resolve({ default: dataUrl });
                    };
                    img.onerror = () => reject('No se pudo leer la imagen.');
                    img.src = reader.result;
                };
                reader.onerror = () => reject('No se pudo leer el archivo.');
                reader.readAsDataURL(file);
            })),
            abort: () => {}
        });
    }

    EditorClass.create(document.querySelector('#editor'), {
        language: 'es',
        extraPlugins: [Base64CompressAdapterPlugin],
        removePlugins: [
            // Premium / licencia
            'RealTimeCollaborativeComments', 'RealTimeCollaborativeTrackChanges',
            'RealTimeCollaborativeRevisionHistory', 'PresenceList',
            'Comments', 'TrackChanges', 'TrackChangesData', 'RevisionHistory',
            'Pagination', 'WProofreader', 'MathType',
            'SlashCommand', 'Template', 'DocumentOutline', 'FormatPainter',
            'TableOfContents', 'PasteFromOfficeEnhanced', 'CaseChange',
            'ExportPdf', 'ExportWord', 'AIAssistant', 'MultiLevelList',
            // CKBox / CKFinder / EasyImage — uploaders externos no deseados
            'CKBox', 'CKBoxEditing', 'CKBoxUI', 'CKBoxImageEdit',
            'CKBoxImageEditEditing', 'CKBoxImageEditUI',
            'CKFinder', 'CKFinderEditing', 'CKFinderUploadAdapter',
            'EasyImage', 'CloudServices',
            // Uploaders estándar: los reemplazamos con nuestro adapter con compresión
            'Base64UploadAdapter', 'SimpleUploadAdapter', 'CloudServicesUploadAdapter',
        ],
        toolbar: {
            items: [
                'undo', 'redo', '|',
                'heading', 'style', '|',
                'fontFamily', 'fontSize', 'fontColor', 'fontBackgroundColor', '|',
                'bold', 'italic', 'underline', 'strikethrough', 'subscript', 'superscript', 'removeFormat', '|',
                'link', 'uploadImage', 'insertTable', 'mediaEmbed', 'htmlEmbed',
                'blockQuote', 'codeBlock', 'horizontalLine', 'pageBreak', 'specialCharacters', '|',
                'alignment', '|',
                'bulletedList', 'numberedList', 'todoList', 'outdent', 'indent', '|',
                'findAndReplace', 'sourceEditing',
            ],
            shouldNotGroupWhenFull: true,
        },
        image: {
            toolbar: [
                'imageTextAlternative', '|',
                'imageStyle:inline', 'imageStyle:block', 'imageStyle:side', '|',
                'resizeImage',
            ],
        },
        mediaEmbed: { previewsInData: true },
        table: {
            contentToolbar: [
                'tableColumn', 'tableRow', 'mergeTableCells',
                'tableProperties', 'tableCellProperties'
            ]
        },
        htmlSupport: {
            allow: [{ name: /.*/, attributes: true, classes: true, styles: true }]
        },
    }).then(editor => {
        ckEditor = editor;
        const actualizarPeso = () => {
            const bytes = new Blob([editor.getData()]).size;
            const MAX = 2_000_000;
            const pct = Math.min(100, (bytes / MAX) * 100);
            const kb = bytes / 1024;
            const texto = kb < 1024 ? `${kb.toFixed(0)} KB / 2 MB` : `${(kb/1024).toFixed(2)} MB / 2 MB`;
            document.getElementById('peso-texto').textContent = texto;
            const barra = document.getElementById('peso-progreso');
            barra.style.width = pct + '%';
            barra.classList.toggle('bg-red-600', bytes > MAX);
            barra.classList.toggle('bg-amber-500', bytes > MAX * 0.8 && bytes <= MAX);
            barra.classList.toggle('bg-blue-600', bytes <= MAX * 0.8);
            document.getElementById('peso-alerta').classList.toggle('hidden', bytes <= MAX);
        };
        editor.model.document.on('change:data', actualizarPeso);
        actualizarPeso();
    }).catch(err => {
        console.error('Error inicializando CKEditor:', err);
        document.getElementById('editor').innerHTML =
            '<div style="color:#b91c1c;padding:12px;">Error inicializando el editor: ' + (err && err.message ? err.message : err) + '</div>';
    });
    }

    document.querySelector('form').addEventListener('submit', function (e) {
        const data = ckEditor ? ckEditor.getData() : '';
        const bytes = new Blob([data]).size;
        if (bytes > 2_000_000) {
            e.preventDefault();
            alert('El contenido supera los 2 MB (' + (bytes/1024/1024).toFixed(2) + ' MB). Elimina o reemplaza imágenes antes de guardar.');
            return;
        }
        document.getElementById('contenido-input').value = data;
    });
</script>

@endsection
