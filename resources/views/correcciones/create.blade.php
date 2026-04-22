@extends('layouts.app-sidebar')

@section('header', 'Nueva Solicitud de Corrección')

@section('slot')

    <div class="max-w-2xl">

        <div class="mb-4">
            <a href="{{ route('correcciones.index') }}" class="text-blue-700 hover:underline text-sm">← Volver a mis solicitudes</a>
        </div>

        @if($errors->any())
            <div class="mb-4 p-3 bg-red-100 text-red-700 rounded-xl text-sm space-y-1">
                @foreach($errors->all() as $e) <p>⚠️ {{ $e }}</p> @endforeach
            </div>
        @endif

        <div class="bg-white rounded-xl shadow p-6 space-y-5">

            <p class="text-sm text-gray-500">
                Completa el formulario indicando qué nota necesita corrección y el motivo detallado.
                Un administrador revisará la solicitud y la aprobará o rechazará.
            </p>

            {{-- Paso 1: seleccionar materia/curso/periodo --}}
            <form method="GET" action="{{ route('correcciones.create') }}" id="form-selector">
                <div class="grid grid-cols-1 sm:grid-cols-{{ $esOrientador ? '2' : '3' }} gap-4 items-end">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Materia</label>
                        <select name="materia" id="sel-materia"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">— Selecciona —</option>
                            @foreach($materias as $mat)
                                <option value="{{ $mat->CODIGO_MAT }}" {{ $matSelec == $mat->CODIGO_MAT ? 'selected' : '' }}>
                                    {{ $mat->NOMBRE_MAT }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @if(!$esOrientador)
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Curso</label>
                        <select name="curso" id="sel-curso"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                            {{ !$matSelec ? 'disabled' : '' }}>
                            <option value="">— Selecciona —</option>
                            @foreach(($mapaMateriasCursos[$matSelec] ?? []) as $c)
                                <option value="{{ $c }}" {{ $cursoSelec == $c ? 'selected' : '' }}>{{ $c }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Período</label>
                        <select name="periodo"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @foreach([1,2,3,4] as $p)
                                <option value="{{ $p }}" {{ $periodo == $p ? 'selected' : '' }}>Período {{ $p }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </form>

            @if($matSelec && ($cursoSelec || $esOrientador))
            {{-- Paso 2: formulario de solicitud --}}
            <form method="POST" action="{{ route('correcciones.store') }}" class="space-y-4" id="form-solicitud">
                @csrf
                <input type="hidden" name="codigo_mat" value="{{ $matSelec }}">
                <input type="hidden" name="periodo"    value="{{ $periodo }}">

                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Estudiante</label>
                    <select name="codigo_alum" id="sel-alumno" required
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">— Selecciona un estudiante —</option>
                        @foreach($estudiantes as $est)
                            <option value="{{ $est->CODIGO }}"
                                data-nota="{{ old('codigo_alum') == $est->CODIGO ? old('nota_actual') : '' }}"
                                {{ old('codigo_alum', $codAlum) == $est->CODIGO ? 'selected' : '' }}>
                                {{ $est->APELLIDO1 }} {{ $est->APELLIDO2 }} {{ $est->NOMBRE1 }} — Cód. {{ $est->CODIGO }}
                            </option>
                        @endforeach
                    </select>
                </div>

                @if($notaActual !== null)
                <div class="bg-gray-50 border border-gray-200 rounded-lg px-4 py-2 text-sm text-gray-600">
                    Nota actual en el sistema: <strong class="text-gray-900">{{ $notaActual }}</strong>
                </div>
                @endif

                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Nota propuesta</label>
                    <input type="number" name="nota_propuesta"
                        value="{{ old('nota_propuesta') }}"
                        min="0" max="10" step="0.1" required
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @error('nota_propuesta')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">
                        Motivo de la corrección <span class="text-gray-400 font-normal normal-case">(mínimo 10 caracteres)</span>
                    </label>
                    <textarea name="motivo" rows="4" required minlength="10"
                        placeholder="Describe detalladamente por qué se debe corregir esta nota…"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none">{{ old('motivo') }}</textarea>
                    @error('motivo')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="submit"
                        class="flex-1 bg-blue-800 hover:bg-blue-700 text-white font-semibold py-2 rounded-lg transition text-sm">
                        Enviar solicitud
                    </button>
                    <a href="{{ route('correcciones.index') }}"
                        class="flex-1 text-center bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold py-2 rounded-lg transition text-sm">
                        Cancelar
                    </a>
                </div>
            </form>
            @else
                <div class="bg-gray-50 border border-gray-200 text-gray-500 rounded-xl p-4 text-sm text-center">
                    Selecciona materia, curso y período para continuar.
                </div>
            @endif

        </div>
    </div>

@endsection

@push('scripts')
<script>
    const esOrientador = @json($esOrientador);
    const mapaMaterias = @json($mapaMateriasCursos);
    const selMateria   = document.getElementById('sel-materia');
    const selCurso     = document.getElementById('sel-curso');
    const formSelector = document.getElementById('form-selector');
    const selAlumno    = document.getElementById('sel-alumno');

    if (selMateria) {
        selMateria.addEventListener('change', () => {
            if (esOrientador) {
                formSelector.submit();
                return;
            }
            selCurso.innerHTML = '<option value="">— Selecciona —</option>';
            const mat = selMateria.value;
            if (mat && mapaMaterias[mat]) {
                mapaMaterias[mat].forEach(c => {
                    const opt = document.createElement('option');
                    opt.value = c; opt.textContent = c;
                    selCurso.appendChild(opt);
                });
                selCurso.disabled = false;
            } else {
                selCurso.disabled = true;
            }
            selCurso.value = '';
        });
    }

    if (selCurso) {
        selCurso.addEventListener('change', () => {
            if (selCurso.value) formSelector.submit();
        });
    }

    if (selAlumno) {
        selAlumno.addEventListener('change', function () {
            if (!this.value) return;
            const url = new URL(window.location.href);
            url.searchParams.set('materia',     '{{ $matSelec }}');
            url.searchParams.set('curso',       '{{ $cursoSelec }}');
            url.searchParams.set('periodo',     '{{ $periodo }}');
            url.searchParams.set('codigo_alum',  this.value);
            window.location.href = url.toString();
        });
    }
</script>
@endpush
