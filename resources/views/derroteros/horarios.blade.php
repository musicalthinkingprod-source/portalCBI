@extends('layouts.app-sidebar')
@section('header', 'Horarios de Recuperación')
@section('slot')

    @if(session('success'))
        <div class="mb-4 p-3 bg-green-100 text-green-800 rounded-xl text-sm">✅ {{ session('success') }}</div>
    @endif

    <div class="bg-white rounded-xl shadow p-5 mb-6">
        <form method="GET" action="{{ route('derroteros.horarios') }}">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-end">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Año</label>
                    <input type="number" name="anio" value="{{ $anio }}" min="2024" max="2030"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Período</label>
                    <select name="periodo" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @foreach([1,2,3,4] as $p)
                            <option value="{{ $p }}" {{ $periodo == $p ? 'selected' : '' }}>Período {{ $p }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Curso</label>
                    <div class="flex gap-2">
                        <select name="curso" class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Todos</option>
                            @foreach($cursos as $c)
                                <option value="{{ $c }}" {{ $cursoFiltro == $c ? 'selected' : '' }}>{{ $c }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="bg-blue-800 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition">
                            Filtrar
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-xl shadow overflow-hidden">
        <div class="px-5 py-3 bg-blue-800 text-white flex items-center justify-between">
            <h3 class="font-bold text-sm uppercase tracking-wide">📅 Horarios de recuperación — Período {{ $periodo }} · {{ $anio }}</h3>
            <span class="text-blue-300 text-xs">{{ $registros->count() }} registros</span>
        </div>

        @if($registros->isEmpty())
            <div class="px-5 py-8 text-center text-gray-400 text-sm">
                Sin derroteros registrados para este período. Los horarios aparecen aquí una vez que los docentes dan resolución a los derroteros.
            </div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                    <tr>
                        <th class="px-4 py-3 text-left">Estudiante</th>
                        <th class="px-4 py-3 text-center w-16">Curso</th>
                        <th class="px-4 py-3 text-left">Materia</th>
                        <th class="px-4 py-3 text-center w-28">Estado</th>
                        <th class="px-4 py-3 text-left">Horario de recuperación</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($registros as $r)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2 font-medium">
                            {{ $r->APELLIDO1 }} {{ $r->APELLIDO2 }} {{ $r->NOMBRE1 }} {{ $r->NOMBRE2 }}
                        </td>
                        <td class="px-4 py-2 text-center text-gray-500">{{ $r->CURSO }}</td>
                        <td class="px-4 py-2 text-gray-700">{{ $r->NOMBRE_MAT }}</td>
                        <td class="px-4 py-2 text-center">
                            @php
                                $badge = match($r->RESOLUCION) {
                                    'RECUPERO'    => ['bg-green-100 text-green-700', 'Recuperó'],
                                    'NO_RECUPERO' => ['bg-red-100 text-red-700', 'No recuperó'],
                                    'INTERMEDIO'  => ['bg-blue-100 text-blue-700', 'Intermedia'],
                                    default       => ['bg-yellow-100 text-yellow-700', 'Pendiente'],
                                };
                            @endphp
                            <span class="inline-block {{ $badge[0] }} text-xs font-semibold px-2 py-0.5 rounded-full">{{ $badge[1] }}</span>
                        </td>
                        <td class="px-4 py-2">
                            @if($esSuperior)
                            <form method="POST" action="{{ route('derroteros.horario.guardar') }}" class="flex gap-2 items-center">
                                @csrf
                                <input type="hidden" name="id" value="{{ $r->id }}">
                                <input type="text" name="horario" value="{{ $r->HORARIO }}"
                                    placeholder="Ej: Martes 3pm - Sala 201"
                                    class="flex-1 border border-gray-300 rounded-lg px-2 py-1 text-xs focus:outline-none focus:ring-2 focus:ring-blue-400 min-w-0">
                                <button type="submit" class="bg-blue-700 hover:bg-blue-600 text-white text-xs font-semibold px-2 py-1 rounded-lg transition whitespace-nowrap">
                                    Guardar
                                </button>
                            </form>
                            @else
                                <span class="text-gray-600 text-xs">{{ $r->HORARIO ?: '—' }}</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

@endsection
