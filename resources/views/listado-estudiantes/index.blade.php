@extends('layouts.app-sidebar')

@section('header', 'Listado de Estudiantes')

@section('slot')

<div class="bg-white rounded-xl shadow p-6 max-w-lg">
    <h3 class="font-semibold text-gray-700 mb-1">Descargar listado de matriculados</h3>
    <p class="text-xs text-gray-400 mb-6">Exporta en Excel el listado con código, nombre, curso y sede. Usa los filtros para acotar el resultado; déjalos vacíos para descargar todos.</p>

    <form method="POST" action="{{ route('listado-estudiantes.exportar') }}" class="space-y-4">
        @csrf

        <div>
            <label class="block text-xs text-gray-500 mb-1 font-medium">Sede <span class="text-gray-400 font-normal">(opcional)</span></label>
            <select name="sede" class="w-full border rounded-lg px-3 py-2 text-sm">
                <option value="">Todas las sedes</option>
                @foreach($sedes as $s)
                <option value="{{ $s }}">{{ $s }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-xs text-gray-500 mb-1 font-medium">Curso <span class="text-gray-400 font-normal">(opcional)</span></label>
            <select name="curso" class="w-full border rounded-lg px-3 py-2 text-sm">
                <option value="">Todos los cursos</option>
                @foreach($cursos as $c)
                <option value="{{ $c }}">{{ $c }}</option>
                @endforeach
            </select>
        </div>

        <button type="submit"
            class="w-full bg-blue-700 text-white rounded-xl py-3 font-semibold text-sm hover:bg-blue-800 transition">
            ⬇️ Descargar Excel (.xlsx)
        </button>
    </form>
</div>

@endsection
