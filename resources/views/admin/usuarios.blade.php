@extends('layouts.app-sidebar')

@section('header', 'Gestión de Usuarios y Docentes')

@section('slot')

@if(session('success_usuario'))
    <div class="mb-4 p-3 bg-green-100 text-green-800 rounded-xl text-sm">✅ {{ session('success_usuario') }}</div>
@endif
@if(session('success_docente'))
    <div class="mb-4 p-3 bg-green-100 text-green-800 rounded-xl text-sm">✅ {{ session('success_docente') }}</div>
@endif
@if($errors->has('store') || $errors->has('delete') || $errors->has('docente'))
    <div class="mb-4 p-3 bg-red-100 text-red-700 rounded-xl text-sm">
        ⚠️ {{ $errors->first('store') ?? $errors->first('delete') ?? $errors->first('docente') }}
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

    @if($errors->has('docente_store'))
        <div class="mx-5 mt-4 p-3 bg-red-100 text-red-700 rounded-xl text-sm">⚠️ {{ $errors->first('docente_store') }}</div>
    @endif

    {{-- Formulario nuevo docente --}}
    <div class="p-5 border-b border-gray-100 bg-gray-50">
        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Nuevo docente</p>
        <form method="POST" action="{{ route('admin.docentes.store') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-3 items-end">
            @csrf
            <div>
                <label class="block text-xs text-gray-500 mb-1">Código</label>
                <input type="text" name="CODIGO_DOC" id="inp-cod-doc"
                    value="{{ old('CODIGO_DOC', $siguienteCodDoc) }}"
                    required maxlength="10"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="sm:col-span-2">
                <label class="block text-xs text-gray-500 mb-1">Nombre completo</label>
                <input type="text" name="NOMBRE_DOC" value="{{ old('NOMBRE_DOC') }}"
                    required maxlength="150" placeholder="Ej: Ana María Pérez López"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Tipo</label>
                <div class="flex gap-2">
                    <select name="TIPO"
                        class="flex-1 border border-gray-300 rounded-lg px-2 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="DOCENTE" {{ old('TIPO', 'DOCENTE') === 'DOCENTE' ? 'selected' : '' }}>Docente</option>
                        <option value="ADMINISTRATIVO" {{ old('TIPO') === 'ADMINISTRATIVO' ? 'selected' : '' }}>Administrativo</option>
                    </select>
                    <button type="submit"
                        class="bg-blue-800 hover:bg-blue-700 text-white font-semibold px-4 py-2 rounded-lg text-sm transition whitespace-nowrap">
                        + Agregar
                    </button>
                </div>
            </div>
        </form>
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

@endsection

@push('scripts')
<script>
    const siguienteCodDoc = @json($siguienteCodDoc);

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
</script>
@endpush
