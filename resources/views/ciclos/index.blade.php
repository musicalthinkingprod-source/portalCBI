@extends('layouts.app-sidebar')

@section('header', 'Control de Digitación por Ciclo')

@section('slot')

    @if(session('success'))
        <div class="mb-4 p-3 bg-green-100 text-green-800 rounded-xl text-sm">✅ {{ session('success') }}</div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">

        {{-- Panel izquierdo: definir ciclos --}}
        <div class="lg:col-span-1">
            <div class="bg-white rounded-xl shadow p-5">
                <h3 class="font-bold text-gray-700 mb-4 text-sm uppercase tracking-wide">Definir ciclos</h3>

                <form method="POST" action="{{ route('ciclos.store') }}" class="space-y-3">
                    @csrf
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Año</label>
                            <input type="number" name="anio" value="{{ old('anio', $anio) }}" min="2020" max="2099"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">N° ciclo</label>
                            <input type="number" name="numero" value="{{ old('numero', ($ciclos->max('numero') ?? 0) + 1) }}" min="1"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Nombre del ciclo</label>
                        <input type="text" name="nombre" value="{{ old('nombre') }}"
                            placeholder="Ej: Ciclo 1, Semanas 1-2…"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Desde</label>
                            <input type="date" name="fecha_inicio" value="{{ old('fecha_inicio') }}"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Hasta</label>
                            <input type="date" name="fecha_fin" value="{{ old('fecha_fin') }}"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                        </div>
                    </div>
                    <button type="submit"
                        class="w-full bg-blue-800 hover:bg-blue-700 text-white font-semibold text-sm py-2 rounded-lg transition">
                        Agregar ciclo
                    </button>
                </form>

                @if($ciclos->isNotEmpty())
                <div class="mt-5">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Ciclos {{ $anio }}</p>
                    <ul class="space-y-1">
                        @foreach($ciclos as $c)
                        <li class="flex items-center justify-between text-xs bg-gray-50 rounded-lg px-3 py-2">
                            <div>
                                <span class="font-semibold text-gray-700">{{ $c->nombre }}</span>
                                <span class="block text-gray-400">
                                    {{ \Carbon\Carbon::parse($c->fecha_inicio)->format('d/m') }}
                                    → {{ \Carbon\Carbon::parse($c->fecha_fin)->format('d/m/Y') }}
                                </span>
                            </div>
                            <form method="POST" action="{{ route('ciclos.destroy', $c->id) }}"
                                onsubmit="return confirm('¿Eliminar el ciclo «{{ $c->nombre }}»?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-400 hover:text-red-600 text-xs">✕</button>
                            </form>
                        </li>
                        @endforeach
                    </ul>
                </div>
                @endif
            </div>
        </div>

        {{-- Panel derecho: tablero de monitoreo --}}
        <div class="lg:col-span-2">

            {{-- Filtro de período --}}
            <form method="GET" action="{{ route('ciclos.index') }}" class="flex gap-3 items-end mb-4">
                <input type="hidden" name="anio" value="{{ $anio }}">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Período a monitorear</label>
                    <select name="periodo"
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                        @foreach([1,2,3,4] as $p)
                            <option value="{{ $p }}" {{ $periodo == $p ? 'selected' : '' }}>Período {{ $p }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit"
                    class="bg-blue-800 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition">
                    Ver
                </button>
            </form>

            @if($ciclos->isEmpty())
                <div class="bg-blue-50 border border-blue-200 text-blue-700 rounded-xl p-6 text-center text-sm">
                    Aún no hay ciclos definidos para {{ $anio }}. Agrégalos en el panel de la izquierda.
                </div>
            @elseif($asignaciones->isEmpty())
                <div class="bg-yellow-50 border border-yellow-200 text-yellow-700 rounded-xl p-6 text-center text-sm">
                    No hay asignaciones de docentes registradas.
                </div>
            @else

            {{-- Leyenda --}}
            <div class="flex gap-3 mb-3 text-xs">
                <span class="inline-flex items-center gap-1.5">
                    <span class="w-4 h-4 rounded bg-green-500 inline-block"></span> Registró notas en el ciclo
                </span>
                <span class="inline-flex items-center gap-1.5">
                    <span class="w-4 h-4 rounded bg-red-400 inline-block"></span> Sin registro en el ciclo
                </span>
                <span class="inline-flex items-center gap-1.5">
                    <span class="w-4 h-4 rounded bg-gray-200 inline-block"></span> Ciclo futuro
                </span>
            </div>

            <div class="bg-white rounded-xl shadow overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="text-xs border-collapse w-full" style="min-width: max-content">
                        <thead>
                            <tr class="bg-gray-800 text-white">
                                <th class="px-4 py-3 text-left font-semibold sticky left-0 bg-gray-800 z-10 min-w-[260px]">
                                    Docente / Materia / Curso
                                </th>
                                @foreach($ciclos as $ciclo)
                                @php
                                    $hoy = now()->toDateString();
                                    $estado = $hoy < $ciclo->fecha_inicio ? 'futuro'
                                            : ($hoy > $ciclo->fecha_fin ? 'pasado' : 'activo');
                                @endphp
                                <th class="px-3 py-3 text-center font-semibold min-w-[90px]
                                    {{ $estado === 'activo' ? 'bg-blue-700' : '' }}">
                                    <span>{{ $ciclo->nombre }}</span>
                                    <span class="block font-normal text-gray-300 text-xs">
                                        {{ \Carbon\Carbon::parse($ciclo->fecha_inicio)->format('d/m') }}–{{ \Carbon\Carbon::parse($ciclo->fecha_fin)->format('d/m') }}
                                    </span>
                                    @if($estado === 'activo')
                                        <span class="block text-blue-200 text-xs">● En curso</span>
                                    @endif
                                </th>
                                @endforeach
                                <th class="px-3 py-3 text-center font-semibold min-w-[70px]">% Cumpl.</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @php $docenteAnterior = null; @endphp
                            @foreach($asignaciones as $asig)
                            @php
                                $key = $asig->CODIGO_DOC . '|' . $asig->CODIGO_MAT . '|' . $asig->CURSO;
                                $ciclosPasados = $ciclos->filter(fn($c) => now()->toDateString() >= $c->fecha_inicio);
                                $cumplidos = $ciclosPasados->filter(fn($c) => ($cumplimiento[$c->id][$key] ?? false))->count();
                                $total     = $ciclosPasados->count();
                                $pct       = $total > 0 ? round($cumplidos / $total * 100) : null;
                                $nuevaFila = $docenteAnterior !== $asig->CODIGO_DOC;
                                $docenteAnterior = $asig->CODIGO_DOC;
                            @endphp
                            <tr class="hover:bg-gray-50 {{ $nuevaFila ? 'border-t-2 border-gray-300' : '' }}">
                                <td class="px-4 py-2 sticky left-0 bg-white z-10">
                                    @if($nuevaFila)
                                    <p class="font-bold text-gray-800">{{ $asig->NOMBRE_DOC ?? $asig->CODIGO_DOC }}</p>
                                    @else
                                    <p class="text-gray-300 text-xs pl-2">└</p>
                                    @endif
                                    <p class="text-gray-500 {{ $nuevaFila ? 'pl-3' : 'pl-5' }} text-xs">
                                        {{ $asig->NOMBRE_MAT }} · <span class="font-semibold">{{ $asig->CURSO }}</span>
                                    </p>
                                </td>

                                @foreach($ciclos as $ciclo)
                                @php
                                    $hoy2   = now()->toDateString();
                                    $futuro = $hoy2 < $ciclo->fecha_inicio;
                                    $cumple = $cumplimiento[$ciclo->id][$key] ?? false;
                                @endphp
                                <td class="px-2 py-2 text-center">
                                    @if($futuro)
                                        <span class="inline-block w-6 h-6 rounded bg-gray-200" title="Ciclo futuro"></span>
                                    @elseif($cumple)
                                        <span class="inline-block w-6 h-6 rounded bg-green-500 text-white text-xs leading-6 font-bold" title="Registró notas">✓</span>
                                    @else
                                        <span class="inline-block w-6 h-6 rounded bg-red-400 text-white text-xs leading-6 font-bold" title="Sin registro">✗</span>
                                    @endif
                                </td>
                                @endforeach

                                <td class="px-3 py-2 text-center font-bold
                                    {{ $pct === null ? 'text-gray-300' : ($pct >= 80 ? 'text-green-600' : ($pct >= 50 ? 'text-yellow-600' : 'text-red-600')) }}">
                                    {{ $pct !== null ? $pct . '%' : '—' }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            @endif
        </div>
    </div>

@endsection
