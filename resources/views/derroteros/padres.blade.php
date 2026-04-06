@extends('layouts.padres')
@section('header', 'Derroteros')
@section('slot')

    @if($bloqueado)
        <div class="bg-red-50 border border-red-200 rounded-xl p-6 text-center">
            <p class="text-3xl mb-3">🔒</p>
            <p class="font-semibold text-red-700 text-lg">Acceso restringido</p>
            <p class="text-sm text-red-500 mt-2">
                La consulta de notas y derroteros no está disponible mientras exista un saldo pendiente de pago.
                Comunícate con la institución para regularizar tu situación.
            </p>
        </div>
    @else

        {{-- Selector de período --}}
        <div class="flex gap-2 mb-5 flex-wrap">
            @foreach([1,2,3,4] as $p)
            <a href="{{ route('padres.derroteros', ['periodo' => $p]) }}"
                class="px-4 py-1.5 rounded-lg text-sm font-semibold transition
                    {{ $periodo == $p ? 'bg-red-700 text-white' : 'bg-white shadow text-gray-600 hover:bg-gray-50' }}">
                Período {{ $p }}
            </a>
            @endforeach
        </div>

        @if($derroteros->isEmpty())
            <div class="bg-white rounded-xl shadow p-8 text-center">
                <p class="text-4xl mb-3">✅</p>
                <p class="font-semibold text-gray-700">Sin derroteros en el período {{ $periodo }}</p>
                <p class="text-sm text-gray-400 mt-1">Tu hijo/a no perdió ninguna materia este período.</p>
            </div>
        @else
        <div class="bg-white rounded-xl shadow overflow-hidden">
            <div class="px-5 py-3 bg-red-700 text-white">
                <h3 class="font-bold text-sm uppercase tracking-wide">Período {{ $periodo }} — {{ $anio }}</h3>
                <p class="text-red-200 text-xs mt-0.5">{{ $derroteros->count() }} {{ $derroteros->count() == 1 ? 'materia con derrotero' : 'materias con derrotero' }}</p>
            </div>
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                    <tr>
                        <th class="px-4 py-3 text-left">Materia</th>
                        <th class="px-4 py-3 text-center w-20">Nota</th>
                        <th class="px-4 py-3 text-left w-40">¿Puede recuperar?</th>
                        <th class="px-4 py-3 text-center w-32">Estado</th>
                        <th class="px-4 py-3 text-left">Horario</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($derroteros as $m)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium">{{ $m->NOMBRE_MAT }}</td>
                        <td class="px-4 py-3 text-center font-bold text-red-600">{{ number_format($m->NOTA, 1) }}</td>
                        <td class="px-4 py-3 text-xs">
                            @if($m->elegible)
                                <span class="text-green-600 font-semibold">✅ Sí</span>
                            @else
                                <span class="text-red-500">❌ {{ $m->razon }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            @php
                                $badge = match($m->resolucion) {
                                    'RECUPERO'    => ['bg-green-100 text-green-700', 'Recuperó'],
                                    'NO_RECUPERO' => ['bg-red-100 text-red-700', 'No recuperó'],
                                    'INTERMEDIO'  => ['bg-blue-100 text-blue-700', 'Intermedia'],
                                    default       => ['bg-yellow-100 text-yellow-700', 'Pendiente'],
                                };
                            @endphp
                            <span class="inline-block {{ $badge[0] }} text-xs font-semibold px-2 py-0.5 rounded-full">
                                {{ $badge[1] }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-xs text-gray-500">
                            {{ $m->horario ?? '—' }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

    @endif

@endsection
