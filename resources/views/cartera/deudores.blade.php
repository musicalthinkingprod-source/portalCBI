@extends('layouts.app-sidebar')

@section('header', 'Lista de Deudores')

@section('slot')

    <div class="flex items-center justify-between mb-5">
        <a href="{{ route('cartera.index') }}" class="text-blue-700 hover:underline text-sm">← Volver a informe de cartera</a>
        <span class="text-sm text-gray-500">{{ $deudores->total() }} estudiantes con saldo pendiente</span>
    </div>

    <div class="bg-white rounded-xl shadow overflow-hidden">
        <div class="px-5 py-3 bg-red-700 text-white flex items-center justify-between">
            <h3 class="font-bold text-sm uppercase tracking-wide">Todos los deudores</h3>
            <span class="text-red-200 text-xs">Ordenados por mayor saldo · Página {{ $deudores->currentPage() }} de {{ $deudores->lastPage() }}</span>
        </div>

        @if($deudores->isEmpty())
            <div class="px-5 py-10 text-center text-gray-400 text-sm">No hay estudiantes con saldo pendiente.</div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                    <tr>
                        <th class="px-4 py-3 text-left">#</th>
                        <th class="px-4 py-3 text-left">Estudiante</th>
                        <th class="px-4 py-3 text-left">Curso</th>
                        <th class="px-4 py-3 text-right">Facturado</th>
                        <th class="px-4 py-3 text-right">Pagado</th>
                        <th class="px-4 py-3 text-right">Saldo</th>
                        <th class="px-4 py-3 text-left">Acudiente / Contacto</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($deudores as $i => $d)
                    @php
                        $pos       = $deudores->firstItem() + $i;
                        $nombre    = trim("{$d->APELLIDO1} {$d->APELLIDO2} {$d->NOMBRE1} {$d->NOMBRE2}");
                        $acudiente = $d->ACUD ?: ($d->MADRE ?: $d->PADRE);

                        // Recopilar teléfonos únicos no vacíos
                        $tels = collect([
                            'Acud.'  => $d->CEL_ACUD  ?: $d->TEL_ACUD,
                            'Madre'  => $d->CEL_MADRE ?: $d->TEL_MADRE,
                            'Padre'  => $d->CEL_PADRE ?: $d->TEL_PADRE,
                        ])->filter()->unique();
                    @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-gray-400 text-xs">{{ $pos }}</td>
                        <td class="px-4 py-3">
                            <span class="font-medium text-gray-800">{{ $nombre ?: $d->codigo_alumno }}</span>
                            <span class="block text-xs text-gray-400">Cód: {{ $d->codigo_alumno }}</span>
                        </td>
                        <td class="px-4 py-3 text-gray-500">{{ $d->CURSO ?? '—' }}</td>
                        <td class="px-4 py-3 text-right text-xs text-gray-500">$ {{ number_format($d->total_facturado, 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right text-xs text-green-700">$ {{ number_format($d->total_pagado, 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right font-bold text-red-700">$ {{ number_format($d->saldo, 0, ',', '.') }}</td>
                        <td class="px-4 py-3">
                            @if($acudiente)
                                <span class="block text-xs font-medium text-gray-700 mb-1">{{ $acudiente }}</span>
                            @endif
                            @if($tels->isEmpty())
                                <span class="text-xs text-gray-400">Sin teléfono registrado</span>
                            @else
                                <div class="flex flex-wrap gap-2">
                                    @foreach($tels as $label => $tel)
                                    <a href="tel:{{ preg_replace('/\D/', '', $tel) }}"
                                        class="inline-flex items-center gap-1 bg-blue-50 hover:bg-blue-100 text-blue-800 text-xs font-semibold px-2 py-1 rounded-lg transition">
                                        📞 {{ $label }}: {{ $tel }}
                                    </a>
                                    @endforeach
                                </div>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($deudores->hasPages())
        <div class="px-5 py-4 border-t border-gray-100">
            {{ $deudores->links() }}
        </div>
        @endif
        @endif
    </div>

@endsection
