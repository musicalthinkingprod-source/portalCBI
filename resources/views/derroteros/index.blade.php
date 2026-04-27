@extends('layouts.app-sidebar')
@section('header', 'Derroteros')
@section('slot')

    {{-- Filtros --}}
    <div class="bg-white rounded-xl shadow p-5 mb-6">
        <form method="GET" action="{{ route('derroteros.index') }}">
            <div class="grid grid-cols-1 sm:grid-cols-6 gap-4 items-end">
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
                    <select name="curso" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Todos</option>
                        @foreach($cursos as $c)
                            <option value="{{ $c }}" {{ $cursoFiltro == $c ? 'selected' : '' }}>{{ $c }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Ordenar por</label>
                    <select name="orden" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="apellido" {{ $ordenSelec == 'apellido' ? 'selected' : '' }}>Apellido (A–Z)</option>
                        <option value="codigo"   {{ $ordenSelec == 'codigo'   ? 'selected' : '' }}>Código</option>
                        <option value="perdidas" {{ $ordenSelec == 'perdidas' ? 'selected' : '' }}>Más pérdidas</option>
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Estudiante</label>
                    <div class="flex gap-2">
                        <input type="text" name="busqueda" value="{{ $busqueda }}" placeholder="Apellido o nombre..."
                            class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <button type="submit" class="bg-blue-800 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition">
                            Filtrar
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    @if($derroteros->isEmpty())
        <div class="bg-white rounded-xl shadow p-8 text-center text-gray-400 text-sm">
            Sin derroteros para el período {{ $periodo }} de {{ $anio }}{{ $cursoFiltro ? ' en el curso ' . $cursoFiltro : '' }}.
        </div>
    @else
        <p class="text-xs text-gray-400 mb-3">{{ $derroteros->count() }} estudiantes con derroteros en el período {{ $periodo }}</p>

        @foreach($derroteros as $codigoAlum => $materias)
        @php $est = $materias->first(); @endphp
        <div class="bg-white rounded-xl shadow overflow-hidden mb-4">
            <div class="px-5 py-3 bg-gray-700 text-white flex items-center justify-between">
                <div>
                    <span class="font-mono text-xs text-gray-300 mr-2">{{ $codigoAlum }}</span>
                    <span class="font-bold text-sm">{{ $est->APELLIDO1 }} {{ $est->APELLIDO2 }} {{ $est->NOMBRE1 }} {{ $est->NOMBRE2 }}</span>
                    <span class="text-gray-300 text-xs ml-2">Curso {{ $est->CURSO }}</span>
                </div>
                <span class="text-xs text-gray-300">{{ $materias->count() }} {{ $materias->count() == 1 ? 'materia' : 'materias' }}</span>
            </div>
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                    <tr>
                        <th class="px-4 py-2 text-left">Materia</th>
                        <th class="px-4 py-2 text-center w-20">Nota</th>
                        <th class="px-4 py-2 text-center w-20">P. ant.</th>
                        <th class="px-4 py-2 text-left w-52">Elegibilidad</th>
                        <th class="px-4 py-2 text-center w-32">Asistencia</th>
                        <th class="px-4 py-2 text-center w-36">Resolución</th>
                        <th class="px-4 py-2 text-center w-20">Nota final</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($materias as $m)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2 font-medium">{{ $m->NOMBRE_MAT }}</td>
                        <td class="px-4 py-2 text-center font-bold text-red-600">{{ number_format($m->NOTA, 1) }}</td>
                        <td class="px-4 py-2 text-center text-gray-500 text-xs">{{ $m->previas_periodos }}</td>
                        <td class="px-4 py-2">
                            @if($m->elegible)
                                <span class="text-green-600 text-xs font-semibold">✅ Puede recuperar</span>
                            @else
                                <span class="text-red-500 text-xs">❌ {{ $m->razon_no_elegible }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-2 text-center">
                            @php
                                $asistBadge = match($m->asistencia ?? null) {
                                    'PRESENTO'    => ['bg-emerald-100 text-emerald-700', '✅ Presentó'],
                                    'NO_PRESENTO' => ['bg-orange-100 text-orange-700', '🚫 No presentó'],
                                    default       => ['bg-gray-100 text-gray-400', '— Sin marcar'],
                                };
                            @endphp
                            <span class="inline-block {{ $asistBadge[0] }} text-xs font-semibold px-2 py-0.5 rounded-full whitespace-nowrap">
                                {{ $asistBadge[1] }}
                            </span>
                        </td>
                        <td class="px-4 py-2 text-center">
                            @php
                                $badge = match($m->resolucion) {
                                    'RECUPERO'    => ['bg-green-100 text-green-700', 'Recuperó'],
                                    'NO_RECUPERO' => ['bg-red-100 text-red-700', 'No recuperó'],
                                    'INTERMEDIO'  => ['bg-blue-100 text-blue-700', 'Intermedia'],
                                    default       => ['bg-yellow-100 text-yellow-700', 'Sin calificar'],
                                };
                            @endphp
                            <span class="inline-block {{ $badge[0] }} text-xs font-semibold px-2 py-0.5 rounded-full">
                                {{ $badge[1] }}
                            </span>
                        </td>
                        <td class="px-4 py-2 text-center font-bold
                            {{ $m->nota_recuperacion >= 7 ? 'text-green-600' : ($m->nota_recuperacion !== null ? 'text-blue-600' : 'text-gray-400') }}">
                            {{ $m->nota_recuperacion !== null ? number_format($m->nota_recuperacion, 1) : '—' }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endforeach
    @endif

@endsection
