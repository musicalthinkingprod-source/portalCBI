@extends('layouts.app-sidebar')

@section('header', 'Parametros de Facturacion')

@section('slot')

@php
function sinT($s){ return str_replace(['Á','É','Í','Ó','Ú','á','é','í','ó','ú','ü','Ü','ñ','Ñ'],['A','E','I','O','U','a','e','i','o','u','u','U','n','N'],$s); }
$esReadOnly = auth()->user()->PROFILE === 'Contab';
@endphp

@if(session('ok'))
<div class="mb-4 bg-green-50 border border-green-300 text-green-800 px-4 py-3 rounded-lg text-sm">
    {{ session('ok') }}
</div>
@endif

@if($errors->any())
<div class="mb-4 bg-red-50 border border-red-300 text-red-800 px-4 py-3 rounded-lg text-sm">
    <ul class="list-disc list-inside space-y-1">
        @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
    </ul>
</div>
@endif

<div x-data="{ tab: '{{ request('tab', 'centro_costos') }}' }">

    <div class="flex flex-wrap gap-1 mb-6 bg-white rounded-xl shadow p-2">
        @foreach([
            'centro_costos'     => 'Centros de Costos',
            'conceptos'         => 'Conceptos',
            'costo_pension'     => 'Tarifas Pension',
            'costo_transporte'  => 'Tarifas Transporte',
            'pension'           => 'Pension x Alumno',
            'transporte'        => 'Transporte x Alumno',
            'nivelacion'        => 'Nivelacion x Alumno',
            'listado_transporte'=> 'Listado Transporte',
            'observaciones'     => 'Observaciones',
        ] as $key => $label)
        <button @click="tab='{{ $key }}'"
            :class="tab==='{{ $key }}' ? 'bg-blue-700 text-white' : 'text-gray-600 hover:bg-gray-100'"
            class="px-3 py-1.5 rounded-lg text-xs font-medium transition">
            {{ $label }}
        </button>
        @endforeach
    </div>

    {{-- ══ 1. Centro de Costos ══════════════════════════════════════════════════ --}}
    <div x-show="tab==='centro_costos'" x-cloak>
        <div class="{{ $esReadOnly ? 'grid grid-cols-1' : 'grid grid-cols-1 lg:grid-cols-3' }} gap-6">

            @if(!$esReadOnly)
            <div class="bg-white rounded-xl shadow p-5">
                <h3 id="cc-titulo" class="font-semibold text-gray-700 mb-4">Nuevo</h3>
                <form method="POST" action="{{ route('parametros.centro_costos.store') }}" class="space-y-3">
                    @csrf
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Codigo</label>
                        <input id="cc-codigo" name="codigo_centro_costos" maxlength="20" required
                            class="w-full border rounded-lg px-3 py-2 text-sm uppercase" placeholder="Ej: PRIMARIA">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Nombre</label>
                        <input id="cc-nombre" name="nombre_centro_costos" maxlength="100" required
                            class="w-full border rounded-lg px-3 py-2 text-sm" placeholder="Ej: Seccion Primaria">
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="flex-1 bg-blue-700 text-white rounded-lg py-2 text-sm font-medium hover:bg-blue-800">Guardar</button>
                        <button type="button" onclick="limpiarCC()" class="px-3 bg-gray-100 text-gray-600 rounded-lg py-2 text-sm hover:bg-gray-200">Limpiar</button>
                    </div>
                </form>
            </div>
            @endif

            <div class="{{ $esReadOnly ? '' : 'lg:col-span-2' }} bg-white rounded-xl shadow overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs text-gray-500 uppercase">Codigo</th>
                            <th class="px-4 py-3 text-left text-xs text-gray-500 uppercase">Nombre</th>
                            @if(!$esReadOnly)<th class="px-4 py-3"></th>@endif
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse($centroCostos as $row)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-mono font-semibold text-blue-700">{{ $row->codigo_centro_costos }}</td>
                            <td class="px-4 py-3">{{ $row->nombre_centro_costos }}</td>
                            @if(!$esReadOnly)
                            <td class="px-4 py-3 text-right flex items-center justify-end gap-3">
                                <button type="button"
                                    onclick="editarCC('{{ $row->codigo_centro_costos }}','{{ addslashes($row->nombre_centro_costos) }}')"
                                    class="text-blue-600 hover:text-blue-800 text-xs font-medium">Editar</button>
                                <form method="POST" action="{{ route('parametros.centro_costos.destroy', $row->codigo_centro_costos) }}"
                                    onsubmit="return confirm('Eliminar?')">
                                    @csrf @method('DELETE')
                                    <button class="text-red-500 hover:text-red-700 text-xs">Eliminar</button>
                                </form>
                            </td>
                            @endif
                        </tr>
                        @empty
                        <tr><td colspan="{{ $esReadOnly ? 2 : 3 }}" class="px-4 py-6 text-center text-gray-400 text-sm">Sin registros</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </div>

    {{-- ══ 2. Conceptos ════════════════════════════════════════════════════════ --}}
    <div x-show="tab==='conceptos'" x-cloak>
        <div class="{{ $esReadOnly ? 'grid grid-cols-1' : 'grid grid-cols-1 lg:grid-cols-3' }} gap-6">

            @if(!$esReadOnly)
            <div class="bg-white rounded-xl shadow p-5">
                <h3 id="con-titulo" class="font-semibold text-gray-700 mb-4">Nuevo</h3>
                <form method="POST" action="{{ route('parametros.conceptos.store') }}" class="space-y-3">
                    @csrf
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Codigo concepto</label>
                        <input id="con-codigo" name="codigo_concepto" maxlength="20" required
                            class="w-full border rounded-lg px-3 py-2 text-sm uppercase" placeholder="Ej: PENSION">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Descripcion</label>
                        <input id="con-desc" name="concepto" maxlength="100" required
                            class="w-full border rounded-lg px-3 py-2 text-sm" placeholder="Ej: Pension mensual">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Centro de costos</label>
                        <select id="con-cc" name="centro_costos" required class="w-full border rounded-lg px-3 py-2 text-sm">
                            <option value="">Seleccione...</option>
                            @foreach($centroCostos as $cc)
                            <option value="{{ $cc->codigo_centro_costos }}">{{ $cc->codigo_centro_costos }} — {{ $cc->nombre_centro_costos }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="flex-1 bg-blue-700 text-white rounded-lg py-2 text-sm font-medium hover:bg-blue-800">Guardar</button>
                        <button type="button" onclick="limpiarCon()" class="px-3 bg-gray-100 text-gray-600 rounded-lg py-2 text-sm hover:bg-gray-200">Limpiar</button>
                    </div>
                </form>
            </div>
            @endif

            <div class="{{ $esReadOnly ? '' : 'lg:col-span-2' }} bg-white rounded-xl shadow overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs text-gray-500 uppercase">Codigo</th>
                            <th class="px-4 py-3 text-left text-xs text-gray-500 uppercase">Concepto</th>
                            <th class="px-4 py-3 text-left text-xs text-gray-500 uppercase">Centro costos</th>
                            @if(!$esReadOnly)<th class="px-4 py-3"></th>@endif
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse($conceptos as $row)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-mono font-semibold text-blue-700">{{ $row->codigo_concepto }}</td>
                            <td class="px-4 py-3">{{ sinT($row->concepto) }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $row->centro_costos }}</td>
                            @if(!$esReadOnly)
                            <td class="px-4 py-3 text-right flex items-center justify-end gap-3">
                                <button type="button"
                                    onclick="editarCon('{{ $row->codigo_concepto }}','{{ addslashes(sinT($row->concepto)) }}','{{ $row->centro_costos }}')"
                                    class="text-blue-600 hover:text-blue-800 text-xs font-medium">Editar</button>
                                <form method="POST" action="{{ route('parametros.conceptos.destroy', $row->codigo_concepto) }}"
                                    onsubmit="return confirm('Eliminar?')">
                                    @csrf @method('DELETE')
                                    <button class="text-red-500 hover:text-red-700 text-xs">Eliminar</button>
                                </form>
                            </td>
                            @endif
                        </tr>
                        @empty
                        <tr><td colspan="{{ $esReadOnly ? 3 : 4 }}" class="px-4 py-6 text-center text-gray-400 text-sm">Sin registros</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </div>

    {{-- ══ 3. Costo Pension ════════════════════════════════════════════════════ --}}
    <div x-show="tab==='costo_pension'" x-cloak>
        <div class="{{ $esReadOnly ? 'grid grid-cols-1' : 'grid grid-cols-1 lg:grid-cols-3' }} gap-6">

            @if(!$esReadOnly)
            <div class="bg-white rounded-xl shadow p-5">
                <h3 id="cp-titulo" class="font-semibold text-gray-700 mb-4">Nueva tarifa</h3>
                <form method="POST" action="{{ route('parametros.costo_pension.store') }}" class="space-y-3">
                    @csrf
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Codigo tarifa</label>
                        <input id="cp-codigo" name="codigo_valor_pension" maxlength="20" required
                            class="w-full border rounded-lg px-3 py-2 text-sm uppercase" placeholder="Ej: PEN-A">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Valor ($)</label>
                        <input id="cp-valor" name="valor" type="number" step="0.01" min="0" required
                            class="w-full border rounded-lg px-3 py-2 text-sm" placeholder="0.00">
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="flex-1 bg-blue-700 text-white rounded-lg py-2 text-sm font-medium hover:bg-blue-800">Guardar</button>
                        <button type="button" onclick="limpiarCP()" class="px-3 bg-gray-100 text-gray-600 rounded-lg py-2 text-sm hover:bg-gray-200">Limpiar</button>
                    </div>
                </form>
            </div>
            @endif

            <div class="{{ $esReadOnly ? '' : 'lg:col-span-2' }} bg-white rounded-xl shadow overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs text-gray-500 uppercase">Codigo</th>
                            <th class="px-4 py-3 text-right text-xs text-gray-500 uppercase">Valor</th>
                            @if(!$esReadOnly)<th class="px-4 py-3"></th>@endif
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse($costoPension as $row)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-mono font-semibold text-blue-700">{{ $row->codigo_valor_pension }}</td>
                            <td class="px-4 py-3 text-right font-medium">$ {{ number_format($row->valor, 0, ',', '.') }}</td>
                            @if(!$esReadOnly)
                            <td class="px-4 py-3 text-right flex items-center justify-end gap-3">
                                <button type="button"
                                    onclick="editarCP('{{ $row->codigo_valor_pension }}','{{ $row->valor }}')"
                                    class="text-blue-600 hover:text-blue-800 text-xs font-medium">Editar</button>
                                <form method="POST" action="{{ route('parametros.costo_pension.destroy', $row->codigo_valor_pension) }}"
                                    onsubmit="return confirm('Eliminar?')">
                                    @csrf @method('DELETE')
                                    <button class="text-red-500 hover:text-red-700 text-xs">Eliminar</button>
                                </form>
                            </td>
                            @endif
                        </tr>
                        @empty
                        <tr><td colspan="{{ $esReadOnly ? 2 : 3 }}" class="px-4 py-6 text-center text-gray-400 text-sm">Sin registros</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </div>

    {{-- ══ 4. Costo Transporte ═════════════════════════════════════════════════ --}}
    <div x-show="tab==='costo_transporte'" x-cloak>
        <div class="{{ $esReadOnly ? 'grid grid-cols-1' : 'grid grid-cols-1 lg:grid-cols-3' }} gap-6">

            @if(!$esReadOnly)
            <div class="bg-white rounded-xl shadow p-5">
                <h3 id="ct-titulo" class="font-semibold text-gray-700 mb-4">Nueva tarifa</h3>
                <form method="POST" action="{{ route('parametros.costo_transporte.store') }}" class="space-y-3">
                    @csrf
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Codigo ruta/tarifa</label>
                        <input id="ct-codigo" name="codigo_transporte" maxlength="20" required
                            class="w-full border rounded-lg px-3 py-2 text-sm uppercase" placeholder="Ej: RUTA-1">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Costo ($)</label>
                        <input id="ct-costo" name="costo" type="number" step="0.01" min="0" required
                            class="w-full border rounded-lg px-3 py-2 text-sm" placeholder="0.00">
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="flex-1 bg-blue-700 text-white rounded-lg py-2 text-sm font-medium hover:bg-blue-800">Guardar</button>
                        <button type="button" onclick="limpiarCT()" class="px-3 bg-gray-100 text-gray-600 rounded-lg py-2 text-sm hover:bg-gray-200">Limpiar</button>
                    </div>
                </form>
            </div>
            @endif

            <div class="{{ $esReadOnly ? '' : 'lg:col-span-2' }} bg-white rounded-xl shadow overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs text-gray-500 uppercase">Codigo</th>
                            <th class="px-4 py-3 text-right text-xs text-gray-500 uppercase">Costo</th>
                            @if(!$esReadOnly)<th class="px-4 py-3"></th>@endif
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse($costoTransporte as $row)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-mono font-semibold text-blue-700">{{ $row->codigo_transporte }}</td>
                            <td class="px-4 py-3 text-right font-medium">$ {{ number_format($row->costo, 0, ',', '.') }}</td>
                            @if(!$esReadOnly)
                            <td class="px-4 py-3 text-right flex items-center justify-end gap-3">
                                <button type="button"
                                    onclick="editarCT('{{ $row->codigo_transporte }}','{{ $row->costo }}')"
                                    class="text-blue-600 hover:text-blue-800 text-xs font-medium">Editar</button>
                                <form method="POST" action="{{ route('parametros.costo_transporte.destroy', $row->codigo_transporte) }}"
                                    onsubmit="return confirm('Eliminar?')">
                                    @csrf @method('DELETE')
                                    <button class="text-red-500 hover:text-red-700 text-xs">Eliminar</button>
                                </form>
                            </td>
                            @endif
                        </tr>
                        @empty
                        <tr><td colspan="{{ $esReadOnly ? 2 : 3 }}" class="px-4 py-6 text-center text-gray-400 text-sm">Sin registros</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </div>

    {{-- ══ 5. Pension x Alumno ═════════════════════════════════════════════════ --}}
    <div x-show="tab==='pension'" x-cloak>
        <div class="{{ $esReadOnly ? 'grid grid-cols-1' : 'grid grid-cols-1 lg:grid-cols-3' }} gap-6">

            @if(!$esReadOnly)
            <div class="bg-white rounded-xl shadow p-5">
                <h3 id="pen-titulo" class="font-semibold text-gray-700 mb-4">Asignar / Actualizar</h3>
                <form method="POST" action="{{ route('parametros.pension.store') }}" class="space-y-3">
                    @csrf
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Codigo alumno</label>
                        <input id="pen-alumno" name="codigo_alumno" type="number" required
                            class="w-full border rounded-lg px-3 py-2 text-sm" placeholder="Numero de codigo">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Tarifa pension</label>
                        <select id="pen-tarifa" name="codigo_valor_pension" required class="w-full border rounded-lg px-3 py-2 text-sm">
                            <option value="">Seleccione...</option>
                            @foreach($costoPension as $cp)
                            <option value="{{ $cp->codigo_valor_pension }}">{{ $cp->codigo_valor_pension }} — $ {{ number_format($cp->valor, 0, ',', '.') }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Concepto</label>
                        <select id="pen-concepto" name="codigo_concepto" required class="w-full border rounded-lg px-3 py-2 text-sm">
                            <option value="">Seleccione...</option>
                            @foreach($conceptos as $c)
                            <option value="{{ $c->codigo_concepto }}">{{ $c->codigo_concepto }} — {{ sinT($c->concepto) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Centro de costos</label>
                        <select id="pen-cc" name="centro_costos" required class="w-full border rounded-lg px-3 py-2 text-sm">
                            <option value="">Seleccione...</option>
                            @foreach($centroCostos as $cc)
                            <option value="{{ $cc->codigo_centro_costos }}">{{ $cc->codigo_centro_costos }} — {{ $cc->nombre_centro_costos }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Año</label>
                        <input id="pen-anio" name="anio" type="number" value="{{ $anioActual }}" required
                            class="w-full border rounded-lg px-3 py-2 text-sm">
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="flex-1 bg-blue-700 text-white rounded-lg py-2 text-sm font-medium hover:bg-blue-800">Guardar</button>
                        <button type="button" onclick="limpiarPen()" class="px-3 bg-gray-100 text-gray-600 rounded-lg py-2 text-sm hover:bg-gray-200">Limpiar</button>
                    </div>
                </form>
            </div>
            @endif

            <div class="{{ $esReadOnly ? '' : 'lg:col-span-2' }} bg-white rounded-xl shadow overflow-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="px-3 py-3 text-left text-xs text-gray-500 uppercase">Cod.</th>
                            <th class="px-3 py-3 text-left text-xs text-gray-500 uppercase">Alumno</th>
                            <th class="px-3 py-3 text-left text-xs text-gray-500 uppercase">Tarifa</th>
                            <th class="px-3 py-3 text-left text-xs text-gray-500 uppercase">Concepto</th>
                            <th class="px-3 py-3 text-left text-xs text-gray-500 uppercase">Año</th>
                            @if(!$esReadOnly)<th class="px-3 py-3"></th>@endif
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse($pension as $row)
                        <tr class="hover:bg-gray-50">
                            <td class="px-3 py-2 font-mono text-blue-700">{{ $row->codigo_alumno }}</td>
                            <td class="px-3 py-2">{{ $row->nombre_alumno ?: '—' }}</td>
                            <td class="px-3 py-2 font-mono">{{ $row->codigo_valor_pension }}</td>
                            <td class="px-3 py-2 text-gray-500">{{ $row->codigo_concepto }}</td>
                            <td class="px-3 py-2">{{ $row->anio }}</td>
                            @if(!$esReadOnly)
                            <td class="px-3 py-2 text-right flex items-center justify-end gap-3">
                                <button type="button"
                                    onclick="editarPen('{{ $row->codigo_alumno }}','{{ $row->codigo_valor_pension }}','{{ $row->codigo_concepto }}','{{ $row->centro_costos }}','{{ $row->anio }}')"
                                    class="text-blue-600 hover:text-blue-800 text-xs font-medium">Editar</button>
                                <form method="POST" action="{{ route('parametros.pension.destroy', $row->id) }}"
                                    onsubmit="return confirm('Eliminar?')">
                                    @csrf @method('DELETE')
                                    <button class="text-red-500 hover:text-red-700 text-xs">Eliminar</button>
                                </form>
                            </td>
                            @endif
                        </tr>
                        @empty
                        <tr><td colspan="{{ $esReadOnly ? 5 : 6 }}" class="px-4 py-6 text-center text-gray-400 text-sm">Sin registros</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </div>

    {{-- ══ 6. Transporte x Alumno ══════════════════════════════════════════════ --}}
    <div x-show="tab==='transporte'" x-cloak>
        <div class="{{ $esReadOnly ? 'grid grid-cols-1' : 'grid grid-cols-1 lg:grid-cols-3' }} gap-6">

            @if(!$esReadOnly)
            <div class="bg-white rounded-xl shadow p-5">
                <h3 id="tra-titulo" class="font-semibold text-gray-700 mb-4">Asignar / Actualizar</h3>
                <form method="POST" action="{{ route('parametros.transporte.store') }}" class="space-y-3">
                    @csrf
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Codigo alumno</label>
                        <input id="tra-alumno" name="codigo_alumno" type="number" required
                            class="w-full border rounded-lg px-3 py-2 text-sm" placeholder="Numero de codigo">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Ruta / tarifa transporte</label>
                        <select id="tra-ruta" name="codigo_transporte" required class="w-full border rounded-lg px-3 py-2 text-sm">
                            <option value="">Seleccione...</option>
                            @foreach($costoTransporte as $ct)
                            <option value="{{ $ct->codigo_transporte }}">{{ $ct->codigo_transporte }} — $ {{ number_format($ct->costo, 0, ',', '.') }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Concepto</label>
                        <select id="tra-concepto" name="codigo_concepto" required class="w-full border rounded-lg px-3 py-2 text-sm">
                            <option value="">Seleccione...</option>
                            @foreach($conceptos as $c)
                            <option value="{{ $c->codigo_concepto }}">{{ $c->codigo_concepto }} — {{ sinT($c->concepto) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Centro de costos</label>
                        <select id="tra-cc" name="centro_costos" required class="w-full border rounded-lg px-3 py-2 text-sm">
                            <option value="">Seleccione...</option>
                            @foreach($centroCostos as $cc)
                            <option value="{{ $cc->codigo_centro_costos }}">{{ $cc->codigo_centro_costos }} — {{ $cc->nombre_centro_costos }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Año</label>
                        <input id="tra-anio" name="anio" type="number" value="{{ $anioActual }}" required
                            class="w-full border rounded-lg px-3 py-2 text-sm">
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="flex-1 bg-blue-700 text-white rounded-lg py-2 text-sm font-medium hover:bg-blue-800">Guardar</button>
                        <button type="button" onclick="limpiarTra()" class="px-3 bg-gray-100 text-gray-600 rounded-lg py-2 text-sm hover:bg-gray-200">Limpiar</button>
                    </div>
                </form>
            </div>
            @endif

            <div class="{{ $esReadOnly ? '' : 'lg:col-span-2' }} bg-white rounded-xl shadow overflow-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="px-3 py-3 text-left text-xs text-gray-500 uppercase">Cod.</th>
                            <th class="px-3 py-3 text-left text-xs text-gray-500 uppercase">Alumno</th>
                            <th class="px-3 py-3 text-left text-xs text-gray-500 uppercase">Ruta</th>
                            <th class="px-3 py-3 text-left text-xs text-gray-500 uppercase">Concepto</th>
                            <th class="px-3 py-3 text-left text-xs text-gray-500 uppercase">Año</th>
                            @if(!$esReadOnly)<th class="px-3 py-3"></th>@endif
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse($transporte as $row)
                        <tr class="hover:bg-gray-50">
                            <td class="px-3 py-2 font-mono text-blue-700">{{ $row->codigo_alumno }}</td>
                            <td class="px-3 py-2">{{ $row->nombre_alumno ?: '—' }}</td>
                            <td class="px-3 py-2 font-mono">{{ $row->codigo_transporte }}</td>
                            <td class="px-3 py-2 text-gray-500">{{ $row->codigo_concepto }}</td>
                            <td class="px-3 py-2">{{ $row->anio }}</td>
                            @if(!$esReadOnly)
                            <td class="px-3 py-2 text-right flex items-center justify-end gap-3">
                                <button type="button"
                                    onclick="editarTra('{{ $row->codigo_alumno }}','{{ $row->codigo_transporte }}','{{ $row->codigo_concepto }}','{{ $row->centro_costos }}','{{ $row->anio }}')"
                                    class="text-blue-600 hover:text-blue-800 text-xs font-medium">Editar</button>
                                <form method="POST" action="{{ route('parametros.transporte.destroy', $row->id) }}"
                                    onsubmit="return confirm('Eliminar?')">
                                    @csrf @method('DELETE')
                                    <button class="text-red-500 hover:text-red-700 text-xs">Eliminar</button>
                                </form>
                            </td>
                            @endif
                        </tr>
                        @empty
                        <tr><td colspan="{{ $esReadOnly ? 5 : 6 }}" class="px-4 py-6 text-center text-gray-400 text-sm">Sin registros</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </div>

    {{-- ══ 7. Nivelacion x Alumno ══════════════════════════════════════════════ --}}
    <div x-show="tab==='nivelacion'" x-cloak>
        <div class="{{ $esReadOnly ? 'grid grid-cols-1' : 'grid grid-cols-1 lg:grid-cols-3' }} gap-6">

            @if(!$esReadOnly)
            <div class="bg-white rounded-xl shadow p-5">
                <h3 id="niv-titulo" class="font-semibold text-gray-700 mb-4">Asignar / Actualizar</h3>
                <form method="POST" action="{{ route('parametros.nivelacion.store') }}" class="space-y-3">
                    @csrf
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Codigo alumno</label>
                        <input id="niv-alumno" name="codigo_alumno" type="number" required
                            class="w-full border rounded-lg px-3 py-2 text-sm" placeholder="Numero de codigo">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Codigo valor nivelacion</label>
                        <input id="niv-valor" name="codigo_valor" maxlength="20" required
                            class="w-full border rounded-lg px-3 py-2 text-sm uppercase" placeholder="Ej: NIV-A">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Concepto</label>
                        <select id="niv-concepto" name="codigo_concepto" required class="w-full border rounded-lg px-3 py-2 text-sm">
                            <option value="">Seleccione...</option>
                            @foreach($conceptos as $c)
                            <option value="{{ $c->codigo_concepto }}">{{ $c->codigo_concepto }} — {{ sinT($c->concepto) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Centro de costos</label>
                        <select id="niv-cc" name="centro_costos" required class="w-full border rounded-lg px-3 py-2 text-sm">
                            <option value="">Seleccione...</option>
                            @foreach($centroCostos as $cc)
                            <option value="{{ $cc->codigo_centro_costos }}">{{ $cc->codigo_centro_costos }} — {{ $cc->nombre_centro_costos }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Año</label>
                        <input id="niv-anio" name="anio" type="number" value="{{ $anioActual }}" required
                            class="w-full border rounded-lg px-3 py-2 text-sm">
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="flex-1 bg-blue-700 text-white rounded-lg py-2 text-sm font-medium hover:bg-blue-800">Guardar</button>
                        <button type="button" onclick="limpiarNiv()" class="px-3 bg-gray-100 text-gray-600 rounded-lg py-2 text-sm hover:bg-gray-200">Limpiar</button>
                    </div>
                </form>
            </div>
            @endif

            <div class="{{ $esReadOnly ? '' : 'lg:col-span-2' }} bg-white rounded-xl shadow overflow-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="px-3 py-3 text-left text-xs text-gray-500 uppercase">Cod.</th>
                            <th class="px-3 py-3 text-left text-xs text-gray-500 uppercase">Alumno</th>
                            <th class="px-3 py-3 text-left text-xs text-gray-500 uppercase">Valor</th>
                            <th class="px-3 py-3 text-left text-xs text-gray-500 uppercase">Concepto</th>
                            <th class="px-3 py-3 text-left text-xs text-gray-500 uppercase">Año</th>
                            @if(!$esReadOnly)<th class="px-3 py-3"></th>@endif
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse($nivelacion as $row)
                        <tr class="hover:bg-gray-50">
                            <td class="px-3 py-2 font-mono text-blue-700">{{ $row->codigo_alumno }}</td>
                            <td class="px-3 py-2">{{ $row->nombre_alumno ?: '—' }}</td>
                            <td class="px-3 py-2 font-mono">{{ $row->codigo_valor }}</td>
                            <td class="px-3 py-2 text-gray-500">{{ $row->codigo_concepto }}</td>
                            <td class="px-3 py-2">{{ $row->anio }}</td>
                            @if(!$esReadOnly)
                            <td class="px-3 py-2 text-right flex items-center justify-end gap-3">
                                <button type="button"
                                    onclick="editarNiv('{{ $row->codigo_alumno }}','{{ $row->codigo_valor }}','{{ $row->codigo_concepto }}','{{ $row->centro_costos }}','{{ $row->anio }}')"
                                    class="text-blue-600 hover:text-blue-800 text-xs font-medium">Editar</button>
                                <form method="POST" action="{{ route('parametros.nivelacion.destroy', $row->id) }}"
                                    onsubmit="return confirm('Eliminar?')">
                                    @csrf @method('DELETE')
                                    <button class="text-red-500 hover:text-red-700 text-xs">Eliminar</button>
                                </form>
                            </td>
                            @endif
                        </tr>
                        @empty
                        <tr><td colspan="{{ $esReadOnly ? 5 : 6 }}" class="px-4 py-6 text-center text-gray-400 text-sm">Sin registros</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </div>

    {{-- ══ 8. Listado Transporte ═══════════════════════════════════════════════ --}}
    <div x-show="tab==='listado_transporte'" x-cloak>
        <div class="{{ $esReadOnly ? 'grid grid-cols-1' : 'grid grid-cols-1 lg:grid-cols-3' }} gap-6">

            @if(!$esReadOnly)
            <div class="bg-white rounded-xl shadow p-5">
                <h3 id="lt-titulo" class="font-semibold text-gray-700 mb-4">Agregar / Actualizar</h3>
                <form method="POST" action="{{ route('parametros.listado_transporte.store') }}" class="space-y-3">
                    @csrf
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Codigo alumno</label>
                        <input id="lt-codigo" name="codigo" type="number" required
                            class="w-full border rounded-lg px-3 py-2 text-sm" placeholder="Numero de codigo">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Barrio</label>
                        <input id="lt-barrio" name="barrio" maxlength="60"
                            class="w-full border rounded-lg px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Telefono</label>
                        <input id="lt-tel" name="telefono" maxlength="20"
                            class="w-full border rounded-lg px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Quien recibe</label>
                        <input id="lt-recibe" name="quien_recibe" maxlength="80"
                            class="w-full border rounded-lg px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Clase ruta</label>
                        <input id="lt-clase" name="clase_ruta" maxlength="30"
                            class="w-full border rounded-lg px-3 py-2 text-sm" placeholder="Ej: IDA, VUELTA, AMBAS">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Ruta</label>
                        <input id="lt-ruta" name="ruta" maxlength="30"
                            class="w-full border rounded-lg px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Direccion</label>
                        <input id="lt-dir" name="direccion" maxlength="100"
                            class="w-full border rounded-lg px-3 py-2 text-sm">
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="flex-1 bg-blue-700 text-white rounded-lg py-2 text-sm font-medium hover:bg-blue-800">Guardar</button>
                        <button type="button" onclick="limpiarLT()" class="px-3 bg-gray-100 text-gray-600 rounded-lg py-2 text-sm hover:bg-gray-200">Limpiar</button>
                    </div>
                </form>
            </div>
            @endif

            <div class="{{ $esReadOnly ? '' : 'lg:col-span-2' }} bg-white rounded-xl shadow overflow-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="px-3 py-3 text-left text-xs text-gray-500 uppercase">Cod.</th>
                            <th class="px-3 py-3 text-left text-xs text-gray-500 uppercase">Alumno</th>
                            <th class="px-3 py-3 text-left text-xs text-gray-500 uppercase">Barrio</th>
                            <th class="px-3 py-3 text-left text-xs text-gray-500 uppercase">Ruta</th>
                            <th class="px-3 py-3 text-left text-xs text-gray-500 uppercase">Direccion</th>
                            @if(!$esReadOnly)<th class="px-3 py-3"></th>@endif
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse($listadoTransporte as $row)
                        <tr class="hover:bg-gray-50">
                            <td class="px-3 py-2 font-mono text-blue-700">{{ $row->codigo }}</td>
                            <td class="px-3 py-2">{{ $row->nombre_alumno ?: '—' }}</td>
                            <td class="px-3 py-2">{{ $row->barrio }}</td>
                            <td class="px-3 py-2">{{ $row->ruta }}</td>
                            <td class="px-3 py-2 text-gray-500 text-xs">{{ $row->direccion }}</td>
                            @if(!$esReadOnly)
                            <td class="px-3 py-2 text-right flex items-center justify-end gap-3">
                                <button type="button"
                                    onclick="editarLT('{{ $row->codigo }}','{{ addslashes($row->barrio) }}','{{ $row->telefono }}','{{ addslashes($row->quien_recibe) }}','{{ $row->clase_ruta }}','{{ $row->ruta }}','{{ addslashes($row->direccion) }}')"
                                    class="text-blue-600 hover:text-blue-800 text-xs font-medium">Editar</button>
                                <form method="POST" action="{{ route('parametros.listado_transporte.destroy', $row->id) }}"
                                    onsubmit="return confirm('Eliminar?')">
                                    @csrf @method('DELETE')
                                    <button class="text-red-500 hover:text-red-700 text-xs">Eliminar</button>
                                </form>
                            </td>
                            @endif
                        </tr>
                        @empty
                        <tr><td colspan="{{ $esReadOnly ? 5 : 6 }}" class="px-4 py-6 text-center text-gray-400 text-sm">Sin registros</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </div>

    {{-- ══ 9. Observaciones Contables ══════════════════════════════════════════ --}}
    <div x-show="tab==='observaciones'" x-cloak>
        <div class="{{ $esReadOnly ? 'grid grid-cols-1' : 'grid grid-cols-1 lg:grid-cols-3' }} gap-6">

            @if(!$esReadOnly)
            <div class="bg-white rounded-xl shadow p-5">
                <h3 id="obs-titulo" class="font-semibold text-gray-700 mb-4">Agregar / Actualizar</h3>
                <form method="POST" action="{{ route('parametros.observaciones.store') }}" class="space-y-3">
                    @csrf
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Codigo alumno</label>
                        <input id="obs-alumno" name="codigo_alumno" type="number" required
                            class="w-full border rounded-lg px-3 py-2 text-sm" placeholder="Numero de codigo">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Observacion</label>
                        <textarea id="obs-texto" name="observacion" rows="4"
                            class="w-full border rounded-lg px-3 py-2 text-sm resize-none"
                            placeholder="Texto libre..."></textarea>
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="flex-1 bg-blue-700 text-white rounded-lg py-2 text-sm font-medium hover:bg-blue-800">Guardar</button>
                        <button type="button" onclick="limpiarObs()" class="px-3 bg-gray-100 text-gray-600 rounded-lg py-2 text-sm hover:bg-gray-200">Limpiar</button>
                    </div>
                </form>
            </div>
            @endif

            <div class="{{ $esReadOnly ? '' : 'lg:col-span-2' }} bg-white rounded-xl shadow overflow-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="px-3 py-3 text-left text-xs text-gray-500 uppercase">Cod.</th>
                            <th class="px-3 py-3 text-left text-xs text-gray-500 uppercase">Alumno</th>
                            <th class="px-3 py-3 text-left text-xs text-gray-500 uppercase">Observacion</th>
                            @if(!$esReadOnly)<th class="px-3 py-3"></th>@endif
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse($observaciones as $row)
                        <tr class="hover:bg-gray-50">
                            <td class="px-3 py-2 font-mono text-blue-700">{{ $row->codigo_alumno }}</td>
                            <td class="px-3 py-2">{{ $row->nombre_alumno ?: '—' }}</td>
                            <td class="px-3 py-2 text-gray-600 text-xs max-w-xs truncate">{{ $row->observacion }}</td>
                            @if(!$esReadOnly)
                            <td class="px-3 py-2 text-right flex items-center justify-end gap-3">
                                <button type="button"
                                    onclick="editarObs('{{ $row->codigo_alumno }}','{{ addslashes($row->observacion) }}')"
                                    class="text-blue-600 hover:text-blue-800 text-xs font-medium">Editar</button>
                                <form method="POST" action="{{ route('parametros.observaciones.destroy', $row->id) }}"
                                    onsubmit="return confirm('Eliminar?')">
                                    @csrf @method('DELETE')
                                    <button class="text-red-500 hover:text-red-700 text-xs">Eliminar</button>
                                </form>
                            </td>
                            @endif
                        </tr>
                        @empty
                        <tr><td colspan="{{ $esReadOnly ? 3 : 4 }}" class="px-4 py-6 text-center text-gray-400 text-sm">Sin registros</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </div>

</div>

@push('scripts')
<script>
function foco(id){ const el=document.getElementById(id); if(el){ el.scrollIntoView({behavior:'smooth',block:'center'}); el.focus(); } }

// ── Centro de Costos ─────────────────────────────────────────────────────────
function editarCC(c,n){
    document.getElementById('cc-codigo').value=c;
    document.getElementById('cc-nombre').value=n;
    document.getElementById('cc-titulo').textContent='Editando: '+c;
    foco('cc-nombre');
}
function limpiarCC(){
    document.getElementById('cc-codigo').value='';
    document.getElementById('cc-nombre').value='';
    document.getElementById('cc-titulo').textContent='Nuevo';
}

// ── Conceptos ────────────────────────────────────────────────────────────────
function editarCon(c,desc,cc){
    document.getElementById('con-codigo').value=c;
    document.getElementById('con-desc').value=desc;
    document.getElementById('con-cc').value=cc;
    document.getElementById('con-titulo').textContent='Editando: '+c;
    foco('con-desc');
}
function limpiarCon(){
    document.getElementById('con-codigo').value='';
    document.getElementById('con-desc').value='';
    document.getElementById('con-cc').value='';
    document.getElementById('con-titulo').textContent='Nuevo';
}

// ── Costo Pension ────────────────────────────────────────────────────────────
function editarCP(c,v){
    document.getElementById('cp-codigo').value=c;
    document.getElementById('cp-valor').value=v;
    document.getElementById('cp-titulo').textContent='Editando: '+c;
    foco('cp-valor');
}
function limpiarCP(){
    document.getElementById('cp-codigo').value='';
    document.getElementById('cp-valor').value='';
    document.getElementById('cp-titulo').textContent='Nueva tarifa';
}

// ── Costo Transporte ─────────────────────────────────────────────────────────
function editarCT(c,v){
    document.getElementById('ct-codigo').value=c;
    document.getElementById('ct-costo').value=v;
    document.getElementById('ct-titulo').textContent='Editando: '+c;
    foco('ct-costo');
}
function limpiarCT(){
    document.getElementById('ct-codigo').value='';
    document.getElementById('ct-costo').value='';
    document.getElementById('ct-titulo').textContent='Nueva tarifa';
}

// ── Pension x Alumno ─────────────────────────────────────────────────────────
function editarPen(alum,tarifa,conc,cc,anio){
    document.getElementById('pen-alumno').value=alum;
    document.getElementById('pen-tarifa').value=tarifa;
    document.getElementById('pen-concepto').value=conc;
    document.getElementById('pen-cc').value=cc;
    document.getElementById('pen-anio').value=anio;
    document.getElementById('pen-titulo').textContent='Editando alumno: '+alum;
    foco('pen-tarifa');
}
function limpiarPen(){
    document.getElementById('pen-alumno').value='';
    document.getElementById('pen-tarifa').value='';
    document.getElementById('pen-concepto').value='';
    document.getElementById('pen-cc').value='';
    document.getElementById('pen-anio').value='{{ $anioActual }}';
    document.getElementById('pen-titulo').textContent='Asignar / Actualizar';
}

// ── Transporte x Alumno ──────────────────────────────────────────────────────
function editarTra(alum,ruta,conc,cc,anio){
    document.getElementById('tra-alumno').value=alum;
    document.getElementById('tra-ruta').value=ruta;
    document.getElementById('tra-concepto').value=conc;
    document.getElementById('tra-cc').value=cc;
    document.getElementById('tra-anio').value=anio;
    document.getElementById('tra-titulo').textContent='Editando alumno: '+alum;
    foco('tra-ruta');
}
function limpiarTra(){
    document.getElementById('tra-alumno').value='';
    document.getElementById('tra-ruta').value='';
    document.getElementById('tra-concepto').value='';
    document.getElementById('tra-cc').value='';
    document.getElementById('tra-anio').value='{{ $anioActual }}';
    document.getElementById('tra-titulo').textContent='Asignar / Actualizar';
}

// ── Nivelacion x Alumno ──────────────────────────────────────────────────────
function editarNiv(alum,val,conc,cc,anio){
    document.getElementById('niv-alumno').value=alum;
    document.getElementById('niv-valor').value=val;
    document.getElementById('niv-concepto').value=conc;
    document.getElementById('niv-cc').value=cc;
    document.getElementById('niv-anio').value=anio;
    document.getElementById('niv-titulo').textContent='Editando alumno: '+alum;
    foco('niv-valor');
}
function limpiarNiv(){
    document.getElementById('niv-alumno').value='';
    document.getElementById('niv-valor').value='';
    document.getElementById('niv-concepto').value='';
    document.getElementById('niv-cc').value='';
    document.getElementById('niv-anio').value='{{ $anioActual }}';
    document.getElementById('niv-titulo').textContent='Asignar / Actualizar';
}

// ── Listado Transporte ───────────────────────────────────────────────────────
function editarLT(cod,barrio,tel,recibe,clase,ruta,dir){
    document.getElementById('lt-codigo').value=cod;
    document.getElementById('lt-barrio').value=barrio;
    document.getElementById('lt-tel').value=tel;
    document.getElementById('lt-recibe').value=recibe;
    document.getElementById('lt-clase').value=clase;
    document.getElementById('lt-ruta').value=ruta;
    document.getElementById('lt-dir').value=dir;
    document.getElementById('lt-titulo').textContent='Editando alumno: '+cod;
    foco('lt-barrio');
}
function limpiarLT(){
    ['lt-codigo','lt-barrio','lt-tel','lt-recibe','lt-clase','lt-ruta','lt-dir'].forEach(id=>document.getElementById(id).value='');
    document.getElementById('lt-titulo').textContent='Agregar / Actualizar';
}

// ── Observaciones ────────────────────────────────────────────────────────────
function editarObs(alum,obs){
    document.getElementById('obs-alumno').value=alum;
    document.getElementById('obs-texto').value=obs;
    document.getElementById('obs-titulo').textContent='Editando alumno: '+alum;
    foco('obs-texto');
}
function limpiarObs(){
    document.getElementById('obs-alumno').value='';
    document.getElementById('obs-texto').value='';
    document.getElementById('obs-titulo').textContent='Agregar / Actualizar';
}
</script>
@endpush

@endsection
