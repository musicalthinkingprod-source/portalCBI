@extends('layouts.padres')

@section('header', 'Documentación institucional')

@section('slot')

@if($grupos->isEmpty())
    <div class="bg-white rounded-xl shadow p-10 text-center text-gray-400 text-sm">
        Por el momento no hay documentos publicados.
    </div>
@else
    <div class="space-y-8 max-w-3xl">
        @foreach($grupos as $categoria => $docs)
        <div>
            <h2 class="text-base font-semibold text-gray-700 mb-3 flex items-center gap-2">
                <span class="w-1 h-5 bg-blue-500 rounded-full inline-block"></span>
                {{ $categoria }}
            </h2>
            <div class="bg-white rounded-xl shadow divide-y divide-gray-100">
                @foreach($docs as $doc)
                <a href="{{ $doc->url }}" target="_blank" rel="noopener"
                   class="flex items-center gap-4 px-5 py-4 hover:bg-blue-50 transition group">

                    {{-- Ícono Drive --}}
                    <div class="shrink-0 w-10 h-10 rounded-lg bg-blue-100 flex items-center justify-center text-blue-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                    </div>

                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-800 group-hover:text-blue-700 transition truncate">
                            {{ $doc->titulo }}
                        </p>
                        @if($doc->descripcion)
                            <p class="text-xs text-gray-500 mt-0.5 truncate">{{ $doc->descripcion }}</p>
                        @endif
                    </div>

                    {{-- Flecha abrir --}}
                    <svg class="w-4 h-4 text-gray-400 group-hover:text-blue-500 shrink-0 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                    </svg>
                </a>
                @endforeach
            </div>
        </div>
        @endforeach
    </div>
@endif

@endsection
