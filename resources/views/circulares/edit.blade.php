@extends('layouts.app-sidebar')

@section('header', 'Editar Circular — ' . $circular->numero)

@section('slot')

<form method="POST" action="{{ route('circulares.update', $circular) }}">
    @csrf @method('PUT')

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Datos generales --}}
        <div class="lg:col-span-1 space-y-4">
            <div class="bg-white rounded-xl shadow p-5 space-y-4">
                <h2 class="text-sm font-semibold text-gray-700 border-b pb-2">Datos de la circular</h2>

                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Número</label>
                    <input type="text" value="{{ $circular->numero }}" disabled
                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm bg-gray-50 font-mono text-blue-800">
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
                    <label class="block text-xs font-medium text-gray-500 mb-1">Emitido por <span class="text-red-500">*</span></label>
                    <input type="text" name="emitido_por" value="{{ old('emitido_por', $circular->emitido_por) }}"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm @error('emitido_por') border-red-400 @enderror">
                    @error('emitido_por') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Estado</label>
                    <select name="estado" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="borrador" @selected(old('estado', $circular->estado) === 'borrador')>Borrador</option>
                        <option value="publicada" @selected(old('estado', $circular->estado) === 'publicada')>Publicada</option>
                    </select>
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
                <h2 class="text-sm font-semibold text-gray-700 border-b pb-2 mb-3">Contenido de la circular</h2>

                @error('contenido') <p class="text-red-500 text-xs mb-2">{{ $message }}</p> @enderror

                <div id="toolbar" class="border border-gray-200 rounded-t-lg px-2 py-1 bg-gray-50 flex flex-wrap gap-1">
                    <button class="ql-bold"></button>
                    <button class="ql-italic"></button>
                    <button class="ql-underline"></button>
                    <span class="ql-formats">
                        <select class="ql-size">
                            <option value="small">Pequeño</option>
                            <option selected>Normal</option>
                            <option value="large">Grande</option>
                        </select>
                    </span>
                    <span class="ql-formats">
                        <button class="ql-list" value="ordered"></button>
                        <button class="ql-list" value="bullet"></button>
                    </span>
                    <span class="ql-formats">
                        <select class="ql-align"></select>
                    </span>
                    <button class="ql-clean"></button>
                </div>
                <div id="editor" style="min-height: 420px; font-size: 14px;"
                    class="border border-t-0 border-gray-200 rounded-b-lg px-4 py-3"></div>

                <input type="hidden" name="contenido" id="contenido-input">
            </div>
        </div>

    </div>
</form>

<link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
<script>
    const quill = new Quill('#editor', {
        modules: { toolbar: '#toolbar' },
        theme: 'snow',
    });

    // Carga el contenido existente
    quill.root.innerHTML = {!! json_encode($circular->contenido) !!};

    document.querySelector('form').addEventListener('submit', function () {
        document.getElementById('contenido-input').value = quill.root.innerHTML;
    });
</script>

@endsection
