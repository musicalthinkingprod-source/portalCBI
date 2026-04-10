@extends('layouts.app-sidebar')
@section('header', 'Reemplazos del día')

@section('slot')
@php
    use App\Http\Controllers\AsistenciaPersonalController as AP;
    Carbon\Carbon::setLocale('es');
@endphp

<div class="space-y-5">

    {{-- Selector fecha --}}
    <div class="flex items-center gap-4 flex-wrap">
        <form method="GET" class="flex items-center gap-3">
            <input type="date" name="fecha" value="{{ $fecha }}"
                class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-blue-500"
                onchange="this.form.submit()">
        </form>
        <span class="text-sm text-gray-500">
            {{ \Carbon\Carbon::parse($fecha)->locale('es')->isoFormat('dddd D [de] MMMM') }}
            @if($diaAcademico)
                · <span class="font-semibold text-blue-700">Día académico {{ $diaAcademico }}</span>
            @else
                · <span class="text-gray-400 italic">Sin día académico registrado</span>
            @endif
        </span>
        <span class="text-xs text-gray-400">
            Ciclo desde {{ \Carbon\Carbon::parse($inicioCiclo)->isoFormat('D MMM') }}
        </span>
    </div>

    @if(session('success_reemplazo'))
        <div class="p-3 bg-green-100 text-green-800 rounded-xl text-sm">✅ {{ session('success_reemplazo') }}</div>
    @endif

    @if($ausentes->isEmpty())
        <div class="bg-green-50 border border-green-200 rounded-xl p-5 text-sm text-green-700">
            ✅ No hay docentes ausentes registrados para esta fecha.
        </div>
    @else

    {{-- Tarjeta por cada docente ausente --}}
    @foreach($ausentes as $doc)
    @php $clases = $horarioAusentes[$doc->codigo_doc] ?? collect(); @endphp

    <div class="bg-white rounded-xl shadow overflow-hidden">

        {{-- Encabezado del docente --}}
        <div class="flex items-center gap-3 px-5 py-3 bg-red-50 border-b border-red-100">
            <div>
                <span class="font-semibold text-gray-800">{{ $doc->NOMBRE_DOC }}</span>
                <span class="ml-2 text-xs font-bold px-2 py-0.5 rounded-full {{ AP::$estadoColor[$doc->estado] ?? 'bg-gray-100' }}">
                    {{ AP::$estadoLabel[$doc->estado] ?? $doc->estado }}
                </span>
                @if($doc->observacion)
                    <span class="ml-2 text-xs text-gray-400 italic">{{ $doc->observacion }}</span>
                @endif
            </div>
        </div>

        @if($clases->isEmpty())
            <p class="px-5 py-4 text-sm text-gray-400 italic">Sin clases en el día académico {{ $diaAcademico ?? '—' }}.</p>
        @else

        <table class="w-full text-sm">
            <thead>
                <tr class="text-xs uppercase text-gray-400 bg-gray-50 border-b border-gray-100">
                    <th class="px-4 py-2 text-left w-28">Hora</th>
                    <th class="px-4 py-2 text-center w-20">Curso</th>
                    <th class="px-4 py-2 text-left w-40">Materia</th>
                    <th class="px-4 py-2 text-left">Reemplazo asignado / Disponibles</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($clases as $clase)
                @php
                    $slotKey  = $doc->codigo_doc . '_' . $clase->HORA . '_' . $clase->CURSO;
                    $asignado = $yaAsignados->get($slotKey)?->first();
                    $dispList = $disponiblesPorHoraCurso[$clase->HORA][$clase->CURSO] ?? collect();
                @endphp
                <tr class="hover:bg-gray-50 align-top">
                    <td class="px-4 py-3 font-semibold text-gray-600 whitespace-nowrap">
                        {{ $horas[$clase->HORA] ?? $clase->HORA.'ª hora' }}
                    </td>
                    <td class="px-4 py-3 text-center">
                        <span class="font-bold text-blue-700">{{ $clase->CURSO }}</span>
                    </td>
                    <td class="px-4 py-3 text-gray-700">{{ $clase->NOMBRE_MAT ?? '—' }}</td>
                    <td class="px-4 py-3">

                        {{-- Ya tiene reemplazo asignado --}}
                        @if($asignado)
                        <div class="flex items-center gap-3 flex-wrap">
                            @php
                                $numReemplazos = $reemplazosPorDocente[$asignado->codigo_doc_reemplazo] ?? 0;
                                $nombreReemplazo = \Illuminate\Support\Facades\DB::table('CODIGOS_DOC')
                                    ->where('CODIGO_DOC', $asignado->codigo_doc_reemplazo)
                                    ->value('NOMBRE_DOC') ?? $asignado->codigo_doc_reemplazo;
                            @endphp
                            <span class="inline-flex items-center gap-2 bg-green-100 text-green-800 font-semibold text-sm px-3 py-1.5 rounded-lg">
                                ✓ {{ $nombreReemplazo }}
                                <span class="bg-green-700 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full" title="Reemplazos este ciclo">
                                    {{ $numReemplazos }}
                                </span>
                            </span>
                            <form method="POST"
                                  action="{{ route('asistencia-personal.reemplazos.quitar', $asignado->id) }}"
                                  onsubmit="return confirm('¿Quitar este reemplazo?')">
                                @csrf @method('DELETE')
                                <input type="hidden" name="fecha" value="{{ $fecha }}">
                                <button type="submit" class="text-xs text-red-500 hover:text-red-700 underline">
                                    Quitar
                                </button>
                            </form>
                        </div>

                        {{-- Sin reemplazo: mostrar lista de disponibles --}}
                        @elseif($dispList->isEmpty())
                            <span class="text-xs text-red-400 italic">Sin docentes disponibles esta hora</span>
                        @else
                        <div class="flex flex-wrap gap-1.5">
                            @foreach($dispList as $disp)
                            <form method="POST" action="{{ route('asistencia-personal.reemplazos.asignar') }}">
                                @csrf
                                <input type="hidden" name="fecha"               value="{{ $fecha }}">
                                <input type="hidden" name="codigo_doc_ausente"  value="{{ $doc->codigo_doc }}">
                                <input type="hidden" name="codigo_doc_reemplazo"value="{{ $disp['codigo'] }}">
                                <input type="hidden" name="hora"                value="{{ $clase->HORA }}">
                                <input type="hidden" name="curso"               value="{{ $clase->CURSO }}">
                                <button type="submit"
                                    title="{{ $disp['reemplazos'] }} reemplazo(s) este ciclo"
                                    class="inline-flex items-center gap-1.5 text-xs font-semibold px-2.5 py-1 rounded-lg border transition
                                        {{ $disp['del_curso']
                                            ? 'bg-blue-50 border-blue-300 text-blue-800 hover:bg-blue-100'
                                            : 'bg-gray-50 border-gray-200 text-gray-700 hover:bg-gray-100' }}">
                                    @if($disp['del_curso'])
                                        <span class="w-1.5 h-1.5 rounded-full bg-blue-500 inline-block shrink-0"
                                              title="Da clases a este curso"></span>
                                    @endif
                                    {{ $disp['nombre'] }}
                                    <span class="bg-gray-200 text-gray-600 text-[10px] font-bold px-1.5 py-0.5 rounded-full min-w-[18px] text-center">
                                        {{ $disp['reemplazos'] }}
                                    </span>
                                </button>
                            </form>
                            @endforeach
                        </div>
                        <p class="text-[10px] text-gray-400 mt-1.5">
                            <span class="inline-block w-1.5 h-1.5 rounded-full bg-blue-500 mr-1"></span>Da clases al curso ·
                            <span class="font-semibold">N</span> = reemplazos asignados este ciclo
                        </p>
                        @endif

                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>
    @endforeach

    @endif

</div>
@endsection
