@extends('layouts.app-sidebar')

@section('header', 'Crear PIAR')

@section('slot')

    <div class="bg-white rounded-xl shadow p-5 mb-6">
        <form method="GET" action="{{ route('piar.buscar') }}">
            <label class="block text-sm font-medium text-gray-700 mb-1">Buscar estudiante por código, nombre o apellido</label>
            <div class="flex gap-3 items-center">
                <input type="text" name="buscar" value="{{ $buscar }}"
                    class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="Ej: 21008 o García" autofocus>
                <button type="submit"
                    class="bg-blue-800 hover:bg-blue-700 text-white px-5 py-2 rounded-lg text-sm font-semibold transition">
                    Buscar
                </button>
            </div>
        </form>
    </div>

    @if($hayBusqueda && $estudiantes->isEmpty())
        <div class="bg-red-100 text-red-700 rounded-xl p-4 text-sm">
            No se encontraron estudiantes matriculados con ese criterio.
        </div>
    @endif

    @if($estudiantes->isNotEmpty())
    <div class="bg-white rounded-xl shadow overflow-hidden">
        <div class="px-5 py-3 bg-blue-800 text-white flex items-center justify-between">
            <h3 class="font-bold text-sm uppercase tracking-wide">Resultados</h3>
            <span class="text-blue-300 text-xs">{{ $estudiantes->total() }} encontrados</span>
        </div>
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                <tr>
                    <th class="px-4 py-3 text-left">Código</th>
                    <th class="px-4 py-3 text-left">Estudiante</th>
                    <th class="px-4 py-3 text-left">Curso</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($estudiantes as $e)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 text-gray-500 font-mono text-xs">{{ $e->CODIGO }}</td>
                    <td class="px-4 py-3 font-medium">
                        {{ $e->APELLIDO1 }} {{ $e->APELLIDO2 }} {{ $e->NOMBRE1 }} {{ $e->NOMBRE2 }}
                    </td>
                    <td class="px-4 py-3 text-gray-500">{{ $e->CURSO ?? '—' }}</td>
                    <td class="px-4 py-3 text-right">
                        <a href="{{ route('piar.crear', $e->CODIGO) }}"
                            class="inline-block bg-blue-800 hover:bg-blue-700 text-white text-xs font-semibold px-3 py-1.5 rounded-lg transition">
                            Crear PIAR
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @if($estudiantes->hasPages())
        <div class="px-5 py-4 border-t border-gray-100">
            {{ $estudiantes->links() }}
        </div>
        @endif
    </div>
    @endif

@endsection
