@extends('layouts.app-sidebar')

@section('header', 'Directores de Grupo')

@section('slot')

@if(session('success_dir_grupo'))
    <div class="mb-4 p-3 bg-green-100 text-green-800 rounded-xl text-sm">✅ {{ session('success_dir_grupo') }}</div>
@endif

<div class="bg-white rounded-xl shadow overflow-hidden">
    <div class="px-5 py-3 bg-blue-800 text-white flex items-center justify-between">
        <h3 class="font-bold text-sm uppercase tracking-wide">Directores de Grupo</h3>
        <span class="text-blue-300 text-xs">{{ $directores->count() }} asignados de {{ $cursos->count() }} cursos</span>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                <tr>
                    <th class="px-4 py-3 text-left w-24">Curso</th>
                    <th class="px-4 py-3 text-left">Director asignado</th>
                    @if($puedeEditar)
                    <th class="px-4 py-3 text-left">Cambiar director</th>
                    @endif
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($cursos as $curso)
                @php $dirActual = $directores->get($curso); @endphp
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-2 font-mono font-semibold text-blue-800">{{ $curso }}</td>
                    <td class="px-4 py-2">
                        @if($dirActual)
                            @php $nombreDir = $docentes->firstWhere('CODIGO_EMP', $dirActual)?->NOMBRE_DOC ?? $dirActual; @endphp
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-blue-100 text-blue-800 text-xs font-semibold">
                                {{ $nombreDir }}
                            </span>
                        @else
                            <span class="text-gray-400 text-xs italic">Sin director asignado</span>
                        @endif
                    </td>
                    @if($puedeEditar)
                    <td class="px-4 py-2">
                        <form method="POST" action="{{ route('admin.dir_grupo') }}" class="flex gap-2 items-center">
                            @csrf
                            <input type="hidden" name="curso" value="{{ $curso }}">
                            <select name="docente"
                                class="flex-1 border border-gray-300 rounded-lg px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">— Sin director —</option>
                                @foreach($docentes as $doc)
                                    <option value="{{ $doc->CODIGO_EMP }}" {{ $dirActual === $doc->CODIGO_EMP ? 'selected' : '' }}>
                                        {{ $doc->NOMBRE_DOC }}
                                    </option>
                                @endforeach
                            </select>
                            <button type="submit"
                                class="bg-blue-800 hover:bg-blue-700 text-white text-xs font-semibold px-3 py-1.5 rounded-lg transition whitespace-nowrap">
                                Guardar
                            </button>
                        </form>
                    </td>
                    @endif
                </tr>
                @empty
                <tr><td colspan="{{ $puedeEditar ? 3 : 2 }}" class="px-4 py-4 text-center text-gray-400 text-sm">No hay cursos registrados en las asignaciones.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
