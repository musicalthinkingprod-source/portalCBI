@extends('layouts.padres')

@section('header', 'Bienvenido')

@section('slot')
    @if(session('aviso'))
        <div class="mb-4 p-3 bg-yellow-100 border border-yellow-300 text-yellow-800 rounded-xl text-sm">
            🔒 {{ session('aviso') }}
        </div>
    @endif
    <div class="bg-white rounded-xl shadow p-6">
        <h3 class="text-2xl font-bold text-blue-800 mb-2">
            Hola, bienvenido al portal de padres
        </h3>
        @if(session('padre_estudiante'))
            <p class="text-gray-500">
                Estás consultando la información de
                <strong>{{ session('padre_estudiante')->NOMBRE1 }} {{ session('padre_estudiante')->NOMBRE2 }} {{ session('padre_estudiante')->APELLIDO1 }} {{ session('padre_estudiante')->APELLIDO2 }}</strong>.
            </p>
        @endif
        <p class="text-sm text-gray-400 mt-3">Selecciona una opción del menú lateral.</p>
    </div>
@endsection
