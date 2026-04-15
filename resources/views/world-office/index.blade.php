@extends('layouts.app-sidebar')

@section('header', 'Plantilla World Office')

@php
function sinTwo($s){ return str_replace(['Á','É','Í','Ó','Ú','á','é','í','ó','ú','ü','Ü','ñ','Ñ'],['A','E','I','O','U','a','e','i','o','u','u','U','n','N'],$s); }
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

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

    {{-- ── Panel izquierdo: Plantilla empresa ─────────────────────────────── --}}
    <div class="bg-white rounded-xl shadow p-6">
        <h3 class="font-semibold text-gray-700 mb-1">Datos fijos de empresa</h3>
        <p class="text-xs text-gray-400 mb-5">Estos valores se repiten en cada fila del Excel exportado.</p>

        <form method="POST" action="{{ route('world-office.plantilla.store') }}" class="space-y-4">
            @csrf

            <div>
                <label class="block text-xs text-gray-500 mb-1 font-medium">Empresa que factura</label>
                <input name="empresa" maxlength="100" required
                    value="{{ old('empresa', $plantilla->empresa ?? '') }}"
                    class="w-full border rounded-lg px-3 py-2 text-sm" placeholder="Nombre completo de la empresa">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs text-gray-500 mb-1 font-medium">Tipo</label>
                    <input name="tipo" maxlength="10" required
                        value="{{ old('tipo', $plantilla->tipo ?? 'FV') }}"
                        class="w-full border rounded-lg px-3 py-2 text-sm uppercase" placeholder="FV">
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1 font-medium">Prefijo</label>
                    <input name="prefijo" maxlength="10"
                        value="{{ old('prefijo', $plantilla->prefijo ?? '') }}"
                        class="w-full border rounded-lg px-3 py-2 text-sm uppercase" placeholder="Opcional">
                </div>
            </div>

            <div>
                <label class="block text-xs text-gray-500 mb-1 font-medium">Cedula / NIT facturador</label>
                <input name="cedula_facturador" maxlength="20" required
                    value="{{ old('cedula_facturador', $plantilla->cedula_facturador ?? '') }}"
                    class="w-full border rounded-lg px-3 py-2 text-sm" placeholder="Numero de documento">
            </div>

            <div>
                <label class="block text-xs text-gray-500 mb-1 font-medium">Forma de pago</label>
                <input name="forma_pago" maxlength="50" required
                    value="{{ old('forma_pago', $plantilla->forma_pago ?? 'CONTADO') }}"
                    class="w-full border rounded-lg px-3 py-2 text-sm uppercase" placeholder="CONTADO">
            </div>

            <div>
                <label class="block text-xs text-gray-500 mb-1 font-medium">
                    Numero de factura inicial
                    <span class="text-gray-400 font-normal">(desde donde arranca la numeracion en el proximo Excel)</span>
                </label>
                <input name="numero_inicio" type="number" min="1" required
                    value="{{ old('numero_inicio', $plantilla->numero_inicio ?? 1) }}"
                    class="w-full border rounded-lg px-3 py-2 text-sm">
            </div>

            <button type="submit"
                class="w-full bg-gray-700 text-white rounded-lg py-2.5 text-sm font-semibold hover:bg-gray-800 transition">
                Guardar plantilla
            </button>
        </form>

        {{-- Vista previa de lo que queda guardado --}}
        @if($plantilla)
        <div class="mt-6 border-t pt-5">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Plantilla activa</p>
            <dl class="space-y-1.5 text-sm">
                <div class="flex gap-2">
                    <dt class="text-gray-400 w-32 shrink-0">Empresa:</dt>
                    <dd class="font-medium text-gray-700">{{ $plantilla->empresa }}</dd>
                </div>
                <div class="flex gap-2">
                    <dt class="text-gray-400 w-32 shrink-0">Tipo / Prefijo:</dt>
                    <dd class="font-mono text-gray-700">{{ $plantilla->tipo }}{{ $plantilla->prefijo ? ' / ' . $plantilla->prefijo : '' }}</dd>
                </div>
                <div class="flex gap-2">
                    <dt class="text-gray-400 w-32 shrink-0">Cedula:</dt>
                    <dd class="font-mono text-gray-700">{{ $plantilla->cedula_facturador }}</dd>
                </div>
                <div class="flex gap-2">
                    <dt class="text-gray-400 w-32 shrink-0">Forma de pago:</dt>
                    <dd class="text-gray-700">{{ $plantilla->forma_pago }}</dd>
                </div>
                <div class="flex gap-2">
                    <dt class="text-gray-400 w-32 shrink-0">N° inicio:</dt>
                    <dd class="font-mono text-blue-700 font-semibold">{{ number_format($plantilla->numero_inicio) }}</dd>
                </div>
            </dl>
        </div>
        @endif
    </div>

    {{-- ── Panel derecho: Exportar CSV ─────────────────────────────────────── --}}
    <div class="bg-white rounded-xl shadow p-6">
        <h3 class="font-semibold text-gray-700 mb-1">Exportar Excel para World Office</h3>
        <p class="text-xs text-gray-400 mb-5">Parametros que cambian cada vez que se genera el archivo.</p>

        @if(!$plantilla)
        <div class="bg-yellow-50 border border-yellow-300 text-yellow-800 rounded-lg p-3 text-sm mb-5">
            ⚠️ Guarda primero los datos de la empresa en el panel izquierdo.
        </div>
        @endif

        <form method="POST" action="{{ route('world-office.exportar') }}" class="space-y-4">
            @csrf

            <div>
                <label class="block text-xs text-gray-500 mb-1 font-medium">MES</label>
                <select name="mes" required class="w-full border rounded-lg px-3 py-2 text-sm">
                    <option value="">Seleccione el mes...</option>
                    @foreach(['ENERO','FEBRERO','MARZO','ABRIL','MAYO','JUNIO','JULIO','AGOSTO','SEPTIEMBRE','OCTUBRE','NOVIEMBRE','DICIEMBRE'] as $m)
                    <option value="{{ $m }}">{{ $m }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs text-gray-500 mb-1 font-medium">
                    Conceptos a incluir
                    <span class="text-gray-400 font-normal">(vacio = todos los del mes)</span>
                </label>
                <select name="conceptos[]" multiple class="w-full border rounded-lg px-3 py-2 text-sm" size="4">
                    @foreach($conceptos as $c)
                    <option value="{{ $c->codigo_concepto }}">
                        {{ $c->codigo_concepto }} — {{ sinTwo($c->concepto) }}
                    </option>
                    @endforeach
                </select>
                <p class="text-xs text-gray-400 mt-1">Ctrl+clic para seleccionar varios.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs text-gray-500 mb-1 font-medium">Fecha de facturacion</label>
                    <input type="date" name="fecha_facturacion" required value="{{ date('Y-m-d') }}"
                        class="w-full border rounded-lg px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1 font-medium">Fecha inicio mes</label>
                    <input type="date" name="fecha_inicio" required
                        class="w-full border rounded-lg px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1 font-medium">Fecha vencimiento</label>
                    <input type="date" name="fecha_venc" required
                        class="w-full border rounded-lg px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1 font-medium">IVA</label>
                    <input type="number" name="iva" value="0" min="0" step="0.01"
                        class="w-full border rounded-lg px-3 py-2 text-sm">
                </div>
                <div class="col-span-2">
                    <label class="block text-xs text-gray-500 mb-1 font-medium">Cantidad</label>
                    <input type="number" name="cantidad" value="1" min="0" step="0.01"
                        class="w-full border rounded-lg px-3 py-2 text-sm">
                </div>
            </div>

            <div>
                <label class="block text-xs text-gray-500 mb-1 font-medium">Encabezado factura</label>
                <input type="text" name="encabezado" required maxlength="200"
                    class="w-full border rounded-lg px-3 py-2 text-sm"
                    placeholder="Ej: Factura de venta mes de ENERO">
            </div>

            <div>
                <label class="block text-xs text-gray-500 mb-1 font-medium">Nota</label>
                <input type="text" name="nota" required maxlength="200"
                    class="w-full border rounded-lg px-3 py-2 text-sm"
                    placeholder="Ej: Factura mes de ENERO">
            </div>

            <button type="submit" {{ !$plantilla ? 'disabled' : '' }}
                class="w-full bg-blue-700 text-white rounded-xl py-3 font-semibold text-sm hover:bg-blue-800 transition disabled:opacity-40 disabled:cursor-not-allowed">
                ⬇️ Descargar Excel (.xlsx)
            </button>
        </form>

        {{-- Columnas del Excel --}}
        <details class="mt-5">
            <summary class="text-xs text-gray-400 cursor-pointer hover:text-gray-600 select-none">
                Ver las 19 columnas del archivo Excel
            </summary>
            <div class="mt-3 flex flex-wrap gap-1.5">
                @foreach([
                    'EMPRESA QUE FACTURA','TIPO','PREFIJO','NUMERO DE FACTURA',
                    'FECHA FACTURACION','CEDULA FACTURADOR','DOCUMENTO ID',
                    'ENCABEZADO FACTURA','FORMA DE PAGO','FECHA FACTURA',
                    'CODIGO ALUMNO','NOMBRE','CODIGO CONCEPTO','CANTIDAD',
                    'IVA','VALOR','FECHA VENCIMIENTO','CENTRO DE COSTO','NOTA'
                ] as $i => $col)
                <span class="bg-gray-100 text-gray-600 text-xs px-2 py-0.5 rounded font-mono">
                    {{ $i + 1 }}. {{ $col }}
                </span>
                @endforeach
            </div>
        </details>
    </div>

</div>

@endsection
