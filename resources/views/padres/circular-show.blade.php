@extends('layouts.padres')

@section('header', $circular->numero . ' — ' . $circular->asunto)

@section('slot')

<div class="mb-4 flex items-center gap-3">
    <a href="{{ route('padres.circulares') }}"
        class="text-sm text-blue-700 hover:text-blue-900 transition flex items-center gap-1">
        ← Volver a circulares
    </a>
    @if($circular->link)
    <a href="{{ $circular->link }}" target="_blank" rel="noopener"
        class="ml-auto border border-green-500 text-green-700 hover:bg-green-50 text-xs font-semibold px-4 py-2 rounded-lg transition">
        Ver en Drive
    </a>
    @endif
    <a href="{{ route('circulares.pdf', $circular) }}" target="_blank"
        class="{{ $circular->link ? '' : 'ml-auto' }} bg-red-600 hover:bg-red-700 text-white text-xs font-semibold px-4 py-2 rounded-lg transition">
        Descargar PDF
    </a>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    @include('circulares._plantilla', ['circular' => $circular])
</div>

@endsection
