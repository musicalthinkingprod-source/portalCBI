@extends('layouts.app-sidebar')

@section('header', 'Control de Fechas')

@section('slot')

    @if(session('success_fechas'))
        <div class="mb-4 p-3 bg-green-100 text-green-800 rounded-xl text-sm">✅ {{ session('success_fechas') }}</div>
    @endif
    @if($errors->any())
        <div class="mb-4 p-3 bg-red-100 text-red-700 rounded-xl text-sm">⚠️ {{ $errors->first() }}</div>
    @endif

    @php $now = now(); @endphp

    @foreach($grupos as $prefix => $grupo)
    <div class="bg-white rounded-xl shadow overflow-hidden mb-6">
        <div class="px-5 py-3 bg-blue-800 text-white">
            <h3 class="font-bold text-sm uppercase tracking-wide">{{ $grupo['icon'] }} {{ $grupo['label'] }}</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                    <tr>
                        <th class="px-4 py-3 text-left w-24">Código</th>
                        <th class="px-4 py-3 text-left">Inicio</th>
                        <th class="px-4 py-3 text-left">Fin</th>
                        <th class="px-4 py-3 text-center w-28">Estado</th>
                        <th class="px-4 py-3 w-32"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach([1,2,3,4] as $num)
                    @php
                        $cod    = $prefix . $num;
                        $fecha  = $fechas[$cod] ?? null;
                        $activo = $fecha && $now >= $fecha->INICIO && $now <= $fecha->FIN;
                        $pasado = $fecha && $now > $fecha->FIN;
                    @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2 font-mono font-bold text-blue-700">{{ $cod }}</td>
                        <td class="px-4 py-2">
                            <form method="POST" action="{{ route('admin.fechas.upsert') }}" class="flex gap-2 items-center" id="form-{{ $cod }}">
                                @csrf
                                <input type="hidden" name="CODIGO_FECHA" value="{{ $cod }}">
                                <input type="datetime-local" name="INICIO"
                                    value="{{ $fecha ? \Carbon\Carbon::parse($fecha->INICIO)->format('Y-m-d\TH:i') : '' }}"
                                    class="border border-gray-300 rounded-lg px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </td>
                        <td class="px-4 py-2">
                                <input type="datetime-local" name="FIN"
                                    value="{{ $fecha ? \Carbon\Carbon::parse($fecha->FIN)->format('Y-m-d\TH:i') : '' }}"
                                    class="border border-gray-300 rounded-lg px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </form>
                        </td>
                        <td class="px-4 py-2 text-center">
                            @if(!$fecha)
                                <span class="text-gray-300 text-xs">Sin configurar</span>
                            @elseif($activo)
                                <span class="px-2 py-0.5 rounded-full bg-green-100 text-green-700 text-xs font-semibold">● Activo</span>
                            @elseif($pasado)
                                <span class="px-2 py-0.5 rounded-full bg-gray-100 text-gray-500 text-xs">Cerrado</span>
                            @else
                                <span class="px-2 py-0.5 rounded-full bg-yellow-100 text-yellow-700 text-xs">Pendiente</span>
                            @endif
                        </td>
                        <td class="px-4 py-2 text-right">
                            <div class="flex gap-2 justify-end">
                                <button type="submit" form="form-{{ $cod }}"
                                    class="bg-blue-800 hover:bg-blue-700 text-white text-xs font-semibold px-3 py-1.5 rounded-lg transition">
                                    Guardar
                                </button>
                                @if($fecha)
                                <form method="POST" action="{{ route('admin.fechas.destroy', $cod) }}"
                                      onsubmit="return confirm('¿Eliminar la fecha {{ $cod }}?')">
                                    @csrf @method('DELETE')
                                    <button type="submit"
                                        class="text-red-500 hover:text-red-700 text-xs font-semibold px-2 py-1.5 rounded hover:bg-red-50 transition">
                                        Borrar
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endforeach

@endsection
