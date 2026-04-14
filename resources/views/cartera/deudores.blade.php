@extends('layouts.app-sidebar')

@section('header', $tab === 'anticipos' ? 'Anticipos · Saldos a Favor' : 'Cartera · Deudores')

@section('slot')

    {{-- Nav + filtro de corte --}}
    <div class="flex flex-wrap items-center justify-between gap-4 mb-5">
        <a href="{{ route('cartera.index') }}" class="text-blue-700 hover:underline text-sm">← Volver a informe de cartera</a>

        <div class="flex items-center gap-3">
            <a href="{{ route('cartera.exportar.deudores', array_filter(['tab' => $tab, 'corte' => $corte])) }}"
                class="bg-green-700 hover:bg-green-800 text-white text-sm font-semibold px-4 py-1.5 rounded-lg transition whitespace-nowrap">
                ⬇️ Excel
            </a>
        </div>

        <form method="GET" action="{{ route('cartera.deudores') }}" class="flex items-center gap-2">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <label class="text-xs font-semibold text-gray-500 whitespace-nowrap">Fecha de corte:</label>
            <input type="date" name="corte" value="{{ $corte ?? '' }}"
                class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
            <button type="submit"
                class="bg-blue-800 hover:bg-blue-700 text-white text-sm font-semibold px-3 py-1.5 rounded-lg transition">
                Aplicar
            </button>
            @if($corte)
            <a href="{{ route('cartera.deudores', ['tab' => $tab]) }}"
                class="text-xs text-red-500 hover:text-red-700 font-semibold whitespace-nowrap">✕ Quitar corte</a>
            @endif
        </form>
    </div>

    {{-- Tabs --}}
    <div class="flex gap-1 mb-5">
        <a href="{{ route('cartera.deudores', array_filter(['tab' => 'cartera', 'corte' => $corte])) }}"
            class="px-5 py-2 rounded-t-lg text-sm font-semibold transition border-b-2
            {{ $tab !== 'anticipos'
                ? 'bg-white border-red-600 text-red-700'
                : 'bg-gray-100 border-transparent text-gray-500 hover:bg-white hover:text-gray-700' }}">
            🔴 Cartera (deudores)
            @if($tab !== 'anticipos')
                <span class="ml-1 text-xs text-red-400">{{ $resultados->total() }}</span>
            @endif
        </a>
        <a href="{{ route('cartera.deudores', array_filter(['tab' => 'anticipos', 'corte' => $corte])) }}"
            class="px-5 py-2 rounded-t-lg text-sm font-semibold transition border-b-2
            {{ $tab === 'anticipos'
                ? 'bg-white border-green-600 text-green-700'
                : 'bg-gray-100 border-transparent text-gray-500 hover:bg-white hover:text-gray-700' }}">
            🟢 Anticipos (saldos a favor)
            @if($tab === 'anticipos')
                <span class="ml-1 text-xs text-green-500">{{ $resultados->total() }}</span>
            @endif
        </a>
    </div>

    @if($corte)
    <div class="mb-4 px-4 py-2 bg-amber-50 border border-amber-200 text-amber-700 rounded-xl text-sm inline-flex items-center gap-2">
        📅 Corte al <strong>{{ \Carbon\Carbon::parse($corte)->format('d/m/Y') }}</strong>
        — solo se consideran movimientos hasta esta fecha.
    </div>
    @endif

    <div class="bg-white rounded-xl shadow overflow-hidden">
        <div class="px-5 py-3 {{ $tab === 'anticipos' ? 'bg-green-700' : 'bg-red-700' }} text-white flex items-center justify-between">
            <h3 class="font-bold text-sm uppercase tracking-wide">
                {{ $tab === 'anticipos' ? 'Estudiantes con saldo a favor' : 'Todos los deudores' }}
            </h3>
            <span class="text-xs opacity-75">
                {{ $resultados->total() }} estudiantes
                · Página {{ $resultados->currentPage() }} de {{ $resultados->lastPage() }}
            </span>
        </div>

        @if($resultados->isEmpty())
            <div class="px-5 py-10 text-center text-gray-400 text-sm">
                {{ $tab === 'anticipos' ? 'No hay estudiantes con saldo a favor.' : 'No hay estudiantes con saldo pendiente.' }}
            </div>
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
                        <th class="px-4 py-3 text-right">
                            {{ $tab === 'anticipos' ? 'Anticipo' : 'Saldo' }}
                        </th>
                        @if($tab !== 'anticipos')
                        <th class="px-4 py-3 text-left">Acudiente / Contacto</th>
                        @endif
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($resultados as $i => $d)
                    @php
                        $pos       = $resultados->firstItem() + $i;
                        $nombre    = trim("{$d->APELLIDO1} {$d->APELLIDO2} {$d->NOMBRE1} {$d->NOMBRE2}");
                        $acudiente = $d->ACUD ?: ($d->MADRE ?: $d->PADRE);
                        $tels = collect([
                            'Acud.'  => $d->CEL_ACUD  ?: $d->TEL_ACUD,
                            'Madre'  => $d->CEL_MADRE ?: $d->TEL_MADRE,
                            'Padre'  => $d->CEL_PADRE ?: $d->TEL_PADRE,
                        ])->filter()->unique();
                        $saldoAbs = abs($d->saldo);
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
                        <td class="px-4 py-3 text-right font-bold {{ $tab === 'anticipos' ? 'text-green-700' : 'text-red-700' }}">
                            $ {{ number_format($saldoAbs, 0, ',', '.') }}
                        </td>
                        @if($tab !== 'anticipos')
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
                        @endif
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('cartera.estudiante', $d->codigo_alumno) }}"
                                class="inline-block {{ $tab === 'anticipos' ? 'bg-green-700 hover:bg-green-600' : 'bg-gray-700 hover:bg-gray-600' }} text-white text-xs font-semibold px-3 py-1.5 rounded-lg transition">
                                Ver {{ $tab === 'anticipos' ? '→' : '/ Seguimiento' }}
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($resultados->hasPages())
        <div class="px-5 py-4 border-t border-gray-100">
            {{ $resultados->links() }}
        </div>
        @endif
        @endif
    </div>

@endsection
