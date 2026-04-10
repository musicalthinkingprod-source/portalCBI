@extends('layouts.app-sidebar')

@section('header', 'Salvavidas')

@section('slot')

    @if(session('success'))
        <div class="mb-4 p-3 bg-green-100 text-green-800 rounded-xl text-sm">✅ {{ session('success') }}</div>
    @endif
    @if($errors->has('fechas'))
        <div class="mb-4 p-3 bg-red-100 text-red-700 rounded-xl text-sm">🔒 {{ $errors->first('fechas') }}</div>
    @endif

    {{-- Filtros --}}
    <div class="bg-white rounded-xl shadow p-5 mb-6">
        <form method="GET" action="{{ route('salvavidas.index') }}" id="form-filtros">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-end">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Período</label>
                    <select name="periodo" id="sel-periodo" data-remember="salvavidas_periodo"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @foreach([1,2,3,4] as $p)
                            <option value="{{ $p }}" {{ $periodoSelec == $p ? 'selected' : '' }}>
                                Período {{ $p }}
                                @if(!in_array($p, $periodosAbiertos)) 🔒 @endif
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Materia</label>
                    <select name="materia" id="sel-materia" data-remember="salvavidas_materia"
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
                    <select name="curso" id="sel-curso" data-remember="salvavidas_curso"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                        {{ !$matSelec ? 'disabled' : '' }}>
                        <option value="">— Selecciona un curso —</option>
                        @foreach($cursosDisponibles as $c)
                            <option value="{{ $c->CURSO }}" {{ $cursoSelec == $c->CURSO ? 'selected' : '' }}>{{ $c->CURSO }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </form>
    </div>

    @if($matSelec && $cursoSelec)
    @php $periodoAbierto = in_array($periodoSelec, $periodosAbiertos); @endphp

    @if(!$periodoAbierto)
        <div class="mb-4 p-3 bg-yellow-50 border border-yellow-200 text-yellow-700 rounded-xl text-sm">
            🔒 El período {{ $periodoSelec }} de salvavidas no está abierto. Puedes consultar los registros pero no modificarlos.
        </div>
    @endif

    @if($estudiantes->isEmpty())
        <div class="bg-yellow-50 border border-yellow-200 text-yellow-700 rounded-xl p-4 text-sm">
            No hay estudiantes matriculados en el curso <strong>{{ $cursoSelec }}</strong>.
        </div>
    @else
    <form method="POST" action="{{ route('salvavidas.guardar') }}">
        @csrf
        <input type="hidden" name="CODIGO_MAT" value="{{ $matSelec }}">
        <input type="hidden" name="curso" value="{{ $cursoSelec }}">
        <input type="hidden" name="periodo" value="{{ $periodoSelec }}">

        <div class="bg-white rounded-xl shadow overflow-hidden">
            <div class="px-5 py-3 bg-orange-700 text-white flex items-center justify-between">
                <div>
                    <h3 class="font-bold text-sm uppercase tracking-wide">🏊 {{ $materiaNombre }} — Curso {{ $cursoSelec }}</h3>
                    <p class="text-orange-200 text-xs mt-0.5">
                        Período {{ $periodoSelec }} · {{ $anio }} ·
                        <strong>{{ count($enSalvavidas) }}</strong> en salvavidas de {{ $estudiantes->count() }} estudiantes
                    </p>
                </div>
                @if($periodoAbierto)
                <button type="submit"
                    class="bg-white text-orange-700 hover:bg-orange-50 font-semibold text-xs px-4 py-1.5 rounded-lg transition">
                    💾 Guardar
                </button>
                @endif
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                        <tr>
                            <th class="px-4 py-3 text-left">Estudiante</th>
                            <th class="px-4 py-3 text-center w-32">En salvavidas</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($estudiantes as $est)
                        @php $marcado = in_array($est->CODIGO, $enSalvavidas); @endphp
                        <tr class="hover:bg-gray-50 {{ $marcado ? 'bg-orange-50' : '' }}">
                            <td class="px-4 py-3 font-medium">
                                {{ $est->APELLIDO1 }} {{ $est->APELLIDO2 }} {{ $est->NOMBRE1 }} {{ $est->NOMBRE2 }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($periodoAbierto)
                                    <input type="checkbox"
                                        name="salvavidas[]"
                                        value="{{ $est->CODIGO }}"
                                        {{ $marcado ? 'checked' : '' }}
                                        class="w-5 h-5 accent-orange-600 cursor-pointer">
                                @else
                                    @if($marcado)
                                        <span class="text-orange-600 font-semibold text-xs bg-orange-100 px-2 py-0.5 rounded-full">🏊 Sí</span>
                                    @else
                                        <span class="text-gray-400 text-xs">—</span>
                                    @endif
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($periodoAbierto)
            <div class="px-5 py-3 border-t border-gray-100 flex justify-end">
                <button type="submit"
                    class="bg-orange-700 hover:bg-orange-600 text-white font-semibold text-sm px-6 py-2 rounded-lg transition">
                    💾 Guardar salvavidas
                </button>
            </div>
            @endif
        </div>
    </form>
    @endif

    @elseif($matSelec && !$cursoSelec)
        <div class="bg-blue-50 border border-blue-200 text-blue-700 rounded-xl p-4 text-sm">
            Selecciona un curso para ver los estudiantes.
        </div>
    @else
        <div class="bg-gray-50 border border-gray-200 text-gray-500 rounded-xl p-6 text-center text-sm">
            Selecciona un período, materia y curso para registrar salvavidas.
        </div>
    @endif

@endsection

@push('scripts')
<script>
    const mapaMaterias = @json($mapaMateriasCursos);
    const selMateria   = document.getElementById('sel-materia');
    const selCurso     = document.getElementById('sel-curso');
    const selPeriodo   = document.getElementById('sel-periodo');
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
</script>
@endpush
