@extends('layouts.app-sidebar')

@section('header', 'Consulta de Estudiantes')

@section('slot')

    <div class="bg-white rounded-xl shadow p-5 mb-6">
        <form method="GET" action="{{ route('alumnos.index') }}" id="form-busqueda">

            {{-- Búsqueda básica --}}
            <div class="flex gap-3 items-end mb-3">
                <div class="flex-1">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Buscar por código, nombre o apellido</label>
                    <input type="text" name="buscar" value="{{ request('buscar') }}"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Ej: 21008 o García" autofocus>
                </div>
                <button type="submit" class="bg-blue-800 hover:bg-blue-700 text-white px-5 py-2 rounded-lg text-sm font-semibold transition">
                    Buscar
                </button>
                <button type="button" onclick="toggleAvanzado()"
                    class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium transition">
                    ⚙️ Avanzado
                </button>
            </div>

            {{-- Búsqueda avanzada --}}
            <div id="busqueda-avanzada" class="{{ request()->anyFilled(['grado','curso','sede','estado','email_padre']) ? '' : 'hidden' }} border-t pt-4 mt-2 grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3">
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Grado</label>
                    <select name="grado" class="w-full border border-gray-300 rounded-lg px-2 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Todos</option>
                        @foreach($grados as $g)
                            <option value="{{ $g }}" {{ request('grado') == $g ? 'selected' : '' }}>{{ $g }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Curso</label>
                    <select name="curso" class="w-full border border-gray-300 rounded-lg px-2 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Todos</option>
                        @foreach($cursos as $c)
                            <option value="{{ $c }}" {{ request('curso') == $c ? 'selected' : '' }}>{{ $c }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Sede</label>
                    <select name="sede" class="w-full border border-gray-300 rounded-lg px-2 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Todas</option>
                        @foreach($sedes as $s)
                            <option value="{{ $s }}" {{ request('sede') == $s ? 'selected' : '' }}>{{ $s }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Estado</label>
                    <select name="estado" class="w-full border border-gray-300 rounded-lg px-2 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Todos</option>
                        @foreach($estados as $e)
                            <option value="{{ $e }}" {{ request('estado') == $e ? 'selected' : '' }}>{{ $e }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Email padre/madre/acudiente</label>
                    <input type="text" name="email_padre" value="{{ request('email_padre') }}"
                        class="w-full border border-gray-300 rounded-lg px-2 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="correo@...">
                </div>
            </div>

        </form>
    </div>

    @if($hayBusqueda && $estudiantes->isEmpty())
        <div class="bg-red-100 text-red-700 rounded-xl p-4 text-sm mb-4">
            No se encontraron estudiantes con ese criterio.
        </div>
    @endif

    @if($estudiantes->isNotEmpty())
    <div class="bg-white rounded-xl shadow overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-bold text-blue-800">Resultados</h3>
            <div class="flex items-center gap-4">
                <span class="text-xs text-gray-400">{{ $estudiantes->total() }} encontrados</span>
                @auth
                    @if(in_array(auth()->user()->PROFILE, ['SuperAd', 'Admin']))
                    <a href="{{ route('alumnos.create') }}"
                        class="bg-blue-800 hover:bg-blue-700 text-white text-xs font-semibold px-3 py-1.5 rounded-lg transition">
                        ➕ Matricular
                    </a>
                    @endif
                @endauth
            </div>
        </div>
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                <tr>
                    <th class="px-4 py-3 text-left">Código</th>
                    <th class="px-4 py-3 text-left">Nombre</th>
                    <th class="px-4 py-3 text-left">Grado</th>
                    <th class="px-4 py-3 text-left">Curso</th>
                    <th class="px-4 py-3 text-left">Estado</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($estudiantes as $e)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-medium">{{ $e->CODIGO }}</td>
                    <td class="px-4 py-3">{{ $e->APELLIDO1 }} {{ $e->APELLIDO2 }} {{ $e->NOMBRE1 }} {{ $e->NOMBRE2 }}</td>
                    <td class="px-4 py-3">{{ $e->GRADO ?? '—' }}</td>
                    <td class="px-4 py-3">{{ $e->CURSO ?? '—' }}</td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-1 rounded-full text-xs font-medium {{ $e->ESTADO === 'MATRICULADO' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                            {{ $e->ESTADO ?? '—' }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <a href="{{ route('alumnos.show', $e->CODIGO) }}" class="text-blue-700 hover:underline text-sm font-medium">
                            Ver ficha →
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @if($estudiantes->hasPages())
        <div class="px-5 py-4 border-t border-gray-100">
            {{ $estudiantes->links() }}
        </div>
        @endif
    </div>
    @endif

    <script>
        function toggleAvanzado() {
            document.getElementById('busqueda-avanzada').classList.toggle('hidden');
        }
    </script>

@endsection
