@extends('layouts.app-sidebar')

@section('header', 'English Acquisition — Docentes del Proyecto')

@section('slot')

    @if(session('success_acq'))
        <div class="mb-4 p-3 bg-green-100 text-green-800 rounded-xl text-sm">
            ✅ {{ session('success_acq') }}
        </div>
    @endif

    @if($errors->has('proyecto'))
        <div class="mb-4 p-3 bg-red-100 text-red-800 rounded-xl text-sm">
            ⚠️ {{ $errors->first('proyecto') }}
        </div>
    @endif

    <div class="bg-blue-50 border border-blue-200 text-blue-800 rounded-xl p-4 mb-6 text-sm">
        Asigna el docente que <strong>digita la nota del proyecto (40%)</strong> de cada curso. El docente verá
        <strong>«English Acquisition - Proyecto»</strong> en su menú de <strong>Notas</strong> como una digitación normal.
        Si el docente cambia en un período, solo reasígnalo aquí: cada uno digita únicamente el período abierto.
    </div>

    <div class="bg-white rounded-xl shadow overflow-hidden">
        <div class="px-5 py-3 bg-blue-800 text-white">
            <h3 class="font-bold text-sm uppercase tracking-wide">Asignación por curso</h3>
        </div>
        <div class="divide-y divide-gray-100">
            @foreach($cursos as $curso)
            @php $actual = $asignados[$curso] ?? null; @endphp
            <form method="POST" action="{{ route('english-acq.proyecto.asignar') }}"
                  class="flex flex-col sm:flex-row sm:items-center gap-3 px-4 py-3 hover:bg-gray-50">
                @csrf
                <input type="hidden" name="curso" value="{{ $curso }}">
                <div class="w-full sm:w-32 font-semibold text-gray-700">{{ $curso }}</div>
                <select name="codigo_emp"
                    class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">— Sin asignar —</option>
                    @foreach($docentes as $d)
                        <option value="{{ $d->CODIGO_EMP }}"
                            {{ $actual && $actual->CODIGO_EMP === $d->CODIGO_EMP ? 'selected' : '' }}>
                            {{ $d->NOMBRE_DOC ?? $d->CODIGO_EMP }}
                        </option>
                    @endforeach
                </select>
                <button type="submit"
                    class="bg-blue-800 hover:bg-blue-700 text-white text-xs font-semibold px-4 py-2 rounded-lg transition whitespace-nowrap">
                    Guardar
                </button>
            </form>
            @endforeach
        </div>
    </div>

@endsection
