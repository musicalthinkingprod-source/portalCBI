@extends('layouts.app-sidebar')

@section('header', 'Documentación institucional')

@section('slot')

<div class="flex items-center justify-between mb-6">
    <p class="text-sm text-gray-500">Links de Drive disponibles para los padres de familia.</p>
    <a href="{{ route('documentacion.create') }}"
       class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition">
        + Nuevo documento
    </a>
</div>

@if(session('success'))
    <div class="mb-4 px-4 py-3 bg-green-50 border border-green-200 text-green-800 text-sm rounded-lg">
        {{ session('success') }}
    </div>
@endif

@if($documentos->isEmpty())
    <div class="bg-white rounded-xl shadow p-10 text-center text-gray-400 text-sm">
        No hay documentos registrados todavía.
    </div>
@else
    <div class="bg-white rounded-xl shadow overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wide border-b">
                <tr>
                    <th class="px-4 py-3 text-left">Categoría</th>
                    <th class="px-4 py-3 text-left">Título</th>
                    <th class="px-4 py-3 text-left hidden md:table-cell">Descripción</th>
                    <th class="px-4 py-3 text-center">Orden</th>
                    <th class="px-4 py-3 text-center">Estado</th>
                    <th class="px-4 py-3 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($documentos as $doc)
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-4 py-3 font-medium text-gray-700 whitespace-nowrap">{{ $doc->categoria }}</td>
                    <td class="px-4 py-3 text-gray-800">
                        <a href="{{ $doc->url }}" target="_blank" rel="noopener"
                           class="text-blue-600 hover:underline flex items-center gap-1">
                            {{ $doc->titulo }}
                            <svg class="w-3 h-3 opacity-60 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                            </svg>
                        </a>
                    </td>
                    <td class="px-4 py-3 text-gray-500 hidden md:table-cell max-w-xs truncate">
                        {{ $doc->descripcion ?? '—' }}
                    </td>
                    <td class="px-4 py-3 text-center text-gray-500">{{ $doc->orden }}</td>
                    <td class="px-4 py-3 text-center">
                        @if($doc->activo)
                            <span class="inline-block px-2 py-0.5 bg-green-100 text-green-700 text-xs rounded-full font-medium">Visible</span>
                        @else
                            <span class="inline-block px-2 py-0.5 bg-gray-100 text-gray-500 text-xs rounded-full font-medium">Oculto</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right whitespace-nowrap">
                        <a href="{{ route('documentacion.edit', $doc) }}"
                           class="text-blue-600 hover:text-blue-800 text-xs font-medium mr-3">Editar</a>
                        <form method="POST" action="{{ route('documentacion.destroy', $doc) }}" class="inline"
                              onsubmit="return confirm('¿Eliminar este documento?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-red-500 hover:text-red-700 text-xs font-medium">Eliminar</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

@endsection
