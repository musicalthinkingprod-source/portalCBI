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

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden circular-preview">
    @include('circulares._plantilla', ['circular' => $circular])
</div>

<style>
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
    .circular-preview ol, .circular-preview ul { padding-left: 28px; margin: 8px 0; }
    .circular-preview h1 { font-size: 22px; margin: 14px 0 8px; }
    .circular-preview h2 { font-size: 19px; margin: 12px 0 6px; }
    .circular-preview h3 { font-size: 16px; margin: 10px 0 6px; }
    .circular-preview h4 { font-size: 14px; margin: 10px 0 6px; }
</style>

@endsection
