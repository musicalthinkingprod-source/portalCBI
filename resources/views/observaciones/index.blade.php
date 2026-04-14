@extends('layouts.app-sidebar')

@section('header', 'Observaciones 2026 · Dirección de Grupo')

@section('slot')

@if(session('ok'))
<div class="mb-5 bg-green-50 border border-green-300 text-green-800 px-4 py-3 rounded-lg text-sm font-medium">
    ✅ {{ session('ok') }}
</div>
@endif

@if(session('error'))
<div class="mb-5 bg-red-50 border border-red-300 text-red-800 px-4 py-3 rounded-lg text-sm font-medium">
    ⚠️ {{ session('error') }}
</div>
@endif

{{-- Selector de curso (solo para Admin/SuperAd) --}}
@unless($isDoc)
<div class="bg-white rounded-xl shadow p-5 mb-6">
    <form method="GET" action="{{ route('observaciones.index') }}" class="flex gap-3 items-end flex-wrap">
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Curso</label>
            <select name="curso"
                class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                <option value="">-- Selecciona un curso --</option>
                @foreach($cursos as $c)
                <option value="{{ $c }}" @selected($cursoDir === $c)>{{ $c }}</option>
                @endforeach
            </select>
        </div>
        <input type="hidden" name="periodo" value="{{ $periodo }}">
        <button type="submit"
            class="bg-blue-800 hover:bg-blue-700 text-white px-5 py-2 rounded-lg text-sm font-semibold transition">
            Cargar
        </button>
    </form>
</div>
@endunless

@if($cursoDir)

{{-- Encabezado e info del curso --}}
<div class="mb-5 flex items-center justify-between flex-wrap gap-2">
    <div>
        <h2 class="text-lg font-bold text-gray-800">Curso: <span class="text-blue-700">{{ $cursoDir }}</span></h2>
        <p class="text-xs text-gray-400 mt-0.5">{{ $estudiantes->count() }} estudiante(s) matriculado(s)</p>
    </div>
</div>

{{-- Tabs de período --}}
<div class="flex gap-1 mb-5">
    @foreach([1,2,3,4] as $p)
    @php $tabAbierto = in_array($p, $periodosAbiertos); @endphp
    <a href="{{ route('observaciones.index', array_filter(['periodo' => $p, 'curso' => $isDoc ? null : $cursoDir])) }}"
       class="px-5 py-2 rounded-lg text-sm font-semibold transition flex items-center gap-1.5
              {{ $periodo === $p
                 ? ($tabAbierto ? 'bg-blue-700 text-white shadow' : 'bg-gray-600 text-white shadow')
                 : ($tabAbierto ? 'bg-white text-gray-600 border border-gray-200 hover:bg-blue-50 hover:text-blue-700'
                                : 'bg-gray-100 text-gray-400 border border-gray-200') }}">
        Período {{ $p }}
        @if(!$tabAbierto)
            <span class="text-xs">🔒</span>
        @endif
    </a>
    @endforeach
</div>

@if($estudiantes->isEmpty())
<div class="bg-white rounded-xl shadow p-8 text-center text-gray-400 text-sm">
    No hay estudiantes matriculados en el curso <span class="font-semibold text-gray-600">{{ $cursoDir }}</span>.
</div>
@else

@if(!$periodoAbierto)
<div class="mb-5 p-4 bg-gray-50 border border-gray-300 rounded-xl flex items-center gap-3 text-gray-600 text-sm">
    🔒 <span>El período <strong>{{ $periodo }}</strong> está cerrado. Las observaciones solo pueden consultarse.</span>
</div>
@endif

<form method="POST" action="{{ route('observaciones.store') }}">
    @csrf
    <input type="hidden" name="periodo" value="{{ $periodo }}">
    <input type="hidden" name="curso" value="{{ $cursoDir }}">

    <div class="bg-white rounded-xl shadow overflow-hidden mb-5">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-200">
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide w-24">Código</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Estudiante</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Observación — Período {{ $periodo }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($estudiantes as $est)
                @php
                    $nombre = trim(implode(' ', array_filter([
                        $est->NOMBRE1, $est->NOMBRE2, $est->APELLIDO1, $est->APELLIDO2
                    ])));
                    $nombre = preg_replace('/\s+/', ' ', $nombre);
                    $obs    = $observaciones[$est->CODIGO] ?? '';
                @endphp
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-4 py-3 font-mono text-gray-500 text-xs align-top pt-4">{{ $est->CODIGO }}</td>
                    <td class="px-4 py-3 align-top pt-4">
                        <span class="font-medium text-gray-800">{{ $nombre }}</span>
                    </td>
                    <td class="px-4 py-3">
                        @if($periodoAbierto)
                        <textarea
                            name="obs[{{ $est->CODIGO }}]"
                            maxlength="512"
                            rows="2"
                            placeholder="Escribe la observación del estudiante para el período {{ $periodo }}..."
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none resize-none"
                        >{{ $obs }}</textarea>
                        @else
                        <p class="text-sm text-gray-600 py-1">{{ $obs ?: '—' }}</p>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if($periodoAbierto)
    <div class="flex items-center gap-3">
        <button type="submit"
            class="bg-blue-700 hover:bg-blue-800 text-white px-6 py-2.5 rounded-lg text-sm font-semibold transition shadow">
            💾 Guardar observaciones del período {{ $periodo }}
        </button>
        <p class="text-xs text-gray-400">Los campos vacíos eliminarán la observación existente.</p>
    </div>
    @endif
</form>

@endif

@else
<div class="bg-white rounded-xl shadow p-8 text-center text-gray-400 text-sm">
    Selecciona un curso para ver y editar las observaciones.
</div>
@endif

@endsection
