@extends('layouts.app-sidebar')
@section('header', 'Suplencias programadas del día')

@section('slot')
@php Carbon\Carbon::setLocale('es'); @endphp

<div class="space-y-5">

    {{-- Selector fecha --}}
    <div class="flex items-center gap-4 flex-wrap">
        <form method="GET" class="flex items-center gap-3">
            <input type="date" name="fecha" value="{{ $fecha }}"
                class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-blue-500"
                onchange="this.form.submit()">
        </form>
        <span class="text-sm text-gray-500">
            {{ \Carbon\Carbon::parse($fecha)->locale('es')->isoFormat('dddd D [de] MMMM') }}
            @if($diaAcademico)
                · <span class="font-semibold text-blue-700">Día académico {{ $diaAcademico }}</span>
            @else
                · <span class="text-gray-400 italic">Sin día académico registrado</span>
            @endif
        </span>
        <span class="text-xs text-gray-400">
            El número junto a cada docente = suplencias en {{ \Carbon\Carbon::parse($fecha)->locale('es')->isoFormat('MMMM YYYY') }}
        </span>
    </div>

    @if($suplencias->isEmpty())
        <div class="bg-gray-50 border border-gray-200 rounded-xl p-5 text-sm text-gray-500">
            No hay suplencias programadas para esta fecha.
        </div>
    @else
    <div class="bg-white rounded-xl shadow overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-xs uppercase text-gray-400 bg-gray-50 border-b border-gray-100">
                    <th class="px-4 py-2 text-left w-28">Hora</th>
                    <th class="px-4 py-2 text-center w-20">Curso</th>
                    <th class="px-4 py-2 text-left w-40">Materia</th>
                    <th class="px-4 py-2 text-left">Docente ausente</th>
                    <th class="px-4 py-2 text-left">Reemplaza</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($suplencias as $s)
                @php $numMes = $reemplazosPorDocente[$s->codigo_emp_reemplazo] ?? 0; @endphp
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-semibold text-gray-600 whitespace-nowrap">
                        {{ $horas[$s->hora] ?? $s->hora.'ª hora' }}
                    </td>
                    <td class="px-4 py-3 text-center">
                        <span class="font-bold text-blue-700">{{ $s->curso }}</span>
                    </td>
                    <td class="px-4 py-3 text-gray-700">{{ $s->NOMBRE_MAT ?? '—' }}</td>
                    <td class="px-4 py-3 text-gray-700">{{ $s->nombre_ausente ?? $s->codigo_emp_ausente }}</td>
                    <td class="px-4 py-3">
                        <span class="inline-flex items-center gap-2 bg-green-100 text-green-800 font-semibold text-sm px-3 py-1.5 rounded-lg">
                            ✓ {{ $s->nombre_reemplazo ?? $s->codigo_emp_reemplazo }}
                            <span class="bg-green-700 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full" title="Suplencias este mes">
                                {{ $numMes }}
                            </span>
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

</div>
@endsection
