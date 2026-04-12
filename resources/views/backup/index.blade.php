@extends('layouts.app-sidebar')

@section('header', 'Copia de Seguridad')

@section('slot')
<div class="max-w-5xl mx-auto space-y-6">

    {{-- Alertas de sesión --}}
    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 rounded-xl px-5 py-3 text-sm">
            {{ session('success') }}
        </div>
    @endif

    {{-- ── Tarjeta principal: descargar --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <div class="flex flex-col sm:flex-row sm:items-center gap-4">
            <div class="flex-1">
                <h2 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                    💾 Descargar copia de seguridad
                </h2>
                <p class="text-sm text-gray-500 mt-1">
                    Genera y descarga un archivo <code class="bg-gray-100 px-1 rounded">.sql</code>
                    con la estructura y todos los datos de la base de datos.
                </p>

                {{-- Estado de hoy --}}
                <div class="mt-3 flex items-center gap-2">
                    @if($copiaHoy)
                        <span class="inline-flex items-center gap-1.5 bg-green-50 text-green-700 border border-green-200 text-xs font-medium px-3 py-1 rounded-full">
                            ✅ Ya se realizó una copia hoy
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 bg-amber-50 text-amber-700 border border-amber-200 text-xs font-medium px-3 py-1 rounded-full">
                            ⚠️ No hay copia de seguridad de hoy
                        </span>
                    @endif

                    @if($miUltimaCopia)
                        <span class="text-xs text-gray-400">
                            Tu última copia:
                            {{ \Carbon\Carbon::parse($miUltimaCopia->created_at)->format('d/m/Y H:i') }}
                        </span>
                    @endif
                </div>
            </div>

            <form method="GET" action="{{ route('backup.descargar') }}">
                <button type="submit"
                    class="inline-flex items-center gap-2 bg-blue-700 hover:bg-blue-800 active:scale-95 text-white font-semibold text-sm px-6 py-3 rounded-xl transition shadow-sm whitespace-nowrap">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    Descargar ahora
                </button>
            </form>
        </div>

        <div class="mt-4 bg-blue-50 border border-blue-100 rounded-xl px-4 py-3 text-xs text-blue-700 leading-relaxed">
            <strong>Recomendación:</strong> Descarga la copia de seguridad diariamente antes de terminar tu jornada.
            Guárdala en un lugar seguro fuera del servidor (computador local, Google Drive, etc.).
        </div>
    </div>

    {{-- ── Panel SuperAd: resumen por usuario + historial ── --}}
    @if($isSuperAd)

    {{-- Resumen por usuario --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <h3 class="text-base font-bold text-gray-800 mb-4">👥 Resumen por usuario</h3>

        @if($porUsuario->isEmpty())
            <p class="text-sm text-gray-400 italic">Aún no hay registros de copias.</p>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100">
                            <th class="text-left py-2 pr-4 font-semibold text-gray-600 text-xs uppercase tracking-wide">Usuario</th>
                            <th class="text-left py-2 pr-4 font-semibold text-gray-600 text-xs uppercase tracking-wide">Perfil</th>
                            <th class="text-left py-2 pr-4 font-semibold text-gray-600 text-xs uppercase tracking-wide">Última copia</th>
                            <th class="text-left py-2 font-semibold text-gray-600 text-xs uppercase tracking-wide">Total descargas</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($porUsuario as $fila)
                        @php
                            $ultima    = \Carbon\Carbon::parse($fila->ultima);
                            $esHoy     = $ultima->isToday();
                            $esReciente = $ultima->diffInDays(now()) <= 1;
                        @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="py-2 pr-4 font-medium text-gray-800">{{ $fila->usuario }}</td>
                            <td class="py-2 pr-4">
                                <span class="bg-blue-50 text-blue-700 text-xs px-2 py-0.5 rounded-full font-medium">
                                    {{ $fila->profile }}
                                </span>
                            </td>
                            <td class="py-2 pr-4">
                                <span class="flex items-center gap-1.5">
                                    @if($esHoy)
                                        <span class="w-2 h-2 rounded-full bg-green-500 inline-block"></span>
                                        <span class="text-green-700 font-medium">{{ $ultima->format('d/m/Y H:i') }}</span>
                                    @elseif($esReciente)
                                        <span class="w-2 h-2 rounded-full bg-amber-400 inline-block"></span>
                                        <span class="text-amber-700">{{ $ultima->format('d/m/Y H:i') }}</span>
                                    @else
                                        <span class="w-2 h-2 rounded-full bg-red-400 inline-block"></span>
                                        <span class="text-red-600">{{ $ultima->format('d/m/Y H:i') }}</span>
                                    @endif
                                </span>
                            </td>
                            <td class="py-2 text-gray-500">{{ $fila->total }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="text-[11px] text-gray-400 mt-3">
                🟢 Hoy &nbsp;|&nbsp; 🟡 Ayer &nbsp;|&nbsp; 🔴 Hace 2+ días
            </p>
        @endif
    </div>

    {{-- Historial completo --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <h3 class="text-base font-bold text-gray-800 mb-4">📋 Historial de descargas (últimas 100)</h3>

        @if($historial->isEmpty())
            <p class="text-sm text-gray-400 italic">No hay registros aún.</p>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100">
                            <th class="text-left py-2 pr-4 font-semibold text-gray-600 text-xs uppercase tracking-wide">#</th>
                            <th class="text-left py-2 pr-4 font-semibold text-gray-600 text-xs uppercase tracking-wide">Usuario</th>
                            <th class="text-left py-2 pr-4 font-semibold text-gray-600 text-xs uppercase tracking-wide">Perfil</th>
                            <th class="text-left py-2 pr-4 font-semibold text-gray-600 text-xs uppercase tracking-wide">Fecha y hora</th>
                            <th class="text-left py-2 font-semibold text-gray-600 text-xs uppercase tracking-wide">IP</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($historial as $reg)
                        <tr class="hover:bg-gray-50">
                            <td class="py-2 pr-4 text-gray-400 text-xs">{{ $reg->id }}</td>
                            <td class="py-2 pr-4 font-medium text-gray-800">{{ $reg->usuario }}</td>
                            <td class="py-2 pr-4">
                                <span class="bg-blue-50 text-blue-700 text-xs px-2 py-0.5 rounded-full">{{ $reg->profile }}</span>
                            </td>
                            <td class="py-2 pr-4 text-gray-600">
                                {{ \Carbon\Carbon::parse($reg->created_at)->format('d/m/Y H:i:s') }}
                            </td>
                            <td class="py-2 text-gray-400 text-xs font-mono">{{ $reg->ip ?? '—' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    @endif {{-- isSuperAd --}}

</div>
@endsection
