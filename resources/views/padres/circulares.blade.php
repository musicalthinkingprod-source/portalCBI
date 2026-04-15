@extends('layouts.padres')

@section('header', 'Circulares')

@section('slot')

@if($circulares->isEmpty())
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-10 text-center text-gray-400">
        <p class="text-3xl mb-2">📭</p>
        <p>No hay circulares publicadas aún.</p>
    </div>
@else

@php
    $porAño = $circulares->groupBy(fn($c) => $c->fecha->year);
@endphp

@foreach($porAño as $año => $items)
<div class="mb-6">
    <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-3">{{ $año }}</p>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden divide-y divide-gray-50">
        @foreach($items as $circular)
        <a href="{{ $circular->link ?? route('padres.circulares.show', $circular) }}"
           @if($circular->link) target="_blank" rel="noopener" @endif
           class="flex items-start gap-4 px-5 py-4 hover:bg-blue-50 transition group">
            <div class="w-10 h-10 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center shrink-0 text-lg font-bold group-hover:bg-blue-200 transition">
                📄
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-gray-800 group-hover:text-blue-800 leading-tight truncate">
                    {{ $circular->asunto }}
                </p>
                <p class="text-xs text-gray-400 mt-0.5">
                    {{ $circular->fecha->translatedFormat('j \d\e F \d\e Y') }}
                    &nbsp;·&nbsp;
                    <span class="text-gray-500">{{ $circular->dirigido_a }}</span>
                </p>
            </div>
            <div class="shrink-0 text-xs font-mono text-gray-400 group-hover:text-blue-600 self-center">
                {{ $circular->numero }}
            </div>
        </a>
        @endforeach
    </div>
</div>
@endforeach

@endif

@endsection
