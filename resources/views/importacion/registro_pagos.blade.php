@extends('layouts.app-sidebar')

@section('header', 'Importar Registro de Pagos')

@section('slot')
    <div class="max-w-2xl space-y-6">

        @if(session('success'))
            <div class="p-4 bg-green-100 text-green-800 rounded-xl text-sm">
                ✅ Importación completada:
                <strong>{{ session('insertados') }} registros insertados</strong>
                — Lote: <code class="bg-green-200 px-1 rounded">{{ session('lote') }}</code>
                @if(session('errores') > 0)
                    — <span class="text-red-600">{{ session('errores') }} con error</span>
                    @if(session('err_detalle'))
                        <br><code class="text-xs text-red-500">{{ session('err_detalle') }}</code>
                    @endif
                @endif
            </div>
        @endif

        @if(session('lote_eliminado'))
            <div class="p-4 bg-orange-100 text-orange-800 rounded-xl text-sm">
                🗑️ {{ session('lote_eliminado') }}
            </div>
        @endif

        @if($errors->has('archivo'))
            <div class="p-4 bg-red-100 text-red-700 rounded-xl text-sm">
                {{ $errors->first('archivo') }}
            </div>
        @endif

        {{-- Formulario de importación --}}
        <div class="bg-white rounded-xl shadow p-6">
            <h3 class="text-lg font-bold text-blue-800 mb-1">Subir archivo CSV</h3>
            <p class="text-sm text-gray-500 mb-4">
                Exporta desde Access como <strong>.csv</strong> con los encabezados:<br>
                <code class="text-xs bg-gray-100 px-2 py-1 rounded">codigo_alumno, fecha, valor, concepto, mes, orden</code>
            </p>
            <form method="POST" action="{{ route('importacion.registro_pagos') }}" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Archivo CSV</label>
                    <input type="file" name="archivo" accept=".csv,.txt"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <button type="submit"
                    class="w-full bg-blue-800 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg transition text-sm">
                    Importar
                </button>
            </form>
        </div>

        {{-- Lotes previos --}}
        <div class="bg-white rounded-xl shadow overflow-hidden">
            <div class="px-5 py-3 bg-gray-700 text-white">
                <h3 class="font-bold text-sm uppercase tracking-wide">Importaciones anteriores</h3>
                <p class="text-gray-300 text-xs mt-0.5">Eliminar un lote borra todos sus registros de pagos</p>
            </div>
            @if($lotes->isEmpty())
                <div class="px-5 py-6 text-center text-gray-400 text-sm">
                    No hay lotes de importación registrados aún. Los próximos archivos que importes aparecerán aquí.
                </div>
            @else
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                    <tr>
                        <th class="px-4 py-3 text-left">Lote</th>
                        <th class="px-4 py-3 text-center">Registros</th>
                        <th class="px-4 py-3 text-center">Rango de fechas</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($lotes as $l)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-mono text-xs text-gray-700">{{ $l->lote_importacion }}</td>
                        <td class="px-4 py-3 text-center font-semibold">{{ $l->total }}</td>
                        <td class="px-4 py-3 text-center text-xs text-gray-500">
                            {{ $l->fecha_min }} → {{ $l->fecha_max }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            <form method="POST"
                                action="{{ route('importacion.registro_pagos.lote.destroy', $l->lote_importacion) }}"
                                onsubmit="return confirm('¿Eliminar el lote {{ $l->lote_importacion }}?\nSe borrarán {{ $l->total }} registros. Esta acción no se puede deshacer.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                    class="text-red-500 hover:text-red-700 text-xs font-semibold transition">
                                    Eliminar lote
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @endif
        </div>

    </div>
@endsection
