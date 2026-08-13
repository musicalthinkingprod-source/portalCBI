@extends('layouts.app-sidebar')

@php
    $edit = $entrevista->exists;
    $e    = $entrevista;
    $sem  = \App\Support\EntrevistaAdmision::SEMAFORO;
    $vias = \App\Support\EntrevistaAdmision::VIABILIDAD;

    // Valor actual de una respuesta, respetando old() tras un error de validación.
    $val = fn($sec, $k) => old("respuestas.$sec.$k", $e->respuestas[$sec][$k] ?? '');
    $familia = old('familia', $e->familia ?: [['nombre' => '', 'parentesco' => '', 'edad' => '', 'ocupacion' => '', 'positivo' => '', 'negativo' => '']]);
@endphp

@section('header', ($edit ? 'Editar entrevista · '.$e->aspirante_nombre : 'Nueva entrevista de admisión'))

@section('slot')

@if($errors->any())
<div class="mb-5 bg-red-50 border border-red-300 text-red-800 px-4 py-3 rounded-lg text-sm">
    <ul class="list-disc list-inside space-y-1">
        @foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach
    </ul>
</div>
@endif

<div class="mb-4">
    <a href="{{ route('admision.entrevistas.index') }}" class="text-sm text-blue-700 hover:underline">← Volver al listado</a>
</div>

<form method="POST" action="{{ $edit ? route('admision.entrevistas.update', $e) : route('admision.entrevistas.store') }}">
    @csrf
    @if($edit) @method('PUT') @endif

    {{-- ── I. Datos personales y contexto de ingreso ───────────────────────── --}}
    <div class="bg-white rounded-xl shadow p-5 mb-6">
        <h2 class="text-base font-semibold text-gray-800 mb-1">I. Datos personales y contexto de ingreso</h2>
        <p class="text-xs text-gray-500 mb-4">Si el aspirante ya presentó el examen, selecciónalo para vincularlo al balance de rectoría.</p>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="lg:col-span-2">
                <label class="block text-xs font-medium text-gray-500 mb-1">Examen de admisión presentado</label>
                <select name="evaluacion_id" id="sel-evaluacion"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <option value="">— Sin examen vinculado —</option>
                    @foreach($evaluaciones as $ev)
                    <option value="{{ $ev->id }}"
                            data-nombre="{{ $ev->aspirante_nombre }}"
                            data-documento="{{ $ev->aspirante_documento }}"
                            data-grado="{{ $ev->grado_key }}"
                            @selected(old('evaluacion_id', $e->evaluacion_id) == $ev->id)>
                        {{ $ev->aspirante_nombre }} — {{ $ev->grado_nombre }}{{ $ev->aspirante_documento ? ' · '.$ev->aspirante_documento : '' }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Fecha de la entrevista</label>
                <input type="date" name="fecha_entrevista"
                       value="{{ old('fecha_entrevista', optional($e->fecha_entrevista)->format('Y-m-d') ?? date('Y-m-d')) }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Grado al que aspira</label>
                <select name="grado_key" id="sel-grado" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <option value="">— Selecciona —</option>
                    @foreach($grados as $g)
                        <option value="{{ $g['key'] }}" @selected(old('grado_key', $e->grado_key) === $g['key'])>{{ $g['nombre'] }}</option>
                    @endforeach
                </select>
            </div>

            <div class="lg:col-span-2">
                <label class="block text-xs font-medium text-gray-500 mb-1">Nombre completo del aspirante *</label>
                <input type="text" name="aspirante_nombre" id="in-nombre" required
                       value="{{ old('aspirante_nombre', $e->aspirante_nombre) }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Tipo de documento</label>
                <select name="documento_tipo" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <option value="">—</option>
                    @foreach(['RC' => 'RC · Registro civil', 'TI' => 'TI · Tarjeta de identidad', 'CC' => 'CC · Cédula', 'CE' => 'CE · Cédula de extranjería', 'PPT' => 'PPT'] as $k => $t)
                        <option value="{{ $k }}" @selected(old('documento_tipo', $e->documento_tipo) === $k)>{{ $t }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Número de documento</label>
                <input type="text" name="aspirante_documento" id="in-documento"
                       value="{{ old('aspirante_documento', $e->aspirante_documento) }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Fecha de nacimiento</label>
                <input type="date" name="fecha_nacimiento" id="in-nacimiento"
                       value="{{ old('fecha_nacimiento', optional($e->fecha_nacimiento)->format('Y-m-d')) }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Lugar de nacimiento</label>
                <input type="text" name="lugar_nacimiento" value="{{ old('lugar_nacimiento', $e->lugar_nacimiento) }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Edad</label>
                <input type="number" name="edad" id="in-edad" min="0" max="99" value="{{ old('edad', $e->edad) }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Teléfono de contacto</label>
                <input type="text" name="telefono" value="{{ old('telefono', $e->telefono) }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>

            <div class="lg:col-span-4">
                <label class="block text-xs font-medium text-gray-500 mb-1">Dirección de residencia</label>
                <input type="text" name="direccion" value="{{ old('direccion', $e->direccion) }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>

            <div class="lg:col-span-2">
                <label class="block text-xs font-medium text-gray-500 mb-1">Acudiente</label>
                <input type="text" name="acudiente" value="{{ old('acudiente', $e->acudiente) }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Parentesco</label>
                <input type="text" name="acudiente_parentesco" value="{{ old('acudiente_parentesco', $e->acudiente_parentesco) }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Ocupación</label>
                <input type="text" name="acudiente_ocupacion" value="{{ old('acudiente_ocupacion', $e->acudiente_ocupacion) }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Teléfono del acudiente</label>
                <input type="text" name="acudiente_telefono" value="{{ old('acudiente_telefono', $e->acudiente_telefono) }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div class="lg:col-span-2">
                <label class="block text-xs font-medium text-gray-500 mb-1">Correo del acudiente</label>
                <input type="text" name="acudiente_correo" value="{{ old('acudiente_correo', $e->acudiente_correo) }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Red de apoyo (contacto)</label>
                <input type="text" name="red_apoyo" value="{{ old('red_apoyo', $e->red_apoyo) }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
        </div>

        <hr class="my-5 border-gray-100">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">1. Institución educativa de procedencia</label>
                <input type="text" name="institucion_procedencia" value="{{ old('institucion_procedencia', $e->institucion_procedencia) }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Tiempo de permanencia en ella</label>
                <input type="text" name="tiempo_permanencia" value="{{ old('tiempo_permanencia', $e->tiempo_permanencia) }}"
                       placeholder="Ej: 4 años" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div class="md:col-span-2">
                <label class="block text-xs font-medium text-gray-500 mb-1">2. Motivo del cambio de institución. Fue decisión de la familia, del colegio anterior, o de ambas partes.</label>
                <textarea name="motivo_retiro" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">{{ old('motivo_retiro', $e->motivo_retiro) }}</textarea>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">3. Ha repetido algún grado. Cuál y por qué motivo.</label>
                <textarea name="ha_reprobado" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">{{ old('ha_reprobado', $e->ha_reprobado) }}</textarea>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">4. Ha tenido más de dos cambios de colegio. Qué llevó a esos cambios.</label>
                <textarea name="cambios_colegio" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">{{ old('cambios_colegio', $e->cambios_colegio) }}</textarea>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">5. Composición del núcleo familiar con quien convive actualmente.</label>
                <textarea name="nucleo_familiar" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">{{ old('nucleo_familiar', $e->nucleo_familiar) }}</textarea>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">6. Persona responsable de acompañar los procesos académicos en casa.</label>
                <input type="text" name="responsable_academico" value="{{ old('responsable_academico', $e->responsable_academico) }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
        </div>

        {{-- Cuadro de composición familiar --}}
        <div class="mt-5">
            <div class="flex items-center justify-between mb-2">
                <p class="text-xs font-semibold text-gray-600">Cuadro de composición familiar</p>
                <button type="button" onclick="agregarFamiliar()"
                        class="text-xs bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-1 rounded-lg">＋ Agregar familiar</button>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm" id="tabla-familia">
                    <thead>
                        <tr class="text-left text-[10px] uppercase tracking-wide text-gray-400 border-b">
                            <th class="py-1 pr-2">Nombre</th>
                            <th class="py-1 pr-2">Parentesco</th>
                            <th class="py-1 pr-2 w-16">Edad</th>
                            <th class="py-1 pr-2">Ocupación</th>
                            <th class="py-1 pr-2">Aspectos positivos</th>
                            <th class="py-1 pr-2">Aspectos por mejorar</th>
                            <th class="w-8"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($familia as $i => $f)
                        <tr>
                            <td class="py-1 pr-2"><input type="text" name="familia[{{ $i }}][nombre]" value="{{ $f['nombre'] ?? '' }}" class="w-full border border-gray-200 rounded px-2 py-1 text-xs"></td>
                            <td class="py-1 pr-2"><input type="text" name="familia[{{ $i }}][parentesco]" value="{{ $f['parentesco'] ?? '' }}" class="w-full border border-gray-200 rounded px-2 py-1 text-xs"></td>
                            <td class="py-1 pr-2"><input type="number" name="familia[{{ $i }}][edad]" value="{{ $f['edad'] ?? '' }}" class="w-full border border-gray-200 rounded px-2 py-1 text-xs"></td>
                            <td class="py-1 pr-2"><input type="text" name="familia[{{ $i }}][ocupacion]" value="{{ $f['ocupacion'] ?? '' }}" class="w-full border border-gray-200 rounded px-2 py-1 text-xs"></td>
                            <td class="py-1 pr-2"><input type="text" name="familia[{{ $i }}][positivo]" value="{{ $f['positivo'] ?? '' }}" class="w-full border border-gray-200 rounded px-2 py-1 text-xs"></td>
                            <td class="py-1 pr-2"><input type="text" name="familia[{{ $i }}][negativo]" value="{{ $f['negativo'] ?? '' }}" class="w-full border border-gray-200 rounded px-2 py-1 text-xs"></td>
                            <td class="py-1"><button type="button" onclick="this.closest('tr').remove()" class="text-red-500 text-xs">✕</button></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ── II a VII: secciones de preguntas ────────────────────────────────── --}}
    @foreach($secciones as $slug => $sec)
    @php $marca = old("alertas.$slug", $e->alertas[$slug] ?? ''); @endphp
    <div class="bg-white rounded-xl shadow p-5 mb-6">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
            <h2 class="text-base font-semibold text-gray-800">
                {{ $sec['icono'] }} {{ $sec['num'] }}. {{ $sec['titulo'] }}
            </h2>
            <div class="flex items-center gap-2">
                <span class="text-[10px] uppercase text-gray-400">Valoración del área</span>
                @foreach($sem as $k => $s)
                <label class="cursor-pointer">
                    <input type="radio" name="alertas[{{ $slug }}]" value="{{ $k }}" class="peer sr-only" @checked($marca === $k)>
                    <span class="text-[11px] px-2.5 py-1 rounded-full border border-gray-200 text-gray-500 transition
                        peer-checked:font-semibold
                        @if($k==='ok') peer-checked:bg-green-100 peer-checked:text-green-700 peer-checked:border-green-300
                        @elseif($k==='atencion') peer-checked:bg-amber-100 peer-checked:text-amber-700 peer-checked:border-amber-300
                        @else peer-checked:bg-red-100 peer-checked:text-red-700 peer-checked:border-red-300 @endif">
                        {{ $s['texto'] }}
                    </span>
                </label>
                @endforeach
            </div>
        </div>

        <div class="space-y-4">
            @foreach($sec['preguntas'] as $i => $p)
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">{{ $i + 1 }}. {{ $p['t'] }}</label>
                <textarea name="respuestas[{{ $slug }}][{{ $p['k'] }}]" rows="{{ $p['filas'] }}"
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">{{ $val($slug, $p['k']) }}</textarea>
            </div>
            @endforeach
        </div>
    </div>
    @endforeach

    {{-- ── VIII. Concepto de los entrevistadores ───────────────────────────── --}}
    <div class="bg-white rounded-xl shadow p-5 mb-6 border-t-4 border-blue-800">
        <h2 class="text-base font-semibold text-gray-800 mb-4">📋 VIII. Concepto de los entrevistadores</h2>

        <div class="space-y-4">
            @foreach($concepto as $i => $p)
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">{{ $i + 1 }}. {{ $p['t'] }}</label>
                <textarea name="respuestas[concepto][{{ $p['k'] }}]" rows="{{ $p['filas'] }}"
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">{{ $val('concepto', $p['k']) }}</textarea>
            </div>
            @endforeach
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-5">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Concepto frente a la viabilidad de ingreso</label>
                <select name="viabilidad" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <option value="">— Sin concepto —</option>
                    @foreach($vias as $k => $t)
                        <option value="{{ $k }}" @selected(old('viabilidad', $e->viabilidad) === $k)>{{ $t }}</option>
                    @endforeach
                </select>
            </div>
            <div class="md:col-span-2">
                <label class="block text-xs font-medium text-gray-600 mb-1">Compromisos acordados con la familia</label>
                <textarea name="compromisos" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">{{ old('compromisos', $e->compromisos) }}</textarea>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Orientador(a) escolar</label>
                <input type="text" name="orientador" value="{{ old('orientador', $e->orientador ?? optional(auth()->user())->USER) }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Coordinador(a)</label>
                <input type="text" name="coordinador" value="{{ old('coordinador', $e->coordinador) }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Estado</label>
                <select name="estado" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <option value="borrador" @selected(old('estado', $e->estado) === 'borrador')>Borrador (aún la estoy diligenciando)</option>
                    <option value="finalizada" @selected(old('estado', $e->estado) === 'finalizada')>Finalizada (lista para rectoría)</option>
                </select>
            </div>
        </div>
    </div>

    <div class="flex flex-wrap gap-3 mb-8">
        <button type="submit" class="bg-blue-800 hover:bg-blue-700 text-white text-sm font-semibold px-6 py-2.5 rounded-lg transition">
            💾 {{ $edit ? 'Guardar cambios' : 'Guardar entrevista' }}
        </button>
        <a href="{{ route('admision.entrevistas.index') }}"
           class="bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-semibold px-6 py-2.5 rounded-lg transition">Cancelar</a>
    </div>
</form>

<script>
// Prellenar datos del aspirante al vincular un examen de admisión.
document.getElementById('sel-evaluacion').addEventListener('change', function () {
    const op = this.selectedOptions[0];
    if (!op || !op.value) return;
    const nombre = document.getElementById('in-nombre');
    const doc    = document.getElementById('in-documento');
    const grado  = document.getElementById('sel-grado');
    if (!nombre.value) nombre.value = op.dataset.nombre || '';
    if (!doc.value)    doc.value    = op.dataset.documento || '';
    if (!grado.value)  grado.value  = op.dataset.grado || '';
});

// Edad automática a partir de la fecha de nacimiento.
document.getElementById('in-nacimiento').addEventListener('change', function () {
    if (!this.value) return;
    const nac = new Date(this.value);
    const hoy = new Date();
    let edad = hoy.getFullYear() - nac.getFullYear();
    const m = hoy.getMonth() - nac.getMonth();
    if (m < 0 || (m === 0 && hoy.getDate() < nac.getDate())) edad--;
    if (edad >= 0 && edad < 100) document.getElementById('in-edad').value = edad;
});

// Filas dinámicas del cuadro familiar.
function agregarFamiliar() {
    const tbody = document.querySelector('#tabla-familia tbody');
    const i = Date.now();
    const campos = ['nombre', 'parentesco', 'edad', 'ocupacion', 'positivo', 'negativo'];
    const tr = document.createElement('tr');
    tr.innerHTML = campos.map(c =>
        `<td class="py-1 pr-2"><input type="${c === 'edad' ? 'number' : 'text'}" name="familia[${i}][${c}]" class="w-full border border-gray-200 rounded px-2 py-1 text-xs"></td>`
    ).join('') + '<td class="py-1"><button type="button" onclick="this.closest(\'tr\').remove()" class="text-red-500 text-xs">✕</button></td>';
    tbody.appendChild(tr);
}
</script>

@endsection
