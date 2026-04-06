@extends('layouts.padres')

@section('header', 'English Acquisition')

@section('slot')

    {{-- Notas por período --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
        @foreach([1,2,3,4] as $p)
        @php $nota = $notas[$p] ?? 10; @endphp
        <div class="bg-white rounded-xl shadow p-4 text-center">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">Período {{ $p }}</p>
            <p class="text-3xl font-bold {{ $nota < 6 ? 'text-red-600' : ($nota < 8 ? 'text-yellow-500' : 'text-green-600') }}">
                {{ number_format($nota, 2) }}
            </p>
            <p class="text-xs text-gray-400 mt-1">/10</p>
        </div>
        @endforeach
    </div>

    {{-- Historial de descuentos --}}
    <div class="bg-white rounded-xl shadow overflow-hidden">
        <div class="px-5 py-3 bg-blue-800 text-white">
            <h3 class="font-bold text-sm uppercase tracking-wide">Historial de descuentos — {{ $anio }}</h3>
            <p class="text-blue-300 text-xs mt-0.5">Cada registro representa -0.25 puntos sobre la nota de 10</p>
        </div>

        @if(empty($detalle))
            <div class="px-5 py-8 text-center text-gray-400 text-sm">
                Sin descuentos registrados este año.
            </div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                    <tr>
                        <th class="px-4 py-3 text-center w-28">Período</th>
                        <th class="px-4 py-3 text-left">Fecha</th>
                        <th class="px-4 py-3 text-center w-28">Descuento</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($detalle as $d)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2 text-center font-semibold text-blue-700">P{{ $d['periodo'] }}</td>
                        <td class="px-4 py-2 text-gray-600">
                            {{ \Carbon\Carbon::parse($d['fecha'])->format('d/m/Y H:i') }}
                        </td>
                        <td class="px-4 py-2 text-center font-semibold text-red-600">-0.25</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

@endsection
