@extends('layouts.app-sidebar')

@section('header', 'Ficha del Alumno')

@section('slot')

    {{-- CABEZADO: estado del día --}}
    <div class="bg-white rounded-xl shadow p-5 mb-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                @include('partials.foto_estudiante', ['fotoDrive' => $estudiante->FOTO_DRIVE ?? null, 'width' => 96, 'height' => 120, 'estilo' => 'avatar'])
                <div>
                <h2 class="text-2xl font-bold text-blue-800">
                    {{ $estudiante->APELLIDO1 }} {{ $estudiante->APELLIDO2 }} {{ $estudiante->NOMBRE1 }} {{ $estudiante->NOMBRE2 }}
                </h2>
                @php
                    $gradosLabels = [
                        -2 => 'Pre-Jardín', -1 => 'Jardín', 0 => 'Transición',
                        1 => 'Grado 1', 2 => 'Grado 2', 3 => 'Grado 3', 4 => 'Grado 4',
                        5 => 'Grado 5', 6 => 'Grado 6', 7 => 'Grado 7', 8 => 'Grado 8',
                        9 => 'Grado 9', 10 => 'Grado 10', 11 => 'Grado 11',
                    ];
                    $gradoLabel = $estudiante->GRADO !== null
                        ? ($gradosLabels[(int)$estudiante->GRADO] ?? $estudiante->GRADO)
                        : '—';
                @endphp
                <div class="flex flex-wrap gap-3 mt-2 text-sm text-gray-500">
                    <span>Código: <strong>{{ $estudiante->CODIGO }}</strong></span>
                    <span>Curso: <strong>{{ $estudiante->CURSO ?? '—' }}</strong></span>
                    <span>Grado: <strong>{{ $gradoLabel }}</strong></span>
                    <span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ $estudiante->ESTADO === 'MATRICULADO' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                        {{ $estudiante->ESTADO ?? '—' }}
                    </span>
                </div>
                </div>
            </div>
            {{-- Asistencia del día (pendiente de tabla) --}}
            <div class="flex gap-2">
                <span class="px-3 py-1.5 rounded-lg bg-gray-100 text-gray-500 text-xs font-medium">
                    📋 Asistencia: <em class="text-gray-400">pendiente</em>
                </span>
            </div>
        </div>

        {{-- Ruta --}}
        <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div class="bg-blue-50 rounded-lg px-4 py-2 text-sm">
                <span class="text-blue-400 text-xs uppercase tracking-wide">Ruta mañana</span>
                <p class="font-semibold text-blue-800">{{ $estudiante->ENTRADA ?: 'No asignada' }}</p>
            </div>
            <div class="bg-blue-50 rounded-lg px-4 py-2 text-sm">
                <span class="text-blue-400 text-xs uppercase tracking-wide">Ruta tarde</span>
                <p class="font-semibold text-blue-800">{{ $estudiante->SALIDA ?: 'No asignada' }}</p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">

        {{-- DATOS PERSONALES --}}
        <div class="bg-white rounded-xl shadow overflow-hidden">
            <div class="px-5 py-3 bg-blue-800 text-white">
                <h3 class="font-bold text-sm uppercase tracking-wide">Datos Personales</h3>
            </div>
            <div class="p-5 space-y-3 text-sm">
                @php
                    $tipoId = $estudiante->TAR_ID ? 'Tarjeta de identidad' : ($estudiante->REG_CIVIL ? 'Registro civil' : '—');
                    $numId  = $estudiante->TAR_ID ?: ($estudiante->REG_CIVIL ?: '—');
                @endphp
                <div class="grid grid-cols-2 gap-2">
                    <div><p class="text-xs text-gray-400">Tipo de identificación</p><p class="font-medium">{{ $tipoId }}</p></div>
                    <div><p class="text-xs text-gray-400">Número</p><p class="font-medium">{{ $numId }}</p></div>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div><p class="text-xs text-gray-400">Fecha de nacimiento</p><p class="font-medium">{{ $estudiante->FECH_NACIMIENTO ?? '—' }}</p></div>
                    <div><p class="text-xs text-gray-400">Edad</p><p class="font-medium">{{ $edad !== null ? $edad . ' años' : '—' }}</p></div>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div><p class="text-xs text-gray-400">Lugar de nacimiento</p><p class="font-medium">{{ $estudiante->LUG_NACIMIENTO ?? '—' }}</p></div>
                    <div><p class="text-xs text-gray-400">Lugar de expedición</p><p class="font-medium">{{ $estudiante->LUG_EXPED ?? '—' }}</p></div>
                </div>
                <div class="grid grid-cols-3 gap-2">
                    <div><p class="text-xs text-gray-400">Grupo sanguíneo</p><p class="font-medium">{{ $estudiante->RH ?? '—' }}</p></div>
                    <div><p class="text-xs text-gray-400">EPS</p><p class="font-medium">{{ $estudiante->EPS ?? '—' }}</p></div>
                    <div><p class="text-xs text-gray-400">Anteojos</p><p class="font-medium">{{ $estudiante->GAFAS ?? 'No' }}</p></div>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div><p class="text-xs text-gray-400">Alergias</p><p class="font-medium">{{ $estudiante->ALERG ?: 'Ninguna' }}</p></div>
                    <div><p class="text-xs text-gray-400">Enfermedades</p><p class="font-medium">{{ $estudiante->ENFER ?: 'Ninguna' }}</p></div>
                </div>
            </div>
        </div>

        {{-- DATOS DE CONTACTO --}}
        <div class="bg-white rounded-xl shadow overflow-hidden">
            <div class="px-5 py-3 bg-blue-800 text-white">
                <h3 class="font-bold text-sm uppercase tracking-wide">Datos de Contacto</h3>
            </div>
            <div class="p-5 space-y-3 text-sm">
                <div><p class="text-xs text-gray-400">Dirección</p><p class="font-medium">{{ $estudiante->DIRECCION ?? '—' }}</p></div>
                <div class="grid grid-cols-2 gap-2">
                    <div><p class="text-xs text-gray-400">Barrio</p><p class="font-medium">{{ $estudiante->BARRIO ?? '—' }}</p></div>
                    <div><p class="text-xs text-gray-400">Estrato</p><p class="font-medium">{{ $estudiante->ESTRATO ?? '—' }}</p></div>
                </div>
                <div><p class="text-xs text-gray-400">Acudiente</p><p class="font-medium">{{ $estudiante->ACUDIENTE ?? '—' }}</p></div>
                <div><p class="text-xs text-gray-400">Fecha matrícula</p><p class="font-medium">{{ $estudiante->FECH_MATRICULA ?? '—' }}</p></div>
                <div><p class="text-xs text-gray-400">Sede</p><p class="font-medium">{{ $estudiante->SEDE ?? '—' }}</p></div>
            </div>
        </div>

    </div>

    {{-- INFO PADRES --}}
    @if($infoPadres)
    <div class="bg-white rounded-xl shadow overflow-hidden mb-6">
        <div class="px-5 py-3 bg-blue-800 text-white">
            <h3 class="font-bold text-sm uppercase tracking-wide">Información de Padres / Acudiente</h3>
        </div>
        <div class="p-5 grid grid-cols-1 md:grid-cols-3 gap-6 text-sm">

            {{-- Madre --}}
            <div class="space-y-2">
                <p class="font-bold text-blue-700 border-b pb-1">Madre</p>
                <div><p class="text-xs text-gray-400">Nombre</p><p>{{ $infoPadres->MADRE ?? '—' }}</p></div>
                <div><p class="text-xs text-gray-400">Cédula</p><p>{{ $infoPadres->CC_MADRE ?? '—' }}</p></div>
                <div><p class="text-xs text-gray-400">Empresa</p><p>{{ $infoPadres->EMP_MADRE ?? '—' }}</p></div>
                <div><p class="text-xs text-gray-400">Tel. empresa</p><p>{{ $infoPadres->TELEMP_MADRE ?? '—' }}</p></div>
                <div><p class="text-xs text-gray-400">Celular</p><p>{{ $infoPadres->CEL_MADRE ?? '—' }}</p></div>
                <div><p class="text-xs text-gray-400">Tel. casa</p><p>{{ $infoPadres->TEL_MADRE ?? '—' }}</p></div>
                <div><p class="text-xs text-gray-400">Email</p><p>{{ $infoPadres->EMAIL_MADRE ?? '—' }}</p></div>
                <div><p class="text-xs text-gray-400">Dir. empresa</p><p>{{ $infoPadres->DIREMP_MADRE ?? '—' }}</p></div>
                <div><p class="text-xs text-gray-400">Dir. casa</p><p>{{ $infoPadres->CASA_MADRE ?? '—' }}</p></div>
            </div>

            {{-- Padre --}}
            <div class="space-y-2">
                <p class="font-bold text-blue-700 border-b pb-1">Padre</p>
                <div><p class="text-xs text-gray-400">Nombre</p><p>{{ $infoPadres->PADRE ?? '—' }}</p></div>
                <div><p class="text-xs text-gray-400">Cédula</p><p>{{ $infoPadres->CC_PADRE ?? '—' }}</p></div>
                <div><p class="text-xs text-gray-400">Empresa</p><p>{{ $infoPadres->EMP_PADRE ?? '—' }}</p></div>
                <div><p class="text-xs text-gray-400">Tel. empresa</p><p>{{ $infoPadres->TELEMP_PADRE ?? '—' }}</p></div>
                <div><p class="text-xs text-gray-400">Celular</p><p>{{ $infoPadres->CEL_PADRE ?? '—' }}</p></div>
                <div><p class="text-xs text-gray-400">Tel. casa</p><p>{{ $infoPadres->TEL_PADRE ?? '—' }}</p></div>
                <div><p class="text-xs text-gray-400">Email</p><p>{{ $infoPadres->EMAIL_PADRE ?? '—' }}</p></div>
                <div><p class="text-xs text-gray-400">Dir. empresa</p><p>{{ $infoPadres->DIREMP_PADRE ?? '—' }}</p></div>
                <div><p class="text-xs text-gray-400">Dir. casa</p><p>{{ $infoPadres->CASA_PADRE ?? '—' }}</p></div>
            </div>

            {{-- Acudiente --}}
            <div class="space-y-2">
                <p class="font-bold text-blue-700 border-b pb-1">Acudiente</p>
                <div><p class="text-xs text-gray-400">Nombre</p><p>{{ $infoPadres->ACUD ?? '—' }}</p></div>
                <div><p class="text-xs text-gray-400">Cédula</p><p>{{ $infoPadres->CC_ACUD ?? '—' }}</p></div>
                <div><p class="text-xs text-gray-400">Empresa</p><p>{{ $infoPadres->EMP_ACUD ?? '—' }}</p></div>
                <div><p class="text-xs text-gray-400">Tel. empresa</p><p>{{ $infoPadres->TELEMP_ACUD ?? '—' }}</p></div>
                <div><p class="text-xs text-gray-400">Celular</p><p>{{ $infoPadres->CEL_ACUD ?? '—' }}</p></div>
                <div><p class="text-xs text-gray-400">Tel. casa</p><p>{{ $infoPadres->TEL_ACUD ?? '—' }}</p></div>
                <div><p class="text-xs text-gray-400">Email</p><p>{{ $infoPadres->EMAIL_ACUD ?? '—' }}</p></div>
                <div><p class="text-xs text-gray-400">Dir. empresa</p><p>{{ $infoPadres->DIREMP_ACUD ?? '—' }}</p></div>
                <div><p class="text-xs text-gray-400">Dir. casa</p><p>{{ $infoPadres->CASA_ACUD ?? '—' }}</p></div>
            </div>

        </div>
    </div>
    @endif

    {{-- HISTORIAL ACADÉMICO --}}
    @if($infoAcadem)
    <div class="bg-white rounded-xl shadow overflow-hidden mb-6">
        <div class="px-5 py-3 bg-blue-800 text-white">
            <h3 class="font-bold text-sm uppercase tracking-wide">Historial Académico</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                    <tr>
                        <th class="px-4 py-3 text-left">Grado</th>
                        <th class="px-4 py-3 text-left">Institución</th>
                        <th class="px-4 py-3 text-left">Año</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach([
                        'Pre-Jardín'  => ['INS_PJ',  'ANO_PJ'],
                        'Jardín'      => ['INS_J',   'ANO_J'],
                        'Transición'  => ['INS_T',   'ANO_T'],
                        'Grado 1'     => ['INS_1',   'ANO_1'],
                        'Grado 2'     => ['INS_2',   'ANO_2'],
                        'Grado 3'     => ['INS_3',   'ANO_3'],
                        'Grado 4'     => ['INS_4',   'ANO_4'],
                        'Grado 5'     => ['INS_5',   'ANO_5'],
                        'Grado 6'     => ['INS_6',   'ANO_6'],
                        'Grado 7'     => ['INS_7',   'ANO_7'],
                        'Grado 8'     => ['INS_8',   'ANO_8'],
                        'Grado 9'     => ['INS_9',   'ANO_9'],
                        'Grado 10'    => ['INS_10',  'ANO_10'],
                        'Grado 11'    => ['INS_11',  'ANO_11'],
                    ] as $grado => $campos)
                        @php $ins = $infoAcadem->{$campos[0]} ?? null; $ano = $infoAcadem->{$campos[1]} ?? null; @endphp
                        @if($ins || $ano)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2 font-medium text-gray-600">{{ $grado }}</td>
                            <td class="px-4 py-2">{{ $ins ?? '—' }}</td>
                            <td class="px-4 py-2">{{ $ano ?? '—' }}</td>
                        </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    @if(session('success'))
        <div class="mb-4 p-3 bg-green-100 text-green-800 rounded-xl text-sm">✅ {{ session('success') }}</div>
    @endif

    <div class="flex gap-3 mb-6">
        <a href="{{ route('alumnos.edit', $estudiante->CODIGO) }}"
            class="bg-blue-800 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition">
            ✏️ Editar información
        </a>
        @auth
            @if(in_array(auth()->user()->PROFILE, ['SuperAd', 'Admin']))
            <a href="{{ route('alumnos.print', $estudiante->CODIGO) }}" target="_blank"
                class="bg-green-700 hover:bg-green-600 text-white text-sm font-semibold px-4 py-2 rounded-lg transition">
                🖨️ Ficha / PDF
            </a>
            <a href="{{ route('boletines.ver', $estudiante->CODIGO) }}" target="_blank"
                class="bg-blue-700 hover:bg-blue-600 text-white text-sm font-semibold px-4 py-2 rounded-lg transition">
                📋 Boletín
            </a>
            @endif
        @endauth
        <a href="{{ route('alumnos.index') }}"
            class="bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-semibold px-4 py-2 rounded-lg transition">
            ← Volver a la búsqueda
        </a>
    </div>

@endsection
