@extends('layouts.app-sidebar')

@section('header', 'Notas')

@section('slot')

    @if(auth()->user()->PROFILE === 'SuperAd')
    <div class="flex justify-end mb-4">
        <a href="{{ route('notas.reporte') }}"
            class="bg-blue-800 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition">
            📊 Informe de digitación
        </a>
    </div>
    @endif

    {{-- Filtros --}}
    <div class="bg-white rounded-xl shadow p-5 mb-6">
        <form method="GET" action="{{ route('notas.index') }}" id="form-filtros">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 items-end">

                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Materia</label>
                    <select name="materia" id="sel-materia" data-remember="notas_materia"
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
                    <select name="curso" id="sel-curso" data-remember="notas_curso"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                        {{ !$matSelec ? 'disabled' : '' }}>
                        <option value="">— Selecciona un curso —</option>
                        @foreach($cursosDisponibles as $c)
                            <option value="{{ $c->CURSO }}" {{ $cursoSelec == $c->CURSO ? 'selected' : '' }}>
                                {{ $c->CURSO }}
                            </option>
                        @endforeach
                    </select>
                </div>

            </div>
        </form>
    </div>

    {{-- Advertencia de período cerrado --}}
    @if($errors->has('fechas'))
        <div class="mb-4 p-3 bg-red-100 text-red-700 rounded-xl text-sm">🔒 {{ $errors->first('fechas') }}</div>
    @endif

    {{-- Mensaje éxito --}}
    @if(session('success'))
        <div class="mb-4 p-3 bg-green-100 text-green-800 rounded-xl text-sm">
            ✅ {{ session('success') }}
        </div>
    @endif

    {{-- Tabla de notas --}}
    @if($matSelec && $cursoSelec)

        @if($estudiantes->isEmpty())
            <div class="bg-yellow-50 border border-yellow-200 text-yellow-700 rounded-xl p-4 text-sm">
                No hay estudiantes matriculados en el curso <strong>{{ $cursoSelec }}</strong>.
            </div>
        @else

        <form method="POST" action="{{ route('notas.guardar') }}">
            @csrf
            <input type="hidden" name="CODIGO_MAT" value="{{ $matSelec }}">
            <input type="hidden" name="curso" value="{{ $cursoSelec }}">

            <div class="bg-white rounded-xl shadow overflow-hidden">
                <div class="px-5 py-3 bg-blue-800 text-white flex items-center justify-between">
                    <div>
                        <h3 class="font-bold text-sm uppercase tracking-wide">{{ $materiaNombre }}</h3>
                        <p class="text-blue-300 text-xs mt-0.5">Curso: {{ $cursoSelec }} — {{ $estudiantes->count() }} estudiantes</p>
                    </div>
                    <button type="submit"
                        class="bg-white text-blue-800 hover:bg-blue-50 font-semibold text-xs px-4 py-1.5 rounded-lg transition">
                        💾 Guardar notas
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                            <tr>
                                <th class="px-4 py-3 text-left w-16">Código</th>
                                <th class="px-4 py-3 text-left">Estudiante</th>
                                @foreach([1,2,3,4] as $ph)
                                <th class="px-4 py-3 text-center w-24">
                                    Período {{ $ph }}
                                    @if(!in_array($ph, $periodosAbiertos))
                                        <span class="block text-red-300 text-xs font-normal normal-case tracking-normal">🔒 cerrado</span>
                                    @endif
                                </th>
                                @endforeach
                                <th class="px-4 py-3 text-center w-20">Promedio</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($estudiantes as $est)
                            @php
                                $n1 = $notasMap[$est->CODIGO][1] ?? null;
                                $n2 = $notasMap[$est->CODIGO][2] ?? null;
                                $n3 = $notasMap[$est->CODIGO][3] ?? null;
                                $n4 = $notasMap[$est->CODIGO][4] ?? null;
                                $vals = array_filter([$n1,$n2,$n3,$n4], fn($v) => $v !== null);
                                $prom = count($vals) > 0 ? round(array_sum($vals) / count($vals), 1) : null;
                            @endphp
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-2 text-gray-500 text-xs">{{ $est->CODIGO }}</td>
                                <td class="px-4 py-2 font-medium">
                                    {{ $est->APELLIDO1 }} {{ $est->APELLIDO2 }} {{ $est->NOMBRE1 }} {{ $est->NOMBRE2 }}
                                </td>
                                @foreach([1,2,3,4] as $p)
                                @php
                                    $nVal    = $notasMap[$est->CODIGO][$p] ?? '';
                                    $abierto = in_array($p, $periodosAbiertos);
                                @endphp
                                <td class="px-2 py-2 text-center {{ !$abierto ? 'bg-gray-50' : '' }}">
                                    <input type="number"
                                        name="notas[{{ $est->CODIGO }}][{{ $p }}]"
                                        value="{{ $nVal }}"
                                        min="0" max="10" step="0.1"
                                        placeholder="—"
                                        {{ !$abierto ? 'disabled' : '' }}
                                        class="w-16 text-center border rounded-lg px-1 py-1 text-sm
                                            {{ !$abierto
                                                ? 'border-gray-200 bg-gray-100 text-gray-400 cursor-not-allowed'
                                                : 'border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-400' }}
                                            {{ $nVal !== '' && round((float)$nVal, 1) < 7 ? 'text-red-600 font-semibold' : ($nVal !== '' && $abierto ? 'text-green-700 font-semibold' : '') }}
                                            {{ $abierto ? 'nota-input' : '' }}">
                                </td>
                                @endforeach
                                <td class="px-4 py-2 text-center font-semibold prom-cell
                                    {{ $prom !== null && $prom < 7 ? 'text-red-600' : ($prom !== null ? 'text-green-700' : 'text-gray-400') }}">
                                    {{ $prom !== null ? $prom : '—' }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="px-5 py-3 border-t border-gray-100 flex justify-end">
                    <button type="submit"
                        class="bg-blue-800 hover:bg-blue-700 text-white font-semibold text-sm px-6 py-2 rounded-lg transition">
                        💾 Guardar notas
                    </button>
                </div>
            </div>
        </form>

        @endif

    @elseif($matSelec && !$cursoSelec)
        <div class="bg-blue-50 border border-blue-200 text-blue-700 rounded-xl p-4 text-sm">
            Selecciona un curso para ver los estudiantes.
        </div>
    @else
        <div class="bg-gray-50 border border-gray-200 text-gray-500 rounded-xl p-6 text-center text-sm">
            Selecciona una materia y un curso para cargar la tabla de notas.
        </div>
    @endif

@endsection

@push('scripts')
<script>
    // Mapa de materia → cursos disponibles
    const mapaMaterias = @json($mapaMateriasCursos);

    const selMateria = document.getElementById('sel-materia');
    const selCurso   = document.getElementById('sel-curso');
    const formFiltros = document.getElementById('form-filtros');

    function actualizarCursos() {
        const mat = selMateria.value;
        selCurso.innerHTML = '<option value="">— Selecciona un curso —</option>';

        if (!mat || !mapaMaterias[mat]) {
            selCurso.disabled = true;
            return;
        }

        mapaMaterias[mat].forEach(curso => {
            const opt = document.createElement('option');
            opt.value = curso;
            opt.textContent = curso;
            selCurso.appendChild(opt);
        });

        selCurso.disabled = false;
    }

    selMateria.addEventListener('change', () => {
        actualizarCursos();
        // Si cambia la materia, resetea curso y recarga
        selCurso.value = '';
        formFiltros.submit();
    });

    selCurso.addEventListener('change', () => {
        if (selCurso.value) formFiltros.submit();
    });

    // Restaurar selección guardada cuando la página carga sin parámetros en la URL
    @if(!$matSelec || !$cursoSelec)
    setTimeout(function () {
        if (!selMateria.value) return;
        actualizarCursos();
        const savedCurso = localStorage.getItem('remember_notas_curso');
        if (savedCurso) {
            const existe = Array.from(selCurso.options).some(o => o.value === savedCurso);
            if (existe) {
                selCurso.value = savedCurso;
                formFiltros.submit();
            }
        }
    }, 0);
    @endif

    // Recalcular promedio en tiempo real al editar notas
    document.querySelectorAll('.nota-input').forEach(input => {
        input.addEventListener('input', function () {
            const row = this.closest('tr');
            const inputs = row.querySelectorAll('.nota-input');
            const promCell = row.querySelector('.prom-cell');

            let sum = 0, count = 0;
            inputs.forEach(inp => {
                const v = parseFloat(inp.value);
                if (!isNaN(v)) { sum += v; count++; }

                // Color según aprobación
                if (inp.value !== '') {
                    const vr = Math.round(v * 10) / 10;
                    inp.classList.toggle('text-red-600', vr < 7);
                    inp.classList.toggle('font-semibold', true);
                    inp.classList.toggle('text-green-700', vr >= 7);
                } else {
                    inp.classList.remove('text-red-600','text-green-700','font-semibold');
                }
            });

            if (count > 0) {
                const prom = Math.round((sum / count) * 10) / 10;
                promCell.textContent = prom;
                promCell.className = 'px-4 py-2 text-center font-semibold prom-cell ' +
                    (prom < 7 ? 'text-red-600' : 'text-green-700');
            } else {
                promCell.textContent = '—';
                promCell.className = 'px-4 py-2 text-center font-semibold prom-cell text-gray-400';
            }
        });
    });
</script>
@endpush

