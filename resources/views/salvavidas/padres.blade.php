@extends('layouts.padres')

@section('header', 'Salvavidas')

@section('slot')

    @if(session('padre_estudiante'))
    <div class="mb-4 bg-orange-50 border border-orange-200 rounded-xl p-4 text-sm text-orange-800">
        🏊 Los salvavidas son avisos de que tu hijo/a está en riesgo de perder una materia a mitad del período.
        Comunícate con la institución para recibir orientación.
    </div>
    @endif

    @if($salvavidas->isEmpty())
        <div class="bg-white rounded-xl shadow p-8 text-center">
            <p class="text-4xl mb-3">✅</p>
            <p class="font-semibold text-gray-700">Sin salvavidas en {{ $anio }}</p>
            <p class="text-sm text-gray-400 mt-1">Tu hijo/a no tiene materias en riesgo registradas este año.</p>
        </div>
    @else
        {{-- Agrupar por período --}}
        @foreach([1,2,3,4] as $p)
        @php $delPeriodo = $salvavidas->where('PERIODO', $p); @endphp
        @if($delPeriodo->isNotEmpty())
        <div class="bg-white rounded-xl shadow overflow-hidden mb-4">
            <div class="px-5 py-3 bg-orange-600 text-white">
                <h3 class="font-bold text-sm uppercase tracking-wide">🏊 Período {{ $p }}</h3>
                <p class="text-orange-200 text-xs mt-0.5">{{ $delPeriodo->count() }} {{ $delPeriodo->count() == 1 ? 'materia en riesgo' : 'materias en riesgo' }}</p>
            </div>
            <ul class="divide-y divide-gray-100">
                @foreach($delPeriodo as $s)
                <li class="px-5 py-3 flex items-center gap-3">
                    <span class="text-orange-500 text-lg">⚠️</span>
                    <span class="font-medium text-gray-800 flex-1">{{ $s->NOMBRE_MAT }}</span>
                    @if(!empty($urlsSite[$s->CODIGO_MAT]))
                    <a href="{{ $urlsSite[$s->CODIGO_MAT] }}" target="_blank" rel="noopener"
                       class="shrink-0 inline-flex items-center gap-1.5 text-xs font-semibold text-blue-600 hover:text-blue-800 bg-blue-50 hover:bg-blue-100 px-3 py-1.5 rounded-lg transition">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                        </svg>
                        Ver guía
                    </a>
                    @endif
                </li>
                @endforeach
            </ul>
        </div>
        @endif
        @endforeach
    @endif

@endsection
