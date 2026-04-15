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

                @error('contenido') <p class="text-red-500 text-xs mb-2">{{ $message }}</p> @enderror

                <div id="editor" style="min-height: 420px;">{!! old('contenido') !!}</div>
                <input type="hidden" name="contenido" id="contenido-input">
            </div>
        </div>

    </div>
</form>

<script src="https://cdn.ckeditor.com/ckeditor5/41.4.2/classic/ckeditor.js"></script>
<script>
    let ckEditor;
    ClassicEditor.create(document.querySelector('#editor'), {
        language: 'es',
        toolbar: [
            'heading', '|',
            'bold', 'italic', 'underline', '|',
            'alignment:left', 'alignment:center', 'alignment:right', 'alignment:justify', '|',
            'bulletedList', 'numberedList', '|',
            'insertTable', '|',
            'undo', 'redo'
        ],
        table: {
            contentToolbar: ['tableColumn', 'tableRow', 'mergeTableCells']
        },
    }).then(editor => {
        ckEditor = editor;
    }).catch(console.error);

    document.querySelector('form').addEventListener('submit', function () {
        document.getElementById('contenido-input').value = ckEditor ? ckEditor.getData() : '';
    });
</script>

@endsection
