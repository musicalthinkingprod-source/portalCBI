@extends('layouts.app-sidebar')

@section('header', 'Control de Usuarios y Docentes')

@section('slot')

@if(session('success_usuario'))
    <div class="mb-4 p-3 bg-green-100 text-green-800 rounded-xl text-sm">✅ {{ session('success_usuario') }}</div>
@endif
@if(session('success_docente'))
    <div class="mb-4 p-3 bg-green-100 text-green-800 rounded-xl text-sm">✅ {{ session('success_docente') }}</div>
@endif
@if(session('success_mover'))
    <div class="mb-4 p-3 bg-green-100 text-green-800 rounded-xl text-sm">✅ {{ session('success_mover') }}</div>
@endif
@if($errors->has('store') || $errors->has('delete') || $errors->has('docente') || $errors->has('mover'))
    <div class="mb-4 p-3 bg-red-100 text-red-700 rounded-xl text-sm">
        ⚠️ {{ $errors->first('store') ?? $errors->first('delete') ?? $errors->first('docente') ?? $errors->first('mover') }}
    </div>
@endif

{{-- ═══════════════════════════════════════════════════════
     SECCIÓN 1 — USUARIOS DEL SISTEMA
═══════════════════════════════════════════════════════ --}}
<div class="bg-white rounded-xl shadow overflow-hidden mb-8">
    <div class="px-5 py-3 bg-blue-800 text-white flex items-center justify-between">
        <h3 class="font-bold text-sm uppercase tracking-wide">Usuarios del Sistema</h3>
        <span class="text-blue-300 text-xs">{{ $usuarios->count() }} usuarios registrados</span>
    </div>

    {{-- Formulario crear usuario --}}
    <div class="p-5 border-b border-gray-100 bg-gray-50">
        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Nuevo usuario</p>
        <form method="POST" action="{{ route('admin.usuarios.store') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-3 items-end">
            @csrf
            <div>
                <label class="block text-xs text-gray-500 mb-1">Usuario</label>
                <input type="text" name="USER" value="{{ old('USER') }}" required maxlength="25"
                    placeholder="ej: profe01"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('USER') border-red-400 @enderror">
                @error('USER')<p class="text-red-500 text-xs mt-0.5">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Contraseña</label>
                <input type="password" name="PASSWORD" required minlength="4"
                    placeholder="Mínimo 4 caracteres"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Perfil</label>
                <div class="flex gap-2">
                    <select id="sel-perfil-base" onchange="aplicarPerfil(this)"
                        class="flex-1 border border-gray-300 rounded-lg px-2 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">— Seleccionar —</option>
                        <option value="SuperAd">SuperAd</option>
                        <option value="Admin">Admin</option>
                        <option value="Ori">Ori</option>
                        <option value="SecA">SecA</option>
                        <option value="SecB">SecB</option>
                        <option value="SecC">SecC</option>
                        <option value="DOC">DOC (escribir código)</option>
                    </select>
                    <input type="text" id="inp-profile" name="PROFILE" value="{{ old('PROFILE') }}" required maxlength="10"
                        placeholder="Perfil"
                        class="w-24 border border-gray-300 rounded-lg px-2 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
            <div>
                <button type="submit"
                    class="w-full bg-blue-800 hover:bg-blue-700 text-white font-semibold px-4 py-2 rounded-lg text-sm transition">
                    + Crear usuario
                </button>
            </div>
        </form>
    </div>

    {{-- Tabla de usuarios --}}
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                <tr>
                    <th class="px-4 py-3 text-left">Usuario</th>
                    <th class="px-4 py-3 text-left">Perfil</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($usuarios as $u)
                <tr class="hover:bg-gray-50 {{ $u->USER === auth()->user()->USER ? 'bg-blue-50' : '' }}">
                    <td class="px-4 py-2 font-medium">
                        {{ $u->USER }}
                        @if($u->USER === auth()->user()->USER)
                            <span class="ml-1 text-xs text-blue-500">(tú)</span>
                        @endif
                    </td>
                    <td class="px-4 py-2">
                        <span class="px-2 py-0.5 rounded-full text-xs font-semibold
                            {{ $u->PROFILE === 'SuperAd' ? 'bg-purple-100 text-purple-700' :
                               ($u->PROFILE === 'Admin'   ? 'bg-blue-100 text-blue-700' :
                               (str_starts_with($u->PROFILE, 'DOC') ? 'bg-green-100 text-green-700' :
                               'bg-gray-100 text-gray-600')) }}">
                            {{ $u->PROFILE }}
                        </span>
                    </td>
                    <td class="px-4 py-2 text-right">
                        @if($u->USER !== auth()->user()->USER)
                        <form method="POST" action="{{ route('admin.usuarios.destroy', $u->USER) }}"
                              onsubmit="return confirm('¿Eliminar usuario «{{ $u->USER }}»? Esta acción no se puede deshacer.')">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                class="text-red-500 hover:text-red-700 text-xs font-semibold px-2 py-1 rounded hover:bg-red-50 transition">
                                Eliminar
                            </button>
                        </form>
                        @else
                        <span class="text-gray-300 text-xs">—</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="3" class="px-4 py-4 text-center text-gray-400 text-sm">No hay usuarios registrados.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════
     SECCIÓN 2 — DOCENTES
═══════════════════════════════════════════════════════ --}}
<div class="bg-white rounded-xl shadow overflow-hidden mb-8">
    <div class="px-5 py-3 bg-blue-800 text-white flex items-center justify-between">
        <h3 class="font-bold text-sm uppercase tracking-wide">Docentes</h3>
        <div class="flex gap-3 text-xs">
            <span class="text-green-300">● {{ $docentes->where('ESTADO','ACTIVO')->count() }} activos</span>
            <span class="text-red-300">● {{ $docentes->where('ESTADO','INACTIVO')->count() }} inactivos</span>
        </div>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                <tr>
                    <th class="px-4 py-3 text-left">Código</th>
                    <th class="px-4 py-3 text-left">Nombre</th>
                    <th class="px-4 py-3 text-left">Tipo</th>
                    <th class="px-4 py-3 text-center">Estado</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($docentes as $doc)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-2 font-mono text-xs text-gray-500">{{ $doc->CODIGO_DOC }}</td>
                    <td class="px-4 py-2 font-medium">{{ $doc->NOMBRE_DOC }}</td>
                    <td class="px-4 py-2 text-xs text-gray-500">{{ $doc->TIPO ?? '—' }}</td>
                    <td class="px-4 py-2 text-center">
                        <span class="px-2 py-0.5 rounded-full text-xs font-semibold
                            {{ $doc->ESTADO === 'ACTIVO' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-600' }}">
                            {{ $doc->ESTADO }}
                        </span>
                    </td>
                    <td class="px-4 py-2 text-right">
                        <form method="POST" action="{{ route('admin.docentes.toggle', $doc->CODIGO_DOC) }}">
                            @csrf
                            <button type="submit"
                                class="text-xs font-semibold px-3 py-1 rounded transition
                                    {{ $doc->ESTADO === 'ACTIVO'
                                        ? 'text-red-600 hover:bg-red-50 hover:text-red-700'
                                        : 'text-green-600 hover:bg-green-50 hover:text-green-700' }}">
                                {{ $doc->ESTADO === 'ACTIVO' ? 'Marcar inactivo' : 'Marcar activo' }}
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="px-4 py-4 text-center text-gray-400 text-sm">No hay docentes registrados.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════
     SECCIÓN 3 — MOVER ASIGNACIONES INDIVIDUALES
═══════════════════════════════════════════════════════ --}}
<div class="bg-white rounded-xl shadow overflow-hidden mb-8">
    <div class="px-5 py-3 bg-blue-800 text-white">
        <h3 class="font-bold text-sm uppercase tracking-wide">Mover Asignaciones Individuales</h3>
        <p class="text-blue-300 text-xs mt-0.5">Selecciona un docente para ver y mover cada asignación por separado.</p>
    </div>

    {{-- Selector de docente --}}
    <div class="p-5 border-b border-gray-100 bg-gray-50">
        <form method="GET" action="{{ route('admin.usuarios') }}#asig-individual">
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
                <a href="{{ route('admin.usuarios') }}#asig-individual"
                    class="bg-gray-100 hover:bg-gray-200 text-gray-600 font-semibold px-4 py-2 rounded-lg text-sm transition">
                    Limpiar
                </a>
                @endif
            </div>
        </form>
    </div>

    {{-- Tabla de asignaciones individuales --}}
    <div id="asig-individual">
        @if($verAsigDoc && $asigIndividual->isEmpty())
            <div class="p-5 text-sm text-gray-400 text-center">Este docente no tiene asignaciones registradas.</div>

        @elseif($asigIndividual->isNotEmpty())

        @if(session('success_mover_una'))
            <div class="mx-5 mt-4 p-3 bg-green-100 text-green-800 rounded-xl text-sm">✅ {{ session('success_mover_una') }}</div>
        @endif
        @if($errors->has('mover_una'))
            <div class="mx-5 mt-4 p-3 bg-red-100 text-red-700 rounded-xl text-sm">⚠️ {{ $errors->first('mover_una') }}</div>
        @endif

        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                <tr>
                    <th class="px-4 py-3 text-left">Materia</th>
                    <th class="px-4 py-3 text-center w-24">Curso</th>
                    <th class="px-4 py-3 text-left">Mover a docente</th>
                    <th class="px-4 py-3 w-24"></th>
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
     SECCIÓN 4 — MOVER ASIGNACIONES EN BLOQUE
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

            @if($errors->has('mover'))
            <div class="mb-3 p-2 bg-red-50 text-red-600 rounded-lg text-xs">{{ $errors->first('mover') }}</div>
            @endif
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
    const siguienteCodDoc = @json($siguienteCodDoc);

    // Selector de perfil con campo libre para DOC
    function aplicarPerfil(sel) {
        const inp = document.getElementById('inp-profile');
        if (sel.value === 'DOC') {
            inp.value = siguienteCodDoc;
            inp.focus();
            inp.select();
        } else {
            inp.value = sel.value;
        }
    }

    // Confirmación antes de mover asignaciones
    function confirmarMover(form) {
        const origen  = form.querySelector('[name=origen]');
        const destino = form.querySelector('[name=destino]');
        if (!origen.value || !destino.value) return true; // dejar que el servidor valide
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
