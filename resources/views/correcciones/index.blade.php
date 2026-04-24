@extends('layouts.app-sidebar')

@section('header', 'Solicitudes de Corrección de Notas')

@section('slot')

    @if(session('success'))
        <div class="mb-4 p-3 bg-green-100 text-green-800 rounded-xl text-sm">✅ {{ session('success') }}</div>
    @endif

    {{-- Acciones y filtros --}}
    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div class="flex gap-2">
            <a href="{{ route('correcciones.create') }}"
                class="bg-blue-800 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition">
                ✏️ Nueva solicitud
            </a>
            @if($esSuperior && $pendientes > 0)
                <span class="inline-flex items-center bg-red-100 text-red-700 text-xs font-bold px-3 py-2 rounded-lg">
                    🔔 {{ $pendientes }} pendiente(s)
                </span>
            @endif
        </div>

        <form method="GET" action="{{ route('correcciones.index') }}" class="flex gap-2 items-center">
            <select name="estado"
                class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                <option value="">Todos los estados</option>
                <option value="PENDIENTE"  {{ request('estado') === 'PENDIENTE'  ? 'selected' : '' }}>Pendientes</option>
                <option value="APROBADA"   {{ request('estado') === 'APROBADA'   ? 'selected' : '' }}>Aprobadas</option>
                <option value="RECHAZADA"  {{ request('estado') === 'RECHAZADA'  ? 'selected' : '' }}>Rechazadas</option>
            </select>
            <button type="submit"
                class="bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-semibold px-3 py-2 rounded-lg transition">
                Filtrar
            </button>
        </form>
    </div>

    @if($solicitudes->isEmpty())
        <div class="bg-gray-50 border border-gray-200 text-gray-500 rounded-xl p-8 text-center text-sm">
            No hay solicitudes{{ request('estado') ? ' con estado ' . request('estado') : '' }}.
        </div>
    @else
    <div class="space-y-4">
        @foreach($solicitudes as $s)
        @php
            $badgeEstado = match($s->estado) {
                'PENDIENTE' => 'bg-yellow-100 text-yellow-800',
                'APROBADA'  => 'bg-green-100 text-green-800',
                'RECHAZADA' => 'bg-red-100 text-red-700',
            };
            $iconoEstado = match($s->estado) {
                'PENDIENTE' => '🕐',
                'APROBADA'  => '✅',
                'RECHAZADA' => '❌',
            };
        @endphp
        <div class="bg-white rounded-xl shadow overflow-hidden">
            {{-- Cabecera de la tarjeta --}}
            <div class="px-5 py-3 border-b border-gray-100 flex flex-wrap items-center justify-between gap-2">
                <div class="flex items-center gap-3">
                    <span class="text-xs font-bold px-2.5 py-1 rounded-full {{ $badgeEstado }}">
                        {{ $iconoEstado }} {{ $s->estado }}
                    </span>
                    <span class="text-xs text-gray-400">
                        #{{ $s->id }} · {{ \Carbon\Carbon::parse($s->created_at)->format('d/m/Y H:i') }}
                    </span>
                </div>
                @if($esSuperior && $s->NOMBRE_DOC)
                    <span class="text-xs text-gray-500 font-medium">{{ $s->NOMBRE_DOC }}</span>
                @endif
            </div>

            <div class="px-5 py-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-3">
                    <div>
                        <p class="text-xs text-gray-400 uppercase tracking-wide">Estudiante</p>
                        <p class="text-sm font-semibold text-gray-800">{{ trim($s->nombre_alumno) ?: 'Cód. '.$s->codigo_alum }}</p>
                        <p class="text-xs text-gray-400">
                            Código: {{ $s->codigo_alum }}
                            @if(!empty($s->curso_alumno))
                                · Curso: <span class="font-semibold text-gray-600">{{ $s->curso_alumno }}</span>
                            @endif
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 uppercase tracking-wide">Materia / Período</p>
                        <p class="text-sm font-semibold text-gray-800">{{ $s->NOMBRE_MAT ?? 'Mat. '.$s->codigo_mat }}</p>
                        <p class="text-xs text-gray-400">Período {{ $s->periodo }} · {{ $s->anio }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 uppercase tracking-wide">Corrección solicitada</p>
                        <p class="text-sm font-semibold">
                            <span class="text-red-600">{{ number_format($s->nota_actual, 1) }}</span>
                            <span class="text-gray-400 mx-1">→</span>
                            <span class="text-green-600">{{ number_format($s->nota_propuesta, 1) }}</span>
                        </p>
                    </div>
                    @if($s->revisado_at)
                    <div>
                        <p class="text-xs text-gray-400 uppercase tracking-wide">Revisado</p>
                        <p class="text-xs text-gray-600">{{ \Carbon\Carbon::parse($s->revisado_at)->format('d/m/Y H:i') }}</p>
                        <p class="text-xs text-gray-400">por {{ $s->revisado_por }}</p>
                    </div>
                    @endif
                </div>

                <div class="bg-gray-50 rounded-lg px-4 py-3 text-sm text-gray-700 mb-3">
                    <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Motivo</p>
                    {{ $s->motivo }}
                </div>

                @if($s->observacion)
                <div class="bg-blue-50 rounded-lg px-4 py-3 text-sm text-blue-800 mb-3">
                    <p class="text-xs text-blue-400 uppercase tracking-wide mb-1">Observación del revisor</p>
                    {{ $s->observacion }}
                </div>
                @endif

                {{-- Acciones del admin para solicitudes pendientes --}}
                @if($esSuperior && $s->estado === 'PENDIENTE')
                <div class="flex flex-wrap gap-3 mt-2" x-data="{ rechazando: false }">

                    {{-- Aprobar --}}
                    <form method="POST" action="{{ route('correcciones.aprobar', $s->id) }}"
                        onsubmit="return confirm('¿Aprobar esta corrección? La nota quedará en {{ $s->nota_propuesta }}.')">
                        @csrf
                        <div class="flex gap-2 items-end">
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Observación (opcional)</label>
                                <input type="text" name="observacion" placeholder="Ej: Verificado con el docente…"
                                    class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm w-56 focus:outline-none focus:ring-2 focus:ring-green-400">
                            </div>
                            <button type="submit"
                                class="bg-green-600 hover:bg-green-700 text-white text-sm font-semibold px-4 py-1.5 rounded-lg transition">
                                ✅ Aprobar
                            </button>
                        </div>
                    </form>

                    {{-- Rechazar --}}
                    <form method="POST" action="{{ route('correcciones.rechazar', $s->id) }}"
                        onsubmit="return this.querySelector('[name=observacion]').value.length >= 5 || (alert('Escribe el motivo del rechazo.'), false)">
                        @csrf
                        <div class="flex gap-2 items-end">
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Motivo del rechazo <span class="text-red-500">*</span></label>
                                <input type="text" name="observacion" required minlength="5"
                                    placeholder="Razón por la que se rechaza…"
                                    class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm w-56 focus:outline-none focus:ring-2 focus:ring-red-400">
                            </div>
                            <button type="submit"
                                class="bg-red-600 hover:bg-red-700 text-white text-sm font-semibold px-4 py-1.5 rounded-lg transition">
                                ❌ Rechazar
                            </button>
                        </div>
                    </form>
                </div>
                @endif
            </div>
        </div>
        @endforeach
    </div>

    @if($solicitudes->hasPages())
    <div class="mt-5">{{ $solicitudes->links() }}</div>
    @endif
    @endif

@endsection
