@extends('layouts.app-sidebar')

@section('header', 'Dashboard')

@section('slot')
    <div class="bg-white rounded-xl shadow p-6">
        <h3 class="text-2xl font-bold text-blue-800 mb-2">¡Bienvenido!</h3>
        <p class="text-gray-500">Has iniciado sesión correctamente en el Portal CBI.</p>
    </div>
@endsection
