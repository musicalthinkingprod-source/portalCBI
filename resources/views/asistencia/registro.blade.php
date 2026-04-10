@extends('layouts.app-sidebar')

@section('header', 'Registro de Asistencia')

@section('slot')

    @if(session('success'))
        <div class="mb-4 p-3 bg-green-100 text-green-800 rounded-xl text-sm">✅ {{ session('success') }}</div>
    @endif

    {{-- Filtros --}}
    <div class="bg-white rounded-xl shadow p-5 mb-6">
        <form method="GET" action="{{ route('asistencia.registro') }}" id="form-filtros">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 items-end">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Fecha</label>
                    <input type="date" name="fecha" value="{{ $fecha }}" max="{{ today()->format('Y-m-d') }}"
                        id="inp-fecha"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Curso</label>
                    <select name="curso" id="sel-curso" data-remember="asistencia_curso"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">— Selecciona un curso —</option>
                        @foreach($cursos as $c)
                            <option value="{{ $c }}" {{ $cursoSelec == $c ? 'selected' : '' }}>{{ $c }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </form>
    </div>

    @if($esFinde)
        <div class="bg-yellow-50 border border-yellow-200 text-yellow-700 rounded-xl p-4 text-sm">
            La fecha seleccionada es fin de semana. Selecciona un día entre semana.
        </div>

    @elseif($cursoSelec && $estudiantes->isEmpty())
        <div class="bg-yellow-50 border border-yellow-200 text-yellow-700 rounded-xl p-4 text-sm">
            No hay estudiantes matriculados en el curso <strong>{{ $cursoSelec }}</strong>.
        </div>

    @elseif($cursoSelec)
    <form method="POST" action="{{ route('asistencia.guardar') }}">
        @csrf
        <input type="hidden" name="fecha" value="{{ $fecha }}">
        <input type="hidden" name="curso" value="{{ $cursoSelec }}">

        <div class="bg-white rounded-xl shadow overflow-hidden">
            <div class="px-5 py-3 bg-blue-800 text-white flex items-center justify-between">
                <div>
                    <h3 class="font-bold text-sm uppercase tracking-wide">Curso {{ $cursoSelec }}</h3>
                    <p class="text-blue-300 text-xs mt-0.5">
                        {{ \Carbon\Carbon::parse($fecha)->locale('es')->isoFormat('dddd D [de] MMMM [de] YYYY') }}
                        · {{ $estudiantes->count() }} estudiantes
                        @if(count($existentes) > 0)
                            · <span class="text-green-300">{{ count($existentes) }} ya registrados</span>
                        @endif
                    </p>
                </div>
                <button type="submit"
                    class="bg-white text-blue-800 hover:bg-blue-50 font-semibold text-xs px-4 py-1.5 rounded-lg transition">
                    💾 Guardar asistencia
                </button>
            </div>

            {{-- Leyenda --}}
            <div class="px-5 py-2 bg-gray-50 border-b border-gray-100 flex flex-wrap gap-4 text-xs text-gray-500">
                <span class="flex items-center gap-1"><span class="w-3 h-3 rounded-full bg-green-500 inline-block"></span> Presente</span>
                <span class="flex items-center gap-1"><span class="w-3 h-3 rounded-full bg-red-500 inline-block"></span> Ausente</span>
                <span class="flex items-center gap-1"><span class="w-3 h-3 rounded-full bg-yellow-400 inline-block"></span> Excusa</span>
                <span class="text-gray-400">· Marcar si hay falta: Sin carnet · Sin uniforme · Retardo · Mala presentación</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                        <tr>
                            <th class="px-4 py-3 text-left">Estudiante</th>
                            <th class="px-4 py-3 text-center w-36">Asistencia</th>
                            <th class="px-4 py-3 text-center w-24">Sin carnet</th>
                            <th class="px-4 py-3 text-center w-24">Sin uniforme</th>
                            <th class="px-4 py-3 text-center w-20">Retardo</th>
                            <th class="px-4 py-3 text-center w-28">Mala presentación</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($estudiantes as $est)
                        @php
                            $ex = $existentes[$est->CODIGO] ?? null;
                        @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2 font-medium">
                                {{ $est->APELLIDO1 }} {{ $est->APELLIDO2 }} {{ $est->NOMBRE1 }} {{ $est->NOMBRE2 }}
                            </td>
                            <td class="px-4 py-2 text-center">
                                <select name="asistencia[{{ $est->CODIGO }}][estado]"
                                    class="border border-gray-300 rounded-lg px-2 py-1 text-xs focus:outline-none focus:ring-2 focus:ring-blue-400 estado-select">
                                    <option value="P"  {{ (!$ex || $ex->ASISTENCIA === 'P')  ? 'selected' : '' }}>✅ Presente</option>
                                    <option value="A"  {{ ($ex && $ex->ASISTENCIA === 'A')  ? 'selected' : '' }}>❌ Ausente</option>
                                    <option value="EX" {{ ($ex && $ex->ASISTENCIA === 'EX') ? 'selected' : '' }}>📄 Excusa</option>
                                    <option value="SA" {{ ($ex && $ex->ASISTENCIA === 'SA') ? 'selected' : '' }}>🚪 Salida Anticipada</option>
                                </select>
                            </td>
                            <td class="px-4 py-2 text-center">
                                <input type="checkbox" name="asistencia[{{ $est->CODIGO }}][carnet]"
                                    {{ ($ex && $ex->CARNET) ? 'checked' : '' }}
                                    class="w-4 h-4 accent-red-500">
                            </td>
                            <td class="px-4 py-2 text-center">
                                <input type="checkbox" name="asistencia[{{ $est->CODIGO }}][uniforme]"
                                    {{ ($ex && $ex->UNIFORME) ? 'checked' : '' }}
                                    class="w-4 h-4 accent-red-500">
                            </td>
                            <td class="px-4 py-2 text-center">
                                <input type="checkbox" name="asistencia[{{ $est->CODIGO }}][retardo]"
                                    {{ ($ex && $ex->RETARDO) ? 'checked' : '' }}
                                    class="w-4 h-4 accent-orange-500">
                            </td>
                            <td class="px-4 py-2 text-center">
                                <input type="checkbox" name="asistencia[{{ $est->CODIGO }}][presentacion]"
                                    {{ ($ex && $ex->PRESENTACION) ? 'checked' : '' }}
                                    class="w-4 h-4 accent-red-500">
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="px-5 py-3 border-t border-gray-100 flex justify-end">
                <button type="submit"
                    class="bg-blue-800 hover:bg-blue-700 text-white font-semibold text-sm px-6 py-2 rounded-lg transition">
                    💾 Guardar asistencia
                </button>
            </div>
        </div>
    </form>

    @else
        <div class="bg-gray-50 border border-gray-200 text-gray-500 rounded-xl p-6 text-center text-sm">
            Selecciona una fecha y un curso para registrar asistencia.
        </div>
    @endif

@endsection

@push('scripts')
<script>
    document.getElementById('inp-fecha').addEventListener('change', () => {
        document.getElementById('form-filtros').submit();
    });
    document.getElementById('sel-curso').addEventListener('change', () => {
        document.getElementById('form-filtros').submit();
    });
</script>
@endpush
