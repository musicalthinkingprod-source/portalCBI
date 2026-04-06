@extends('layouts.app-sidebar')

@section('header', 'Gestión de Asignaciones')

@section('slot')

@if(session('success_mover'))
    <div class="mb-4 p-3 bg-green-100 text-green-800 rounded-xl text-sm">✅ {{ session('success_mover') }}</div>
@endif
@if(session('success_mover_una'))
    <div class="mb-4 p-3 bg-green-100 text-green-800 rounded-xl text-sm">✅ {{ session('success_mover_una') }}</div>
@endif
@if($errors->has('mover') || $errors->has('mover_una'))
    <div class="mb-4 p-3 bg-red-100 text-red-700 rounded-xl text-sm">
        ⚠️ {{ $errors->first('mover') ?? $errors->first('mover_una') }}
    </div>
@endif

{{-- ═══════════════════════════════════════════════════════
     SECCIÓN 1 — MOVER ASIGNACIONES INDIVIDUALES
═══════════════════════════════════════════════════════ --}}
<div class="bg-white rounded-xl shadow overflow-hidden mb-8">
    <div class="px-5 py-3 bg-blue-800 text-white">
        <h3 class="font-bold text-sm uppercase tracking-wide">Mover Asignaciones Individuales</h3>
        <p class="text-blue-300 text-xs mt-0.5">Selecciona un docente para ver y mover cada asignación por separado.</p>
    </div>

    <div class="p-5 border-b border-gray-100 bg-gray-50">
        <form method="GET" action="{{ route('admin.asignaciones') }}#asig-individual">
            <div class="flex gap-3 items-end">
                <div class="flex-1">
                    <label class="block text-xs text-gray-500 mb-1">Selecciona un docente</label>
                    <select name="ver_asig"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">— Seleccionar docente —</option>
                        @foreach($docentesConAsig as $d)
                        <option value="{{ $d->CODIGO_DOC }}" {{ $verAsigDoc == $d->CODIGO_DOC ? 'selected' : '' }}>
                            {{ $d->NOMBRE_DOC }} ({{ $d->CODIGO_DOC }}) — {{ $d->total_asig }} asignación(es)
                        </option>
                        @endforeach
                    </select>
                </div>
                <button type="submit"
                    class="bg-blue-800 hover:bg-blue-700 text-white font-semibold px-5 py-2 rounded-lg text-sm transition">
                    Ver asignaciones
                </button>
                @if($verAsigDoc)
                <a href="{{ route('admin.asignaciones') }}#asig-individual"
                    class="bg-gray-100 hover:bg-gray-200 text-gray-600 font-semibold px-4 py-2 rounded-lg text-sm transition">
                    Limpiar
                </a>
                @endif
            </div>
        </form>
    </div>

    <div id="asig-individual">
        @if($verAsigDoc && $asigIndividual->isEmpty())
            <div class="p-5 text-sm text-gray-400 text-center">Este docente no tiene asignaciones registradas.</div>

        @elseif($asigIndividual->isNotEmpty())

        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                <tr>
                    <th class="px-4 py-3 text-left">Materia</th>
                    <th class="px-4 py-3 text-center w-24">Curso</th>
                    <th class="px-4 py-3 text-left">Mover a docente</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($asigIndividual as $asig)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-2 font-medium">{{ $asig->NOMBRE_MAT }}</td>
                    <td class="px-4 py-2 text-center">{{ $asig->CURSO }}</td>
                    <td class="px-4 py-2">
                        <form method="POST" action="{{ route('admin.asignaciones.mover_una') }}"
                              class="flex gap-2 items-center"
                              onsubmit="return confirm('¿Mover esta asignación?')">
                            @csrf
                            <input type="hidden" name="origen"     value="{{ $asig->CODIGO_DOC }}">
                            <input type="hidden" name="CODIGO_MAT" value="{{ $asig->CODIGO_MAT }}">
                            <input type="hidden" name="CURSO"      value="{{ $asig->CURSO }}">
                            <select name="destino" required
                                class="flex-1 border border-gray-300 rounded-lg px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">— Seleccionar destino —</option>
                                @foreach($docentesActivos as $dest)
                                    @if($dest->CODIGO_DOC !== $asig->CODIGO_DOC)
                                    <option value="{{ $dest->CODIGO_DOC }}">
                                        {{ $dest->NOMBRE_DOC }} ({{ $dest->CODIGO_DOC }})
                                    </option>
                                    @endif
                                @endforeach
                            </select>
                            <button type="submit"
                                class="bg-orange-600 hover:bg-orange-500 text-white text-xs font-semibold px-3 py-1.5 rounded-lg transition whitespace-nowrap">
                                Mover →
                            </button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        @else
            <div class="p-5 text-sm text-gray-400 text-center">
                Selecciona un docente arriba para ver sus asignaciones.
            </div>
        @endif
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════
     SECCIÓN 2 — MOVER ASIGNACIONES EN BLOQUE
═══════════════════════════════════════════════════════ --}}
<div class="bg-white rounded-xl shadow overflow-hidden mb-8">
    <div class="px-5 py-3 bg-blue-800 text-white">
        <h3 class="font-bold text-sm uppercase tracking-wide">Mover Todas las Asignaciones (en bloque)</h3>
        <p class="text-blue-300 text-xs mt-0.5">Transfiere todas las asignaciones (materias/cursos) de un docente a otro.</p>
    </div>
    <div class="p-5">
        <form method="POST" action="{{ route('admin.asignaciones.mover') }}"
              onsubmit="return confirmarMover(this)">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">
                        Docente origen <span class="text-red-500">*</span>
                    </label>
                    <select name="origen" id="sel-origen" required
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">— Selecciona el docente con las asignaciones actuales —</option>
                        @foreach($docentesConAsig as $d)
                        <option value="{{ $d->CODIGO_DOC }}">
                            {{ $d->NOMBRE_DOC }} ({{ $d->CODIGO_DOC }}) — {{ $d->total_asig }} asignación(es)
                        </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">
                        Docente destino <span class="text-red-500">*</span>
                    </label>
                    <select name="destino" id="sel-destino" required
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">— Selecciona el docente que recibirá las asignaciones —</option>
                        @foreach($docentesActivos as $d)
                        <option value="{{ $d->CODIGO_DOC }}">
                            {{ $d->NOMBRE_DOC }} ({{ $d->CODIGO_DOC }})
                        </option>
                        @endforeach
                    </select>
                </div>
            </div>

            @if($errors->has('destino'))
            <div class="mb-3 p-2 bg-red-50 text-red-600 rounded-lg text-xs">{{ $errors->first('destino') }}</div>
            @endif

            <div class="flex items-center gap-3">
                <button type="submit"
                    class="bg-orange-600 hover:bg-orange-500 text-white font-semibold px-6 py-2 rounded-lg text-sm transition">
                    Mover asignaciones →
                </button>
                <p class="text-xs text-gray-400">Solo se mueven las asignaciones. Las notas ya registradas quedan asociadas al docente que las ingresó.</p>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
    function confirmarMover(form) {
        const origen  = form.querySelector('[name=origen]');
        const destino = form.querySelector('[name=destino]');
        if (!origen.value || !destino.value) return true;
        const textoOrigen  = origen.options[origen.selectedIndex].text;
        const textoDestino = destino.options[destino.selectedIndex].text;
        return confirm(
            `¿Confirmas mover TODAS las asignaciones de:\n\n` +
            `  Origen:  ${textoOrigen}\n` +
            `  Destino: ${textoDestino}\n\n` +
            `Esta acción no se puede deshacer fácilmente.`
        );
    }
</script>
@endpush
