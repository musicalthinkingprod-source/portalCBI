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
<div class="bg-white rounded-xl shadow max-w-4xl mx-auto circular-preview">
    @include('circulares._plantilla', ['circular' => $circular])
</div>

<style>
    /* Render del contenido producido por CKEditor 5 en la vista web */
    .circular-preview img { max-width: 100%; height: auto; }
    .circular-preview figure.image { display: block; margin: 10px 0; text-align: center; }
    .circular-preview figure.image figcaption { font-size: 11px; color: #555; margin-top: 4px; }
    .circular-preview figure.image-style-side { float: right; margin: 0 0 10px 14px; max-width: 45%; }
    .circular-preview figure.image-style-align-left { float: left; margin: 0 14px 10px 0; }
    .circular-preview figure.image-style-align-right { float: right; margin: 0 0 10px 14px; }
    .circular-preview figure.image-style-align-center { margin: 10px auto; }

    .circular-preview figure.table { display: block; margin: 10px 0; overflow-x: auto; }
    .circular-preview figure.table table { border-collapse: collapse; width: 100%; }
    .circular-preview figure.table td,
    .circular-preview figure.table th { border: 1px solid #cbd5e1; padding: 6px 10px; vertical-align: top; }
    .circular-preview figure.table th { background: #f3f4f6; font-weight: 700; }

    .circular-preview figure.media { margin: 14px 0; }
    .circular-preview figure.media .ck-media__wrapper,
    .circular-preview figure.media oembed { display: block; }
    .circular-preview figure.media iframe { width: 100%; aspect-ratio: 16/9; border: 0; }

    .circular-preview .text-tiny  { font-size: 0.7em; }
    .circular-preview .text-small { font-size: 0.85em; }
    .circular-preview .text-big   { font-size: 1.4em; }
    .circular-preview .text-huge  { font-size: 1.8em; }

    .circular-preview blockquote { border-left: 4px solid #cbd5e1; padding-left: 14px; margin: 10px 0; color: #475569; font-style: italic; }
    .circular-preview pre { background: #f3f4f6; padding: 10px; border-radius: 4px; font-family: Consolas, monospace; font-size: 12px; overflow: auto; }
    .circular-preview code { background: #f3f4f6; padding: 1px 4px; border-radius: 3px; font-family: Consolas, monospace; font-size: 12px; }
    .circular-preview hr { border: none; border-top: 1px solid #cbd5e1; margin: 14px 0; }
    .circular-preview .page-break { page-break-after: always; }
    .circular-preview ol, .circular-preview ul { padding-left: 28px; margin: 8px 0; }
    .circular-preview h1 { font-size: 22px; margin: 14px 0 8px; }
    .circular-preview h2 { font-size: 19px; margin: 12px 0 6px; }
    .circular-preview h3 { font-size: 16px; margin: 10px 0 6px; }
    .circular-preview h4 { font-size: 14px; margin: 10px 0 6px; }
</style>

@endsection
