@extends('layouts.app-sidebar')
@section('header', 'Recuperaciones')
@section('slot')

    @if(session('success'))
        <div class="mb-4 p-3 bg-green-100 text-green-800 rounded-xl text-sm">✅ {{ session('success') }}</div>
    @endif
    @if($errors->has('resolucion'))
        <div class="mb-4 p-3 bg-red-100 text-red-700 rounded-xl text-sm">⚠️ {{ $errors->first('resolucion') }}</div>
    @endif

    {{-- Filtros --}}
    <div class="bg-white rounded-xl shadow p-5 mb-6">
        <form method="GET" action="{{ route('derroteros.docente') }}" id="form-filtros">
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 items-end">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Período</label>
                    <select name="periodo" id="sel-periodo"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @foreach([1,2,3,4] as $p)
                            <option value="{{ $p }}" {{ $periodoSelec == $p ? 'selected' : '' }}>Período {{ $p }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Materia</label>
                    <select name="materia" id="sel-materia"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">— Selecciona una materia —</option>
                        @foreach($materias as $mat)
                            <option value="{{ $mat->CODIGO_MAT }}" {{ $matSelec == $mat->CODIGO_MAT ? 'selected' : '' }}>
                                {{ $mat->NOMBRE_MAT }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Curso</label>
                    <select name="curso" id="sel-curso"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                        {{ !$matSelec ? 'disabled' : '' }}>
                        <option value="">— Selecciona un curso —</option>
                        @foreach($cursosDisponibles as $c)
                            <option value="{{ $c->CURSO }}" {{ $cursoSelec == $c->CURSO ? 'selected' : '' }}>{{ $c->CURSO }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Ordenar por</label>
                    <select name="orden" id="sel-orden"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="apellido" {{ $ordenSelec == 'apellido' ? 'selected' : '' }}>Apellido (A–Z)</option>
                        <option value="codigo"   {{ $ordenSelec == 'codigo'   ? 'selected' : '' }}>Código</option>
                    </select>
                </div>
            </div>
        </form>
    </div>

    @if($matSelec && $cursoSelec)
        @php
            $fechaRecupHumano = $recupFecha
                ? \Carbon\Carbon::parse($recupFecha)->locale('es')->isoFormat('dddd D [de] MMMM [de] YYYY')
                : null;
        @endphp

        @if(!$recupAbierto)
            <div class="mb-4 p-3 bg-yellow-50 border border-yellow-200 text-yellow-800 rounded-xl text-sm">
                @if($recupFecha)
                    🔒 Las resoluciones del período {{ $periodoSelec }} solo se pueden guardar el
                    <strong>{{ $fechaRecupHumano }}</strong> entre <strong>6:30 a. m.</strong> y <strong>4:30 p. m.</strong>
                    Por ahora puedes consultar el listado.
                @else
                    🔒 No hay fecha de <em>Sustentación de Recuperaciones</em> registrada en el calendario académico para el período {{ $periodoSelec }}.
                    Pídele al administrador que la agregue.
                @endif
            </div>
        @elseif($esSuperior)
            <div class="mb-4 p-3 bg-blue-50 border border-blue-200 text-blue-800 rounded-xl text-sm">
                🛡️ Modo administrador: puedes resolver derroteros aunque la ventana no esté abierta.
                @if($recupFecha)
                    La ventana para docentes es el <strong>{{ $fechaRecupHumano }}</strong> de 6:30 a. m. a 4:30 p. m.
                @endif
            </div>
        @else
            <div class="mb-4 p-3 bg-green-50 border border-green-200 text-green-800 rounded-xl text-sm">
                ✅ Ventana de sustentación abierta hoy hasta las <strong>4:30 p. m.</strong>
            </div>
        @endif

        @if($derroteros->isEmpty())
            <div class="bg-green-50 border border-green-200 text-green-700 rounded-xl p-4 text-sm">
                ✅ Ningún estudiante del curso <strong>{{ $cursoSelec }}</strong> tiene derrotero en <strong>{{ $materiaNombre }}</strong> para el período {{ $periodoSelec }}.
            </div>
        @else
        <div class="bg-white rounded-xl shadow overflow-hidden">
            <div class="px-5 py-3 bg-red-800 text-white">
                <h3 class="font-bold text-sm uppercase tracking-wide">{{ $materiaNombre }} — Curso {{ $cursoSelec }} — Período {{ $periodoSelec }}</h3>
                <p class="text-red-200 text-xs mt-0.5">Estudiantes con nota menor a 7.0</p>
            </div>

            @foreach($derroteros as $codigoAlum => $materias)
            @foreach($materias as $m)
            <div class="border-b border-gray-100 px-5 py-4 {{ $loop->parent->last && $loop->last ? '' : '' }}">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="font-semibold text-gray-800">
                            <span class="font-mono text-xs text-gray-500 mr-2">{{ $m->CODIGO_ALUM }}</span>
                            {{ $m->APELLIDO1 }} {{ $m->APELLIDO2 }} {{ $m->NOMBRE1 }} {{ $m->NOMBRE2 }}
                        </p>
                        <div class="flex items-center gap-3 mt-1 text-xs text-gray-500">
                            <span>Nota: <strong class="text-red-600 text-sm">{{ number_format($m->NOTA, 1) }}</strong></span>
                            <span>Fallas previas: {{ $m->previas_periodos }}</span>
                            @if($m->elegible)
                                <span class="text-green-600 font-semibold">✅ Puede recuperar</span>
                            @else
                                <span class="text-red-500 font-semibold">❌ No puede recuperar · {{ $m->razon_no_elegible }}</span>
                            @endif
                        </div>
                    </div>

                    {{-- Resolución actual --}}
                    @if($m->resolucion !== 'PENDIENTE')
                    @php
                        $badge = match($m->resolucion) {
                            'RECUPERO'    => 'bg-green-100 text-green-700',
                            'NO_RECUPERO' => 'bg-red-100 text-red-700',
                            'INTERMEDIO'  => 'bg-blue-100 text-blue-700',
                            'NO_ASISTIO'  => 'bg-orange-100 text-orange-700',
                            default       => 'bg-gray-100 text-gray-500',
                        };
                        $label = match($m->resolucion) {
                            'RECUPERO'    => 'Recuperó → 7.0',
                            'NO_RECUPERO' => 'No recuperó → ' . number_format($m->NOTA, 1),
                            'INTERMEDIO'  => 'Intermedia → ' . number_format($m->nota_recuperacion, 1),
                            'NO_ASISTIO'  => 'No asistió → ' . number_format($m->NOTA, 1),
                            default       => '',
                        };
                    @endphp
                    <span class="inline-block {{ $badge }} text-xs font-semibold px-3 py-1 rounded-full whitespace-nowrap">
                        {{ $label }}
                    </span>
                    @endif
                </div>

                @if($m->elegible && $recupAbierto)
                <form method="POST" action="{{ route('derroteros.resolver') }}" class="mt-3 flex flex-wrap items-end gap-3">
                    @csrf
                    <input type="hidden" name="CODIGO_ALUM" value="{{ $m->CODIGO_ALUM }}">
                    <input type="hidden" name="CODIGO_MAT"  value="{{ $m->CODIGO_MAT }}">
                    <input type="hidden" name="periodo"     value="{{ $periodoSelec }}">

                    {{-- Opciones --}}
                    <div class="flex gap-2 flex-wrap">
                        <button type="submit" name="resolucion" value="RECUPERO"
                            onclick="return confirm('¿Confirmar que {{ $m->NOMBRE1 }} {{ $m->APELLIDO1 }} recuperó? La nota quedará en 7.0')"
                            class="bg-green-600 hover:bg-green-700 text-white text-xs font-semibold px-3 py-1.5 rounded-lg transition">
                            ✅ Recuperó (7.0)
                        </button>
                        <button type="submit" name="resolucion" value="NO_RECUPERO"
                            onclick="return confirm('¿Confirmar que no recuperó? La nota quedará en {{ number_format($m->NOTA, 1) }}')"
                            class="bg-red-600 hover:bg-red-700 text-white text-xs font-semibold px-3 py-1.5 rounded-lg transition">
                            ❌ No recuperó
                        </button>
                        <button type="submit" name="resolucion" value="NO_ASISTIO"
                            onclick="return confirm('¿Confirmar que {{ $m->NOMBRE1 }} {{ $m->APELLIDO1 }} no asistió a la recuperación? La nota quedará en {{ number_format($m->NOTA, 1) }}')"
                            class="bg-orange-500 hover:bg-orange-600 text-white text-xs font-semibold px-3 py-1.5 rounded-lg transition">
                            🚫 No asistió
                        </button>
                    </div>

                    {{-- Nota intermedia --}}
                    <div class="flex items-end gap-2" id="form-intermedio-{{ $m->CODIGO_ALUM }}">
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">
                                Nota intermedia ({{ number_format($m->NOTA, 1) }} &lt; x ≤ 7.0)
                            </label>
                            <input type="number"
                                name="nota_recuperacion"
                                id="nota-intermedia-{{ $m->CODIGO_ALUM }}"
                                min="{{ $m->NOTA + 0.1 }}" max="7" step="0.1"
                                placeholder="{{ $m->nota_intermedia }}"
                                class="w-24 border border-gray-300 rounded-lg px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                        </div>
                        <button type="submit" name="resolucion" value="INTERMEDIO"
                            onclick="return validarIntermedia(this, {{ $m->NOTA }}, '{{ $m->NOMBRE1 }} {{ $m->APELLIDO1 }}')"
                            class="bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold px-3 py-1.5 rounded-lg transition">
                            💙 Guardar intermedia
                        </button>
                    </div>
                </form>
                @endif
            </div>
            @endforeach
            @endforeach
        </div>
        @endif
    @else
        <div class="bg-gray-50 border border-gray-200 text-gray-500 rounded-xl p-6 text-center text-sm">
            Selecciona un período, materia y curso para ver los derroteros.
        </div>
    @endif

@endsection

@push('scripts')
<script>
    const mapaMaterias = @json($mapaMateriasCursos);
    const selMateria   = document.getElementById('sel-materia');
    const selCurso     = document.getElementById('sel-curso');
    const selPeriodo   = document.getElementById('sel-periodo');
    const selOrden     = document.getElementById('sel-orden');
    const form         = document.getElementById('form-filtros');

    function actualizarCursos() {
        const mat = selMateria.value;
        selCurso.innerHTML = '<option value="">— Selecciona un curso —</option>';
        if (!mat || !mapaMaterias[mat]) { selCurso.disabled = true; return; }
        mapaMaterias[mat].forEach(c => {
            const opt = document.createElement('option');
            opt.value = c; opt.textContent = c;
            selCurso.appendChild(opt);
        });
        selCurso.disabled = false;
    }

    selPeriodo.addEventListener('change', () => form.submit());
    selMateria.addEventListener('change', () => { actualizarCursos(); selCurso.value = ''; form.submit(); });
    selCurso.addEventListener('change',   () => { if (selCurso.value) form.submit(); });
    selOrden.addEventListener('change',   () => form.submit());

    function validarIntermedia(btn, notaOriginal, nombre) {
        const form   = btn.closest('form');
        const input  = form.querySelector('input[name="nota_recuperacion"]');
        const valor  = parseFloat(input.value);
        if (isNaN(valor) || valor <= notaOriginal || valor > 7) {
            alert(`La nota debe ser mayor a ${notaOriginal} y no mayor a 7.0`);
            return false;
        }
        return confirm(`¿Guardar nota intermedia ${valor} para ${nombre}?`);
    }
</script>
@endpush
