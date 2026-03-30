@extends('layouts.padres')

@section('header', 'Estado de Cuenta')

@section('slot')

    {{-- Info estudiante --}}
    <div class="bg-white rounded-xl shadow p-5 mb-6">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Estudiante</p>
                <p class="text-lg font-bold text-blue-800">
                    {{ $estudiante->NOMBRE1 }} {{ $estudiante->NOMBRE2 }}
                    {{ $estudiante->APELLIDO1 }} {{ $estudiante->APELLIDO2 }}
                </p>
            </div>
            <div>
                <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Código</p>
                <p class="text-lg font-semibold text-gray-700">{{ $estudiante->CODIGO }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Curso</p>
                <p class="text-lg font-semibold text-gray-700">{{ $estudiante->CURSO ?? '—' }}</p>
            </div>
        </div>
    </div>

    {{-- Resumen financiero --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="bg-blue-50 border border-blue-200 rounded-xl p-5 text-center">
            <p class="text-xs text-blue-400 uppercase tracking-wide mb-1">Total Facturado</p>
            <p class="text-2xl font-bold text-blue-800">$ {{ number_format($totalFactura, 0, ',', '.') }}</p>
        </div>
        <div class="bg-green-50 border border-green-200 rounded-xl p-5 text-center">
            <p class="text-xs text-green-400 uppercase tracking-wide mb-1">Total Pagado</p>
            <p class="text-2xl font-bold text-green-700">$ {{ number_format($totalPagado, 0, ',', '.') }}</p>
        </div>
        <div class="bg-{{ $saldo > 0 ? 'red' : 'gray' }}-50 border border-{{ $saldo > 0 ? 'red' : 'gray' }}-200 rounded-xl p-5 text-center">
            <p class="text-xs text-{{ $saldo > 0 ? 'red' : 'gray' }}-400 uppercase tracking-wide mb-1">
                {{ $saldo > 0 ? 'Saldo Pendiente' : 'Al día' }}
            </p>
            <p class="text-2xl font-bold text-{{ $saldo > 0 ? 'red' : 'green' }}-700">
                $ {{ number_format(abs($saldo), 0, ',', '.') }}
            </p>
        </div>
    </div>

    {{-- Tabla facturación --}}
    <div class="bg-white rounded-xl shadow overflow-hidden mb-6">
        <div class="px-5 py-4 border-b border-gray-100">
            <h3 class="font-bold text-blue-800">Facturación</h3>
        </div>
        @if($facturacion->isEmpty())
            <p class="text-sm text-gray-400 px-5 py-4">Sin registros de facturación.</p>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                    <tr>
                        <th class="px-4 py-3 text-left">Fecha</th>
                        <th class="px-4 py-3 text-left">Concepto</th>
                        <th class="px-4 py-3 text-left">Mes</th>
                        <th class="px-4 py-3 text-right">Valor</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($facturacion as $f)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">{{ $f->fecha }}</td>
                        <td class="px-4 py-3">{{ $f->concepto }}</td>
                        <td class="px-4 py-3">{{ $f->mes }}</td>
                        <td class="px-4 py-3 text-right font-medium text-blue-800">$ {{ number_format($f->valor, 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-blue-50">
                    <tr>
                        <td colspan="3" class="px-4 py-3 font-semibold text-blue-800">Total</td>
                        <td class="px-4 py-3 text-right font-bold text-blue-800">$ {{ number_format($totalFactura, 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
        @endif
    </div>

    {{-- Tabla pagos --}}
    <div class="bg-white rounded-xl shadow overflow-hidden mb-6">
        <div class="px-5 py-4 border-b border-gray-100">
            <h3 class="font-bold text-blue-800">Pagos Realizados</h3>
        </div>
        @if($pagos->isEmpty())
            <p class="text-sm text-gray-400 px-5 py-4">Sin registros de pagos.</p>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                    <tr>
                        <th class="px-4 py-3 text-left">Fecha</th>
                        <th class="px-4 py-3 text-left">Concepto</th>
                        <th class="px-4 py-3 text-left">Mes</th>
                        <th class="px-4 py-3 text-right">Valor</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($pagos as $p)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">{{ $p->fecha }}</td>
                        <td class="px-4 py-3">{{ $p->concepto }}</td>
                        <td class="px-4 py-3">{{ $p->mes }}</td>
                        <td class="px-4 py-3 text-right font-medium text-green-700">$ {{ number_format($p->valor, 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-green-50">
                    <tr>
                        <td colspan="3" class="px-4 py-3 font-semibold text-green-700">Total</td>
                        <td class="px-4 py-3 text-right font-bold text-green-700">$ {{ number_format($totalPagado, 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
        @endif
    </div>

    {{-- Observaciones --}}
    <div class="bg-white rounded-xl shadow p-5">
        <h3 class="font-bold text-blue-800 mb-2">Observaciones</h3>
        <p class="text-sm text-gray-400 italic">Próximamente disponible.</p>
    </div>

@endsection
