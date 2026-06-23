@extends('layouts.padres')

@section('header', 'Agenda Estudiantil Virtual')

@section('slot')

@php
    $nombreCompleto = preg_replace('/\s+/', ' ', trim(
        ($estudiante->NOMBRE1 ?? '') . ' ' . ($estudiante->NOMBRE2 ?? '') . ' ' .
        ($estudiante->APELLIDO1 ?? '') . ' ' . ($estudiante->APELLIDO2 ?? '')
    ));
    $badge = fn($color) => match ($color) {
        'blue'   => 'bg-blue-100 text-blue-800',
        'indigo' => 'bg-indigo-100 text-indigo-800',
        'red'    => 'bg-red-100 text-red-800',
        'green'  => 'bg-green-100 text-green-800',
        'amber'  => 'bg-amber-100 text-amber-800',
        default  => 'bg-gray-100 text-gray-700',
    };
    $pendientesAlta = $entradas->filter(fn($e) => $e->prioridad === 'alta' && empty($e->acknowledged_at))->count();
@endphp

<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-800">📖 Agenda Estudiantil Virtual</h2>
    <p class="text-gray-500 text-sm mt-0.5">
        Observaciones registradas por la institución sobre
        <strong class="text-gray-700">{{ $nombreCompleto }}</strong>
        (código <span class="font-mono">{{ $estudiante->CODIGO }}</span>).
    </p>
</div>

@if(session('ok'))
<div class="mb-5 bg-green-50 border border-green-300 text-green-800 px-4 py-3 rounded-xl text-sm font-medium">✅ {{ session('ok') }}</div>
@endif

@if($pendientesAlta > 0)
<div class="mb-5 bg-red-50 border border-red-300 text-red-800 px-4 py-3 rounded-xl text-sm font-medium">
    ⚠️ Tienes <strong>{{ $pendientesAlta }}</strong> observación(es) de <strong>prioridad alta</strong> sin confirmar. Por favor léelas y confirma su lectura.
</div>
@endif

@if($entradas->isEmpty())
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 text-center">
    <p class="text-4xl mb-3">📭</p>
    <p class="text-gray-600 font-medium">Aún no hay observaciones registradas.</p>
    <p class="text-gray-400 text-sm mt-1">Cuando la institución registre una observación sobre tu hijo(a), aparecerá aquí.</p>
</div>
@else
<div class="space-y-3">
    @foreach($entradas as $e)
    @php $alta = $e->prioridad === 'alta'; $leida = !empty($e->acknowledged_at); $hiloE = $comentarios[$e->id] ?? collect(); @endphp
    <div class="bg-white rounded-2xl shadow-sm border p-5 {{ $alta && !$leida ? 'border-red-300 ring-1 ring-red-100' : 'border-gray-100' }}" x-data="{ hilo: {{ $hiloE->count() ? 'true' : 'false' }} }">
        <div class="flex items-center justify-between flex-wrap gap-2 mb-2">
            <div class="flex items-center gap-2 flex-wrap">
                <span class="inline-block px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $badge($e->categoria_color) }}">{{ $e->categoria }}</span>
                @if($alta)
                <span class="inline-block px-2.5 py-0.5 rounded-full text-xs font-bold bg-red-600 text-white">⚠ Prioridad alta</span>
                @endif
            </div>
            <span class="text-xs text-gray-400">{{ \Carbon\Carbon::parse($e->fecha)->locale('es')->isoFormat('dddd D [de] MMMM [de] YYYY') }}</span>
        </div>
        @if($e->es_aula && $e->materia)
        <p class="text-xs font-semibold text-gray-500 mb-1">📘 {{ $e->materia }}</p>
        @endif
        <p class="text-sm text-gray-700 whitespace-pre-line leading-relaxed">{{ $e->observacion }}</p>
        @if($e->es_aula && $e->registrado_nombre)
        <p class="text-xs text-gray-400 mt-2 pt-2 border-t border-gray-50">Registrado por: <span class="font-medium text-gray-600">{{ $e->registrado_nombre }}</span></p>
        @endif

        {{-- Acuse de recibo --}}
        <div class="mt-3 pt-3 border-t border-gray-100">
            @if($leida)
            <p class="text-xs font-semibold text-green-700">✓ Lectura confirmada el {{ \Carbon\Carbon::parse($e->acknowledged_at)->locale('es')->isoFormat('D MMM YYYY, h:mm a') }}</p>
            @elseif($alta)
            {{-- Prioridad alta: confirmación destacada --}}
            <form method="POST" action="{{ route('padres.bitacora.confirmar', $e->id) }}"
                  onsubmit="return confirm('Confirmas que leíste esta observación de prioridad alta. Este acuse queda registrado. ¿Continuar?');">
                @csrf
                <button type="submit" class="w-full sm:w-auto bg-red-600 hover:bg-red-700 text-white px-5 py-2.5 rounded-xl text-sm font-bold transition shadow">
                    He leído y confirmo esta observación
                </button>
            </form>
            @else
            <form method="POST" action="{{ route('padres.bitacora.confirmar', $e->id) }}">
                @csrf
                <button type="submit" class="text-sm font-semibold text-blue-700 hover:text-blue-900 border border-blue-200 rounded-lg px-4 py-1.5 transition">
                    Confirmar lectura
                </button>
            </form>
            @endif
        </div>

        {{-- Hilo de conversación --}}
        <div class="mt-3 pt-3 border-t border-gray-100">
            <button type="button" @click="hilo=!hilo" class="text-xs font-semibold text-blue-700 hover:text-blue-900">
                💬 Conversación<span class="text-gray-400">{{ $hiloE->count() ? ' ('.$hiloE->count().')' : '' }}</span>
            </button>
            <div x-show="hilo" x-cloak class="mt-2 space-y-2">
                @forelse($hiloE as $c)
                <div class="flex items-start gap-2">
                    <div class="flex-1 bg-gray-50 rounded-xl px-3 py-2">
                        <p class="text-xs leading-tight">
                            <span class="font-semibold {{ $c->autor_rol === 'acudiente' ? 'text-emerald-700' : 'text-blue-700' }}">{{ $c->autor_nombre }}</span>
                            <span class="text-gray-400">· {{ \Carbon\Carbon::parse($c->created_at)->locale('es')->isoFormat('D MMM, h:mm a') }}</span>
                        </p>
                        <p class="text-sm text-gray-700 whitespace-pre-line">{{ $c->mensaje }}</p>
                    </div>
                    @if($c->autor_rol === 'acudiente' && (string) $c->autor_id === (string) $estudiante->CODIGO)
                    <form method="POST" action="{{ route('padres.bitacora.comentar.destroy', $c->id) }}" onsubmit="return confirm('¿Eliminar tu comentario?');">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-gray-300 hover:text-red-500 text-sm" title="Eliminar">✕</button>
                    </form>
                    @endif
                </div>
                @empty
                <p class="text-xs text-gray-400">Aún no hay mensajes. Si tienes algo que comentar sobre esta anotación, escríbelo aquí.</p>
                @endforelse

                <form method="POST" action="{{ route('padres.bitacora.comentar', $e->id) }}" class="flex gap-2 pt-1">
                    @csrf
                    <input type="text" name="mensaje" required maxlength="4000" placeholder="Escribe una respuesta…"
                        class="flex-1 border border-gray-300 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    <button type="submit" class="bg-blue-700 hover:bg-blue-800 text-white px-4 py-2 rounded-xl text-sm font-semibold whitespace-nowrap">Enviar</button>
                </form>
            </div>
        </div>
    </div>
    @endforeach
</div>
@endif

@endsection
