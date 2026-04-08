@extends('layouts.app-sidebar')

@section('header', 'Editar Factura')

@section('slot')
    <div class="max-w-xl">
        <div class="bg-white rounded-xl shadow p-6">

            <form method="POST" action="{{ route('facturacion.update', $factura->id) }}" class="space-y-4" id="form-factura">
                @csrf
                @method('PUT')

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Código alumno</label>
                    <input type="number" name="codigo_alumno" value="{{ old('codigo_alumno', $factura->codigo_alumno) }}"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @error('codigo_alumno')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Concepto</label>
                    <input type="text" name="concepto" value="{{ old('concepto', $factura->concepto) }}" id="concepto"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                        oninput="toggleOtro()">
                    @error('concepto')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <div id="campo-otro" class="{{ old('concepto', $factura->concepto) === 'OTRO' ? '' : 'hidden' }}">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Especificar concepto</label>
                    <input type="text" name="concepto_otro" value="{{ old('concepto_otro', $factura->concepto_otro) }}"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Valor</label>
                    <input type="number" name="valor" value="{{ old('valor', $factura->valor) }}" step="0.01"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @error('valor')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Mes</label>
                    <select name="mes" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Seleccionar...</option>
                        @foreach(['ENERO','FEBRERO','MARZO','ABRIL','MAYO','JUNIO','JULIO','AGOSTO','SEPTIEMBRE','OCTUBRE','NOVIEMBRE','DICIEMBRE'] as $mes)
                            <option value="{{ $mes }}" {{ old('mes', $factura->mes) === $mes ? 'selected' : '' }}>{{ $mes }}</option>
                        @endforeach
                    </select>
                    @error('mes')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Orden <span class="text-gray-400">(opcional)</span></label>
                        <input type="number" name="orden" value="{{ old('orden', $factura->orden) }}"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Código concepto <span class="text-gray-400">(opcional)</span></label>
                        <input type="text" name="codigo_concepto" value="{{ old('codigo_concepto', $factura->codigo_concepto) }}"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Centro de costos <span class="text-gray-400">(opcional)</span></label>
                    <input type="text" name="centro_costos" value="{{ old('centro_costos', $factura->centro_costos) }}"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fecha</label>
                    <input type="date" name="fecha" value="{{ old('fecha', $factura->fecha) }}"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @error('fecha')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="submit"
                        class="flex-1 bg-blue-800 hover:bg-blue-700 text-white font-semibold py-2 rounded-lg transition text-sm">
                        Guardar cambios
                    </button>
                    <a href="{{ route('facturacion.index') }}"
                        class="flex-1 text-center bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold py-2 rounded-lg transition text-sm">
                        Cancelar
                    </a>
                </div>

            </form>
        </div>
    </div>

    <script>
        function toggleOtro() {
            const val = document.getElementById('concepto').value.toUpperCase();
            document.getElementById('campo-otro').classList.toggle('hidden', val !== 'OTRO');
        }
    </script>
@endsection
