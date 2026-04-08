@extends('layouts.app-sidebar')

@section('header', 'Registro de Pagos')

@section('slot')

@php
    function sortUrl(string $col, string $currentCol, string $currentDir): string {
        $dir = ($col === $currentCol && $currentDir === 'desc') ? 'asc' : 'desc';
        return request()->fullUrlWithQuery(['sort' => $col, 'direction' => $dir, 'page' => 1]);
    }
    function sortIcon(string $col, string $currentCol, string $currentDir): string {
        if ($col !== $currentCol) return '<span class="text-gray-300 ml-1">↕</span>';
        return $currentDir === 'asc'
            ? '<span class="text-blue-600 ml-1">↑</span>'
            : '<span class="text-blue-600 ml-1">↓</span>';
    }
@endphp

    {{-- Acciones --}}
    <div class="flex flex-wrap gap-3 mb-6">
        <a href="{{ route('pagos.create') }}"
            class="bg-blue-800 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition">
            ➕ Agregar pago
        </a>
        <a href="{{ route('importacion.registro_pagos.show') }}"
            class="bg-white border border-blue-800 text-blue-800 hover:bg-blue-50 text-sm font-semibold px-4 py-2 rounded-lg transition">
            📂 Importar en lote
        </a>
    </div>

    @if(session('success'))
        <div class="mb-4 p-3 bg-green-100 text-green-800 rounded-xl text-sm">
            ✅ {{ session('success') }}
        </div>
    @endif

    {{-- Tabla pagos --}}
    <div class="bg-white rounded-xl shadow overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-bold text-blue-800">Todos los pagos</h3>
            <div class="flex items-center gap-3">
                <span class="text-xs text-gray-400">{{ $pagos->total() }} registros</span>
                @if(request()->hasAny(['codigo_alumno','fecha','concepto','mes','orden']))
                    <a href="{{ route('pagos.index') }}" class="text-xs text-red-500 hover:text-red-700 font-semibold">✕ Limpiar filtros</a>
                @endif
            </div>
        </div>

        <form method="GET" action="{{ route('pagos.index') }}">
            @if(request('sort'))<input type="hidden" name="sort" value="{{ request('sort') }}">@endif
            @if(request('direction'))<input type="hidden" name="direction" value="{{ request('direction') }}">@endif

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                    <tr>
                        <th class="px-4 py-3 text-left">
                            <a href="{{ sortUrl('codigo_alumno', $sortCol, $sortDir) }}" class="hover:text-blue-700 inline-flex items-center">
                                Código alumno{!! sortIcon('codigo_alumno', $sortCol, $sortDir) !!}
                            </a>
                        </th>
                        <th class="px-4 py-3 text-left">
                            <a href="{{ sortUrl('fecha', $sortCol, $sortDir) }}" class="hover:text-blue-700 inline-flex items-center">
                                Fecha{!! sortIcon('fecha', $sortCol, $sortDir) !!}
                            </a>
                        </th>
                        <th class="px-4 py-3 text-left">
                            <a href="{{ sortUrl('concepto', $sortCol, $sortDir) }}" class="hover:text-blue-700 inline-flex items-center">
                                Concepto{!! sortIcon('concepto', $sortCol, $sortDir) !!}
                            </a>
                        </th>
                        <th class="px-4 py-3 text-left">
                            <a href="{{ sortUrl('mes', $sortCol, $sortDir) }}" class="hover:text-blue-700 inline-flex items-center">
                                Mes{!! sortIcon('mes', $sortCol, $sortDir) !!}
                            </a>
                        </th>
                        <th class="px-4 py-3 text-left">Orden</th>
                        <th class="px-4 py-3 text-right">
                            <a href="{{ sortUrl('valor', $sortCol, $sortDir) }}" class="hover:text-blue-700 inline-flex items-center justify-end w-full">
                                Valor{!! sortIcon('valor', $sortCol, $sortDir) !!}
                            </a>
                        </th>
                        <th class="px-4 py-3 text-center">Acciones</th>
                    </tr>
                    <tr class="bg-white border-b border-gray-200">
                        <td class="px-2 py-1">
                            <input type="number" name="codigo_alumno" value="{{ request('codigo_alumno') }}"
                                placeholder="Filtrar…"
                                class="w-full border border-gray-300 rounded px-2 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-blue-400">
                        </td>
                        <td class="px-2 py-1">
                            <input type="date" name="fecha" value="{{ request('fecha') }}"
                                class="w-full border border-gray-300 rounded px-2 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-blue-400">
                        </td>
                        <td class="px-2 py-1">
                            <input type="text" name="concepto" value="{{ request('concepto') }}"
                                placeholder="Filtrar…"
                                class="w-full border border-gray-300 rounded px-2 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-blue-400">
                        </td>
                        <td class="px-2 py-1">
                            <input type="text" name="mes" value="{{ request('mes') }}"
                                placeholder="Filtrar…"
                                class="w-full border border-gray-300 rounded px-2 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-blue-400">
                        </td>
                        <td class="px-2 py-1">
                            <input type="text" name="orden" value="{{ request('orden') }}"
                                placeholder="Filtrar…"
                                class="w-full border border-gray-300 rounded px-2 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-blue-400">
                        </td>
                        <td class="px-2 py-1 text-right">
                            <button type="submit"
                                class="bg-blue-800 hover:bg-blue-700 text-white text-xs font-semibold px-3 py-1 rounded transition">
                                Filtrar
                            </button>
                        </td>
                        <td></td>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($pagos as $pago)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium">{{ $pago->codigo_alumno }}</td>
                        <td class="px-4 py-3">{{ $pago->fecha }}</td>
                        <td class="px-4 py-3">{{ $pago->concepto }}</td>
                        <td class="px-4 py-3">{{ $pago->mes }}</td>
                        <td class="px-4 py-3">{{ $pago->orden ?? '—' }}</td>
                        <td class="px-4 py-3 text-right font-medium text-green-700">$ {{ number_format($pago->valor, 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-center whitespace-nowrap">
                            <a href="{{ route('pagos.edit', $pago->id) }}"
                                class="inline-block text-xs bg-yellow-100 hover:bg-yellow-200 text-yellow-800 font-semibold px-2 py-1 rounded transition mr-1">
                                ✏️ Editar
                            </a>
                            <form method="POST" action="{{ route('pagos.destroy', $pago->id) }}" class="inline"
                                onsubmit="return confirm('¿Eliminar este pago?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                    class="text-xs bg-red-100 hover:bg-red-200 text-red-700 font-semibold px-2 py-1 rounded transition">
                                    🗑️ Eliminar
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-4 py-6 text-center text-gray-400 text-sm">Sin registros de pagos.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        </form>

        @if($pagos->hasPages())
        <div class="px-5 py-4 border-t border-gray-100">
            {{ $pagos->links() }}
        </div>
        @endif
    </div>

@endsection
