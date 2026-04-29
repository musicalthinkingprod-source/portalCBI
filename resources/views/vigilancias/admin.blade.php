@extends('layouts.app-sidebar')

@section('header', 'Gestión de Vigilancias')

@section('slot')
<div class="max-w-full space-y-8">

    {{-- Selector de año --}}
    <form method="GET" action="{{ route('vigilancias.admin') }}" class="flex items-center gap-3">
        <label class="text-sm font-medium text-gray-600">Año:</label>
        <select name="anio" onchange="this.form.submit()"
            class="rounded-lg border-gray-300 text-sm shadow-sm focus:ring-blue-500 focus:border-blue-500 py-1.5 px-3">
            @for($y = date('Y'); $y >= date('Y') - 2; $y--)
                <option value="{{ $y }}" {{ $anio == $y ? 'selected' : '' }}>{{ $y }}</option>
            @endfor
        </select>
        @if($verDoc)<input type="hidden" name="ver_asig" value="{{ $verDoc }}">@endif
    </form>

    {{-- Alertas globales --}}
    @foreach(['success_reasig_una','success_reasig_bloque','success'] as $k)
        @if(session($k))
            <div class="p-3 bg-green-100 text-green-800 rounded-xl text-sm">✅ {{ session($k) }}</div>
        @endif
    @endforeach
    @if($errors->any())
        <div class="p-3 bg-red-100 text-red-700 rounded-xl text-sm">
            ⚠️ {{ $errors->first() }}
        </div>
    @endif

    {{-- ============================================================
         SECCIÓN 1: REASIGNAR POSICIONES INDIVIDUALES
    ============================================================ --}}
    <div class="bg-white rounded-xl shadow p-5">
        <h2 class="text-base font-semibold text-blue-900 mb-1">Reasignar posiciones individuales</h2>
        <p class="text-xs text-gray-400 mb-4">Selecciona un docente para ver sus slots y moverlos uno por uno.</p>

        <div class="mb-4">
            <form method="GET" action="{{ route('vigilancias.admin') }}#reasig-individual">
                <input type="hidden" name="anio" value="{{ $anio }}">
                <div class="flex gap-3 items-end flex-wrap">
                    <div class="flex-1 min-w-[220px]">
                        <label class="block text-xs text-gray-500 mb-1">Selecciona un docente</label>
                        <select name="ver_asig"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                            <option value="">— Seleccionar docente —</option>
                            @foreach($docentesConAsig as $d)
                            <option value="{{ $d->CODIGO_EMP }}" {{ ($verDoc ?? '') == $d->CODIGO_EMP ? 'selected' : '' }}>
                                {{ $d->NOMBRE_DOC }} ({{ $d->CODIGO_EMP }}) — {{ $d->total_slots }} slot(s)
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit"
                        class="bg-blue-800 hover:bg-blue-700 text-white font-semibold px-5 py-2 rounded-lg text-sm transition">
                        Ver slots
                    </button>
                    @if($verDoc ?? false)
                    <a href="{{ route('vigilancias.admin', ['anio' => $anio]) }}"
                        class="bg-gray-100 hover:bg-gray-200 text-gray-600 font-semibold px-4 py-2 rounded-lg text-sm transition">
                        Limpiar
                    </a>
                    @endif
                </div>
            </form>
        </div>

        <div id="reasig-individual">
            @if(($verDoc ?? false) && $slotsDoc->isEmpty())
                <p class="text-sm text-gray-400 italic">Este docente no tiene vigilancias asignadas para {{ $anio }}.</p>
            @elseif($slotsDoc->isNotEmpty())
            <div class="overflow-x-auto rounded-lg border border-gray-200">
                <table class="min-w-full text-sm">
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
                                        <option value="">— Destino —</option>
                                        @foreach($docentesDestino as $dest)
                                        <option value="{{ $dest->CODIGO_EMP }}">{{ $dest->NOMBRE_DOC }}</option>
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
            </div>
            @else
                <p class="text-sm text-gray-400 italic">Selecciona un docente arriba para ver sus slots.</p>
            @endif
        </div>
    </div>

    {{-- ============================================================
         SECCIÓN 2: MOVER / INTERCAMBIAR EN BLOQUE
    ============================================================ --}}
    <div class="bg-white rounded-xl shadow p-5">
        <h2 class="text-base font-semibold text-blue-900 mb-1">Mover o intercambiar en bloque</h2>
        <p class="text-xs text-gray-400 mb-4">
            Transfiere todos los slots de un docente a otro. Si el destino ya tiene vigilancias, se intercambian completas.
        </p>
        <form method="POST" action="{{ route('vigilancias.reasignar.bloque') }}"
            onsubmit="return confirmarBloque(this)">
            @csrf
            <input type="hidden" name="anio" value="{{ $anio }}">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">
                        Docente origen <span class="text-red-500">*</span>
                    </label>
                    <select name="origen" required
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                        <option value="">— Docente con vigilancias actuales —</option>
                        @foreach($docentesConAsig as $d)
                        <option value="{{ $d->CODIGO_EMP }}">
                            {{ $d->NOMBRE_DOC }} ({{ $d->CODIGO_EMP }}) — {{ $d->total_slots }} slot(s)
                        </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">
                        Docente destino <span class="text-red-500">*</span>
                    </label>
                    <select name="destino" required
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                        <option value="">— Docente que recibirá las vigilancias —</option>
                        @foreach($docentesDestino as $d)
                        <option value="{{ $d->CODIGO_EMP }}">{{ $d->NOMBRE_DOC }} ({{ $d->CODIGO_EMP }})</option>
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
                    Si el destino ya tiene vigilancias, se realiza un intercambio completo entre ambos.
                </p>
            </div>
        </form>
    </div>

    {{-- ============================================================
         SECCIÓN 3: MAPA DE POSICIONES
    ============================================================ --}}
    <div class="bg-white rounded-xl shadow p-5">
        <h2 class="text-base font-semibold text-blue-900 mb-3">Mapa de posiciones {{ $anio }}</h2>
        <div class="rounded-xl overflow-hidden border border-gray-200 isolate" style="height: 500px;">
            <div id="mapa-admin" class="w-full h-full"></div>
        </div>
        <div class="mt-3 flex flex-wrap gap-4 text-xs text-gray-500">
            <span class="flex items-center gap-1">
                <span class="inline-block w-4 h-4 rounded-full bg-blue-600 border-2 border-white shadow"></span> Descanso 1
            </span>
            <span class="flex items-center gap-1">
                <span class="inline-block w-4 h-4 rounded-full bg-orange-500 border-2 border-white shadow"></span> Descanso 2
            </span>
            <span class="flex items-center gap-1">
                <span class="inline-block w-4 h-4 rounded-full bg-gray-400 border-2 border-white shadow"></span> Sin asignar
            </span>
        </div>
    </div>

    {{-- ============================================================
         SECCIÓN 3b: AGREGAR DOCENTE A LA LISTA
    ============================================================ --}}
    @if($docentes->where('ESTADO', 'ACTIVO')->count() < DB::table('CODIGOS_DOC')->where('ESTADO', 'ACTIVO')->count())
    <div class="bg-white rounded-xl shadow p-5">
        <h2 class="text-base font-semibold text-blue-900 mb-1">Agregar docente a la lista</h2>
        <p class="text-xs text-gray-400 mb-4">Docentes activos que aún no tienen vigilancias asignadas para {{ $anio }}.</p>
        @if(session('success_agregar') || $errors->has('agregar_doc'))
            @if(session('success_agregar'))
                <div class="mb-3 p-3 bg-green-100 text-green-800 rounded-xl text-sm">✅ {{ session('success_agregar') }}</div>
            @endif
            @if($errors->has('agregar_doc'))
                <div class="mb-3 p-3 bg-red-100 text-red-700 rounded-xl text-sm">⚠️ {{ $errors->first('agregar_doc') }}</div>
            @endif
        @endif
        @php
            $codigosConAsig = $docentes->pluck('CODIGO_EMP')->toArray();
            $sinAsignar = DB::table('CODIGOS_DOC')
                ->where('ESTADO', 'ACTIVO')
                ->whereNotIn('CODIGO_EMP', $codigosConAsig)
                ->orderBy('NOMBRE_DOC')->get();
        @endphp
        @if($sinAsignar->isNotEmpty())
        <form method="POST" action="{{ route('vigilancias.docente.agregar') }}" class="flex gap-3 items-end flex-wrap">
            @csrf
            <input type="hidden" name="anio" value="{{ $anio }}">
            <div class="flex-1 min-w-[250px]">
                <label class="block text-xs text-gray-500 mb-1">Docente</label>
                <select name="CODIGO_EMP" required
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="">— Seleccionar docente —</option>
                    @foreach($sinAsignar as $d)
                    <option value="{{ $d->CODIGO_EMP }}">{{ $d->NOMBRE_DOC }} ({{ $d->CODIGO_EMP }})</option>
                    @endforeach
                </select>
            </div>
            <button type="submit"
                class="bg-green-700 hover:bg-green-600 text-white font-semibold px-5 py-2 rounded-lg text-sm transition">
                + Agregar
            </button>
        </form>
        @else
            <p class="text-sm text-gray-400 italic">Todos los docentes activos ya están en la lista.</p>
        @endif
    </div>
    @endif

    {{-- ============================================================
         SECCIÓN 4: TABLA DE ASIGNACIONES
    ============================================================ --}}
    <div class="bg-white rounded-xl shadow p-5">
        <h2 class="text-base font-semibold text-blue-900 mb-1">Tabla de asignaciones {{ $anio }}</h2>
        <p class="text-xs text-gray-400 mb-4">
            Escribe la posición de cada docente (ej: <strong>20A</strong>, <strong>5B</strong>). Deja en blanco para sin asignación.
        </p>

        @if($docentes->isEmpty())
            <p class="text-sm text-gray-400 italic">No hay docentes con vigilancias asignadas.</p>
        @else
        <form method="POST" action="{{ route('vigilancias.asignaciones.guardar') }}">
            @csrf
            <input type="hidden" name="anio" value="{{ $anio }}">
            <div class="overflow-x-auto rounded-lg border border-gray-200">
                <table class="min-w-full text-sm">
                    <thead class="bg-blue-900 text-white">
                        <tr>
                            <th class="px-3 py-2 text-left font-medium sticky left-0 bg-blue-900 z-10 min-w-[160px]">Docente</th>
                            @for($dia = 1; $dia <= 6; $dia++)
                                <th class="px-2 py-2 text-center font-medium text-xs" colspan="2">Día {{ $dia }}</th>
                            @endfor
                        </tr>
                        <tr class="bg-blue-800 text-blue-100 text-xs">
                            <th class="px-3 py-1 sticky left-0 bg-blue-800 z-10"></th>
                            @for($dia = 1; $dia <= 6; $dia++)
                                <th class="px-2 py-1 text-center font-normal">D1</th>
                                <th class="px-2 py-1 text-center font-normal border-r border-blue-700">D2</th>
                            @endfor
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($docentes as $doc)
                            @php $inactivo = $doc->ESTADO !== 'ACTIVO'; @endphp
                            <tr class="{{ $inactivo ? 'bg-red-50 hover:bg-red-100' : 'hover:bg-gray-50' }}">
                                <td class="px-3 py-2 font-medium sticky left-0 z-10 whitespace-nowrap border-r border-gray-200
                                    {{ $inactivo ? 'bg-red-50 text-red-700' : 'bg-white text-gray-700' }}">
                                    {{ $doc->NOMBRE_DOC }}
                                    @if($inactivo)
                                        <span class="ml-1 text-[10px] font-bold uppercase bg-red-200 text-red-700 px-1 py-0.5 rounded">
                                            {{ $doc->ESTADO }}
                                        </span>
                                    @endif
                                </td>
                                @for($dia = 1; $dia <= 6; $dia++)
                                    @foreach([1, 2] as $desc)
                                        @php $val = $matriz[$doc->CODIGO_EMP][$dia][$desc] ?? ''; @endphp
                                        <td class="px-1 py-1 {{ $desc === 2 ? 'border-r border-gray-200' : '' }}">
                                            <input type="text"
                                                name="asignaciones[{{ $doc->CODIGO_EMP }}][{{ $dia }}][{{ $desc }}]"
                                                value="{{ $val }}"
                                                maxlength="10" placeholder="—"
                                                class="w-14 text-center text-xs rounded border py-1 px-1 uppercase
                                                    {{ $inactivo
                                                        ? 'border-red-300 bg-red-50 text-red-500 focus:ring-red-400 focus:border-red-400'
                                                        : 'border-gray-300 focus:ring-1 focus:ring-blue-400 focus:border-blue-400' }}">
                                        </td>
                                    @endforeach
                                @endfor
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4 flex justify-end">
                <button type="submit"
                    class="bg-blue-700 hover:bg-blue-800 text-white text-sm font-medium px-6 py-2 rounded-lg transition">
                    Guardar asignaciones
                </button>
            </div>
        </form>
        @endif
    </div>

    {{-- ============================================================
         SECCIÓN: CALENDARIO ACADÉMICO
    ============================================================ --}}
    <div class="bg-white rounded-xl shadow p-5">
        <h2 class="text-base font-semibold text-blue-900 mb-1">Calendario Académico</h2>
        <p class="text-xs text-gray-400 mb-4">
            Asigna qué día del ciclo (1–6) corresponde a cada fecha y registra eventos especiales.
        </p>

        @if(session('success_cal'))
            <div class="mb-3 p-3 bg-green-100 text-green-800 rounded-xl text-sm">✅ {{ session('success_cal') }}</div>
        @endif

        {{-- Formulario agregar / editar fecha --}}
        <form method="POST" action="{{ route('vigilancias.calendario.guardar') }}"
              class="flex flex-wrap gap-3 items-end mb-5">
            @csrf
            <div>
                <label class="block text-xs text-gray-500 mb-1">Fecha</label>
                <input type="date" name="fecha" required
                    class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Día de ciclo (1–6)</label>
                <select name="dia_ciclo" required
                    class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-blue-500">
                    @for($d = 1; $d <= 6; $d++)
                        <option value="{{ $d }}">Día {{ $d }}</option>
                    @endfor
                </select>
            </div>
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs text-gray-500 mb-1">Evento (opcional)</label>
                <input type="text" name="evento" maxlength="200" placeholder="Ej: Día de izadas, Jornada pedagógica…"
                    class="w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Visibilidad</label>
                <select name="visibilidad"
                    class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="interno">Interno (docentes + directivas)</option>
                    <option value="todos">Para todos</option>
                    <option value="docentes">Solo docentes</option>
                    <option value="directivas">Solo directivas</option>
                    <option value="padres">Solo padres</option>
                </select>
            </div>
            <button type="submit"
                class="bg-blue-700 hover:bg-blue-800 text-white text-sm font-medium px-5 py-1.5 rounded-lg transition">
                Guardar fecha
            </button>
        </form>

        {{-- Tabla de fechas registradas --}}
        @if($calendario->isEmpty())
            <p class="text-sm text-gray-400 italic">No hay fechas registradas para {{ $anio }}.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm border-collapse">
                    <thead>
                        <tr class="bg-blue-50 text-blue-900 text-xs uppercase">
                            <th class="px-3 py-2 text-left">Fecha</th>
                            <th class="px-3 py-2 text-center">Día ciclo</th>
                            <th class="px-3 py-2 text-left">Evento</th>
                            <th class="px-3 py-2 text-center">Visibilidad</th>
                            <th class="px-3 py-2 text-center">Acción</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($calendario as $fila)
                        <tr class="hover:bg-gray-50 {{ $fila->evento ? 'bg-yellow-50' : '' }}">
                            <td class="px-3 py-2 font-medium text-gray-700">
                                {{ \Carbon\Carbon::parse($fila->fecha)->isoFormat('dddd D [de] MMMM') }}
                            </td>
                            <td class="px-3 py-2 text-center">
                                <span class="inline-block bg-blue-100 text-blue-800 font-bold rounded-full px-3 py-0.5">
                                    D{{ $fila->dia_ciclo }}
                                </span>
                            </td>
                            <td class="px-3 py-2 text-gray-600 italic">
                                {{ $fila->evento ?: '—' }}
                            </td>
                            <td class="px-3 py-2 text-center">
                                @if($fila->evento)
                                    @php
                                        $badges = [
                                            'todos'       => ['bg-green-100 text-green-800',  'Para todos'],
                                            'interno'     => ['bg-gray-100 text-gray-600',    'Interno'],
                                            'docentes'    => ['bg-blue-100 text-blue-800',    'Docentes'],
                                            'directivas'  => ['bg-purple-100 text-purple-800','Directivas'],
                                            'padres'      => ['bg-orange-100 text-orange-800','Padres'],
                                        ];
                                        [$cls, $lbl] = $badges[$fila->visibilidad] ?? ['bg-gray-100 text-gray-500', $fila->visibilidad];
                                    @endphp
                                    <span class="inline-block {{ $cls }} text-xs font-semibold px-2 py-0.5 rounded-full">
                                        {{ $lbl }}
                                    </span>
                                @else
                                    <span class="text-gray-300">—</span>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-center">
                                <form method="POST"
                                      action="{{ route('vigilancias.calendario.eliminar', $fila->id) }}"
                                      onsubmit="return confirm('¿Eliminar esta fecha?')">
                                    @csrf @method('DELETE')
                                    <button type="submit"
                                        class="text-xs text-red-600 hover:text-red-800 font-medium underline">
                                        Eliminar
                                    </button>
                                </form>
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
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<style>
    .m-asignado-d1 {
        width:30px;height:30px;background:#1d4ed8;border:2px solid white;border-radius:50%;
        display:flex;align-items:center;justify-content:center;
        color:white;font-size:11px;font-weight:800;
        box-shadow:0 2px 6px rgba(29,78,216,.5);cursor:pointer;
    }
    .m-asignado-d2 {
        width:30px;height:30px;background:#ea580c;border:2px solid white;border-radius:50%;
        display:flex;align-items:center;justify-content:center;
        color:white;font-size:11px;font-weight:800;
        box-shadow:0 2px 6px rgba(234,88,12,.5);cursor:pointer;
    }
    .m-libre {
        width:24px;height:24px;background:#9ca3af;border:2px solid white;border-radius:50%;
        display:flex;align-items:center;justify-content:center;
        color:white;font-size:10px;font-weight:700;
        box-shadow:0 1px 4px rgba(0,0,0,.3);cursor:pointer;
    }
    .m-escuela {
        width:30px;height:30px;background:#dc2626;border:2px solid white;border-radius:6px;
        display:flex;align-items:center;justify-content:center;font-size:14px;
        box-shadow:0 2px 6px rgba(0,0,0,.3);
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const puntos         = @json($puntosMapa);
    const posicionDoc    = @json($posicionDocente);   // { "5A": {docente, descanso, dia}, ... }

    const map = L.map('mapa-admin');

    L.tileLayer(
        'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
        { attribution: 'Tiles &copy; Esri', maxZoom: 21, maxNativeZoom: 19 }
    ).addTo(map);

    L.tileLayer(
        'https://services.arcgisonline.com/ArcGIS/rest/services/Reference/World_Boundaries_and_Places/MapServer/tile/{z}/{y}/{x}',
        { attribution: '', maxZoom: 21, maxNativeZoom: 19, opacity: 0.8 }
    ).addTo(map);

    // Sedes
    [
        { lat: 4.597329430079155, lng: -74.10431720923572, label: '🏫 Sede A' },
        { lat: 4.596231989229937, lng: -74.10479238842063, label: '🏫 Sede B' },
    ].forEach(s => {
        L.marker([s.lat, s.lng], {
            icon: L.divIcon({ className:'', html:'<div class="m-escuela">🏫</div>', iconSize:[30,30], iconAnchor:[15,15] })
        }).bindPopup(`<b>${s.label}</b>`).addTo(map);
    });

    // Posiciones
    puntos.forEach(p => {
        const info = posicionDoc[p.id] ?? null;
        let clase = 'm-libre';
        if (info) clase = info.descanso === 1 ? 'm-asignado-d1' : 'm-asignado-d2';

        const size = info ? 30 : 24;
        const icon = L.divIcon({
            className: '',
            html: `<div class="${clase}">${p.numero}</div>`,
            iconSize: [size, size], iconAnchor: [size/2, size/2],
        });

        let popup = `<b>${p.id}</b>`;
        if (p.desc) popup += `<br><span style="font-size:11px;color:#555">${p.desc}</span>`;
        if (info) {
            const color = info.descanso === 1 ? '#1d4ed8' : '#ea580c';
            popup += `<br><span style="color:${color};font-weight:600">
                ${info.docente}<br>Día ${info.dia} · Descanso ${info.descanso}
            </span>`;
        } else {
            popup += `<br><span style="color:#9ca3af;font-size:11px">Sin asignar</span>`;
        }

        L.marker([p.lat, p.lng], { icon }).bindPopup(popup).addTo(map);
    });

    if (puntos.length) {
        map.fitBounds(puntos.map(p => [p.lat, p.lng]), { padding: [30, 30] });
    }
});
</script>

<script>
function confirmarBloque(form) {
    const origen  = form.querySelector('[name=origen]');
    const destino = form.querySelector('[name=destino]');
    if (!origen.value || !destino.value) return true;
    return confirm(
        `¿Confirmas mover / intercambiar TODAS las vigilancias?\n\n` +
        `  Origen:  ${origen.options[origen.selectedIndex].text}\n` +
        `  Destino: ${destino.options[destino.selectedIndex].text}\n\n` +
        `Si el destino ya tiene vigilancias, se realizará un intercambio completo.`
    );
}
</script>
@endpush
