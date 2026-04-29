@extends('layouts.app-sidebar')
@section('header', 'Registrar Asistencia')

@section('slot')
@php
    use App\Http\Controllers\AsistenciaPersonalController as AP;
    Carbon\Carbon::setLocale('es');
@endphp

<div class="max-w-4xl mx-auto space-y-5">

    @if(session('success'))
        <div class="p-3 bg-green-100 text-green-800 rounded-xl text-sm">✅ {{ session('success') }}</div>
    @endif

    <form method="POST" action="{{ route('asistencia-personal.guardar') }}">
        @csrf

        {{-- Selector fecha --}}
        <div class="bg-white rounded-xl shadow p-4 flex items-center gap-4 flex-wrap">
            <div>
                <label class="block text-xs text-gray-500 mb-1">Fecha</label>
                <input type="date" name="fecha" value="{{ $fecha }}"
                    id="fechaInput"
                    class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-blue-500">
            </div>
            <p class="text-sm text-gray-500 mt-4">
                {{ \Carbon\Carbon::parse($fecha)->locale('es')->isoFormat('dddd D [de] MMMM [de] YYYY') }}
            </p>
        </div>

        {{-- Lista de docentes --}}
        <div class="bg-white rounded-xl shadow overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-blue-50 text-blue-900 text-xs uppercase">
                        <th class="px-4 py-3 text-left">Docente</th>
                        <th class="px-4 py-3 text-center w-44">Estado</th>
                        <th class="px-4 py-3 text-center w-32">Hora llegada</th>
                        <th class="px-4 py-3 text-left">Observación</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($docentes as $doc)
                    @php
                        $estadoActual = $doc->tipo_permiso ? $doc->tipo_permiso : ($doc->estado ?? 'presente');
                        $tienePermiso = !is_null($doc->tipo_permiso);
                    @endphp
                    <tr class="hover:bg-gray-50 {{ $tienePermiso ? 'bg-blue-50' : '' }}">
                        <td class="px-4 py-2.5 font-medium text-gray-800">
                            {{ $doc->NOMBRE_DOC }}
                            @if($tienePermiso)
                                <span class="ml-1 text-[10px] bg-blue-100 text-blue-700 px-1.5 py-0.5 rounded-full font-semibold">
                                    {{ AP::$tipoPermisoLabel[$doc->tipo_permiso] ?? $doc->tipo_permiso }}
                                </span>
                            @endif
                        </td>
                        <td class="px-3 py-2 text-center">
                            <select name="asistencias[{{ $doc->CODIGO_EMP }}][estado]"
                                onchange="toggleHora(this, '{{ $doc->CODIGO_EMP }}')"
                                class="estado-select border border-gray-300 rounded-lg px-2 py-1 text-xs focus:ring-2 focus:ring-blue-500 w-full"
                                {{ $tienePermiso ? 'disabled' : '' }}>
                                @foreach(AP::$estadoLabel as $val => $lbl)
                                    <option value="{{ $val }}" {{ $estadoActual === $val ? 'selected' : '' }}>
                                        {{ $lbl }}
                                    </option>
                                @endforeach
                            </select>
                            @if($tienePermiso)
                                <input type="hidden" name="asistencias[{{ $doc->CODIGO_EMP }}][estado]"
                                    value="{{ $doc->tipo_permiso === 'incapacidad' ? 'incapacidad' : 'permiso' }}">
                            @endif
                        </td>
                        <td class="px-3 py-2 text-center">
                            <input type="time"
                                name="asistencias[{{ $doc->CODIGO_EMP }}][hora_llegada]"
                                value="{{ $doc->hora_llegada ?? '' }}"
                                id="hora_{{ $doc->CODIGO_EMP }}"
                                class="border border-gray-300 rounded-lg px-2 py-1 text-xs focus:ring-2 focus:ring-yellow-400 w-full
                                    {{ $estadoActual !== 'retardo' ? 'hidden' : '' }}">
                        </td>
                        <td class="px-3 py-2">
                            <input type="text"
                                name="asistencias[{{ $doc->CODIGO_EMP }}][observacion]"
                                value="{{ $doc->observacion ?? '' }}"
                                maxlength="300" placeholder="—"
                                class="border border-gray-200 rounded-lg px-2 py-1 text-xs w-full focus:ring-1 focus:ring-blue-400">
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('asistencia-personal.index') }}"
               class="border border-gray-300 text-gray-600 text-sm px-5 py-2 rounded-lg hover:bg-gray-50 transition">
                Cancelar
            </a>
            <button type="submit"
                class="bg-blue-700 hover:bg-blue-800 text-white text-sm font-medium px-6 py-2 rounded-lg transition">
                Guardar asistencia
            </button>
        </div>

    </form>
</div>

<script>
function toggleHora(select, codigo) {
    const horaInput = document.getElementById('hora_' + codigo);
    if (select.value === 'retardo') {
        horaInput.classList.remove('hidden');
    } else {
        horaInput.classList.add('hidden');
        horaInput.value = '';
    }
}
</script>
@endsection
