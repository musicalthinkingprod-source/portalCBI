@extends('layouts.app-sidebar')

@section('header', 'Horarios')

@section('slot')
<div class="max-w-4xl mx-auto py-8 px-4">
    <h1 class="text-2xl font-bold text-gray-800 mb-2">Horarios</h1>
    <p class="text-gray-500 mb-8">Consulta el horario por curso o por docente.</p>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

        {{-- Por curso --}}
        <div class="bg-white rounded-xl shadow p-6">
            <h2 class="text-lg font-semibold text-gray-700 mb-4">📅 Por curso</h2>
            <form action="{{ route('horarios.por_curso') }}" method="GET">
                <label class="block text-sm text-gray-600 mb-1">Selecciona un curso</label>
                <select name="curso" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm mb-4 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @foreach($cursos as $curso)
                        <option value="{{ $curso }}">{{ $curso }}</option>
                    @endforeach
                </select>
                <button type="submit" class="w-full bg-blue-600 text-white rounded-lg py-2 text-sm font-medium hover:bg-blue-700 transition">
                    Ver horario
                </button>
            </form>
        </div>

        {{-- Por docente --}}
        <div class="bg-white rounded-xl shadow p-6">
            <h2 class="text-lg font-semibold text-gray-700 mb-4">👤 Por docente</h2>
            <form action="{{ route('horarios.por_docente') }}" method="GET">
                <label class="block text-sm text-gray-600 mb-1">Selecciona un docente</label>
                <select name="docente" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm mb-4 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @foreach($docentes as $doc)
                        <option value="{{ $doc->CODIGO_EMP }}">{{ $doc->NOMBRE_DOC }}</option>
                    @endforeach
                </select>
                <button type="submit" class="w-full bg-indigo-600 text-white rounded-lg py-2 text-sm font-medium hover:bg-indigo-700 transition">
                    Ver horario
                </button>
            </form>
        </div>

    </div>
</div>
@endsection
