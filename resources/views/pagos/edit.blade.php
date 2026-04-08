@extends('layouts.app-sidebar')

@section('header', 'Editar Pago')

@section('slot')
    <div class="max-w-xl">
        <div class="bg-white rounded-xl shadow p-6">

            <form method="POST" action="{{ route('pagos.update', $pago->id) }}" class="space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Código alumno</label>
                    <input type="number" name="codigo_alumno" value="{{ old('codigo_alumno', $pago->codigo_alumno) }}"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @error('codigo_alumno')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fecha</label>
                    <input type="date" name="fecha" value="{{ old('fecha', $pago->fecha) }}"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @error('fecha')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Valor</label>
                    <input type="number" name="valor" value="{{ old('valor', $pago->valor) }}" step="0.01"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @error('valor')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Concepto</label>
                    <input type="text" name="concepto" value="{{ old('concepto', $pago->concepto) }}"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @error('concepto')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Mes</label>
                    <select name="mes" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Seleccionar...</option>
                        @foreach(['ENERO','FEBRERO','MARZO','ABRIL','MAYO','JUNIO','JULIO','AGOSTO','SEPTIEMBRE','OCTUBRE','NOVIEMBRE','DICIEMBRE'] as $mes)
                            <option value="{{ $mes }}" {{ old('mes', $pago->mes) === $mes ? 'selected' : '' }}>{{ $mes }}</option>
                        @endforeach
                    </select>
                    @error('mes')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Orden <span class="text-gray-400">(opcional)</span></label>
                    <input type="text" name="orden" value="{{ old('orden', $pago->orden) }}"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="submit"
                        class="flex-1 bg-blue-800 hover:bg-blue-700 text-white font-semibold py-2 rounded-lg transition text-sm">
                        Guardar cambios
                    </button>
                    <a href="{{ route('pagos.index') }}"
                        class="flex-1 text-center bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold py-2 rounded-lg transition text-sm">
                        Cancelar
                    </a>
                </div>

            </form>
        </div>
    </div>
@endsection
