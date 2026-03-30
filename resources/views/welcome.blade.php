@extends('layouts.app-sidebar')

@section('header', 'Inicio')

@section('slot')
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- Bienvenida -->


        <!-- Formulario verificación padres -->
        <div class="bg-white rounded-xl shadow p-6">
            <h3 class="text-lg font-bold text-blue-800 mb-1">Consulta para padres</h3>
            <p class="text-sm text-gray-500 mb-4">Ingresa tu cédula y el código del estudiante para verificar tu acceso.</p>

            @if ($errors->has('verificacion'))
                <div class="mb-4 p-3 bg-red-100 text-red-700 rounded-lg text-sm">
                    {{ $errors->first('verificacion') }}
                </div>
            @endif

            @if (session('verificado'))
                <div class="mb-4 p-3 bg-green-100 text-green-700 rounded-lg text-sm">
                    ✅ Verificación exitosa. Estudiante: <strong>{{ session('estudiante')->NOMBRE1 }} {{ session('estudiante')->APELLIDO1 }}</strong>
                </div>
            @endif

            <form method="POST" action="{{ route('padre.verificar') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Cédula del acudiente</label>
                    <input type="number" name="cedula" value="{{ old('cedula') }}"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Ej: 12345678" required>
                    @error('cedula')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Código del estudiante</label>
                    <input type="number" name="codigo" value="{{ old('codigo') }}"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Ej: 24512" required>
                    @error('codigo')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <button type="submit"
                    class="w-full bg-blue-800 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg transition text-sm">
                    Verificar
                </button>
            </form>
        </div>

    </div>
@endsection
