@extends('layouts.app-sidebar')

@section('header', 'PIAR – ' . $estudiante->APELLIDO1 . ' ' . $estudiante->APELLIDO2 . ' ' . $estudiante->NOMBRE1)

@section('slot')

@php
    // ── Datos del estudiante ──────────────────────────────────────────────────
    $nombreCompleto = trim("{$estudiante->NOMBRE1} {$estudiante->NOMBRE2}");
    $apellidos      = trim("{$estudiante->APELLIDO1} {$estudiante->APELLIDO2}");

    $tipoDoc = 'TI';
    $numId   = $estudiante->TAR_ID ?? '';
    if (!$numId && ($estudiante->REG_CIVIL ?? '')) { $tipoDoc = 'RC'; $numId = $estudiante->REG_CIVIL; }

    $fechaNac = '';
    if ($estudiante->FECH_NACIMIENTO ?? null) {
        try { $fechaNac = \Carbon\Carbon::parse($estudiante->FECH_NACIMIENTO)->translatedFormat('d \d\e F \d\e Y'); }
        catch (\Exception $e) { $fechaNac = $estudiante->FECH_NACIMIENTO; }
    }

    $edad      = $estudiante->EDAD ?? '';
    $grado     = $estudiante->GRADO ?? '';
    $curso     = $estudiante->CURSO ?? '';
    $sede      = $estudiante->SEDE  ? 'Sede ' . $estudiante->SEDE : '';
    $lugarNac  = $estudiante->LUG_NACIMIENTO ?? '';
    $direccion = $estudiante->DIRECCION ?? '';
    $barrio    = $estudiante->BARRIO ?? '';
    $epsEst    = $estudiante->EPS ?? '';
    $enferEst  = $estudiante->ENFER ?? '';

    // Teléfono y correo desde padres
    $telPadres    = '';
    $correoPadres = '';
    $nombreMadre  = $padres->MADRE     ?? '';
    $nombrePadre  = $padres->PADRE     ?? '';
    $empMadre     = $padres->EMP_MADRE ?? '';
    $empPadre     = $padres->EMP_PADRE ?? '';
    $celMadre     = $padres->CEL_MADRE ?? '';
    $emailMadre   = $padres->EMAIL_MADRE ?? '';
    $celPadre     = $padres->CEL_PADRE  ?? '';
    $nombreAcud   = $padres->ACUD       ?? $nombreMadre;
    $celAcud      = $padres->CEL_ACUD   ?? '';
    $emailAcud    = $padres->EMAIL_ACUD ?? $emailMadre;
    if ($padres) {
        $telPadres    = $padres->CEL_ACUD ?: ($padres->CEL_MADRE ?: ($padres->CEL_PADRE ?: ''));
        $correoPadres = $padres->EMAIL_ACUD ?: ($padres->EMAIL_MADRE ?: ($padres->EMAIL_PADRE ?: ''));
    }

    // Helper: valor guardado > prellenado de BD
    $p = $piar; // alias corto
    $v = fn($campo, $default = '') => ($p && $p->$campo !== null && $p->$campo !== '') ? $p->$campo : $default;
    $vb = fn($campo) => $p ? (bool) $p->$campo : null; // booleano guardado

    $anio = date('Y');
@endphp

{{-- Barra de acciones --}}
<div class="flex items-center justify-between mb-4 no-print">
    <a href="{{ route('piar.buscar') }}" class="text-blue-700 hover:underline text-sm">← Volver a búsqueda</a>
    <div class="flex gap-3">
        <button type="submit" form="form-piar"
            class="bg-green-700 hover:bg-green-600 text-white text-sm font-semibold px-5 py-2 rounded-lg transition">
            💾 Guardar PIAR
        </button>
        <a href="{{ route('piar.imprimir', $estudiante->CODIGO) }}" target="_blank"
            class="bg-blue-800 hover:bg-blue-700 text-white text-sm font-semibold px-5 py-2 rounded-lg transition inline-block">
            🖨️ Imprimir Anexo 1
        </a>
        @if($piar)
        <form method="POST" action="{{ route('piar.eliminar', $estudiante->CODIGO) }}"
              onsubmit="return confirm('¿Seguro que deseas eliminar el PIAR de este estudiante? Esta acción no se puede deshacer.')">
            @csrf
            @method('DELETE')
            <button type="submit"
                class="bg-red-700 hover:bg-red-600 text-white text-sm font-semibold px-5 py-2 rounded-lg transition">
                🗑️ Eliminar PIAR
            </button>
        </form>
        @endif
    </div>
</div>

@if(session('piar_saved'))
    <div class="mb-4 p-3 bg-green-100 text-green-800 rounded-xl text-sm no-print">
        ✅ {{ session('piar_saved') }}
    </div>
@endif

<form id="form-piar" method="POST" action="{{ route('piar.guardar', $estudiante->CODIGO) }}">
@csrf

<div class="space-y-6 max-w-5xl mx-auto">

{{-- ══ ENCABEZADO ══ --}}
<div class="bg-white rounded-xl shadow p-6 print-card">
    <div class="text-center mb-4">
        <h1 class="text-2xl font-bold text-blue-900 uppercase tracking-wide">Plan Individual de Ajustes Razonables</h1>
        <p class="text-sm text-gray-500 mt-1">PIAR – Colegio Bilingüe Integral</p>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label class="piar-label">Nombre del estudiante</label>
            <input type="text" value="{{ $nombreCompleto }} {{ $apellidos }}" class="piar-input" readonly>
        </div>
        <div>
            <label class="piar-label">Tarjeta de identidad / Documento</label>
            <input type="text" value="{{ $numId }}" class="piar-input" readonly>
        </div>
        <div>
            <label class="piar-label">Grado</label>
            <input type="text" value="{{ $curso }}" class="piar-input" readonly>
        </div>
        <div>
            <label class="piar-label">Dx PIAR</label>
            <input type="text" name="DIAGNOSTICO" value="{{ $v('DIAGNOSTICO') }}"
                placeholder="Ej: TDA, TEA, Dificultades de aprendizaje…" class="piar-input">
        </div>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4 pt-4 border-t border-gray-100">
        <div>
            <label class="piar-label">Fecha y lugar de diligenciamiento</label>
            <input type="text" name="LUGAR_DIL"
                value="{{ $v('LUGAR_DIL', 'Bogotá, Colegio Bilingüe Integral. Año ' . $anio) }}" class="piar-input">
        </div>
        <div>
            <label class="piar-label">Nombre de la persona que diligencia</label>
            <input type="text" name="PERSONA_DIL"
                value="{{ $v('PERSONA_DIL', 'Jennifer Andrea Martínez Londoño') }}" class="piar-input">
        </div>
    </div>
</div>

{{-- ══ SECCIÓN 1 – INFORMACIÓN GENERAL ══ --}}
<div class="bg-white rounded-xl shadow p-6 print-card">
    <h2 class="piar-section-title">1. Información General del Estudiante</h2>
    <p class="text-xs text-gray-400 mb-4">Todos los campos se prellenan desde la ficha del estudiante. Puede modificar cualquier dato si el acudiente lo solicita.</p>

    {{-- Nombres y apellidos --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4">
        <div>
            <label class="piar-label">Nombres</label>
            <input type="text" name="ALU_NOMBRES" value="{{ $v('ALU_NOMBRES', $nombreCompleto) }}" class="piar-input">
        </div>
        <div>
            <label class="piar-label">Apellidos</label>
            <input type="text" name="ALU_APELLIDOS" value="{{ $v('ALU_APELLIDOS', $apellidos) }}" class="piar-input">
        </div>
        <div>
            <label class="piar-label">Curso – Sede – Jornada</label>
            <input type="text" name="ALU_CURSO_INFO"
                value="{{ $v('ALU_CURSO_INFO', $curso . ($sede ? ' – Sede ' . $sede : '') . ' – Jornada única. Colegio Bilingüe Integral.') }}"
                class="piar-input">
        </div>
        <div>
            <label class="piar-label">Lugar de nacimiento</label>
            <input type="text" name="ALU_LUG_NAC" value="{{ $v('ALU_LUG_NAC', $lugarNac) }}" class="piar-input">
        </div>
        <div>
            <label class="piar-label">Edad</label>
            <input type="text" name="ALU_EDAD" value="{{ $v('ALU_EDAD', $edad) }}" class="piar-input">
        </div>
        <div>
            <label class="piar-label">Fecha de nacimiento</label>
            <input type="text" name="ALU_FECH_NAC" value="{{ $v('ALU_FECH_NAC', $fechaNac) }}" class="piar-input">
        </div>
    </div>

    {{-- Tipo de documento --}}
    <div class="mt-4 pt-4 border-t border-gray-100">
        <label class="piar-label mb-2 block">Tipo de identificación</label>
        @php $tipoDocGuardado = $v('ALU_TIPO_DOC', $tipoDoc); @endphp
        <div class="flex flex-wrap gap-6 items-center mb-3">
            <label class="flex items-center gap-2 text-sm">
                <input type="radio" name="ALU_TIPO_DOC" value="TI" {{ $tipoDocGuardado === 'TI' ? 'checked' : '' }}> TI
            </label>
            <label class="flex items-center gap-2 text-sm">
                <input type="radio" name="ALU_TIPO_DOC" value="CC" {{ $tipoDocGuardado === 'CC' ? 'checked' : '' }}> CC
            </label>
            <label class="flex items-center gap-2 text-sm">
                <input type="radio" name="ALU_TIPO_DOC" value="RC" {{ $tipoDocGuardado === 'RC' ? 'checked' : '' }}> RC
            </label>
            <label class="flex items-center gap-2 text-sm">
                <input type="radio" name="ALU_TIPO_DOC" value="Otro"
                    {{ !in_array($tipoDocGuardado, ['TI','CC','RC']) ? 'checked' : '' }}> Otro:
                <input type="text" name="ALU_TIPO_DOC_OTRO"
                    value="{{ $v('ALU_TIPO_DOC_OTRO', !in_array($tipoDoc, ['TI','CC','RC']) ? $tipoDoc : '') }}"
                    class="piar-input-inline">
            </label>
        </div>
        <div>
            <label class="piar-label">No. de identificación</label>
            <input type="text" name="ALU_NUM_ID" value="{{ $v('ALU_NUM_ID', $numId) }}" class="piar-input">
        </div>
    </div>

    {{-- Ubicación --}}
    <div class="mt-4 pt-4 border-t border-gray-100 grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4">
        <div>
            <label class="piar-label">Departamento donde vive</label>
            <input type="text" name="ALU_DEPTO" value="{{ $v('ALU_DEPTO', 'Cundinamarca') }}" class="piar-input">
        </div>
        <div>
            <label class="piar-label">Municipio</label>
            <input type="text" name="MUNICIPIO" value="{{ $v('MUNICIPIO', 'Bogotá') }}" class="piar-input">
        </div>
        <div>
            <label class="piar-label">Dirección de vivienda</label>
            <input type="text" name="ALU_DIRECCION" value="{{ $v('ALU_DIRECCION', $direccion) }}" class="piar-input">
        </div>
        <div>
            <label class="piar-label">Barrio / Vereda</label>
            <input type="text" name="ALU_BARRIO" value="{{ $v('ALU_BARRIO', $barrio) }}" class="piar-input">
        </div>
        <div>
            <label class="piar-label">Estrato</label>
            <input type="text" name="ALU_ESTRATO" value="{{ $v('ALU_ESTRATO', $estudiante->ESTRATO ?? '') }}" class="piar-input" placeholder="Ej: 2">
        </div>
        <div>
            <label class="piar-label">Teléfono</label>
            <input type="text" name="TELEFONO" value="{{ $v('TELEFONO', $telPadres) }}" class="piar-input">
        </div>
        <div>
            <label class="piar-label">Correo electrónico</label>
            <input type="text" name="EMAIL" value="{{ $v('EMAIL', $correoPadres) }}" class="piar-input">
        </div>
    </div>

    {{-- Datos de salud del estudiante --}}
    <div class="mt-4 pt-4 border-t border-gray-100 grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4">
        <div>
            <label class="piar-label">Tipo de sangre (RH)</label>
            <input type="text" name="ALU_RH" value="{{ $v('ALU_RH', $estudiante->RH ?? '') }}" class="piar-input" placeholder="Ej: O+">
        </div>
        <div>
            <label class="piar-label">¿Usa gafas?</label>
            <div class="flex gap-4 mt-1">
                @php $gafasGuardado = $p ? (bool)$p->ALU_GAFAS : (bool)($estudiante->GAFAS ?? false); @endphp
                <label class="text-sm flex items-center gap-1">
                    <input type="radio" name="ALU_GAFAS" value="si" {{ $gafasGuardado ? 'checked' : '' }}> Sí
                </label>
                <label class="text-sm flex items-center gap-1">
                    <input type="radio" name="ALU_GAFAS" value="no" {{ !$gafasGuardado ? 'checked' : '' }}> No
                </label>
            </div>
        </div>
        <div class="sm:col-span-2">
            <label class="piar-label">Alergias / condiciones conocidas</label>
            <input type="text" name="ALU_ALERG" value="{{ $v('ALU_ALERG', $estudiante->ALERG ?? '') }}" class="piar-input" placeholder="Ej: Alergia al polvo, asma…">
        </div>
    </div>

    {{-- Preguntas de contexto --}}
    <div class="mt-4 pt-4 border-t border-gray-100 space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="piar-label">¿Está en centro de protección?</label>
                <div class="flex gap-4 mt-1 flex-wrap">
                    <label class="text-sm flex items-center gap-1">
                        <input type="radio" name="PROTEC" value="no"
                            {{ $vb('PROTEC') === false || $vb('PROTEC') === null ? 'checked' : '' }}> No
                    </label>
                    <label class="text-sm flex items-center gap-1">
                        <input type="radio" name="PROTEC" value="si"
                            {{ $vb('PROTEC') === true ? 'checked' : '' }}> Sí – ¿Dónde?
                        <input type="text" name="PROTEC_WHICH" value="{{ $v('PROTEC_WHICH') }}" class="piar-input-inline ml-1">
                    </label>
                </div>
            </div>
            <div>
                <label class="piar-label">Grado al que aspira ingresar</label>
                <input type="text" name="ASPIRA" value="{{ $v('ASPIRA', $grado ? $grado . ' (' . $anio . ')' : '') }}" class="piar-input">
            </div>
        </div>
        <div>
            <label class="piar-label">Gestión registro civil (si aplica)</label>
            <input type="text" name="REGIS" value="{{ $v('REGIS', 'No aplica') }}" class="piar-input">
        </div>
        <div>
            <label class="piar-label">¿Se reconoce o pertenece a un grupo étnico?</label>
            <div class="flex gap-4 mt-1 flex-wrap">
                <label class="text-sm flex items-center gap-1">
                    <input type="radio" name="ETNIC" value="no"
                        {{ $vb('ETNIC') === false || $vb('ETNIC') === null ? 'checked' : '' }}> No
                </label>
                <label class="text-sm flex items-center gap-1">
                    <input type="radio" name="ETNIC" value="si"
                        {{ $vb('ETNIC') === true ? 'checked' : '' }}> Sí – ¿Cuál?
                    <input type="text" name="ETNIC_WHICH" value="{{ $v('ETNIC_WHICH') }}" class="piar-input-inline ml-1">
                </label>
            </div>
        </div>
        <div>
            <label class="piar-label">¿Se reconoce como víctima del conflicto armado?</label>
            <div class="flex gap-4 mt-1 flex-wrap items-center">
                <label class="text-sm flex items-center gap-1">
                    <input type="radio" name="CONFARM" value="no"
                        {{ $vb('CONFARM') === false || $vb('CONFARM') === null ? 'checked' : '' }}> No
                </label>
                <label class="text-sm flex items-center gap-1">
                    <input type="radio" name="CONFARM" value="si"
                        {{ $vb('CONFARM') === true ? 'checked' : '' }}> Sí – ¿Cuenta con registro?
                    <input type="text" name="CONFARM_REG" value="{{ $v('CONFARM_REG') }}" placeholder="Sí / No" class="piar-input-inline ml-1">
                </label>
            </div>
        </div>
    </div>
</div>

{{-- ══ SECCIÓN 2 – ENTORNO SALUD ══ --}}
<div class="bg-white rounded-xl shadow p-6 print-card">
    <h2 class="piar-section-title">2. Entorno Salud</h2>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4">
        <div>
            <label class="piar-label">Afiliación al sistema de salud</label>
            <div class="flex gap-4 mt-1">
                <label class="text-sm flex items-center gap-1">
                    <input type="radio" name="SALUD" value="si"
                        {{ ($vb('SALUD') !== false) ? 'checked' : '' }}> Sí
                </label>
                <label class="text-sm flex items-center gap-1">
                    <input type="radio" name="SALUD" value="no"
                        {{ $vb('SALUD') === false ? 'checked' : '' }}> No
                </label>
            </div>
        </div>
        <div>
            <label class="piar-label">EPS</label>
            <input type="text" name="EPS" value="{{ $v('EPS', $epsEst) }}" class="piar-input">
        </div>
        <div>
            <label class="piar-label">Tipo de afiliación</label>
            <div class="flex gap-4 mt-1">
                <label class="text-sm flex items-center gap-1">
                    <input type="radio" name="CONT" value="contributivo"
                        {{ ($p && $p->CONT) || !$p ? 'checked' : '' }}> Contributivo
                </label>
                <label class="text-sm flex items-center gap-1">
                    <input type="radio" name="CONT" value="subsidiado"
                        {{ ($p && !$p->CONT) ? 'checked' : '' }}> Subsidiado
                </label>
            </div>
        </div>
        <div>
            <label class="piar-label">Lugar de atención en caso de emergencia</label>
            <input type="text" name="EMERG" value="{{ $v('EMERG') }}" class="piar-input">
        </div>
        <div>
            <label class="piar-label">¿El niño está siendo atendido por el sector salud?</label>
            <div class="flex gap-4 mt-1">
                <label class="text-sm flex items-center gap-1">
                    <input type="radio" name="PROTEGIDO" value="si"
                        {{ $vb('PROTEGIDO') === true ? 'checked' : '' }}> Sí
                </label>
                <label class="text-sm flex items-center gap-1">
                    <input type="radio" name="PROTEGIDO" value="no"
                        {{ $vb('PROTEGIDO') !== true ? 'checked' : '' }}> No
                </label>
            </div>
        </div>
        <div>
            <label class="piar-label">Frecuencia de atención en salud</label>
            <input type="text" name="FREC_PROTEG" value="{{ $v('FREC_PROTEG') }}" class="piar-input">
        </div>
        <div>
            <label class="piar-label">¿Tiene diagnóstico médico?</label>
            <div class="flex gap-4 mt-1">
                <label class="text-sm flex items-center gap-1">
                    <input type="radio" name="DIAGMED" value="si"
                        {{ ($vb('DIAGMED') === true || (!$p && $enferEst)) ? 'checked' : '' }}> Sí
                </label>
                <label class="text-sm flex items-center gap-1">
                    <input type="radio" name="DIAGMED" value="no"
                        {{ ($vb('DIAGMED') === false || (!$p && !$enferEst)) ? 'checked' : '' }}> No
                </label>
            </div>
        </div>
        <div>
            <label class="piar-label">¿Cuál diagnóstico?</label>
            <input type="text" name="DIAGMED_WHICH" value="{{ $v('DIAGMED_WHICH', $enferEst) }}" class="piar-input">
        </div>
    </div>

    {{-- Terapias --}}
    <div class="mt-4 pt-4 border-t border-gray-100">
        <label class="piar-label mb-2 block">¿El niño está asistiendo a terapias?</label>
        <div class="flex gap-4 mb-3">
            <label class="text-sm flex items-center gap-1">
                <input type="radio" name="TERAP" value="si"
                    {{ $vb('TERAP') === true ? 'checked' : '' }}> Sí
            </label>
            <label class="text-sm flex items-center gap-1">
                <input type="radio" name="TERAP" value="no"
                    {{ $vb('TERAP') !== true ? 'checked' : '' }}> No
            </label>
        </div>
        @foreach([['TERAP_WHICH1','TERAP_FREC1'],['TERAP_WHICH2','TERAP_FREC2'],['TERAP_WHICH3','TERAP_FREC3'],['TERAP_WHICH4','TERAP_FREC4'],['TERAP_WHICH5','TERAP_FREC5']] as $i => $par)
        <div class="grid grid-cols-2 gap-4 mb-3">
            <div>
                <label class="piar-label">¿Cuál terapia? ({{ $i + 1 }})</label>
                <input type="text" name="{{ $par[0] }}" value="{{ $v($par[0]) }}"
                    placeholder="Ej: Terapia ocupacional" class="piar-input">
            </div>
            <div>
                <label class="piar-label">Frecuencia</label>
                <input type="text" name="{{ $par[1] }}" value="{{ $v($par[1]) }}"
                    placeholder="Ej: 3 veces por semana" class="piar-input">
            </div>
        </div>
        @endforeach
    </div>

    <div class="mt-4 pt-4 border-t border-gray-100 grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4">
        <div>
            <label class="piar-label">¿Recibe tratamiento médico por alguna enfermedad?</label>
            <div class="flex gap-4 mt-1">
                <label class="text-sm flex items-center gap-1">
                    <input type="radio" name="ENFERPAR" value="si"
                        {{ $vb('ENFERPAR') === true ? 'checked' : '' }}> Sí
                </label>
                <label class="text-sm flex items-center gap-1">
                    <input type="radio" name="ENFERPAR" value="no"
                        {{ $vb('ENFERPAR') !== true ? 'checked' : '' }}> No
                </label>
            </div>
        </div>
        <div>
            <label class="piar-label">¿Cuál? (epilepsia, oxígeno, insulina, etc.)</label>
            <input type="text" name="ENFERPAR_WHICH" value="{{ $v('ENFERPAR_WHICH') }}" class="piar-input">
        </div>
        <div>
            <label class="piar-label">¿Consume medicamentos?</label>
            <div class="flex gap-4 mt-1">
                <label class="text-sm flex items-center gap-1">
                    <input type="radio" name="MEDIC" value="si"
                        {{ $vb('MEDIC') === true ? 'checked' : '' }}> Sí
                </label>
                <label class="text-sm flex items-center gap-1">
                    <input type="radio" name="MEDIC" value="no"
                        {{ $vb('MEDIC') !== true ? 'checked' : '' }}> No
                </label>
            </div>
        </div>
        <div>
            <label class="piar-label">Frecuencia y horario (nombre del medicamento)</label>
            <input type="text" name="MEDIC_FREC" value="{{ $v('MEDIC_FREC') }}" class="piar-input">
        </div>
        <div>
            <label class="piar-label">¿Cuenta con productos de apoyo para movilidad o comunicación?</label>
            <div class="flex gap-4 mt-1">
                <label class="text-sm flex items-center gap-1">
                    <input type="radio" name="MOVILID" value="si"
                        {{ $vb('MOVILID') === true ? 'checked' : '' }}> Sí
                </label>
                <label class="text-sm flex items-center gap-1">
                    <input type="radio" name="MOVILID" value="no"
                        {{ $vb('MOVILID') !== true ? 'checked' : '' }}> No
                </label>
            </div>
        </div>
        <div>
            <label class="piar-label">¿Cuáles? (silla de ruedas, audífonos, tablero, etc.)</label>
            <input type="text" name="MOVILID_WHICH" value="{{ $v('MOVILID_WHICH') }}" class="piar-input">
        </div>
    </div>
</div>

{{-- ══ SECCIÓN 3 – ENTORNO HOGAR ══ --}}
<div class="bg-white rounded-xl shadow p-6 print-card">
    <h2 class="piar-section-title">3. Entorno Hogar</h2>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4">
        <div>
            <label class="piar-label">Nombre de la madre</label>
            <input type="text" name="PAD_MADRE" value="{{ $v('PAD_MADRE', $nombreMadre) }}" class="piar-input">
        </div>
        <div>
            <label class="piar-label">Nombre del padre</label>
            <input type="text" name="PAD_PADRE" value="{{ $v('PAD_PADRE', $nombrePadre) }}" class="piar-input">
        </div>
        <div>
            <label class="piar-label">Ocupación de la madre</label>
            <input type="text" name="OCUP_MADRE" value="{{ $v('OCUP_MADRE', $empMadre) }}" class="piar-input">
        </div>
        <div>
            <label class="piar-label">Ocupación del padre</label>
            <input type="text" name="OCUP_PADRE" value="{{ $v('OCUP_PADRE', $empPadre) }}" class="piar-input">
        </div>
        <div>
            <label class="piar-label">Nivel educativo de la madre</label>
            <input type="text" name="EDUC_MADRE" value="{{ $v('EDUC_MADRE') }}" placeholder="Ej: Universitario" class="piar-input">
        </div>
        <div>
            <label class="piar-label">Nivel educativo del padre</label>
            <input type="text" name="EDUC_PADRE" value="{{ $v('EDUC_PADRE') }}" placeholder="Ej: Universitario" class="piar-input">
        </div>
    </div>

    <div class="mt-4 pt-4 border-t border-gray-100 grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4">
        <div>
            <label class="piar-label">Nombre del cuidador</label>
            <input type="text" name="NOMB_CUID" value="{{ $v('NOMB_CUID', $nombreAcud) }}" class="piar-input">
        </div>
        <div>
            <label class="piar-label">Parentesco con el estudiante</label>
            <input type="text" name="PAREN_CUID" value="{{ $v('PAREN_CUID') }}" placeholder="Ej: Madre" class="piar-input">
        </div>
        <div>
            <label class="piar-label">Nivel educativo del cuidador</label>
            <input type="text" name="EDUC_CUID" value="{{ $v('EDUC_CUID') }}" placeholder="Ej: Universitario" class="piar-input">
        </div>
        <div>
            <label class="piar-label">Teléfono del cuidador</label>
            <input type="text" name="TEL_CUID" value="{{ $v('TEL_CUID', $celAcud ?: $celMadre) }}" class="piar-input">
        </div>
        <div class="sm:col-span-2">
            <label class="piar-label">Correo electrónico del cuidador</label>
            <input type="text" name="EMAIL_CUID" value="{{ $v('EMAIL_CUID', $emailAcud) }}" class="piar-input">
        </div>
    </div>

    <div class="mt-4 pt-4 border-t border-gray-100 grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4">
        <div>
            <label class="piar-label">No. de hermanos</label>
            <input type="text" name="HERMANOS" value="{{ $v('HERMANOS') }}" class="piar-input">
        </div>
        <div>
            <label class="piar-label">Lugar que ocupa entre los hermanos</label>
            <input type="text" name="LUGAR" value="{{ $v('LUGAR') }}" class="piar-input">
        </div>
        <div>
            <label class="piar-label">¿Quiénes apoyan la crianza del estudiante?</label>
            <input type="text" name="CRIANZA" value="{{ $v('CRIANZA') }}" placeholder="Ej: Mamá y papá" class="piar-input">
        </div>
        <div>
            <label class="piar-label">Personas con quienes vive</label>
            <input type="text" name="PERS_VIVE" value="{{ $v('PERS_VIVE') }}" placeholder="Ej: Mamá, papá, hermanos" class="piar-input">
        </div>
        <div>
            <label class="piar-label">¿Está bajo protección institucional?</label>
            <div class="flex gap-4 mt-1 flex-wrap items-center">
                <label class="text-sm flex items-center gap-1">
                    <input type="radio" name="HOG_PROTEC" value="si"
                        {{ $vb('HOG_PROTEC') === true ? 'checked' : '' }}> Sí – Institución:
                    <input type="text" name="HOG_PROTEC_WHICH" value="{{ $v('HOG_PROTEC_WHICH') }}" class="piar-input-inline ml-1">
                </label>
                <label class="text-sm flex items-center gap-1">
                    <input type="radio" name="HOG_PROTEC" value="no"
                        {{ $vb('HOG_PROTEC') !== true ? 'checked' : '' }}> No
                </label>
            </div>
        </div>
        <div>
            <label class="piar-label">¿La familia recibe algún subsidio?</label>
            <div class="flex gap-4 mt-1 flex-wrap items-center">
                <label class="text-sm flex items-center gap-1">
                    <input type="radio" name="HOG_SUB" value="si"
                        {{ $vb('HOG_SUB') === true ? 'checked' : '' }}> Sí – ¿Cuál?
                    <input type="text" name="HOG_SUB_WHICH" value="{{ $v('HOG_SUB_WHICH') }}" class="piar-input-inline ml-1">
                </label>
                <label class="text-sm flex items-center gap-1">
                    <input type="radio" name="HOG_SUB" value="no"
                        {{ $vb('HOG_SUB') !== true ? 'checked' : '' }}> No
                </label>
            </div>
        </div>
    </div>
</div>

{{-- ══ SECCIÓN 4 – ENTORNO EDUCATIVO ══ --}}
<div class="bg-white rounded-xl shadow p-6 print-card">
    <h2 class="piar-section-title">4. Entorno Educativo</h2>
    <p class="text-xs text-gray-400 mb-4">Información de la Trayectoria Educativa</p>

    <div class="space-y-4">
        <div>
            <label class="piar-label">¿Ha estado vinculado en otra institución educativa, fundación o modalidad de educación inicial?</label>
            <div class="flex gap-4 mt-1 flex-wrap items-center">
                <label class="text-sm flex items-center gap-1">
                    <input type="radio" name="INSTITUPREV" value="si"
                        {{ $vb('INSTITUPREV') === true ? 'checked' : '' }}
                        onchange="document.getElementById('instituprev-cual').classList.remove('hidden');document.getElementById('instituprev-porque').classList.add('hidden')"> Sí – ¿Cuál?
                    <input type="text" name="INTITUPREV_WHICH" value="{{ $v('INTITUPREV_WHICH') }}" class="piar-input-inline ml-1">
                </label>
                <label class="text-sm flex items-center gap-1">
                    <input type="radio" name="INSTITUPREV" value="no"
                        {{ $vb('INSTITUPREV') !== true ? 'checked' : '' }}
                        onchange="document.getElementById('instituprev-porque').classList.remove('hidden');document.getElementById('instituprev-cual').classList.add('hidden')"> No
                </label>
            </div>
            <div id="instituprev-cual" class="{{ $vb('INSTITUPREV') === true ? '' : 'hidden' }} mt-2">
                {{-- el campo ¿Cuál? ya está inline, este div es solo un placeholder por si se necesita ampliar --}}
            </div>
            <div id="instituprev-porque" class="{{ $vb('INSTITUPREV') !== true ? '' : 'hidden' }} mt-2">
                <label class="piar-label">¿Por qué?</label>
                <input type="text" name="INSTITUPREV_PORQUE" value="{{ $v('INSTITUPREV_PORQUE') }}"
                    placeholder="Ej: Nunca ha asistido a otra institución" class="piar-input">
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4">
            <div>
                <label class="piar-label">Último grado cursado</label>
                <input type="text" name="ULTGRADO" value="{{ $v('ULTGRADO', $grado) }}" class="piar-input">
            </div>
            <div>
                <label class="piar-label">¿Aprobó?</label>
                <div class="flex gap-4 mt-1">
                    <label class="text-sm flex items-center gap-1">
                        <input type="radio" name="APRUEBA" value="si"
                            {{ ($vb('APRUEBA') !== false) ? 'checked' : '' }}
                            onchange="document.getElementById('aprueba-porque').classList.add('hidden')"> Sí
                    </label>
                    <label class="text-sm flex items-center gap-1">
                        <input type="radio" name="APRUEBA" value="no"
                            {{ $vb('APRUEBA') === false ? 'checked' : '' }}
                            onchange="document.getElementById('aprueba-porque').classList.remove('hidden')"> No
                    </label>
                </div>
                <div id="aprueba-porque" class="{{ $vb('APRUEBA') === false ? '' : 'hidden' }} mt-2">
                    <label class="piar-label">¿Por qué no aprobó?</label>
                    <input type="text" name="APRUEBA_PORQUE" value="{{ $v('APRUEBA_PORQUE') }}"
                        placeholder="Ej: Repitió por bajo rendimiento académico" class="piar-input">
                </div>
            </div>
            <div class="sm:col-span-2">
                <label class="piar-label">Observaciones</label>
                <textarea name="OBSERV" rows="2" class="piar-input">{{ $v('OBSERV') }}</textarea>
            </div>
        </div>

        <div class="pt-4 border-t border-gray-100">
            <label class="piar-label">¿Se recibe informe pedagógico cualitativo (PIAR de institución anterior)?</label>
            <div class="flex gap-4 mt-1">
                <label class="text-sm flex items-center gap-1">
                    <input type="radio" name="INFOPIAR" value="si"
                        {{ $vb('INFOPIAR') === true ? 'checked' : '' }}> Sí
                </label>
                <label class="text-sm flex items-center gap-1">
                    <input type="radio" name="INFOPIAR" value="no"
                        {{ $vb('INFOPIAR') !== true ? 'checked' : '' }}> No
                </label>
            </div>
            <div class="mt-3">
                <label class="piar-label">¿De qué institución o modalidad proviene el informe?</label>
                <input type="text" name="INFOPIAR_WHICH" value="{{ $v('INFOPIAR_WHICH') }}" class="piar-input">
            </div>
        </div>

        <div class="pt-4 border-t border-gray-100">
            <label class="piar-label">¿Está asistiendo a programas complementarios?</label>
            <div class="flex gap-4 mt-1 flex-wrap items-center">
                <label class="text-sm flex items-center gap-1">
                    <input type="radio" name="COMPLEM" value="si"
                        {{ $vb('COMPLEM') === true ? 'checked' : '' }}> Sí – ¿Cuáles?
                    <input type="text" name="COMPLEM_WHICH" value="{{ $v('COMPLEM_WHICH') }}" class="piar-input-inline ml-1">
                </label>
                <label class="text-sm flex items-center gap-1">
                    <input type="radio" name="COMPLEM" value="no"
                        {{ $vb('COMPLEM') !== true ? 'checked' : '' }}> No
                </label>
            </div>
        </div>

        <div class="pt-4 border-t border-gray-100">
            <h3 class="text-sm font-semibold text-gray-700 mb-3">Información de la institución educativa en la que se matricula</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4">
                <div>
                    <label class="piar-label">Nombre de la institución</label>
                    <input type="text" name="INST_NOMBRE" value="{{ $v('INST_NOMBRE', 'Colegio Bilingüe Integral CBI.') }}" class="piar-input">
                </div>
                <div>
                    <label class="piar-label">Sede</label>
                    <input type="text" name="INST_SEDE" value="{{ $v('INST_SEDE', $sede ?: 'Sede A') }}" class="piar-input">
                </div>
                <div>
                    <label class="piar-label">Medio de transporte del estudiante</label>
                    <input type="text" name="TRANSPOR" value="{{ $v('TRANSPOR') }}" placeholder="Ej: Vehículo propio / caminando" class="piar-input">
                </div>
                <div>
                    <label class="piar-label">Distancia entre la institución y el hogar</label>
                    <input type="text" name="DISTANCIA" value="{{ $v('DISTANCIA') }}" placeholder="Ej: 10 minutos en vehículo" class="piar-input">
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ══ FIRMAS ══ --}}
<div class="bg-white rounded-xl shadow p-6 print-card">
    <div class="grid grid-cols-3 gap-8 mt-6">
        @foreach([1,2,3] as $f)
        <div class="text-center">
            <div class="border-b-2 border-gray-400 mb-2 h-16"></div>
            <p class="text-xs text-gray-400">Nombre y firma</p>
        </div>
        @endforeach
    </div>
</div>

{{-- Botón de guardado inferior --}}
<div class="flex justify-end no-print pb-6">
    <button type="submit"
        class="bg-green-700 hover:bg-green-600 text-white text-sm font-semibold px-6 py-2.5 rounded-lg transition">
        💾 Guardar PIAR
    </button>
</div>

</div>{{-- fin space-y-6 --}}
</form>

{{-- ── Panel: Estudiantes registrados en PIAR ─────────────────────────── --}}
<div class="mt-8 no-print">
    <div class="bg-white rounded-2xl shadow-md border border-blue-100 overflow-hidden">
        <div class="flex items-center justify-between px-5 py-3 bg-blue-800 text-white">
            <h2 class="text-sm font-bold uppercase tracking-wide">Estudiantes registrados en PIAR</h2>
            <span class="bg-white text-blue-800 text-xs font-bold px-3 py-1 rounded-full">
                Total: {{ $totalEnPiar }}
            </span>
        </div>

        @if($estudiantesEnPiar->isEmpty())
            <p class="text-sm text-gray-500 px-5 py-4">Aún no hay estudiantes con PIAR registrado.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-blue-50 text-blue-900 text-xs uppercase">
                        <tr>
                            <th class="px-4 py-2 text-left font-semibold">#</th>
                            <th class="px-4 py-2 text-left font-semibold">Estudiante</th>
                            <th class="px-4 py-2 text-left font-semibold">Curso</th>
                            <th class="px-4 py-2 text-left font-semibold">Diagnóstico</th>
                            <th class="px-4 py-2 text-center font-semibold">Acción</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($estudiantesEnPiar as $i => $est)
                        <tr class="{{ $est->CODIGO == $estudiante->CODIGO ? 'bg-yellow-50 font-semibold' : 'hover:bg-gray-50' }}">
                            <td class="px-4 py-2 text-gray-500">{{ $i + 1 }}</td>
                            <td class="px-4 py-2">
                                {{ $est->APELLIDO1 }} {{ $est->APELLIDO2 }}, {{ $est->NOMBRE1 }} {{ $est->NOMBRE2 }}
                                @if($est->CODIGO == $estudiante->CODIGO)
                                    <span class="ml-1 text-xs text-yellow-700 font-normal">(actual)</span>
                                @endif
                            </td>
                            <td class="px-4 py-2 text-center">
                                <span class="bg-blue-100 text-blue-800 text-xs font-semibold px-2 py-0.5 rounded">
                                    {{ $est->CURSO }}
                                </span>
                            </td>
                            <td class="px-4 py-2 text-gray-700 max-w-xs truncate" title="{{ $est->DIAGNOSTICO }}">
                                {{ $est->DIAGNOSTICO ?: '—' }}
                            </td>
                            <td class="px-4 py-2 text-center">
                                <a href="{{ route('piar.crear', $est->CODIGO) }}"
                                   class="text-blue-700 hover:underline text-xs">Ver PIAR</a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

@endsection

@push('scripts')
<style>
    .piar-label {
        display: block;
        font-size: 0.65rem;
        font-weight: 600;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 0.25rem;
    }
    .piar-input {
        width: 100%;
        border: none;
        border-bottom: 1px solid #d1d5db;
        background: transparent;
        padding: 0.25rem 0;
        font-size: 0.875rem;
        color: #1f2937;
        outline: none;
    }
    .piar-input:focus { border-bottom-color: #3b82f6; }
    .piar-input[readonly] { color: #4b5563; }
    textarea.piar-input { border: 1px solid #d1d5db; border-radius: 0.375rem; padding: 0.375rem 0.5rem; resize: none; }
    .piar-input-inline {
        border: none;
        border-bottom: 1px solid #d1d5db;
        background: transparent;
        padding: 0.125rem 0;
        font-size: 0.875rem;
        color: #1f2937;
        outline: none;
        width: 8rem;
    }
    .piar-section-title {
        font-size: 0.9rem;
        font-weight: 700;
        color: #1e3a8a;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 1rem;
        padding-bottom: 0.5rem;
        border-bottom: 2px solid #dbeafe;
    }

    @media print {
        .no-print { display: none !important; }
        body { background: white !important; }
        aside, header { display: none !important; }
        .flex-1 { padding: 0 !important; }
        main { padding: 0 !important; }
        .space-y-6 > div { max-width: 100% !important; }
        .print-card { box-shadow: none !important; border: 1px solid #e5e7eb; page-break-inside: avoid; margin-bottom: 1rem; }
    }
</style>
@endpush
