@extends('layouts.app-sidebar')
@section('header', 'Estadísticas de recuperaciones')
@section('slot')

    @php
        $pct = function ($n, $total) { return $total > 0 ? round($n * 100 / $total, 1) : 0; };
    @endphp

    {{-- Filtros --}}
    <div class="bg-white rounded-xl shadow p-5 mb-6">
        <form method="GET" action="{{ route('derroteros.estadisticas') }}">
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 items-end">
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
                <div class="sm:col-span-2 flex justify-end">
                    <button type="submit" class="bg-blue-800 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition">
                        Actualizar
                    </button>
                </div>
            </div>
        </form>
    </div>

    {{-- Resumen general --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
        <div class="bg-white rounded-xl shadow p-4">
            <p class="text-xs uppercase tracking-wide text-gray-500">Total derroteros</p>
            <p class="text-3xl font-bold text-gray-800 mt-1">{{ $resumen->total }}</p>
            <p class="text-xs text-gray-400 mt-1">{{ $resumen->elegibles }} elegibles · {{ $resumen->no_elegibles }} no elegibles</p>
        </div>
        <div class="bg-white rounded-xl shadow p-4">
            <p class="text-xs uppercase tracking-wide text-gray-500">Con franja</p>
            <p class="text-3xl font-bold text-indigo-700 mt-1">{{ $resumen->con_franja }}</p>
            <p class="text-xs text-gray-400 mt-1">{{ $pct($resumen->con_franja, $resumen->total) }}% del total</p>
        </div>
        <div class="bg-white rounded-xl shadow p-4">
            <p class="text-xs uppercase tracking-wide text-gray-500">Sin franja</p>
            <p class="text-3xl font-bold text-amber-600 mt-1">{{ $resumen->sin_franja }}</p>
            <p class="text-xs text-gray-400 mt-1">{{ $pct($resumen->sin_franja, $resumen->total) }}% del total</p>
        </div>
        <div class="bg-white rounded-xl shadow p-4">
            <p class="text-xs uppercase tracking-wide text-gray-500">Pendientes calificación</p>
            <p class="text-3xl font-bold text-yellow-600 mt-1">{{ $resumen->pendiente }}</p>
            <p class="text-xs text-gray-400 mt-1">{{ $pct($resumen->pendiente, $resumen->elegibles) }}% de elegibles</p>
        </div>
    </div>

    {{-- Asistencia --}}
    <div class="bg-white rounded-xl shadow p-5 mb-6">
        <h3 class="text-sm font-bold uppercase tracking-wide text-gray-700 mb-3">Asistencia (sobre {{ $resumen->elegibles }} elegibles)</h3>
        @php $eg = max($resumen->elegibles, 1); @endphp
        <div class="flex w-full h-6 rounded-full overflow-hidden bg-gray-100 mb-3">
            <div class="bg-emerald-500"   style="width: {{ $pct($resumen->presento,    $eg) }}%"></div>
            <div class="bg-orange-500"    style="width: {{ $pct($resumen->no_presento, $eg) }}%"></div>
            <div class="bg-gray-300"      style="width: {{ $pct($resumen->sin_asist,   $eg) }}%"></div>
        </div>
        <div class="grid grid-cols-3 gap-3 text-sm">
            <div>
                <p class="text-emerald-700 font-semibold">✅ Presentó</p>
                <p class="text-2xl font-bold">{{ $resumen->presento }} <span class="text-sm text-gray-400">({{ $pct($resumen->presento, $eg) }}%)</span></p>
            </div>
            <div>
                <p class="text-orange-700 font-semibold">🚫 No presentó</p>
                <p class="text-2xl font-bold">{{ $resumen->no_presento }} <span class="text-sm text-gray-400">({{ $pct($resumen->no_presento, $eg) }}%)</span></p>
            </div>
            <div>
                <p class="text-gray-500 font-semibold">— Sin marcar</p>
                <p class="text-2xl font-bold">{{ $resumen->sin_asist }} <span class="text-sm text-gray-400">({{ $pct($resumen->sin_asist, $eg) }}%)</span></p>
            </div>
        </div>
    </div>

    {{-- Calificación --}}
    <div class="bg-white rounded-xl shadow p-5 mb-6">
        <h3 class="text-sm font-bold uppercase tracking-wide text-gray-700 mb-3">Calificación (sobre {{ $resumen->elegibles }} elegibles)</h3>
        <div class="flex w-full h-6 rounded-full overflow-hidden bg-gray-100 mb-3">
            <div class="bg-green-500"  style="width: {{ $pct($resumen->recupero,    $eg) }}%"></div>
            <div class="bg-blue-500"   style="width: {{ $pct($resumen->intermedio,  $eg) }}%"></div>
            <div class="bg-red-500"    style="width: {{ $pct($resumen->no_recupero, $eg) }}%"></div>
            <div class="bg-yellow-300" style="width: {{ $pct($resumen->pendiente,   $eg) }}%"></div>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-sm">
            <div>
                <p class="text-green-700 font-semibold">✅ Recuperó</p>
                <p class="text-2xl font-bold">{{ $resumen->recupero }} <span class="text-sm text-gray-400">({{ $pct($resumen->recupero, $eg) }}%)</span></p>
            </div>
            <div>
                <p class="text-blue-700 font-semibold">💙 Intermedia</p>
                <p class="text-2xl font-bold">{{ $resumen->intermedio }} <span class="text-sm text-gray-400">({{ $pct($resumen->intermedio, $eg) }}%)</span></p>
            </div>
            <div>
                <p class="text-red-700 font-semibold">❌ No recuperó</p>
                <p class="text-2xl font-bold">{{ $resumen->no_recupero }} <span class="text-sm text-gray-400">({{ $pct($resumen->no_recupero, $eg) }}%)</span></p>
            </div>
            <div>
                <p class="text-yellow-700 font-semibold">⏳ Pendiente</p>
                <p class="text-2xl font-bold">{{ $resumen->pendiente }} <span class="text-sm text-gray-400">({{ $pct($resumen->pendiente, $eg) }}%)</span></p>
            </div>
        </div>
    </div>

    {{-- Por franja --}}
    <div class="bg-white rounded-xl shadow overflow-hidden">
        <div class="px-5 py-3 bg-indigo-700 text-white">
            <h3 class="font-bold text-sm uppercase tracking-wide">Distribución por franja horaria</h3>
        </div>
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                <tr>
                    <th class="px-4 py-2 text-left w-32">Franja</th>
                    <th class="px-4 py-2 text-left">Horario</th>
                    <th class="px-4 py-2 text-center w-16">Total</th>
                    <th class="px-4 py-2 text-center w-24">Presentó</th>
                    <th class="px-4 py-2 text-center w-24">No present.</th>
                    <th class="px-4 py-2 text-center w-24">Sin marcar</th>
                    <th class="px-4 py-2 text-center w-24">Recuperó</th>
                    <th class="px-4 py-2 text-center w-24">Intermedia</th>
                    <th class="px-4 py-2 text-center w-24">No recup.</th>
                    <th class="px-4 py-2 text-center w-24">Pendiente</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($porFranja as $f => $d)
                @php $b = max($d->total, 1); @endphp
                <tr class="hover:bg-gray-50 {{ $d->total === 0 ? 'opacity-50' : '' }}">
                    <td class="px-4 py-2 font-bold text-indigo-700">F{{ $f }}</td>
                    <td class="px-4 py-2 text-xs text-gray-600">{{ $d->rango }}</td>
                    <td class="px-4 py-2 text-center font-bold">{{ $d->total }}</td>
                    <td class="px-4 py-2 text-center text-emerald-700">
                        {{ $d->presento }}
                        @if($d->total > 0)<span class="text-gray-400 text-xs">({{ $pct($d->presento, $b) }}%)</span>@endif
                    </td>
                    <td class="px-4 py-2 text-center text-orange-700">
                        {{ $d->no_presento }}
                        @if($d->total > 0)<span class="text-gray-400 text-xs">({{ $pct($d->no_presento, $b) }}%)</span>@endif
                    </td>
                    <td class="px-4 py-2 text-center text-gray-500">
                        {{ $d->sin_marcar }}
                        @if($d->total > 0)<span class="text-gray-400 text-xs">({{ $pct($d->sin_marcar, $b) }}%)</span>@endif
                    </td>
                    <td class="px-4 py-2 text-center text-green-700">
                        {{ $d->recupero }}
                        @if($d->total > 0)<span class="text-gray-400 text-xs">({{ $pct($d->recupero, $b) }}%)</span>@endif
                    </td>
                    <td class="px-4 py-2 text-center text-blue-700">
                        {{ $d->intermedio }}
                        @if($d->total > 0)<span class="text-gray-400 text-xs">({{ $pct($d->intermedio, $b) }}%)</span>@endif
                    </td>
                    <td class="px-4 py-2 text-center text-red-700">
                        {{ $d->no_recupero }}
                        @if($d->total > 0)<span class="text-gray-400 text-xs">({{ $pct($d->no_recupero, $b) }}%)</span>@endif
                    </td>
                    <td class="px-4 py-2 text-center text-yellow-700">
                        {{ $d->pendiente }}
                        @if($d->total > 0)<span class="text-gray-400 text-xs">({{ $pct($d->pendiente, $b) }}%)</span>@endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

@endsection
