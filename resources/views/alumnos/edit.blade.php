@extends('layouts.app-sidebar')

@section('header', 'Editar Información del Alumno')

@section('slot')

    <form method="POST" action="{{ route('alumnos.update', $estudiante->CODIGO) }}">
        @csrf
        @method('PUT')

        {{-- DATOS PERSONALES --}}
        <div class="bg-white rounded-xl shadow overflow-hidden mb-6">
            <div class="px-5 py-3 bg-blue-800 text-white flex items-center justify-between">
                <h3 class="font-bold text-sm uppercase tracking-wide">Datos del Estudiante</h3>
                <span class="text-blue-300 text-sm">Código: {{ $estudiante->CODIGO }}</span>
            </div>
            <div class="p-5 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 text-sm">

                @foreach([
                    'APELLIDO1' => 'Apellido 1',
                    'APELLIDO2' => 'Apellido 2',
                    'NOMBRE1'   => 'Nombre 1',
                    'NOMBRE2'   => 'Nombre 2',
                ] as $campo => $label)
                <div>
                    <label class="block text-xs text-gray-500 mb-1">{{ $label }}</label>
                    <input type="text" name="{{ $campo }}" value="{{ old($campo, $estudiante->$campo) }}"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                @endforeach

                @php
                    $gradosOpciones = [
                        -2 => 'Pre-Jardín', -1 => 'Jardín', 0 => 'Transición',
                        1 => 'Grado 1', 2 => 'Grado 2', 3 => 'Grado 3', 4 => 'Grado 4',
                        5 => 'Grado 5', 6 => 'Grado 6', 7 => 'Grado 7', 8 => 'Grado 8',
                        9 => 'Grado 9', 10 => 'Grado 10', 11 => 'Grado 11',
                    ];
                    $gradoActual = old('GRADO', $estudiante->GRADO);
                @endphp
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Grado</label>
                    <select name="GRADO" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">— Sin grado —</option>
                        @foreach($gradosOpciones as $valor => $etiqueta)
                            <option value="{{ $valor }}" {{ (string)$gradoActual === (string)$valor ? 'selected' : '' }}>{{ $etiqueta }}</option>
                        @endforeach
                    </select>
                </div>

                @foreach([
                    'CURSO'  => 'Curso',
                    'SEDE'   => 'Sede',
                    'ESTADO' => 'Estado',
                ] as $campo => $label)
                <div>
                    <label class="block text-xs text-gray-500 mb-1">{{ $label }}</label>
                    <input type="text" name="{{ $campo }}" value="{{ old($campo, $estudiante->$campo) }}"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                @endforeach

                <div>
                    <label class="block text-xs text-gray-500 mb-1">Fecha de nacimiento</label>
                    <input type="date" name="FECH_NACIMIENTO" value="{{ old('FECH_NACIMIENTO', $estudiante->FECH_NACIMIENTO) }}"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                @foreach([
                    'LUG_NACIMIENTO' => 'Lugar de nacimiento',
                    'LUG_EXPED'      => 'Lugar de expedición',
                    'TAR_ID'         => 'Tarjeta de identidad',
                    'REG_CIVIL'      => 'Registro civil',
                    'RH'             => 'Grupo sanguíneo / RH',
                    'EPS'            => 'EPS',
                    'ALERG'          => 'Alergias',
                    'ENFER'          => 'Enfermedades',
                    'GAFAS'          => 'Uso de anteojos',
                    'DIRECCION'      => 'Dirección',
                    'BARRIO'         => 'Barrio',
                    'ESTRATO'        => 'Estrato',
                    'ACUDIENTE'      => 'Acudiente',
                    'ENTRADA'        => 'Ruta mañana',
                    'SALIDA'         => 'Ruta tarde',
                ] as $campo => $label)
                <div>
                    <label class="block text-xs text-gray-500 mb-1">{{ $label }}</label>
                    <input type="text" name="{{ $campo }}" value="{{ old($campo, $estudiante->$campo) }}"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                @endforeach

            </div>
        </div>

        {{-- INFO PADRES --}}
        @if($infoPadres)
        <div class="bg-white rounded-xl shadow overflow-hidden mb-6">
            <div class="px-5 py-3 bg-blue-800 text-white">
                <h3 class="font-bold text-sm uppercase tracking-wide">Información de Padres / Acudiente</h3>
            </div>
            <div class="p-5 grid grid-cols-1 md:grid-cols-3 gap-6 text-sm">

                @foreach([
                    'Madre'     => ['MADRE','CC_MADRE','CEL_MADRE','EMAIL_MADRE'],
                    'Padre'     => ['PADRE','CC_PADRE','CEL_PADRE','EMAIL_PADRE'],
                    'Acudiente' => ['ACUD','CC_ACUD','CEL_ACUD','EMAIL_ACUD'],
                ] as $titulo => $campos)
                <div class="space-y-3">
                    <p class="font-bold text-blue-700 border-b pb-1">{{ $titulo }}</p>
                    @foreach($campos as $campo)
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">{{ $campo }}</label>
                        <input type="text" name="{{ $campo }}" value="{{ old($campo, $infoPadres->$campo) }}"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    @endforeach
                </div>
                @endforeach

            </div>
        </div>
        @endif

        {{-- HISTORIAL ACADÉMICO --}}
        <div class="bg-white rounded-xl shadow overflow-hidden mb-6">
            <div class="px-5 py-3 bg-blue-800 text-white">
                <h3 class="font-bold text-sm uppercase tracking-wide">Historial Académico</h3>
            </div>
            <div class="p-5 text-sm">
                <table class="w-full">
                    <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                        <tr>
                            <th class="px-3 py-2 text-left w-24">Grado</th>
                            <th class="px-3 py-2 text-left">Institución</th>
                            <th class="px-3 py-2 text-left w-28">Año</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach([
                            'PJ' => 'Pre-Jardín',
                            'J'  => 'Jardín',
                            'T'  => 'Transición',
                            '1'  => 'Grado 1',
                            '2'  => 'Grado 2',
                            '3'  => 'Grado 3',
                            '4'  => 'Grado 4',
                            '5'  => 'Grado 5',
                            '6'  => 'Grado 6',
                            '7'  => 'Grado 7',
                            '8'  => 'Grado 8',
                            '9'  => 'Grado 9',
                            '10' => 'Grado 10',
                            '11' => 'Grado 11',
                        ] as $nivel => $label)
                        @php
                            $ins = $infoAcadem->{"INS_$nivel"} ?? null;
                            $ano = $infoAcadem->{"ANO_$nivel"} ?? null;
                        @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-3 py-2 font-medium text-gray-600">{{ $label }}</td>
                            <td class="px-3 py-2">
                                <input type="text" name="INS_{{ $nivel }}" value="{{ old("INS_$nivel", $ins) }}"
                                    class="w-full border border-gray-300 rounded-lg px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    placeholder="Institución...">
                            </td>
                            <td class="px-3 py-2">
                                <input type="text" name="ANO_{{ $nivel }}" value="{{ old("ANO_$nivel", $ano) }}"
                                    class="w-full border border-gray-300 rounded-lg px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    placeholder="Año...">
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Botones --}}
        <div class="flex gap-3 pb-6">
            <button type="submit"
                class="bg-blue-800 hover:bg-blue-700 text-white font-semibold px-6 py-2 rounded-lg transition text-sm">
                💾 Guardar cambios
            </button>
            <a href="{{ route('alumnos.show', $estudiante->CODIGO) }}"
                class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold px-6 py-2 rounded-lg transition text-sm">
                Cancelar
            </a>
            <a href="{{ route('alumnos.index') }}"
                class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold px-6 py-2 rounded-lg transition text-sm">
                ← Volver a la búsqueda
            </a>
        </div>

    </form>

@endsection
