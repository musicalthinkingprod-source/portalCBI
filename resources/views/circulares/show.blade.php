@extends('layouts.app-sidebar')

@section('header', $circular->numero . ' — ' . $circular->asunto)

@section('slot')

{{-- Acciones --}}
<div class="flex flex-wrap gap-3 mb-6">
    <a href="{{ route('circulares.index') }}"
        class="border border-gray-300 text-gray-600 px-4 py-2 rounded-lg text-sm hover:bg-gray-50 transition">
        ← Volver al listado
    </a>
    <a href="{{ route('circulares.edit', $circular) }}"
        class="border border-blue-300 text-blue-700 px-4 py-2 rounded-lg text-sm hover:bg-blue-50 transition">
        Editar
    </a>
    <a href="{{ route('circulares.pdf', $circular) }}" target="_blank"
        class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition">
        Descargar PDF
    </a>
    @if($circular->estado === 'borrador')
        <span class="bg-yellow-100 text-yellow-700 text-xs font-semibold px-3 py-2 rounded-full self-center">
            Borrador
        </span>
    @endif
</div>

{{-- Previsualización de la circular --}}
<div class="bg-white rounded-xl shadow max-w-4xl mx-auto">
    @include('circulares._plantilla', ['circular' => $circular])
</div>

@endsection
