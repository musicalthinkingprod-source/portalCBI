@extends('layouts.app-sidebar')

@section('header', 'Crear PIAR')

@section('slot')

    @if(session('piar_deleted'))
        <div class="mb-4 p-3 bg-red-100 text-red-800 rounded-xl text-sm">
            ✅ {{ session('piar_deleted') }}
        </div>
    @endif

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

    {{-- ── Estudiantes registrados en PIAR ──────────────────────────────── --}}
    <div class="bg-white rounded-xl shadow overflow-hidden mb-6">
        <div class="flex items-center justify-between px-5 py-3 bg-blue-800 text-white">
            <h2 class="text-sm font-bold uppercase tracking-wide">Estudiantes registrados en PIAR</h2>
            <span class="bg-white text-blue-800 text-xs font-bold px-3 py-1 rounded-full">
                Total: {{ $totalEnPiar }}
            </span>
        </div>

        @if($estudiantesEnPiar->isEmpty())
            <p class="text-sm text-gray-500 px-5 py-4">Aún no hay estudiantes con PIAR registrado.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-blue-50 text-blue-900 text-xs uppercase">
                        <tr>
                            <th class="px-4 py-2 text-left font-semibold">#</th>
                            <th class="px-4 py-2 text-left font-semibold">Estudiante</th>
                            <th class="px-4 py-2 text-center font-semibold">Curso</th>
                            <th class="px-4 py-2 text-left font-semibold">Diagnóstico</th>
                            <th class="px-4 py-2 text-center font-semibold">Acción</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($estudiantesEnPiar as $i => $est)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2 text-gray-400">{{ $i + 1 }}</td>
                            <td class="px-4 py-2 font-medium">
                                {{ $est->APELLIDO1 }} {{ $est->APELLIDO2 }}, {{ $est->NOMBRE1 }} {{ $est->NOMBRE2 }}
                            </td>
                            <td class="px-4 py-2 text-center">
                                <span class="bg-blue-100 text-blue-800 text-xs font-semibold px-2 py-0.5 rounded">
                                    {{ $est->CURSO }}
                                </span>
                            </td>
                            <td class="px-4 py-2 text-gray-600 max-w-xs truncate" title="{{ $est->DIAGNOSTICO }}">
                                {{ $est->DIAGNOSTICO ?: '—' }}
                            </td>
                            <td class="px-4 py-2 text-center">
                                <a href="{{ route('piar.crear', $est->CODIGO) }}"
                                   class="inline-block bg-blue-800 hover:bg-blue-700 text-white text-xs font-semibold px-3 py-1.5 rounded-lg transition">
                                    Editar PIAR
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

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
