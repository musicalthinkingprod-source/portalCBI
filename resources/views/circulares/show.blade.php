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
    @if($circular->link)
    <a href="{{ $circular->link }}" target="_blank" rel="noopener"
        class="border border-green-400 text-green-700 px-4 py-2 rounded-lg text-sm hover:bg-green-50 transition">
        Ver en Drive
    </a>
    @endif
    @if($circular->grados)
    @php
        $etiquetasGrado = ['PJ'=>'PreJardín','J'=>'Jardín','T'=>'Transición','1'=>'1°','2'=>'2°','3'=>'3°','4'=>'4°','5'=>'5°','6'=>'6°','7'=>'7°','8'=>'8°','9'=>'9°','10'=>'10°','11'=>'11°'];
        $etiquetas = array_map(fn($g) => $etiquetasGrado[$g] ?? $g, $circular->grados);
    @endphp
        <span class="bg-blue-100 text-blue-700 text-xs font-semibold px-3 py-2 rounded-full self-center">
            Para: {{ implode(', ', $etiquetas) }}
        </span>
    @endif
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
