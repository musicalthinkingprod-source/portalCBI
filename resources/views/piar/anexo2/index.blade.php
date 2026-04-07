@extends('layouts.app-sidebar')

@section('header', 'PIAR – Anexo 2 y Caracterizaciones')

@section('slot')

<div class="space-y-5">

{{-- Título --}}
<div class="bg-blue-900 text-white rounded-xl px-6 py-4">
    <h1 class="text-lg font-bold tracking-wide uppercase">PIAR Anexo 2 y Caracterizaciones</h1>
    <p class="text-blue-200 text-xs mt-1">
        Caracterización y ajustes razonables por estudiante.
        @if($esDirector)
            Director del grupo <strong>{{ $dirInfo->DIR_GRUPO }}</strong>.
        @endif
    </p>
</div>

@if($filas->isEmpty())
    <div class="bg-white rounded-xl shadow p-6 text-center text-gray-400 text-sm">
        No hay estudiantes con PIAR asignados a tu cuenta.
    </div>
@else

@foreach($filas as $codigo => $materias)
@php
    $est      = $materias->first();
    $nombre   = trim($est->APELLIDO1 . ' ' . $est->APELLIDO2) . ', ' . trim($est->NOMBRE1 . ' ' . $est->NOMBRE2);
    $esMiDir  = !$esDocente || ($esDirector && $dirInfo->DIR_GRUPO === $est->CURSO);
    $dirOk    = isset($caractDirGuardadas[$codigo]);
@endphp

<div class="bg-white rounded-xl shadow overflow-hidden">

    {{-- Cabecera del estudiante --}}
    <div class="bg-gray-700 text-white px-5 py-3 flex flex-wrap items-center justify-between gap-2">
        <div>
            <span class="text-gray-400 text-xs font-mono mr-3">{{ $codigo }}</span>
            <span class="font-bold text-base">{{ $nombre }}</span>
            <span class="ml-3 text-gray-300 text-xs">{{ $est->GRADO }} – {{ $est->CURSO }}</span>
        </div>
        @if($est->DIAGNOSTICO)
            <span class="text-xs text-gray-300 italic">{{ $est->DIAGNOSTICO }}</span>
        @endif
    </div>

    <table class="min-w-full text-sm">
        <thead class="bg-gray-50 text-gray-400 uppercase text-xs">
            <tr>
                <th class="px-4 py-2 text-left">Módulo / Materia</th>
                <th class="px-4 py-2 text-center whitespace-nowrap">Caracterización</th>
                <th class="px-4 py-2 text-center whitespace-nowrap">Ajustes por período</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">

            {{-- Fila dirección de grupo (solo si soy director de este curso) --}}
            @if($esMiDir)
            <tr class="hover:bg-blue-50 bg-blue-50/40">
                <td class="px-4 py-2.5 font-semibold text-blue-800">
                    Dirección de grupo
                </td>
                <td class="px-4 py-4 text-center">
                    @if($dirOk)
                        <span class="inline-flex items-center gap-1 bg-green-100 text-green-700 text-xs px-2 py-0.5 rounded-full font-semibold">✓ Guardado</span>
                    @else
                        <span class="inline-flex items-center gap-1 bg-yellow-100 text-yellow-700 text-xs px-2 py-0.5 rounded-full font-semibold">Pendiente</span>
                    @endif
                    <a href="{{ route('piar.caract.dir.form', $codigo) }}"
                       class="ml-2 bg-blue-700 hover:bg-blue-600 text-white text-xs px-3 py-1 rounded-lg transition">
                        {{ $dirOk ? 'Editar' : 'Diligenciar' }}
                    </a>
                </td>
                <td class="px-4 py-2.5 text-center text-gray-300 text-xs">—</td>
            </tr>
            @endif

            {{-- Filas por materia --}}
            @foreach($materias as $mat)
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-4 text-gray-800 font-medium">{{ $mat->NOMBRE_MAT }}</td>
                <td class="px-4 py-4 text-center">
                    @if($mat->CARACT_MAT_OK)
                        <span class="inline-flex items-center gap-1 bg-green-100 text-green-700 text-xs px-2 py-0.5 rounded-full font-semibold">✓ Guardado</span>
                    @else
                        <span class="inline-flex items-center gap-1 bg-yellow-100 text-yellow-700 text-xs px-2 py-0.5 rounded-full font-semibold">Pendiente</span>
                    @endif
                    <a href="{{ route('piar.caract.mat.form', [$codigo, $mat->CODIGO_MAT]) }}"
                       class="ml-2 bg-blue-700 hover:bg-blue-600 text-white text-xs px-3 py-1 rounded-lg transition">
                        {{ $mat->CARACT_MAT_OK ? 'Editar' : 'Diligenciar' }}
                    </a>
                </td>
                <td class="px-4 py-4 text-center">
                    @if($mat->AJUSTES_OK)
                        <span class="inline-flex items-center gap-1 bg-green-100 text-green-700 text-xs px-2 py-0.5 rounded-full font-semibold">✓ Guardado</span>
                    @else
                        <span class="inline-flex items-center gap-1 bg-yellow-100 text-yellow-700 text-xs px-2 py-0.5 rounded-full font-semibold">Pendiente</span>
                    @endif
                    <a href="{{ route('piar.anexo2.form', [$codigo, $mat->CODIGO_MAT]) }}"
                       class="ml-2 bg-green-700 hover:bg-green-600 text-white text-xs px-3 py-1 rounded-lg transition">
                        {{ $mat->AJUSTES_OK ? 'Editar' : 'Diligenciar' }}
                    </a>
                </td>
            </tr>
            @endforeach

        </tbody>
    </table>
</div>
@endforeach

@endif

</div>
@endsection
