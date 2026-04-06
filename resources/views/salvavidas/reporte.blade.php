@extends('layouts.app-sidebar')

@section('header', 'Reporte de Salvavidas')

@section('slot')

    <div class="bg-white rounded-xl shadow p-5 mb-6">
        <form method="GET" action="{{ route('salvavidas.reporte') }}">
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 items-end">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Año</label>
                    <input type="number" name="anio" value="{{ $anio }}" min="2024" max="2030"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Período</label>
                    <select name="periodo"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Todos</option>
                        @foreach([1,2,3,4] as $p)
                            <option value="{{ $p }}" {{ $periodo == $p ? 'selected' : '' }}>Período {{ $p }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Curso</label>
                    <select name="curso"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Todos</option>
                        @foreach($cursos as $c)
                            <option value="{{ $c }}" {{ $cursoFiltro == $c ? 'selected' : '' }}>{{ $c }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Estudiante</label>
                    <div class="flex gap-2">
                        <input type="text" name="busqueda" value="{{ $busqueda }}" placeholder="Apellido o nombre..."
                            class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <button type="submit"
                            class="bg-orange-700 hover:bg-orange-600 text-white text-sm font-semibold px-4 py-2 rounded-lg transition">
                            Filtrar
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-xl shadow overflow-hidden">
        <div class="px-5 py-3 bg-orange-700 text-white flex items-center justify-between">
            <h3 class="font-bold text-sm uppercase tracking-wide">🏊 Salvavidas {{ $anio }}</h3>
            <span class="text-orange-200 text-xs">{{ $registros->count() }} registros</span>
        </div>

        @if($registros->isEmpty())
            <div class="px-5 py-8 text-center text-gray-400 text-sm">Sin registros para los filtros seleccionados.</div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                    <tr>
                        <th class="px-4 py-3 text-left">Estudiante</th>
                        <th class="px-4 py-3 text-center w-16">Curso</th>
                        <th class="px-4 py-3 text-center w-24">Período</th>
                        <th class="px-4 py-3 text-left">Materia</th>
                        <th class="px-4 py-3 text-left">Reportado por</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($registros as $r)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2 font-medium">
                            {{ $r->APELLIDO1 }} {{ $r->APELLIDO2 }} {{ $r->NOMBRE1 }} {{ $r->NOMBRE2 }}
                        </td>
                        <td class="px-4 py-2 text-center text-gray-500">{{ $r->CURSO }}</td>
                        <td class="px-4 py-2 text-center">
                            <span class="bg-orange-100 text-orange-700 font-semibold text-xs px-2 py-0.5 rounded-full">P{{ $r->PERIODO }}</span>
                        </td>
                        <td class="px-4 py-2 text-gray-700">{{ $r->NOMBRE_MAT }}</td>
                        <td class="px-4 py-2 text-gray-500 text-xs">{{ $r->NOMBRE_DOC ?? $r->CODIGO_DOC }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

@endsection
