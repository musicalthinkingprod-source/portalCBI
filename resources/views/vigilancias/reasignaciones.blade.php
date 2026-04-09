@extends('layouts.app-sidebar')

@section('header', 'Reasignación de Vigilancias')

@section('slot')
<div class="max-w-5xl mx-auto space-y-8">

    {{-- Selector de año --}}
    <form method="GET" action="{{ route('vigilancias.reasignaciones') }}" class="flex items-center gap-3">
        <label class="text-sm font-medium text-gray-600">Año:</label>
        <select name="anio" onchange="this.form.submit()"
            class="rounded-lg border-gray-300 text-sm shadow-sm py-1.5 px-3">
            @for($y = date('Y'); $y >= date('Y') - 2; $y--)
                <option value="{{ $y }}" {{ $anio == $y ? 'selected' : '' }}>{{ $y }}</option>
            @endfor
        </select>
        @if($verDoc)
            <input type="hidden" name="ver_asig" value="{{ $verDoc }}">
        @endif
    </form>

    {{-- Alertas --}}
    @foreach(['success_reasig_una', 'success_reasig_bloque'] as $key)
        @if(session($key))
            <div class="p-3 bg-green-100 text-green-800 rounded-xl text-sm">✅ {{ session($key) }}</div>
        @endif
    @endforeach
    @if($errors->hasAny(['reasig_una', 'reasig_bloque', 'destino']))
        <div class="p-3 bg-red-100 text-red-700 rounded-xl text-sm">
            ⚠️ {{ $errors->first('reasig_una') ?? $errors->first('reasig_bloque') ?? $errors->first('destino') }}
        </div>
    @endif

    {{-- ══════════════════════════════════════════════════════
         SECCIÓN 1 — REASIGNAR SLOTS INDIVIDUALES
    ══════════════════════════════════════════════════════ --}}
    <div class="bg-white rounded-xl shadow overflow-hidden">
        <div class="px-5 py-3 bg-blue-800 text-white">
            <h3 class="font-bold text-sm uppercase tracking-wide">Reasignar posiciones individuales</h3>
            <p class="text-blue-300 text-xs mt-0.5">Selecciona un docente para ver sus slots y moverlos uno por uno.</p>
        </div>

        <div class="p-5 border-b border-gray-100 bg-gray-50">
            <form method="GET" action="{{ route('vigilancias.reasignaciones') }}#slots-individuales">
                <input type="hidden" name="anio" value="{{ $anio }}">
                <div class="flex gap-3 items-end flex-wrap">
                    <div class="flex-1 min-w-[220px]">
                        <label class="block text-xs text-gray-500 mb-1">Selecciona un docente</label>
                        <select name="ver_asig"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                            <option value="">— Seleccionar docente —</option>
                            @foreach($docentesConAsig as $d)
                            <option value="{{ $d->CODIGO_DOC }}" {{ $verDoc == $d->CODIGO_DOC ? 'selected' : '' }}>
                                {{ $d->NOMBRE_DOC }} ({{ $d->CODIGO_DOC }}) — {{ $d->total_slots }} slot(s)
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit"
                        class="bg-blue-800 hover:bg-blue-700 text-white font-semibold px-5 py-2 rounded-lg text-sm transition">
                        Ver slots
                    </button>
                    @if($verDoc)
                    <a href="{{ route('vigilancias.reasignaciones', ['anio' => $anio]) }}"
                        class="bg-gray-100 hover:bg-gray-200 text-gray-600 font-semibold px-4 py-2 rounded-lg text-sm transition">
                        Limpiar
                    </a>
                    @endif
                </div>
            </form>
        </div>

        <div id="slots-individuales">
            @if($verDoc && $slotsDoc->isEmpty())
                <div class="p-5 text-sm text-gray-400 text-center">Este docente no tiene vigilancias asignadas para {{ $anio }}.</div>

            @elseif($slotsDoc->isNotEmpty())
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                    <tr>
                        <th class="px-4 py-3 text-center w-24">Día</th>
                        <th class="px-4 py-3 text-center w-28">Descanso</th>
                        <th class="px-4 py-3 text-center w-24">Posición</th>
                        <th class="px-4 py-3 text-left">Reasignar a</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($slotsDoc as $slot)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2 text-center font-semibold text-gray-700">Día {{ $slot->DIA_CICLO }}</td>
                        <td class="px-4 py-2 text-center">
                            <span class="inline-block px-2 py-0.5 rounded-full text-xs font-bold
                                {{ $slot->DESCANSO == 1 ? 'bg-blue-100 text-blue-700' : 'bg-orange-100 text-orange-700' }}">
                                Descanso {{ $slot->DESCANSO }}
                            </span>
                        </td>
                        <td class="px-4 py-2 text-center font-black text-lg
                            {{ $slot->DESCANSO == 1 ? 'text-blue-700' : 'text-orange-600' }}">
                            {{ $slot->POSICION }}
                        </td>
                        <td class="px-4 py-2">
                            <form method="POST" action="{{ route('vigilancias.reasignar.una') }}"
                                class="flex gap-2 items-center"
                                onsubmit="return confirm('¿Reasignar este slot?')">
                                @csrf
                                <input type="hidden" name="origen"    value="{{ $verDoc }}">
                                <input type="hidden" name="DIA_CICLO" value="{{ $slot->DIA_CICLO }}">
                                <input type="hidden" name="DESCANSO"  value="{{ $slot->DESCANSO }}">
                                <input type="hidden" name="anio"      value="{{ $anio }}">
                                <select name="destino" required
                                    class="flex-1 border border-gray-300 rounded-lg px-2 py-1.5 text-sm focus:ring-2 focus:ring-blue-500">
                                    <option value="">— Seleccionar destino —</option>
                                    @foreach($docentesActivos as $dest)
                                        @if($dest->CODIGO_DOC !== $verDoc)
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
                    Selecciona un docente arriba para ver sus slots.
                </div>
            @endif
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════
         SECCIÓN 2 — MOVER / INTERCAMBIAR EN BLOQUE
    ══════════════════════════════════════════════════════ --}}
    <div class="bg-white rounded-xl shadow overflow-hidden">
        <div class="px-5 py-3 bg-blue-800 text-white">
            <h3 class="font-bold text-sm uppercase tracking-wide">Mover o intercambiar en bloque</h3>
            <p class="text-blue-300 text-xs mt-0.5">
                Transfiere todos los slots de un docente a otro. Si el destino ya tiene vigilancias, se intercambian completas.
            </p>
        </div>
        <div class="p-5">
            <form method="POST" action="{{ route('vigilancias.reasignar.bloque') }}"
                onsubmit="return confirmarBloque(this)">
                @csrf
                <input type="hidden" name="anio" value="{{ $anio }}">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">
                            Docente origen <span class="text-red-500">*</span>
                        </label>
                        <select name="origen" id="sel-origen" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                            <option value="">— Docente con vigilancias actuales —</option>
                            @foreach($docentesConAsig as $d)
                            <option value="{{ $d->CODIGO_DOC }}">
                                {{ $d->NOMBRE_DOC }} ({{ $d->CODIGO_DOC }}) — {{ $d->total_slots }} slot(s)
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">
                            Docente destino <span class="text-red-500">*</span>
                        </label>
                        <select name="destino" id="sel-destino" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                            <option value="">— Docente que recibirá las vigilancias —</option>
                            @foreach($docentesActivos as $d)
                            <option value="{{ $d->CODIGO_DOC }}">
                                {{ $d->NOMBRE_DOC }} ({{ $d->CODIGO_DOC }})
                            </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <button type="submit"
                        class="bg-orange-600 hover:bg-orange-500 text-white font-semibold px-6 py-2 rounded-lg text-sm transition">
                        Mover / Intercambiar →
                    </button>
                    <p class="text-xs text-gray-400">
                        Si el destino ya tiene vigilancias asignadas, se realiza un intercambio completo entre ambos docentes.
                    </p>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function confirmarBloque(form) {
    const origen  = form.querySelector('[name=origen]');
    const destino = form.querySelector('[name=destino]');
    if (!origen.value || !destino.value) return true;
    const textoOrigen  = origen.options[origen.selectedIndex].text;
    const textoDestino = destino.options[destino.selectedIndex].text;
    return confirm(
        `¿Confirmas mover / intercambiar TODAS las vigilancias?\n\n` +
        `  Origen:  ${textoOrigen}\n` +
        `  Destino: ${textoDestino}\n\n` +
        `Si el destino ya tiene vigilancias, se realizará un intercambio completo.`
    );
}
</script>
@endpush
