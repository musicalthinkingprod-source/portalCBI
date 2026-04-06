@extends('layouts.app-sidebar')

@section('header', 'Reporte de Asistencia')

@section('slot')

    {{-- Filtros generales --}}
    <div class="bg-white rounded-xl shadow p-5 mb-6">
        <form method="GET" action="{{ route('asistencia.reporte') }}" id="form-reporte">

            {{-- Pestañas vista --}}
            <div class="flex gap-2 mb-4">
                <button type="button" onclick="setVista('acumulado')"
                    class="vista-btn px-4 py-1.5 rounded-lg text-sm font-semibold transition
                        {{ $vista === 'acumulado' ? 'bg-blue-800 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                    📊 Acumulado
                </button>
                <button type="button" onclick="setVista('semana')"
                    class="vista-btn px-4 py-1.5 rounded-lg text-sm font-semibold transition
                        {{ $vista === 'semana' ? 'bg-blue-800 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                    📅 Por semana
                </button>
            </div>

            <input type="hidden" name="vista" id="inp-vista" value="{{ $vista }}">

            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 items-end">

                {{-- Campos acumulado --}}
                <div id="campos-acumulado" class="{{ $vista !== 'acumulado' ? 'hidden' : '' }}">
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Desde</label>
                    <input type="date" name="desde" value="{{ $fechaDesde }}"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div id="campos-hasta" class="{{ $vista !== 'acumulado' ? 'hidden' : '' }}">
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Hasta</label>
                    <input type="date" name="hasta" value="{{ $fechaHasta }}"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Curso</label>
                    <select name="curso"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Todos</option>
                        @foreach($cursos as $c)
                            <option value="{{ $c }}" {{ $cursoFiltro == $c ? 'selected' : '' }}>{{ $c }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Estudiante</label>
                    <div class="flex gap-2">
                        <input type="text" name="busqueda" value="{{ $busqueda }}" placeholder="Apellido o nombre..."
                            class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <button type="submit"
                            class="bg-blue-800 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition">
                            Filtrar
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    {{-- ══ VISTA ACUMULADA ══ --}}
    @if($vista === 'acumulado')
    <div class="bg-white rounded-xl shadow overflow-hidden">
        <div class="px-5 py-3 bg-blue-800 text-white flex items-center justify-between">
            <div>
                <h3 class="font-bold text-sm uppercase tracking-wide">Acumulado de asistencia</h3>
                <p class="text-blue-300 text-xs mt-0.5">
                    Del {{ \Carbon\Carbon::parse($fechaDesde)->format('d/m/Y') }}
                    al {{ \Carbon\Carbon::parse($fechaHasta)->format('d/m/Y') }}
                </p>
            </div>
            <span class="text-blue-300 text-xs">{{ $acumulado->count() }} estudiantes</span>
        </div>

        @if($acumulado->isEmpty())
            <div class="px-5 py-8 text-center text-gray-400 text-sm">Sin registros para el período seleccionado.</div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                    <tr>
                        <th class="px-4 py-3 text-left">Estudiante</th>
                        <th class="px-3 py-3 text-center w-16">Curso</th>
                        <th class="px-3 py-3 text-center w-16">Días reg.</th>
                        <th class="px-3 py-3 text-center w-16 text-green-600">Presente</th>
                        <th class="px-3 py-3 text-center w-16 text-red-600">Ausente</th>
                        <th class="px-3 py-3 text-center w-16 text-yellow-600">Excusa</th>
                        <th class="px-3 py-3 text-center w-20 text-purple-600">Sal. Ant.</th>
                        <th class="px-3 py-3 text-center w-16 text-orange-500">Retardo</th>
                        <th class="px-3 py-3 text-center w-20 text-gray-500">Sin carnet</th>
                        <th class="px-3 py-3 text-center w-20 text-gray-500">Sin uniforme</th>
                        <th class="px-3 py-3 text-center w-24 text-gray-500">Mal. present.</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($acumulado as $r)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2 font-medium">
                            {{ $r->APELLIDO1 }} {{ $r->APELLIDO2 }} {{ $r->NOMBRE1 }} {{ $r->NOMBRE2 }}
                        </td>
                        <td class="px-3 py-2 text-center text-gray-500">{{ $r->CURSO }}</td>
                        <td class="px-3 py-2 text-center text-gray-500">{{ $r->total_dias }}</td>
                        <td class="px-3 py-2 text-center font-semibold text-green-700">{{ $r->presentes }}</td>
                        <td class="px-3 py-2 text-center font-semibold {{ $r->ausentes > 0 ? 'text-red-600' : 'text-gray-400' }}">{{ $r->ausentes }}</td>
                        <td class="px-3 py-2 text-center font-semibold {{ $r->excusas > 0 ? 'text-yellow-600' : 'text-gray-400' }}">{{ $r->excusas }}</td>
                        <td class="px-3 py-2 text-center font-semibold {{ $r->salidas_anticipadas > 0 ? 'text-purple-600' : 'text-gray-400' }}">{{ $r->salidas_anticipadas }}</td>
                        <td class="px-3 py-2 text-center font-semibold {{ $r->retardos > 0 ? 'text-orange-500' : 'text-gray-400' }}">{{ $r->retardos }}</td>
                        <td class="px-3 py-2 text-center {{ $r->falta_carnet > 0 ? 'text-red-500 font-semibold' : 'text-gray-400' }}">{{ $r->falta_carnet }}</td>
                        <td class="px-3 py-2 text-center {{ $r->falta_uniforme > 0 ? 'text-red-500 font-semibold' : 'text-gray-400' }}">{{ $r->falta_uniforme }}</td>
                        <td class="px-3 py-2 text-center {{ $r->falta_presentacion > 0 ? 'text-red-500 font-semibold' : 'text-gray-400' }}">{{ $r->falta_presentacion }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
    @endif

    {{-- ══ VISTA SEMANAL ══ --}}
    @if($vista === 'semana')

    {{-- Navegación de semana --}}
    <div class="flex items-center justify-between mb-4">
        <a href="{{ route('asistencia.reporte', array_merge(request()->except('semana'), ['vista' => 'semana', 'semana' => $semanaAnterior])) }}"
            class="bg-white shadow hover:bg-gray-50 text-gray-700 font-semibold text-sm px-4 py-2 rounded-lg transition flex items-center gap-2">
            ← Semana anterior
        </a>
        <span class="text-sm font-semibold text-gray-700 bg-white shadow px-4 py-2 rounded-lg">
            📅 {{ $semanaLabel }}
        </span>
        <a href="{{ route('asistencia.reporte', array_merge(request()->except('semana'), ['vista' => 'semana', 'semana' => $semanaSiguiente])) }}"
            class="bg-white shadow hover:bg-gray-50 text-gray-700 font-semibold text-sm px-4 py-2 rounded-lg transition flex items-center gap-2">
            Semana siguiente →
        </a>
    </div>

    <div class="bg-white rounded-xl shadow overflow-hidden">
        <div class="px-5 py-3 bg-blue-800 text-white">
            <h3 class="font-bold text-sm uppercase tracking-wide">Asistencia por día</h3>
            <p class="text-blue-300 text-xs mt-0.5">
                <span class="inline-flex items-center gap-1 mr-3"><span class="w-2 h-2 rounded-full bg-green-400 inline-block"></span>P=Presente</span>
                <span class="inline-flex items-center gap-1 mr-3"><span class="w-2 h-2 rounded-full bg-red-400 inline-block"></span>A=Ausente</span>
                <span class="inline-flex items-center gap-1 mr-3"><span class="w-2 h-2 rounded-full bg-yellow-300 inline-block"></span>EX=Excusa</span>
                <span class="inline-flex items-center gap-1 mr-3"><span class="w-2 h-2 rounded-full bg-purple-400 inline-block"></span>SA=Salida Anticipada</span>
                <span class="text-blue-400">· 🪪=sin carnet · 👔=sin uniforme · ⏰=retardo · 🚩=presentación</span>
            </p>
        </div>

        @if($estudiantesSemana->isEmpty())
            <div class="px-5 py-8 text-center text-gray-400 text-sm">
                Sin registros para esta semana{{ $cursoFiltro ? ' en el curso ' . $cursoFiltro : '' }}.
            </div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 text-xs">
                    <tr>
                        <th class="px-4 py-3 text-left uppercase tracking-wide">Estudiante</th>
                        <th class="px-2 py-3 text-center w-16 uppercase tracking-wide text-gray-400">Curso</th>
                        @foreach($dias as $dia)
                        <th class="px-2 py-3 text-center w-28">
                            <span class="font-semibold {{ $dia->isToday() ? 'text-blue-600' : '' }}">
                                {{ $dia->locale('es')->isoFormat('ddd') }}
                            </span>
                            <span class="block font-normal text-gray-400 text-xs">{{ $dia->format('d/m') }}</span>
                        </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($estudiantesSemana as $est)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2 font-medium whitespace-nowrap">
                            {{ $est->APELLIDO1 }} {{ $est->APELLIDO2 }} {{ $est->NOMBRE1 }}
                        </td>
                        <td class="px-2 py-2 text-center text-gray-400 text-xs">{{ $est->CURSO }}</td>
                        @foreach($dias as $dia)
                        @php
                            $reg = $mapaAsist[$est->CODIGO][$dia->format('Y-m-d')] ?? null;
                        @endphp
                        <td class="px-2 py-2 text-center">
                            @if($reg)
                                @php
                                    $color = match($reg->ASISTENCIA) {
                                        'P'  => 'bg-green-100 text-green-700',
                                        'A'  => 'bg-red-100 text-red-700',
                                        'EX' => 'bg-yellow-100 text-yellow-700',
                                        'SA' => 'bg-purple-100 text-purple-700',
                                        default => 'bg-gray-100 text-gray-500'
                                    };
                                @endphp
                                <span class="inline-block {{ $color }} font-bold text-xs px-2 py-0.5 rounded-full">
                                    {{ $reg->ASISTENCIA }}
                                </span>
                                <div class="flex justify-center gap-0.5 mt-0.5 text-xs">
                                    @if($reg->CARNET)       <span title="Sin carnet">🪪</span>      @endif
                                    @if($reg->UNIFORME)     <span title="Sin uniforme">👔</span>    @endif
                                    @if($reg->RETARDO)      <span title="Retardo">⏰</span>         @endif
                                    @if($reg->PRESENTACION) <span title="Mala presentación">🚩</span> @endif
                                </div>
                            @else
                                <span class="text-gray-300 text-xs">—</span>
                            @endif
                        </td>
                        @endforeach
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
    @endif

@endsection

@push('scripts')
<script>
    function setVista(v) {
        document.getElementById('inp-vista').value = v;
        document.getElementById('form-reporte').submit();
    }

    const vista = '{{ $vista }}';
    if (vista === 'acumulado') {
        document.getElementById('campos-acumulado').classList.remove('hidden');
        document.getElementById('campos-hasta').classList.remove('hidden');
    }
</script>
@endpush
