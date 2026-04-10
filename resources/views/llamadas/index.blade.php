@extends('layouts.app-sidebar')

@section('header', 'Registro de Llamadas por Inasistencia')

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

{{-- Selector de fecha --}}
<div class="bg-white rounded-xl shadow p-5 mb-6">
    <form method="GET" action="{{ route('llamadas.index') }}" class="flex gap-3 items-end">
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Fecha de inasistencia</label>
            <input type="date" name="fecha" value="{{ $fecha }}"
                class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
        </div>
        <button type="submit"
            class="bg-blue-800 hover:bg-blue-700 text-white px-5 py-2 rounded-lg text-sm font-semibold transition">
            Cargar ausentes
        </button>
    </form>
</div>

{{-- Resumen del día --}}
@if($ausentes->isNotEmpty())
<div class="grid grid-cols-3 gap-4 mb-6">
    <div class="bg-red-50 border border-red-200 rounded-xl p-4 text-center">
        <p class="text-xs text-red-400 uppercase tracking-wide mb-0.5">Total ausentes</p>
        <p class="text-2xl font-bold text-red-700">{{ $ausentes->count() }}</p>
    </div>
    <div class="bg-green-50 border border-green-200 rounded-xl p-4 text-center">
        <p class="text-xs text-green-400 uppercase tracking-wide mb-0.5">Llamadas registradas</p>
        <p class="text-2xl font-bold text-green-700">{{ $registradas }}</p>
    </div>
    <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4 text-center">
        <p class="text-xs text-yellow-500 uppercase tracking-wide mb-0.5">Pendientes</p>
        <p class="text-2xl font-bold text-yellow-700">{{ $pendientes }}</p>
    </div>
</div>
@endif

{{-- Listado de ausentes --}}
@if($ausentes->isEmpty())
    <div class="bg-white rounded-xl shadow p-8 text-center text-gray-400 text-sm">
        No hay estudiantes con inasistencia registrada para el
        <span class="font-semibold text-gray-600">{{ \Carbon\Carbon::parse($fecha)->translatedFormat('d \d\e F \d\e Y') }}</span>.
    </div>
@else

<div class="space-y-4" x-data="{ abierto: null }">

    @foreach($ausentes as $est)
    @php $yaRegistrado = !is_null($est->llamada_id); @endphp

    <div class="bg-white rounded-xl shadow overflow-hidden">

        {{-- Encabezado del estudiante --}}
        <div class="flex items-center justify-between px-5 py-3
                    {{ $yaRegistrado ? 'bg-green-50 border-b border-green-100' : 'bg-gray-50 border-b border-gray-100' }}">
            <div class="flex items-center gap-3">
                <span class="{{ $yaRegistrado ? 'text-green-600' : 'text-yellow-500' }} text-lg">
                    {{ $yaRegistrado ? '✅' : '📞' }}
                </span>
                <div>
                    <p class="font-semibold text-gray-800 text-sm">{{ $est->nombre_completo }}</p>
                    <p class="text-xs text-gray-400">
                        Código {{ $est->CODIGO }} · {{ $est->CURSO }}
                        @if($est->ruta_transporte)
                            · <span class="text-blue-600 font-medium">🚌 Ruta {{ $est->ruta_transporte }}</span>
                        @endif
                        @if($est->ASISTENCIA === 'SA')
                            · <span class="text-orange-500 font-medium">Salida anticipada</span>
                        @else
                            · <span class="text-red-500 font-medium">Ausente</span>
                        @endif
                    </p>
                </div>
            </div>

            <button type="button"
                @click="abierto = (abierto === {{ $est->CODIGO }}) ? null : {{ $est->CODIGO }}"
                class="text-sm px-3 py-1.5 rounded-lg border transition
                       {{ $yaRegistrado
                          ? 'border-green-300 text-green-700 hover:bg-green-100'
                          : 'border-blue-300 text-blue-700 hover:bg-blue-50' }}">
                {{ $yaRegistrado ? 'Ver / Editar' : 'Registrar llamada' }}
            </button>
        </div>

        {{-- Si ya tiene motivo y no está expandido, muestra resumen --}}
        @if($yaRegistrado)
        <div x-show="abierto !== {{ $est->CODIGO }}" class="px-5 py-3 text-sm text-gray-600">
            <span class="font-medium text-gray-700">Motivo:</span> {{ $est->motivo }}
            @if($est->quien_atendio)
                · <span class="text-gray-500">Atendió: {{ $est->quien_atendio }}</span>
            @endif
        </div>
        @endif

        {{-- Formulario expandible --}}
        <div x-show="abierto === {{ $est->CODIGO }}" x-cloak class="px-5 py-4 border-t border-gray-100">
            <form method="POST" action="{{ route('llamadas.store') }}" class="space-y-3">
                @csrf
                <input type="hidden" name="codigo" value="{{ $est->CODIGO }}">
                <input type="hidden" name="fecha_inasistencia" value="{{ $fecha }}">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-medium text-gray-500 mb-1">
                            Motivo de la inasistencia <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="motivo" required maxlength="300"
                            value="{{ old('motivo_'.$est->CODIGO, $est->motivo ?? '') }}"
                            placeholder="Ej: Cita médica, enfermedad general, problemas familiares..."
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Quien atendio la llamada</label>
                        <input type="text" name="quien_atendio" maxlength="100"
                            value="{{ old('quien_atendio_'.$est->CODIGO, $est->quien_atendio ?? '') }}"
                            placeholder="Ej: Mamá, Papá, Acudiente..."
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Observacion adicional</label>
                        <input type="text" name="observacion" maxlength="1000"
                            value="{{ old('observacion_'.$est->CODIGO, $est->observacion ?? '') }}"
                            placeholder="Opcional"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    </div>
                </div>

                <div class="flex gap-2 pt-1">
                    <button type="submit"
                        class="bg-blue-700 hover:bg-blue-800 text-white px-4 py-2 rounded-lg text-sm font-semibold transition">
                        {{ $yaRegistrado ? 'Actualizar' : 'Guardar llamada' }}
                    </button>
                    <button type="button" @click="abierto = null"
                        class="px-4 py-2 rounded-lg text-sm border border-gray-300 text-gray-600 hover:bg-gray-50 transition">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>

    </div>
    @endforeach

</div>

@endif

@endsection
