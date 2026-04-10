@extends('layouts.app-sidebar')
@section('header', 'Permisos del Personal')

@section('slot')
@php
    use App\Http\Controllers\AsistenciaPersonalController as AP;
    Carbon\Carbon::setLocale('es');
@endphp

<div class="max-w-5xl mx-auto space-y-6">

    @if(session('success_permiso'))
        <div class="p-3 bg-green-100 text-green-800 rounded-xl text-sm">✅ {{ session('success_permiso') }}</div>
    @endif

    {{-- Formulario nuevo permiso --}}
    <div class="bg-white rounded-xl shadow p-5">
        <h2 class="text-sm font-semibold text-blue-900 mb-4">Registrar permiso / licencia</h2>

        <form method="POST" action="{{ route('asistencia-personal.permisos.crear') }}"
              class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @csrf

            <div>
                <label class="block text-xs text-gray-500 mb-1">Docente</label>
                <select name="codigo_doc" required
                    class="w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="">— Seleccionar —</option>
                    @foreach($docentes as $doc)
                        <option value="{{ $doc->CODIGO_DOC }}">{{ $doc->NOMBRE_DOC }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs text-gray-500 mb-1">Tipo</label>
                <select name="tipo" required
                    class="w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-blue-500">
                    @foreach(AP::$tipoPermisoLabel as $val => $lbl)
                        <option value="{{ $val }}">{{ $lbl }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs text-gray-500 mb-1">Fecha inicio</label>
                <input type="date" name="fecha_inicio" required
                    class="w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-xs text-gray-500 mb-1">Fecha fin</label>
                <input type="date" name="fecha_fin" required
                    class="w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="md:col-span-2">
                <label class="block text-xs text-gray-500 mb-1">Motivo</label>
                <textarea name="motivo" rows="2" required maxlength="500"
                    class="w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-blue-500"
                    placeholder="Descripción del permiso..."></textarea>
            </div>

            <div class="md:col-span-2 flex justify-end">
                <button type="submit"
                    class="bg-blue-700 hover:bg-blue-800 text-white text-sm font-medium px-6 py-2 rounded-lg transition">
                    Registrar permiso
                </button>
            </div>
        </form>
    </div>

    {{-- Listado de permisos --}}
    <div class="bg-white rounded-xl shadow overflow-hidden">
        <div class="px-5 py-3 bg-gray-50 border-b border-gray-100">
            <h2 class="text-sm font-semibold text-blue-900">Permisos registrados</h2>
        </div>
        @if($permisos->isEmpty())
            <p class="p-5 text-sm text-gray-400 italic">No hay permisos registrados.</p>
        @else
        <table class="w-full text-sm">
            <thead>
                <tr class="text-xs uppercase text-gray-400 border-b border-gray-100">
                    <th class="px-4 py-2 text-left">Docente</th>
                    <th class="px-4 py-2 text-center">Tipo</th>
                    <th class="px-4 py-2 text-center">Desde</th>
                    <th class="px-4 py-2 text-center">Hasta</th>
                    <th class="px-4 py-2 text-left">Motivo</th>
                    <th class="px-4 py-2 text-center">Aprobado por</th>
                    <th class="px-4 py-2 text-center">Acción</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($permisos as $p)
                @php
                    $hoy = today()->toDateString();
                    $activo = $p->fecha_inicio <= $hoy && $p->fecha_fin >= $hoy;
                @endphp
                <tr class="hover:bg-gray-50 {{ $activo ? 'bg-blue-50' : '' }}">
                    <td class="px-4 py-2.5 font-medium text-gray-800">
                        {{ $p->NOMBRE_DOC ?? $p->codigo_doc }}
                        @if($activo)
                            <span class="ml-1 text-[10px] bg-blue-600 text-white px-1.5 py-0.5 rounded-full">Activo</span>
                        @endif
                    </td>
                    <td class="px-4 py-2.5 text-center">
                        <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-indigo-100 text-indigo-800">
                            {{ AP::$tipoPermisoLabel[$p->tipo] ?? $p->tipo }}
                        </span>
                    </td>
                    <td class="px-4 py-2.5 text-center text-gray-600 text-xs">
                        {{ \Carbon\Carbon::parse($p->fecha_inicio)->isoFormat('D MMM YYYY') }}
                    </td>
                    <td class="px-4 py-2.5 text-center text-gray-600 text-xs">
                        {{ \Carbon\Carbon::parse($p->fecha_fin)->isoFormat('D MMM YYYY') }}
                    </td>
                    <td class="px-4 py-2.5 text-gray-500 text-xs max-w-[200px] truncate" title="{{ $p->motivo }}">
                        {{ $p->motivo }}
                    </td>
                    <td class="px-4 py-2.5 text-center text-gray-400 text-xs">{{ $p->aprobado_por ?? '—' }}</td>
                    <td class="px-4 py-2.5 text-center">
                        <form method="POST" action="{{ route('asistencia-personal.permisos.eliminar', $p->id) }}"
                              onsubmit="return confirm('¿Eliminar este permiso?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-xs text-red-600 hover:text-red-800 font-medium underline">
                                Eliminar
                            </button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>

</div>
@endsection
