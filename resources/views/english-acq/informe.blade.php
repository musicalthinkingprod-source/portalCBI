@extends('layouts.app-sidebar')

@section('header', 'Informe English Acquisition')

@section('slot')

    @if(session('success_acq'))
        <div class="mb-4 p-3 bg-green-100 text-green-800 rounded-xl text-sm">
            ✅ {{ session('success_acq') }}
        </div>
    @endif

    {{-- Filtros --}}
    <div class="bg-white rounded-xl shadow p-5 mb-6">
        <form method="GET" action="{{ route('english-acq.informe') }}">
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
                            class="bg-blue-800 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition">
                            Filtrar
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    {{-- Resumen por estudiante --}}
    <div class="bg-white rounded-xl shadow overflow-hidden mb-6">
        <div class="px-5 py-3 bg-blue-800 text-white flex items-center justify-between">
            <div>
                <h3 class="font-bold text-sm uppercase tracking-wide">Resumen por estudiante</h3>
                <p class="text-blue-300 text-xs mt-0.5">Nota = 10 - (descuentos × 0.25)</p>
            </div>
            <span class="text-blue-300 text-xs">{{ $resumen->count() }} registros</span>
        </div>

        @if($resumen->isEmpty())
            <div class="px-5 py-8 text-center text-gray-400 text-sm">Sin datos para los filtros seleccionados.</div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                    <tr>
                        <th class="px-4 py-3 text-left">Estudiante</th>
                        <th class="px-4 py-3 text-center w-20">Curso</th>
                        <th class="px-4 py-3 text-center w-24">Período</th>
                        <th class="px-4 py-3 text-center w-28">Descuentos</th>
                        <th class="px-4 py-3 text-center w-28">Nota</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($resumen as $r)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2 font-medium">
                            {{ $r->APELLIDO1 }} {{ $r->APELLIDO2 }} {{ $r->NOMBRE1 }} {{ $r->NOMBRE2 }}
                        </td>
                        <td class="px-4 py-2 text-center text-gray-500">{{ $r->CURSO }}</td>
                        <td class="px-4 py-2 text-center font-semibold text-blue-700">P{{ $r->PERIODO }}</td>
                        <td class="px-4 py-2 text-center">
                            <span class="bg-red-100 text-red-700 font-semibold text-xs px-2 py-0.5 rounded-full">
                                {{ $r->descuentos }} × -0.25
                            </span>
                        </td>
                        <td class="px-4 py-2 text-center font-bold text-lg
                            {{ $r->nota < 6 ? 'text-red-600' : ($r->nota < 8 ? 'text-yellow-600' : 'text-green-700') }}">
                            {{ number_format($r->nota, 2) }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

    {{-- Detalle completo con docente --}}
    <div class="bg-white rounded-xl shadow overflow-hidden">
        <div class="px-5 py-3 bg-gray-700 text-white">
            <h3 class="font-bold text-sm uppercase tracking-wide">Detalle de registros</h3>
            <p class="text-gray-300 text-xs mt-0.5">Registro completo incluyendo docente que reportó</p>
        </div>

        @if($detalle->isEmpty())
            <div class="px-5 py-8 text-center text-gray-400 text-sm">Sin registros.</div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                    <tr>
                        <th class="px-4 py-3 text-left">Estudiante</th>
                        <th class="px-4 py-3 text-center w-20">Curso</th>
                        <th class="px-4 py-3 text-center w-24">Período</th>
                        <th class="px-4 py-3 text-left">Docente</th>
                        <th class="px-4 py-3 text-left w-40">Fecha y hora</th>
                        <th class="px-4 py-3 text-center w-24">Acción</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($detalle as $d)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2 font-medium">
                            {{ $d->APELLIDO1 }} {{ $d->APELLIDO2 }} {{ $d->NOMBRE1 }} {{ $d->NOMBRE2 }}
                        </td>
                        <td class="px-4 py-2 text-center text-gray-500">{{ $d->CURSO }}</td>
                        <td class="px-4 py-2 text-center font-semibold text-blue-700">P{{ $d->PERIODO }}</td>
                        <td class="px-4 py-2 text-gray-700">{{ $d->NOMBRE_DOC ?? $d->CODIGO_DOC }}</td>
                        <td class="px-4 py-2 text-gray-500 text-xs">
                            {{ \Carbon\Carbon::parse($d->FECHA)->format('d/m/Y H:i') }}
                        </td>
                        <td class="px-4 py-2 text-center">
                            <form method="POST" action="{{ route('english-acq.eliminar', $d->id) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                    onclick="return confirm('¿Eliminar este registro?')"
                                    class="text-red-400 hover:text-red-600 text-xs font-semibold transition">
                                    Eliminar
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

@endsection
