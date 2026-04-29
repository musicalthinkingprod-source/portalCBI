@extends('layouts.app-sidebar')

@section('header', 'Horario — ' . ($nombreDocente ?? 'Docente'))

@section('slot')
@php
    $esSuperAd = auth()->user()->PROFILE === 'SuperAd';

    $horaInicio = [
        1=>'7:00', 2=>'7:45', 3=>'8:50', 4=>'9:35',
        5=>'10:20', 6=>'11:05', 7=>'12:10', 8=>'12:55',
    ];
    $horaFin = [
        1=>'7:45', 2=>'8:30', 3=>'9:35', 4=>'10:20',
        5=>'11:05', 6=>'11:50', 7=>'12:55', 8=>'13:40',
    ];
@endphp

<div class="max-w-6xl mx-auto py-6 px-4" x-data="reemplazoModal()">

    {{-- Encabezado y selector --}}
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
            <a href="{{ route('horarios.index') }}" class="text-blue-600 text-sm hover:underline">&larr; Horarios</a>
            <h1 class="text-2xl font-bold text-gray-800 mt-1">
                @if($nombreDocente)
                    Horario — <span class="text-indigo-700">{{ $nombreDocente }}</span>
                @else
                    Horario por docente
                @endif
            </h1>
        </div>

        <form action="{{ route('horarios.por_docente') }}" method="GET" class="flex items-center gap-2">
            <select name="docente" onchange="this.form.submit()"
                class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="">— Seleccionar —</option>
                @foreach($docentes as $doc)
                    <option value="{{ $doc->CODIGO_EMP }}" {{ $doc->CODIGO_EMP === $docenteActual ? 'selected' : '' }}>
                        {{ $doc->NOMBRE_DOC }}{{ ($doc->ESTADO ?? '') !== 'ACTIVO' ? ' ⚠ sin docente' : '' }}
                    </option>
                @endforeach
            </select>
        </form>
    </div>

    @if(!$docenteActual)
        <div class="bg-gray-50 border border-gray-200 text-gray-500 rounded-lg p-6 text-sm text-center">
            Selecciona un docente para ver su horario.
        </div>
    @elseif(empty($diasConDatos))
        <div class="bg-yellow-50 border border-yellow-200 text-yellow-700 rounded-lg p-4 text-sm">
            No hay horario registrado para este docente.
        </div>
    @else

    {{-- Feedback --}}
    @if(session('success_reemplazo'))
        <div class="bg-green-50 border border-green-200 text-green-700 rounded-lg px-4 py-3 text-sm mb-4">
            {{ session('success_reemplazo') }}
        </div>
    @endif

    {{-- Grilla --}}
    <div class="bg-white rounded-xl shadow overflow-x-auto">
        <table class="min-w-full text-sm border-collapse">
            <thead>
                <tr class="bg-indigo-700 text-white">
                    <th class="px-4 py-3 text-left font-semibold w-28">Hora</th>
                    @foreach($diasConDatos as $diaNum)
                    @php $proxFecha = $proximaFecha[$diaNum] ?? null; @endphp
                    <th class="px-4 py-3 text-center font-semibold">
                        {{ $dias[$diaNum] ?? 'Día '.$diaNum }}
                        @if($proxFecha)
                            <div class="text-xs font-normal opacity-75 mt-0.5">
                                {{ $proxFecha->locale('es')->isoFormat('ddd D MMM') }}
                            </div>
                        @endif
                    </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($horas as $horaNum => $horaLabel)
                <tr class="{{ $loop->even ? 'bg-gray-50' : 'bg-white' }} border-b border-gray-100">
                    {{-- Columna hora --}}
                    <td class="px-4 whitespace-nowrap" style="height:80px;vertical-align:middle;">
                        <span class="font-semibold text-indigo-700 text-xs">{{ $horaNum }}ª hora</span><br>
                        <span class="text-gray-400 text-xs">{{ $horaInicio[$horaNum] ?? '' }} – {{ $horaFin[$horaNum] ?? '' }}</span>
                    </td>

                    @foreach($diasConDatos as $diaNum)
                    @php
                        $celdas    = $grid[$horaNum][$diaNum] ?? [];
                        $proxFecha = $proximaFecha[$diaNum] ?? null;
                    @endphp
                    <td class="px-3 text-center" style="height:80px;vertical-align:middle;">
                        @if(!empty($celdas))
                            @foreach($celdas as $celda)
                            @php
                                $rems = $reemplazosGrid[$diaNum][$horaNum][$celda['curso']] ?? [];
                            @endphp
                            <div class="inline-block min-w-[90px] mb-1">
                                {{-- Ficha de clase --}}
                                <div class="bg-indigo-50 border border-indigo-100 rounded-lg px-2 text-center"
                                     style="height:56px;display:flex;flex-direction:column;justify-content:center;overflow:hidden;">
                                    <span class="block text-xs font-bold text-indigo-700" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $celda['curso'] }}</span>
                                    <span class="block text-xs text-gray-600" style="overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;line-height:1.3;">{{ $celda['materia'] }}</span>
                                </div>

                                {{-- Reemplazos existentes --}}
                                @foreach($rems as $rem)
                                <div class="mt-1 bg-amber-50 border border-amber-200 rounded-lg px-2 py-1 text-xs text-left">
                                    <div class="font-semibold text-amber-700 leading-tight">
                                        🔁 {{ \Carbon\Carbon::parse($rem->fecha)->format('d/m') }}
                                    </div>
                                    <div class="text-amber-600 leading-tight truncate max-w-[120px]" title="{{ $rem->nombre_reemplazo }}">
                                        {{ $rem->nombre_reemplazo }}
                                    </div>
                                    @if($esSuperAd)
                                    <form method="POST" action="{{ route('asistencia-personal.reemplazos.quitar', $rem->id) }}"
                                          onsubmit="return confirm('¿Quitar este reemplazo?')" class="mt-0.5">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-red-400 hover:text-red-600 text-xs">✕ quitar</button>
                                    </form>
                                    @endif
                                </div>
                                @endforeach

                                {{-- Botón asignar reemplazo (solo SuperAd) --}}
                                @if($esSuperAd && $proxFecha)
                                <button type="button"
                                    @click="abrir({
                                        ausente:   '{{ $docenteActual }}',
                                        hora:      {{ $horaNum }},
                                        diaCiclo:  {{ $diaNum }},
                                        curso:     '{{ $celda['curso'] }}',
                                        fecha:     '{{ $proxFecha->format('Y-m-d') }}',
                                        horaLabel: '{{ $horaNum }}ª hora ({{ $horaInicio[$horaNum] ?? '' }})',
                                        cursoLabel:'{{ $celda['curso'] }} – {{ addslashes($celda['materia']) }}'
                                    })"
                                    class="mt-1 w-full text-xs bg-indigo-600 hover:bg-indigo-700 text-white rounded px-2 py-0.5 transition">
                                    + Reemplazo
                                </button>
                                @endif
                            </div>
                            @endforeach
                        @else
                            <span class="text-gray-200 text-xs">—</span>
                        @endif
                    </td>
                    @endforeach
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @endif

    {{-- ══ Modal de reemplazo (solo SuperAd) ══ --}}
    @if($esSuperAd && $docenteActual)
    <div x-show="abierto" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm"
         @keydown.escape.window="cerrar()">

        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 overflow-hidden"
             @click.outside="cerrar()">

            <div class="bg-indigo-700 px-6 py-4 flex items-center justify-between">
                <h2 class="text-white font-bold text-base">Asignar reemplazo</h2>
                <button @click="cerrar()" class="text-white/70 hover:text-white text-xl leading-none">&times;</button>
            </div>

            <form method="POST" action="{{ route('asistencia-personal.reemplazos.asignar') }}" class="px-6 py-5 space-y-4">
                @csrf

                {{-- Contexto --}}
                <div class="bg-indigo-50 rounded-lg px-4 py-3 text-sm">
                    <div class="font-semibold text-indigo-800">{{ $nombreDocente }}</div>
                    <div class="text-indigo-600 text-xs mt-0.5" x-text="datos.horaLabel + ' · Curso ' + datos.cursoLabel"></div>
                </div>

                <input type="hidden" name="codigo_emp_ausente" :value="datos.ausente">
                <input type="hidden" name="hora"               :value="datos.hora">
                <input type="hidden" name="curso"              :value="datos.curso">

                {{-- Fecha --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Fecha del reemplazo</label>
                    <input type="date" name="fecha" :value="datos.fecha"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400 focus:outline-none"
                           required>
                    <p class="text-xs text-gray-400 mt-1">Próxima ocurrencia sugerida. Puedes cambiarla.</p>
                </div>

                {{-- Docente de reemplazo --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Docente que reemplaza</label>
                    <select name="codigo_emp_reemplazo"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400 focus:outline-none"
                            required>
                        <option value="">— Seleccionar —</option>
                        <template x-for="doc in disponibles" :key="doc.codigo">
                            <option :value="doc.codigo"
                                    x-text="(doc.delCurso ? '★ ' : '') + doc.nombre + ' (' + doc.reemplazos + ' reem.)'">
                            </option>
                        </template>
                    </select>
                    <p class="text-xs text-gray-400 mt-1">
                        ★ = dicta en este curso · ordenados por menos reemplazos en el ciclo
                    </p>
                    <p x-show="disponibles.length === 0" class="text-xs text-red-500 mt-1">
                        No hay docentes libres en esta hora.
                    </p>
                </div>

                <div class="flex gap-3 pt-1">
                    <button type="submit"
                        class="flex-1 bg-indigo-700 hover:bg-indigo-800 text-white font-semibold rounded-lg py-2 text-sm transition">
                        Guardar reemplazo
                    </button>
                    <button type="button" @click="cerrar()"
                        class="flex-1 border border-gray-300 text-gray-600 hover:bg-gray-50 rounded-lg py-2 text-sm transition">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

</div>

<script>
const _ocupadosPorSlot  = @json($ocupadosPorSlot ?? []);
const _reemplazosCiclo  = @json($reemplazosCiclo ?? []);
const _docentesPorCurso = @json($docentesPorCurso ?? []);
const _todosDocentes    = @json($docentesActivos->map(fn($d) => ['codigo' => $d->CODIGO_EMP, 'nombre' => $d->NOMBRE_DOC])->values());

function reemplazoModal() {
    return {
        abierto: false,
        datos: { ausente:'', hora:1, diaCiclo:1, curso:'', fecha:'', horaLabel:'', cursoLabel:'' },
        disponibles: [],

        abrir(d) {
            this.datos = d;

            // Docentes ocupados en ese día/hora
            const ocupados = (_ocupadosPorSlot[d.diaCiclo] ?? {})[d.hora] ?? [];

            // Docentes que dictan en ese curso
            const delCurso = _docentesPorCurso[d.curso] ?? [];

            this.disponibles = _todosDocentes
                .filter(doc => doc.codigo !== d.ausente && !ocupados.includes(doc.codigo))
                .map(doc => ({
                    codigo:     doc.codigo,
                    nombre:     doc.nombre,
                    delCurso:   delCurso.includes(doc.codigo),
                    reemplazos: _reemplazosCiclo[doc.codigo] ?? 0,
                }))
                .sort((a, b) => {
                    // 1. Primero los del curso
                    if (b.delCurso !== a.delCurso) return (b.delCurso ? 1 : 0) - (a.delCurso ? 1 : 0);
                    // 2. Luego los que menos reemplazos tienen
                    if (a.reemplazos !== b.reemplazos) return a.reemplazos - b.reemplazos;
                    // 3. Alfabético
                    return a.nombre.localeCompare(b.nombre);
                });

            this.abierto = true;
        },

        cerrar() { this.abierto = false; },
    }
}
</script>
@endsection
