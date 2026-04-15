@extends('layouts.app-sidebar')

@section('header', 'Nuevo documento')

@section('slot')

<div class="max-w-2xl">
    <form method="POST" action="{{ route('documentacion.store') }}" class="space-y-5">
        @csrf

        <div class="bg-white rounded-xl shadow p-6 space-y-5">

            {{-- Categoría --}}
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">
                    Categoría <span class="text-red-500">*</span>
                </label>
                <input type="text" name="categoria"
                    value="{{ old('categoria') }}"
                    list="categorias-list"
                    placeholder="Ej: Manual de Convivencia, Contratos, Reglamentos…"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm @error('categoria') border-red-400 @enderror">
                <datalist id="categorias-list">
                    @foreach($categorias as $cat)
                        <option value="{{ $cat }}">
                    @endforeach
                </datalist>
                @error('categoria') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Título --}}
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">
                    Título <span class="text-red-500">*</span>
                </label>
                <input type="text" name="titulo"
                    value="{{ old('titulo') }}"
                    placeholder="Nombre visible para los padres"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm @error('titulo') border-red-400 @enderror">
                @error('titulo') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Descripción --}}
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Descripción (opcional)</label>
                <textarea name="descripcion" rows="2"
                    placeholder="Breve detalle del documento"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm resize-none">{{ old('descripcion') }}</textarea>
            </div>

            {{-- URL --}}
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">
                    Link de Drive <span class="text-red-500">*</span>
                </label>
                <input type="url" name="url"
                    value="{{ old('url') }}"
                    placeholder="https://drive.google.com/…"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-mono @error('url') border-red-400 @enderror">
                @error('url') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="flex items-center gap-6">
                {{-- Orden --}}
                <div class="w-32">
                    <label class="block text-xs font-medium text-gray-500 mb-1">Orden</label>
                    <input type="number" name="orden" value="{{ old('orden', 0) }}" min="0" max="999"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <p class="text-xs text-gray-400 mt-0.5">Menor = primero</p>
                </div>

                {{-- Activo --}}
                <div class="flex items-center gap-2 mt-4">
                    <input type="hidden" name="activo" value="0">
                    <input type="checkbox" name="activo" id="activo" value="1"
                        {{ old('activo', '1') == '1' ? 'checked' : '' }}
                        class="w-4 h-4 text-blue-600">
                    <label for="activo" class="text-sm text-gray-700">Visible para padres</label>
                </div>
            </div>

        </div>

        <div class="flex items-center gap-3">
            <button type="submit"
                class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-5 py-2 rounded-lg transition">
                Guardar documento
            </button>
            <a href="{{ route('documentacion.index') }}"
               class="text-sm text-gray-500 hover:text-gray-700 px-3 py-2">Cancelar</a>
        </div>
    </form>
</div>

@endsection
