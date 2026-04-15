@extends('layouts.padres')

@section('header', 'Conducto Regular')

@section('slot')

{{-- Encabezado --}}
<div class="text-center mb-8">
    <div class="flex items-center justify-center gap-4 mb-3">
        <img src="{{ asset('images/escudoCBI.png') }}" alt="CBI" class="h-14 w-auto">
        <div>
            <h1 class="text-2xl sm:text-3xl font-black text-gray-800 uppercase tracking-wide">Sigue la ruta correcta</h1>
            <p class="text-sm sm:text-base font-semibold text-red-600 mt-1">"Trabajemos juntos para mejorar la comunicación"</p>
        </div>
    </div>
    <div class="inline-block bg-blue-900 text-white px-6 py-2 rounded-full text-sm font-bold uppercase tracking-widest mt-2">
        Conducto Regular
    </div>
</div>

{{-- Cuatro columnas --}}
<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5 mb-8">

    {{-- CONVIVENCIA --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden flex flex-col">
        <div class="bg-red-600 px-4 py-3 text-center">
            <h2 class="text-white font-black text-base uppercase tracking-wider">Convivencia</h2>
        </div>
        {{-- Rhino imagen --}}
        <div class="flex justify-center py-5 bg-red-50">
            <img src="{{ asset('images/rhino-convivencia.png') }}" alt="Convivencia" class="h-28 w-auto object-contain">
        </div>
        <div class="px-4 py-3 text-center">
            <p class="text-xs font-semibold text-gray-500 leading-tight">Normas, disciplina y convivencia escolar</p>
        </div>
        {{-- Flujograma --}}
        <div class="px-4 pb-5 flex-1 flex flex-col gap-1">
            @php
            $convivencia = [
                'Docente conocedor de la situación',
                'Director de grupo',
                'Coordinador de Convivencia',
                'Dirección General',
                'Comité Escolar de Convivencia / Consejo Disciplinario',
                'Autoridades pertinentes',
            ];
            @endphp
            @foreach($convivencia as $i => $paso)
                <div class="bg-red-50 border border-red-200 rounded-xl px-3 py-2 text-center">
                    <p class="text-xs font-semibold text-red-800 leading-tight">{{ $paso }}</p>
                </div>
                @if(!$loop->last)
                <div class="flex justify-center">
                    <svg class="w-4 h-4 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v9.586l2.293-2.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 13.586V4a1 1 0 011-1z" clip-rule="evenodd"/>
                    </svg>
                </div>
                @endif
            @endforeach
        </div>
    </div>

    {{-- ACADÉMICO --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden flex flex-col">
        <div class="bg-blue-700 px-4 py-3 text-center">
            <h2 class="text-white font-black text-base uppercase tracking-wider">Académico</h2>
        </div>
        {{-- Rhino imagen --}}
        <div class="flex justify-center py-5 bg-blue-50">
            <img src="{{ asset('images/rhino-academico.png') }}" alt="Académico" class="h-28 w-auto object-contain">
        </div>
        <div class="px-4 py-3 text-center">
            <p class="text-xs font-semibold text-gray-500 leading-tight">Tareas, calificaciones y apoyo escolar</p>
        </div>
        {{-- Flujograma --}}
        <div class="px-4 pb-5 flex-1 flex flex-col gap-1">
            @php
            $academico = [
                'Docente de la asignatura',
                'Coordinador Académica',
                'Comité académico',
                'Dirección General',
                'Comité de Evaluación y Promoción',
                'Consejo Directivo',
            ];
            @endphp
            @foreach($academico as $paso)
                <div class="bg-blue-50 border border-blue-200 rounded-xl px-3 py-2 text-center">
                    <p class="text-xs font-semibold text-blue-800 leading-tight">{{ $paso }}</p>
                </div>
                @if(!$loop->last)
                <div class="flex justify-center">
                    <svg class="w-4 h-4 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v9.586l2.293-2.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 13.586V4a1 1 0 011-1z" clip-rule="evenodd"/>
                    </svg>
                </div>
                @endif
            @endforeach
        </div>
    </div>

    {{-- ADMINISTRATIVO --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden flex flex-col">
        <div class="bg-gray-700 px-4 py-3 text-center">
            <h2 class="text-white font-black text-base uppercase tracking-wider">Administrativo</h2>
        </div>
        {{-- Rhino imagen --}}
        <div class="flex justify-center py-5 bg-gray-100">
            <img src="{{ asset('images/rhino-administrativo.png') }}" alt="Administrativo" class="h-28 w-auto object-contain">
        </div>
        <div class="px-4 py-3 text-center">
            <p class="text-xs font-semibold text-gray-500 leading-tight">Pagos, trámites y certificados</p>
        </div>
        {{-- Flujograma --}}
        <div class="px-4 pb-5 flex-1 flex flex-col gap-1">
            @php
            $administrativo = [
                'Área administrativa',
                'Dirección General',
                'Autoridades pertinentes',
            ];
            @endphp
            @foreach($administrativo as $paso)
                <div class="bg-gray-100 border border-gray-300 rounded-xl px-3 py-2 text-center">
                    <p class="text-xs font-semibold text-gray-700 leading-tight">{{ $paso }}</p>
                </div>
                @if(!$loop->last)
                <div class="flex justify-center">
                    <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v9.586l2.293-2.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 13.586V4a1 1 0 011-1z" clip-rule="evenodd"/>
                    </svg>
                </div>
                @endif
            @endforeach
        </div>
    </div>

    {{-- AUSENCIAS ESCOLARES --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden flex flex-col">
        <div class="bg-red-800 px-4 py-3 text-center">
            <h2 class="text-white font-black text-base uppercase tracking-wider">Ausencias Escolares</h2>
        </div>
        {{-- Rhino imagen --}}
        <div class="flex justify-center py-5 bg-red-50">
            <img src="{{ asset('images/rhino-ausencias.png') }}" alt="Ausencias Escolares" class="h-28 w-auto object-contain">
        </div>
        <div class="px-4 py-3 text-center">
            <p class="text-xs font-semibold text-gray-500 leading-tight">Permisos e incapacidades</p>
        </div>
        {{-- Flujograma --}}
        <div class="px-4 pb-5 flex-1 flex flex-col gap-1">
            @php
            $ausencias = [
                'Coordinador de Convivencia',
                'Dirección General',
            ];
            @endphp
            @foreach($ausencias as $paso)
                <div class="bg-red-50 border border-red-200 rounded-xl px-3 py-2 text-center">
                    <p class="text-xs font-semibold text-red-800 leading-tight">{{ $paso }}</p>
                </div>
                @if(!$loop->last)
                <div class="flex justify-center">
                    <svg class="w-4 h-4 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v9.586l2.293-2.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 13.586V4a1 1 0 011-1z" clip-rule="evenodd"/>
                    </svg>
                </div>
                @endif
            @endforeach
        </div>
    </div>

</div>

{{-- Banner de formulario de PQRS --}}
<div class="bg-gradient-to-r from-blue-900 to-blue-700 rounded-2xl p-6 flex flex-col sm:flex-row items-center justify-between gap-4 shadow-md">
    <div class="flex items-center gap-4">
        <div class="w-12 h-12 bg-white bg-opacity-20 rounded-2xl flex items-center justify-center text-2xl shrink-0">
            📋
        </div>
        <div>
            <h3 class="text-white font-bold text-base leading-tight">¿Tienes una solicitud, queja o sugerencia?</h3>
            <p class="text-blue-200 text-sm mt-0.5">Usa nuestro formulario oficial para gestionar tu caso de forma oportuna.</p>
        </div>
    </div>
    <a href="https://forms.gle/SB1VQsf2TpV4Q1Qn8"
       target="_blank"
       rel="noopener noreferrer"
       class="shrink-0 inline-flex items-center gap-2 bg-white text-blue-900 font-bold text-sm px-5 py-3 rounded-xl hover:bg-blue-50 transition shadow-sm">
        Ir al formulario
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
        </svg>
    </a>
</div>

@endsection
