@extends('layouts.app-sidebar')

@section('header', 'Facturacion Automatica')

@php
function sinTauto($s){ return str_replace(['Á','É','Í','Ó','Ú','á','é','í','ó','ú','ü','Ü','ñ','Ñ'],['A','E','I','O','U','a','e','i','o','u','u','U','n','N'],$s); }
@endphp

@section('slot')

@if(session('ok'))
<div class="mb-5 bg-green-50 border border-green-300 text-green-800 px-4 py-3 rounded-lg text-sm font-medium">
    ✅ {{ session('ok') }}
</div>
@endif

@if($errors->any())
<div class="mb-5 bg-red-50 border border-red-300 text-red-800 px-4 py-3 rounded-lg text-sm">
    <ul class="list-disc list-inside space-y-1">
        @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
    </ul>
</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- ── Formulario ─────────────────────────────────────────────────────── --}}
    <div class="bg-white rounded-xl shadow p-6 space-y-4">
        <h3 class="font-semibold text-gray-700 text-base">Generar facturacion</h3>

        <form method="POST" action="{{ route('facturacion.auto.preview') }}" class="space-y-4">
            @csrf

            <div>
                <label class="block text-xs text-gray-500 mb-1 font-medium">MES</label>
                <select name="mes" required class="w-full border rounded-lg px-3 py-2 text-sm">
                    <option value="">Seleccione el mes...</option>
                    @foreach(['ENERO','FEBRERO','MARZO','ABRIL','MAYO','JUNIO','JULIO','AGOSTO','SEPTIEMBRE','OCTUBRE','NOVIEMBRE','DICIEMBRE'] as $m)
                    <option value="{{ $m }}" {{ (isset($mes) && $mes === $m) ? 'selected' : '' }}>{{ $m }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs text-gray-500 mb-1 font-medium">CONCEPTO</label>
                <select name="codigo_concepto" required class="w-full border rounded-lg px-3 py-2 text-sm">
                    <option value="">Seleccione el concepto...</option>
                    @foreach($conceptos as $c)
                    <option value="{{ $c->codigo_concepto }}"
                        {{ (isset($codigoConcepto) && $codigoConcepto === $c->codigo_concepto) ? 'selected' : '' }}>
                        {{ $c->codigo_concepto }} — {{ sinTauto($c->concepto) }}
                    </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs text-gray-500 mb-1 font-medium">FECHA DE REGISTRO</label>
                <input type="date" name="fecha" required
                    value="{{ isset($fecha) ? $fecha : date('Y-m-d') }}"
                    class="w-full border rounded-lg px-3 py-2 text-sm">
            </div>

            <div>
                <label class="block text-xs text-gray-500 mb-1 font-medium">ORDEN <span class="text-gray-400">(opcional)</span></label>
                <input type="number" name="orden" min="1"
                    value="{{ isset($orden) ? $orden : '' }}"
                    class="w-full border rounded-lg px-3 py-2 text-sm" placeholder="Numero de orden">
            </div>

            <button type="submit"
                class="w-full bg-blue-700 text-white rounded-lg py-2.5 text-sm font-semibold hover:bg-blue-800 transition">
                Ver preview →
            </button>
        </form>
    </div>

    {{-- ── Preview / Confirmación ──────────────────────────────────────────── --}}
    <div class="lg:col-span-2 space-y-4">

        @isset($todos)
        @php
            $porFacturar = $todos->filter(fn($r) => !isset($yaFacturados[$r->codigo_alumno]));
            $omitidos    = $todos->filter(fn($r) =>  isset($yaFacturados[$r->codigo_alumno]));
            $totalValor  = $porFacturar->sum('valor');
        @endphp

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 text-center">
                <p class="text-2xl font-bold text-blue-800">{{ $porFacturar->count() }}</p>
                <p class="text-xs text-blue-500 mt-1">Estudiantes a facturar</p>
            </div>
            <div class="bg-green-50 border border-green-200 rounded-xl p-4 text-center">
                <p class="text-2xl font-bold text-green-700">$ {{ number_format($totalValor, 0, ',', '.') }}</p>
                <p class="text-xs text-green-500 mt-1">Total a generar</p>
            </div>
            <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4 text-center">
                <p class="text-2xl font-bold text-yellow-700">{{ $omitidos->count() }}</p>
                <p class="text-xs text-yellow-600 mt-1">Ya facturados (se omiten)</p>
            </div>
        </div>

        <div class="bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm text-gray-600 flex items-center gap-3">
            <span>🏷️</span>
            Lote: <code class="bg-white border rounded px-2 py-0.5 font-mono text-blue-700 ml-1">
                AUTO-{{ $mes }}-{{ $codigoConcepto }}-{{ date('Y') }}
            </code>
        </div>

        @if($porFacturar->isEmpty())
        <div class="bg-yellow-50 border border-yellow-300 text-yellow-800 rounded-xl p-4 text-sm">
            ⚠️ No hay estudiantes nuevos para facturar con <strong>{{ $codigoConcepto }}</strong> en <strong>{{ $mes }}</strong>.
        </div>
        @else
        <form method="POST" action="{{ route('facturacion.auto.generar') }}">
            @csrf
            <input type="hidden" name="mes" value="{{ $mes }}">
            <input type="hidden" name="codigo_concepto" value="{{ $codigoConcepto }}">
            <input type="hidden" name="fecha" value="{{ $fecha }}">
            <input type="hidden" name="orden" value="{{ $orden }}">
            <button type="submit"
                class="w-full bg-green-600 text-white rounded-xl py-3 font-semibold text-sm hover:bg-green-700 transition"
                onclick="return confirm('Confirmar generacion de {{ $porFacturar->count() }} registros para {{ $mes }} - {{ $codigoConcepto }}?')">
                ✅ Confirmar y generar {{ $porFacturar->count() }} registros
            </button>
        </form>

        <div class="bg-white rounded-xl shadow overflow-auto max-h-96">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b sticky top-0">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs text-gray-500 uppercase">Cod.</th>
                        <th class="px-3 py-2 text-left text-xs text-gray-500 uppercase">Alumno</th>
                        <th class="px-3 py-2 text-left text-xs text-gray-500 uppercase">Centro costos</th>
                        <th class="px-3 py-2 text-right text-xs text-gray-500 uppercase">Valor</th>
                        <th class="px-3 py-2 text-center text-xs text-gray-500 uppercase">Estado</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @foreach($todos->sortBy('nombre') as $row)
                    @php $omitir = isset($yaFacturados[$row->codigo_alumno]); @endphp
                    <tr class="{{ $omitir ? 'bg-yellow-50 opacity-60' : 'hover:bg-gray-50' }}">
                        <td class="px-3 py-2 font-mono text-blue-700">{{ $row->codigo_alumno }}</td>
                        <td class="px-3 py-2">{{ $row->nombre ?: '—' }}</td>
                        <td class="px-3 py-2 text-gray-500 text-xs">{{ $row->centro_costos }}</td>
                        <td class="px-3 py-2 text-right font-medium">$ {{ number_format($row->valor, 0, ',', '.') }}</td>
                        <td class="px-3 py-2 text-center">
                            @if($omitir)
                                <span class="bg-yellow-100 text-yellow-700 text-xs px-2 py-0.5 rounded-full">ya facturado</span>
                            @else
                                <span class="bg-green-100 text-green-700 text-xs px-2 py-0.5 rounded-full">nuevo</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        @endisset

        {{-- Lotes existentes --}}
        @if($lotes->isNotEmpty())
        <div class="bg-white rounded-xl shadow overflow-hidden">
            <div class="px-4 py-3 border-b bg-gray-50">
                <p class="text-sm font-semibold text-gray-700">Lotes generados automaticamente</p>
            </div>
            <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">Lote</th>
                        <th class="px-4 py-2 text-right text-xs text-gray-500 uppercase">Registros</th>
                        <th class="px-4 py-2 text-right text-xs text-gray-500 uppercase">Total</th>
                        <th class="px-4 py-2 text-center text-xs text-gray-500 uppercase">Fecha</th>
                        <th class="px-4 py-2"></th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @foreach($lotes as $lote)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2 font-mono text-xs text-blue-700">{{ $lote->lote_importacion }}</td>
                        <td class="px-4 py-2 text-right">{{ $lote->total }}</td>
                        <td class="px-4 py-2 text-right font-medium">$ {{ number_format($lote->suma, 0, ',', '.') }}</td>
                        <td class="px-4 py-2 text-center text-gray-500 text-xs">{{ $lote->fecha }}</td>
                        <td class="px-4 py-2 text-right">
                            <form method="POST"
                                action="{{ route('facturacion.auto.lote.destroy', $lote->lote_importacion) }}"
                                onsubmit="return confirm('Eliminar el lote {{ $lote->lote_importacion }}?')">
                                @csrf @method('DELETE')
                                <button class="text-red-500 hover:text-red-700 text-xs">Revertir</button>
                            </form>
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
