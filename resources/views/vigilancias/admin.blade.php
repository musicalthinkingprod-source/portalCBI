@extends('layouts.app-sidebar')

@section('header', 'Gestión de Vigilancias')

@section('slot')
<div class="max-w-full space-y-8">

    {{-- Selector de año --}}
    <form method="GET" action="{{ route('vigilancias.admin') }}" class="flex items-center gap-3">
        <label class="text-sm font-medium text-gray-600">Año:</label>
        <select name="anio" onchange="this.form.submit()"
            class="rounded-lg border-gray-300 text-sm shadow-sm focus:ring-blue-500 focus:border-blue-500 py-1.5 px-3">
            @for($y = date('Y'); $y >= date('Y') - 2; $y--)
                <option value="{{ $y }}" {{ $anio == $y ? 'selected' : '' }}>{{ $y }}</option>
            @endfor
        </select>
    </form>

    {{-- ============================================================
         SECCIÓN 1: CALENDARIO DE CICLO
    ============================================================ --}}
    <div class="bg-white rounded-xl shadow p-5">
        <h2 class="text-base font-semibold text-blue-900 mb-4">Calendario del ciclo {{ $anio }}</h2>

        @if(session('success_cal'))
            <div class="mb-4 rounded-lg bg-green-50 border border-green-200 px-4 py-2 text-sm text-green-700">
                {{ session('success_cal') }}
            </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

            {{-- Formulario para agregar fecha --}}
            <div>
                <h3 class="text-sm font-semibold text-gray-600 mb-3">Asignar fecha al ciclo</h3>
                <form method="POST" action="{{ route('vigilancias.calendario.guardar') }}" class="space-y-3">
                    @csrf
                    <div class="flex gap-3 items-end flex-wrap">
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Fecha</label>
                            <input type="date" name="fecha" required
                                class="rounded-lg border-gray-300 text-sm shadow-sm focus:ring-blue-500 focus:border-blue-500 py-1.5 px-3">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Día del ciclo</label>
                            <select name="dia_ciclo" required
                                class="rounded-lg border-gray-300 text-sm shadow-sm focus:ring-blue-500 focus:border-blue-500 py-1.5 px-3">
                                @for($d = 1; $d <= 6; $d++)
                                    <option value="{{ $d }}">Día {{ $d }}</option>
                                @endfor
                            </select>
                        </div>
                        <button type="submit"
                            class="bg-blue-700 hover:bg-blue-800 text-white text-sm font-medium px-4 py-2 rounded-lg transition">
                            Guardar
                        </button>
                    </div>
                    @error('fecha') <p class="text-red-600 text-xs">{{ $message }}</p> @enderror
                    @error('dia_ciclo') <p class="text-red-600 text-xs">{{ $message }}</p> @enderror
                </form>
            </div>

            {{-- Lista de fechas registradas --}}
            <div>
                <h3 class="text-sm font-semibold text-gray-600 mb-3">Fechas registradas</h3>
                @if($calendario->isEmpty())
                    <p class="text-sm text-gray-400 italic">No hay fechas registradas para {{ $anio }}.</p>
                @else
                    <div class="overflow-x-auto overflow-y-auto max-h-64 rounded-lg border border-gray-200">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-100 sticky top-0">
                                <tr>
                                    <th class="px-3 py-2 text-left font-medium text-gray-600">Fecha</th>
                                    <th class="px-3 py-2 text-center font-medium text-gray-600">Día ciclo</th>
                                    <th class="px-3 py-2"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($calendario as $cal)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-3 py-2 text-gray-700">
                                            {{ \Carbon\Carbon::parse($cal->fecha)->isoFormat('ddd D MMM YYYY') }}
                                        </td>
                                        <td class="px-3 py-2 text-center">
                                            <span class="inline-block bg-blue-100 text-blue-800 text-xs font-bold px-2 py-0.5 rounded-full">
                                                Día {{ $cal->dia_ciclo }}
                                            </span>
                                        </td>
                                        <td class="px-3 py-2 text-right">
                                            <form method="POST"
                                                action="{{ route('vigilancias.calendario.eliminar', $cal->id) }}"
                                                onsubmit="return confirm('¿Eliminar esta fecha?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="text-red-500 hover:text-red-700 text-xs font-medium">
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
        </div>
    </div>

    {{-- ============================================================
         SECCIÓN 2: TABLA DE ASIGNACIONES
    ============================================================ --}}
    <div class="bg-white rounded-xl shadow p-5">
        <h2 class="text-base font-semibold text-blue-900 mb-1">Asignaciones de posiciones {{ $anio }}</h2>
        <p class="text-xs text-gray-400 mb-4">
            Escribe la posición de cada docente (ej: <strong>20A</strong>, <strong>5B</strong>). Deja en blanco para sin asignación.
        </p>

        @if(session('success'))
            <div class="mb-4 rounded-lg bg-green-50 border border-green-200 px-4 py-2 text-sm text-green-700">
                {{ session('success') }}
            </div>
        @endif

        @if($docentes->isEmpty())
            <p class="text-sm text-gray-400 italic">No hay docentes activos registrados.</p>
        @else
        <form method="POST" action="{{ route('vigilancias.asignaciones.guardar') }}">
            @csrf
            <input type="hidden" name="anio" value="{{ $anio }}">

            <div class="overflow-x-auto rounded-lg border border-gray-200">
                <table class="min-w-full text-sm">
                    <thead class="bg-blue-900 text-white">
                        <tr>
                            <th class="px-3 py-2 text-left font-medium sticky left-0 bg-blue-900 z-10 min-w-[160px]">
                                Docente
                            </th>
                            @for($dia = 1; $dia <= 6; $dia++)
                                <th class="px-2 py-2 text-center font-medium text-xs" colspan="2">
                                    Día {{ $dia }}
                                </th>
                            @endfor
                        </tr>
                        <tr class="bg-blue-800 text-blue-100 text-xs">
                            <th class="px-3 py-1 sticky left-0 bg-blue-800 z-10"></th>
                            @for($dia = 1; $dia <= 6; $dia++)
                                <th class="px-2 py-1 text-center font-normal">D1</th>
                                <th class="px-2 py-1 text-center font-normal border-r border-blue-700">D2</th>
                            @endfor
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($docentes as $doc)
                            <tr class="hover:bg-gray-50">
                                <td class="px-3 py-2 font-medium text-gray-700 sticky left-0 bg-white z-10 whitespace-nowrap border-r border-gray-200">
                                    {{ $doc->NOMBRE_DOC }}
                                </td>
                                @for($dia = 1; $dia <= 6; $dia++)
                                    @foreach([1, 2] as $desc)
                                        @php
                                            $val = $matriz[$doc->CODIGO_DOC][$dia][$desc] ?? '';
                                        @endphp
                                        <td class="px-1 py-1 {{ $desc === 2 ? 'border-r border-gray-200' : '' }}">
                                            <input
                                                type="text"
                                                name="asignaciones[{{ $doc->CODIGO_DOC }}][{{ $dia }}][{{ $desc }}]"
                                                value="{{ $val }}"
                                                maxlength="10"
                                                placeholder="—"
                                                class="w-14 text-center text-xs rounded border border-gray-300 py-1 px-1 focus:ring-1 focus:ring-blue-400 focus:border-blue-400 uppercase"
                                            >
                                        </td>
                                    @endforeach
                                @endfor
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4 flex justify-end">
                <button type="submit"
                    class="bg-blue-700 hover:bg-blue-800 text-white text-sm font-medium px-6 py-2 rounded-lg transition">
                    Guardar asignaciones
                </button>
            </div>
        </form>
        @endif
    </div>

</div>
@endsection
