@extends('layouts.app-sidebar')

@section('header', 'English Acquisition')

@section('slot')

    @if(session('success_acq'))
        <div class="mb-4 p-3 bg-green-100 text-green-800 rounded-xl text-sm">
            ✅ {{ session('success_acq') }}
        </div>
    @endif

    @if(session('error_acq'))
        <div class="mb-4 p-3 bg-red-100 text-red-800 rounded-xl text-sm">
            ⚠️ {{ session('error_acq') }}
        </div>
    @endif

    {{-- Filtros --}}
    <div class="bg-white rounded-xl shadow p-5 mb-6">
        <form method="GET" action="{{ route('english-acq.docente') }}" id="form-filtros">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 items-end">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Período</label>
                    <select name="periodo" id="sel-periodo" data-remember="english_periodo"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @foreach([1,2,3,4] as $p)
                            <option value="{{ $p }}" {{ $periodoSelec == $p ? 'selected' : '' }}>Período {{ $p }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Curso</label>
                    <select name="curso" id="sel-curso" data-remember="english_curso"
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

    {{-- Banner de ventana de registro --}}
    @if($cursoSelec)
        @if($registroActivo)
            <div class="mb-4 p-3 bg-green-50 border border-green-200 text-green-800 rounded-xl text-sm flex items-center gap-2">
                <span class="font-semibold">Registro abierto</span> — Período {{ $periodoSelec }} está activo según el calendario académico.
                @if($esAdmin && $periodoCalendario !== $periodoSelec)
                    <span class="ml-auto text-yellow-600 text-xs">(Admin — período activo real: {{ $periodoCalendario ?? '—' }})</span>
                @endif
            </div>
        @else
            <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-800 rounded-xl text-sm">
                <span class="font-semibold">Registro cerrado</span> — El período {{ $periodoSelec }} no corresponde al período activo del calendario académico.
                @if($periodoCalendario)
                    Período activo actual: <strong>{{ $periodoCalendario }}</strong>.
                @else
                    No hay ningún período activo en este momento.
                @endif
            </div>
        @endif
    @endif

    {{-- Tabla de estudiantes --}}
    @if($cursoSelec && $estudiantes->isNotEmpty())
    <div class="bg-white rounded-xl shadow overflow-hidden">
        <div class="px-5 py-3 bg-blue-800 text-white">
            <h3 class="font-bold text-sm uppercase tracking-wide">Curso {{ $cursoSelec }} — Período {{ $periodoSelec }} — {{ $anio }}</h3>
            <p class="text-blue-300 text-xs mt-0.5">{{ $estudiantes->count() }} estudiantes · Nota base: 10 · Descuento: -0.25 por registro</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                    <tr>
                        <th class="px-4 py-3 text-left">Estudiante</th>
                        <th class="px-4 py-3 text-center w-32">Nota actual</th>
                        <th class="px-4 py-3 text-center w-48">Acción</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($estudiantes as $est)
                    @php
                        $nota = $notasMap[$est->CODIGO] ?? 10;
                    @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium">
                            {{ $est->APELLIDO1 }} {{ $est->APELLIDO2 }} {{ $est->NOMBRE1 }} {{ $est->NOMBRE2 }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="text-lg font-bold {{ $nota < 6 ? 'text-red-600' : ($nota < 8 ? 'text-yellow-600' : 'text-green-700') }}">
                                {{ number_format($nota, 2) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($registroActivo)
                            <form method="POST" action="{{ route('english-acq.registrar') }}">
                                @csrf
                                <input type="hidden" name="CODIGO_ALUM" value="{{ $est->CODIGO }}">
                                <input type="hidden" name="PERIODO" value="{{ $periodoSelec }}">
                                <input type="hidden" name="curso" value="{{ $cursoSelec }}">
                                <input type="hidden" name="periodo" value="{{ $periodoSelec }}">
                                <button type="submit"
                                    onclick="return confirm('¿Registrar que {{ $est->NOMBRE1 }} {{ $est->APELLIDO1 }} habló en español?')"
                                    class="bg-red-100 hover:bg-red-200 text-red-700 font-semibold text-xs px-3 py-1.5 rounded-lg transition">
                                    🗣️ Habló en español
                                </button>
                            </form>
                            @else
                            <span class="text-gray-300 text-xs">Cerrado</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @elseif($cursoSelec && $estudiantes->isEmpty())
        <div class="bg-yellow-50 border border-yellow-200 text-yellow-700 rounded-xl p-4 text-sm">
            No hay estudiantes matriculados en el curso <strong>{{ $cursoSelec }}</strong>.
        </div>
    @else
        <div class="bg-gray-50 border border-gray-200 text-gray-500 rounded-xl p-6 text-center text-sm">
            Selecciona un período y un curso para ver los estudiantes.
        </div>
    @endif

@endsection

@push('scripts')
<script>
    document.getElementById('sel-periodo').addEventListener('change', () => {
        document.getElementById('form-filtros').submit();
    });
    document.getElementById('sel-curso').addEventListener('change', () => {
        document.getElementById('form-filtros').submit();
    });
</script>
@endpush
