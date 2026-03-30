@extends('layouts.app-sidebar')

@section('header', 'Importar Facturación')

@section('slot')
    <div class="max-w-xl">

        @if(session('success'))
            <div class="mb-6 p-4 bg-green-100 text-green-800 rounded-xl text-sm">
                ✅ Importación completada:
                <strong>{{ session('insertados') }} registros insertados</strong>
                @if(session('errores') > 0)
                    — <span class="text-red-600">{{ session('errores') }} con error</span>
                    @if(session('err_detalle'))
                        <br><code class="text-xs text-red-500">{{ session('err_detalle') }}</code>
                    @endif
                @endif
            </div>
        @endif

        @if($errors->has('archivo'))
            <div class="mb-6 p-4 bg-red-100 text-red-700 rounded-xl text-sm">
                {{ $errors->first('archivo') }}
            </div>
        @endif

        <div class="bg-white rounded-xl shadow p-6">
            <h3 class="text-lg font-bold text-blue-800 mb-1">Subir archivo CSV</h3>
            <p class="text-sm text-gray-500 mb-4">
                Exporta desde Access como <strong>.csv</strong> con los encabezados:<br>
                <code class="text-xs bg-gray-100 px-2 py-1 rounded">Código Alumno, Concepto, Valor, Mes, Orden, Código Concepto, Centro de Costo, Fecha</code>
            </p>

            <form method="POST" action="{{ route('importacion.facturacion') }}" enctype="multipart/form-data" class="space-y-4">
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

    </div>
@endsection
