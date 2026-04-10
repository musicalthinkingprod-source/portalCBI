@extends('layouts.app-sidebar')
@section('header', 'Asistencia Personal — Estado del día')

@section('slot')
@php
    use App\Http\Controllers\AsistenciaPersonalController as AP;
    Carbon\Carbon::setLocale('es');
@endphp

<div class="space-y-5">

    {{-- Selector de fecha --}}
    <form method="GET" class="flex items-center gap-3">
        <input type="date" name="fecha" value="{{ $fecha }}"
            class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-blue-500"
            onchange="this.form.submit()">
        <span class="text-sm text-gray-500">
            {{ \Carbon\Carbon::parse($fecha)->locale('es')->isoFormat('dddd D [de] MMMM [de] YYYY') }}
            @if($diaAcademico && $diaAcademico->dia_ciclo > 0)
                · <span class="font-semibold text-blue-700">Día académico {{ $diaAcademico->dia_ciclo }}</span>
            @endif
        </span>
    </form>

    {{-- Resumen --}}
    <div class="grid grid-cols-3 md:grid-cols-6 gap-3">
        @foreach([
            ['presente',     'bg-green-600',  $resumen['presente']],
            ['retardo',      'bg-yellow-500', $resumen['retardo']],
            ['ausente',      'bg-red-600',    $resumen['ausente']],
            ['permiso',      'bg-blue-600',   $resumen['permiso']],
            ['incapacidad',  'bg-purple-600', $resumen['incapacidad']],
            ['sin_registro', 'bg-gray-400',   $resumen['sin_registro']],
        ] as [$key, $color, $count])
        <div class="bg-white rounded-xl shadow-sm p-3 text-center">
            <p class="text-2xl font-black text-gray-800">{{ $count }}</p>
            <span class="inline-block text-[10px] font-bold text-white px-2 py-0.5 rounded-full {{ $color }} mt-1">
                {{ AP::$estadoLabel[$key] ?? 'Sin registro' }}
            </span>
        </div>
        @endforeach
    </div>

    {{-- Tabla de docentes --}}
    <div class="bg-white rounded-xl shadow overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-blue-50 text-blue-900 text-xs uppercase">
                    <th class="px-4 py-3 text-left">Docente</th>
                    <th class="px-4 py-3 text-center">Estado</th>
                    <th class="px-4 py-3 text-center">Hora llegada</th>
                    <th class="px-4 py-3 text-left">Observación</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($docentes as $doc)
                <tr class="hover:bg-gray-50 {{ is_null($doc->estado) ? 'opacity-50' : '' }}">
                    <td class="px-4 py-2.5 font-medium text-gray-800">{{ $doc->NOMBRE_DOC }}</td>
                    <td class="px-4 py-2.5 text-center">
                        @if($doc->estado)
                            <span class="inline-block text-xs font-semibold px-2 py-0.5 rounded-full {{ AP::$estadoColor[$doc->estado] ?? '' }}">
                                {{ AP::$estadoLabel[$doc->estado] }}
                            </span>
                        @else
                            <span class="text-gray-300 text-xs">Sin registrar</span>
                        @endif
                    </td>
                    <td class="px-4 py-2.5 text-center text-gray-500 text-xs">
                        {{ $doc->hora_llegada ?? '—' }}
                    </td>
                    <td class="px-4 py-2.5 text-gray-500 text-xs italic">
                        {{ $doc->observacion ?? '—' }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @auth
        @if(auth()->user()->PROFILE === 'SecA')
        <div class="flex justify-end">
            <a href="{{ route('asistencia-personal.registro', ['fecha' => $fecha]) }}"
               class="bg-blue-700 hover:bg-blue-800 text-white text-sm font-medium px-5 py-2 rounded-lg transition">
                Registrar asistencia
            </a>
        </div>
        @endif
    @endauth

</div>
@endsection
