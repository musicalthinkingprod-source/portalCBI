@extends('layouts.app-sidebar')

@section('header', 'Editar Circular — ' . $circular->numero)

@section('slot')

@php $esSuperAd = auth()->user()->PROFILE === 'SuperAd'; @endphp

<form method="POST" action="{{ route('circulares.update', $circular) }}">
    @csrf @method('PUT')

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Datos generales --}}
        <div class="lg:col-span-1 space-y-4">
            <div class="bg-white rounded-xl shadow p-5 space-y-4">
                <h2 class="text-sm font-semibold text-gray-700 border-b pb-2">Datos de la circular</h2>

                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Número <span class="text-red-500">*</span></label>
                    <input type="text" name="numero" value="{{ old('numero', $circular->numero) }}"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-mono text-blue-800 @error('numero') border-red-400 @enderror">
                    @error('numero') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Fecha <span class="text-red-500">*</span></label>
                    <input type="date" name="fecha" value="{{ old('fecha', $circular->fecha->format('Y-m-d')) }}"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm @error('fecha') border-red-400 @enderror">
                    @error('fecha') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Asunto <span class="text-red-500">*</span></label>
                    <input type="text" name="asunto" value="{{ old('asunto', $circular->asunto) }}"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm @error('asunto') border-red-400 @enderror">
                    @error('asunto') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Dirigido a <span class="text-red-500">*</span></label>
                    <input type="text" name="dirigido_a" value="{{ old('dirigido_a', $circular->dirigido_a) }}"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm @error('dirigido_a') border-red-400 @enderror">
                    @error('dirigido_a') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Firmado por <span class="text-red-500">*</span></label>
                    @if($esSuperAd)
                        <input type="text" name="emitido_por" value="{{ old('emitido_por', $circular->emitido_por) }}"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm @error('emitido_por') border-red-400 @enderror">
                    @else
                        <input type="hidden" name="emitido_por" value="{{ $circular->emitido_por }}">
                        <p class="px-3 py-2 text-sm bg-gray-50 border border-gray-200 rounded-lg text-gray-700">{{ $circular->emitido_por }}</p>
                    @endif
                    @error('emitido_por') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Cargo del firmante</label>
                    @if($esSuperAd)
                        <input type="text" name="cargo" value="{{ old('cargo', $circular->cargo) }}"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm @error('cargo') border-red-400 @enderror">
                    @else
                        <input type="hidden" name="cargo" value="{{ $circular->cargo }}">
                        <p class="px-3 py-2 text-sm bg-gray-50 border border-gray-200 rounded-lg text-gray-700">{{ $circular->cargo }}</p>
                    @endif
                    @error('cargo') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Estado</label>
                    @if($esSuperAd)
                        <select name="estado" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option value="borrador" @selected(old('estado', $circular->estado) === 'borrador')>Borrador</option>
                            <option value="publicada" @selected(old('estado', $circular->estado) === 'publicada')>Publicada</option>
                        </select>
                    @else
                        <input type="hidden" name="estado" value="{{ $circular->estado }}">
                        @if($circular->estado === 'publicada')
                            <p class="px-3 py-2 text-sm bg-green-50 border border-green-200 rounded-lg text-green-700 text-xs">✅ Publicada</p>
                        @else
                            <p class="px-3 py-2 text-sm bg-yellow-50 border border-yellow-200 rounded-lg text-yellow-700 text-xs">
                                🔒 Solo la rectora puede publicar circulares
                            </p>
                        @endif
                    @endif
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Enlace (Google Drive)</label>
                    <input type="url" name="link" value="{{ old('link', $circular->link) }}" placeholder="https://drive.google.com/..."
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm @error('link') border-red-400 @enderror">
                    @error('link') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Visible para</label>
                    <p class="text-xs text-gray-400 mb-2">Sin selección = todos los grados</p>
                    @php
                        $todosGrados = [
                            'Preescolar'   => ['PJ' => 'PreJardín', 'J' => 'Jardín', 'T' => 'Transición'],
                            'Primaria'     => ['1' => '1°', '2' => '2°', '3' => '3°', '4' => '4°', '5' => '5°'],
                            'Bachillerato' => ['6' => '6°', '7' => '7°', '8' => '8°', '9' => '9°', '10' => '10°', '11' => '11°'],
                        ];
                        $selGrados = array_map('strval', old('grados', $circular->grados ?? []));
                    @endphp
                    @foreach($todosGrados as $nivel => $opciones)
                    <p class="text-xs text-gray-400 mt-2 mb-1">{{ $nivel }}</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach($opciones as $val => $etiqueta)
                        <label class="flex items-center gap-1.5 cursor-pointer">
                            <input type="checkbox" name="grados[]" value="{{ $val }}"
                                {{ in_array((string)$val, $selGrados) ? 'checked' : '' }}
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
                    Actualizar
                </button>
                <a href="{{ route('circulares.show', $circular) }}"
                    class="flex-1 text-center border border-gray-300 text-gray-600 py-2 rounded-lg text-sm hover:bg-gray-50 transition">
                    Cancelar
                </a>
            </div>
        </div>

        {{-- Editor --}}
        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl shadow p-5">
                <h2 class="text-sm font-semibold text-gray-700 border-b pb-2 mb-3">Contenido de la circular <span class="text-xs font-normal text-gray-400">(opcional si hay enlace a Drive)</span></h2>

                <details class="mb-3 text-xs text-gray-600 bg-blue-50 border border-blue-200 rounded-lg p-3">
                    <summary class="cursor-pointer font-semibold text-blue-800">¿Cómo insertar una imagen o documento?</summary>
                    <div class="mt-2 space-y-2">
                        <p>Por espacio en servidor, <b>no se pueden subir imágenes</b> directamente. Usa una de estas opciones:</p>
                        <ol class="list-decimal ml-5 space-y-2">
                            <li>
                                <b>Imagen desde Google Drive (recomendado):</b>
                                <ol class="list-[lower-alpha] ml-5 mt-1 space-y-0.5">
                                    <li>Sube la imagen a Drive y compártela como <i>"Cualquier persona con el enlace"</i>.</li>
                                    <li>Copia el ID del archivo (la parte larga entre <code>/d/</code> y <code>/view</code> del link).<br>
                                        Ej. si el link es <code>drive.google.com/file/d/<b>1aBcDeFgHiJk</b>/view</code>, el ID es <code>1aBcDeFgHiJk</code>.
                                    </li>
                                    <li>En el editor, usa el botón <b>Insertar HTML</b> (&lt;/&gt;) y pega:
                                        <pre class="bg-white border border-blue-100 rounded p-2 mt-1 text-[11px] overflow-x-auto">&lt;img src="https://lh3.googleusercontent.com/d/ID_ARCHIVO" style="max-width:100%"&gt;</pre>
                                    </li>
                                </ol>
                                <p class="mt-1 text-[11px] text-blue-800">✅ Esta opción se ve en el portal <u>y en el PDF</u>. Importante: el archivo debe estar compartido <b>públicamente</b> en Drive.</p>
                            </li>
                            <li>
                                <b>Documento (PDF, Word, etc.) desde Drive:</b> usa <b>Insertar HTML</b> con:
                                <pre class="bg-white border border-blue-100 rounded p-2 mt-1 text-[11px] overflow-x-auto">&lt;iframe src="https://drive.google.com/file/d/ID_ARCHIVO/preview" width="640" height="480"&gt;&lt;/iframe&gt;</pre>
                                <p class="mt-1 text-[11px] text-amber-700">⚠️ Los iframes solo se ven en el portal; en el PDF aparecen como un enlace "Ver en Drive".</p>
                            </li>
                            <li><b>Video de YouTube/Vimeo:</b> usa el botón <b>Insertar medio</b> y pega la URL del video. (También solo se ve en portal, no en PDF.)</li>
                        </ol>
                    </div>
                </details>

                @error('contenido') <p class="text-red-500 text-xs mb-2">{{ $message }}</p> @enderror

                <div id="editor" style="min-height: 420px;">{!! old('contenido', $circular->contenido) !!}</div>
                <input type="hidden" name="contenido" id="contenido-input">
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
    EditorClass.create(document.querySelector('#editor'), {
        language: 'es',
        removePlugins: [
            // Premium / licencia
            'RealTimeCollaborativeComments', 'RealTimeCollaborativeTrackChanges',
            'RealTimeCollaborativeRevisionHistory', 'PresenceList',
            'Comments', 'TrackChanges', 'TrackChangesData', 'RevisionHistory',
            'Pagination', 'WProofreader', 'MathType',
            'SlashCommand', 'Template', 'DocumentOutline', 'FormatPainter',
            'TableOfContents', 'PasteFromOfficeEnhanced', 'CaseChange',
            'ExportPdf', 'ExportWord', 'AIAssistant', 'MultiLevelList',
            // CKBox / CKFinder / EasyImage (primero estos, dependen de Image/PictureEditing)
            'CKBox', 'CKBoxEditing', 'CKBoxUI', 'CKBoxImageEdit',
            'CKBoxImageEditEditing', 'CKBoxImageEditUI',
            'CKFinder', 'CKFinderEditing', 'CKFinderUploadAdapter',
            'EasyImage', 'CloudServices',
            // Adaptadores de upload
            'Base64UploadAdapter', 'SimpleUploadAdapter', 'CloudServicesUploadAdapter',
            // Imágenes — desactivadas para no saturar espacio. Usar iframe (htmlEmbed) o mediaEmbed.
            'PictureEditing', 'AutoImage', 'LinkImage',
            'ImageUpload', 'ImageUploadEditing', 'ImageUploadUI', 'ImageUploadProgress',
            'ImageInsert', 'ImageInsertViaUrl', 'ImageInsertUI',
            'ImageResize', 'ImageResizeEditing', 'ImageResizeHandles', 'ImageResizeButtons',
            'ImageStyle', 'ImageTextAlternative', 'ImageToolbar', 'ImageCaption',
            'ImageBlock', 'ImageInline', 'Image',
        ],
        toolbar: {
            items: [
                'undo', 'redo', '|',
                'heading', 'style', '|',
                'fontFamily', 'fontSize', 'fontColor', 'fontBackgroundColor', '|',
                'bold', 'italic', 'underline', 'strikethrough', 'subscript', 'superscript', 'removeFormat', '|',
                'link', 'insertTable', 'mediaEmbed', 'htmlEmbed',
                'blockQuote', 'codeBlock', 'horizontalLine', 'pageBreak', 'specialCharacters', '|',
                'alignment', '|',
                'bulletedList', 'numberedList', 'todoList', 'outdent', 'indent', '|',
                'findAndReplace', 'sourceEditing',
            ],
            shouldNotGroupWhenFull: true,
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
    }).catch(err => {
        console.error('Error inicializando CKEditor:', err);
        document.getElementById('editor').innerHTML =
            '<div style="color:#b91c1c;padding:12px;">Error inicializando el editor: ' + (err && err.message ? err.message : err) + '</div>';
    });
    }

    document.querySelector('form').addEventListener('submit', function () {
        document.getElementById('contenido-input').value = ckEditor ? ckEditor.getData() : '';
    });
</script>

@endsection
