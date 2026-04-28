@extends('layouts.app-sidebar')

@section('header', 'Diligenciamiento PIAR')

@section('slot')

<div class="space-y-5">

{{-- Título --}}
<div class="bg-blue-900 text-white rounded-xl px-6 py-4">
    <h1 class="text-lg font-bold tracking-wide uppercase">Diligenciamiento PIAR</h1>
    <p class="text-blue-200 text-xs mt-1">
        Caracterización y ajustes razonables por estudiante.
        @if($esDirector)
            Director del grupo <strong>{{ $dirInfo->DIR_GRUPO }}</strong>.
        @endif
    </p>
</div>

@php
    $etapaCaract      = $etapasControl['caract']      ?? 'cerrado';
    $etapaAjustes     = $etapasControl['ajustes']     ?? 'cerrado';
    $etapaPlanCasero  = $etapasControl['plan_casero'] ?? 'cerrado';

    // Helper para badge del estado del registro (aprobado / revision / pendiente)
    function badgeEstado(string $estado): string {
        return match($estado) {
            'aprobado'          => '<span class="inline-flex items-center gap-1 bg-green-100 text-green-700 text-xs px-2 py-0.5 rounded-full font-semibold">✓ Aprobado</span>',
            'revision'          => '<span class="inline-flex items-center gap-1 bg-blue-100 text-blue-700 text-xs px-2 py-0.5 rounded-full font-semibold">👁 En revisión</span>',
            'con_observaciones' => '<span class="inline-flex items-center gap-1 bg-orange-100 text-orange-700 text-xs px-2 py-0.5 rounded-full font-semibold">💬 Observaciones</span>',
            default             => '<span class="inline-flex items-center gap-1 bg-yellow-100 text-yellow-700 text-xs px-2 py-0.5 rounded-full font-semibold">Pendiente</span>',
        };
    }

    // Helper para badge del estado de la etapa global
    function badgeEtapa(string $estado, string $label): string {
        [$bg, $text, $icono] = match($estado) {
            'abierto'    => ['bg-green-100',  'text-green-800',  '🟢'],
            'revision'   => ['bg-blue-100',   'text-blue-800',   '👁'],
            'finalizado' => ['bg-purple-100', 'text-purple-800', '✅'],
            default      => ['bg-gray-100',   'text-gray-500',   '🔒'],
        };
        $txt = match($estado) {
            'abierto'    => "Abierta — puedes diligenciar y entregar",
            'revision'   => "En revisión — ya no se aceptan nuevas entregas",
            'finalizado' => "Finalizada",
            default      => "Cerrada — aún no está habilitada",
        };
        return "<div class=\"rounded-xl border px-4 py-3 {$bg}\">
            <p class=\"text-xs font-bold {$text}\">{$icono} {$label} · P{PERIODO}</p>
            <p class=\"text-xs {$text} opacity-75 mt-0.5\">{$txt}</p>
        </div>";
    }
@endphp

{{-- Estado de etapas del período actual --}}
<div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
    @php
        $etiquetas = ['cerrado'=>'Cerrada','abierto'=>'Abierta','revision'=>'En revisión','finalizado'=>'Finalizada'];
        $etapaItems = [
            ['etapa' => $etapaCaract,     'label' => 'Caracterizaciones'],
            ['etapa' => $etapaAjustes,    'label' => 'Ajustes razonables'],
            ['etapa' => $etapaPlanCasero, 'label' => 'Plan Casero'],
        ];
    @endphp
    @foreach($etapaItems as $item)
    @php
        [$bg, $text] = match($item['etapa']) {
            'abierto'    => ['bg-green-50 border-green-300',  'text-green-800'],
            'revision'   => ['bg-blue-50 border-blue-300',    'text-blue-800'],
            'finalizado' => ['bg-purple-50 border-purple-300','text-purple-800'],
            default      => ['bg-gray-50 border-gray-200',    'text-gray-500'],
        };
        $desc = match($item['etapa']) {
            'abierto'    => 'Puedes diligenciar y marcar como entregado.',
            'revision'   => 'En revisión — ya no se aceptan nuevas entregas.',
            'finalizado' => 'Etapa finalizada y aprobada.',
            default      => 'Aún no está habilitada para este período.',
        };
    @endphp
    <div class="rounded-xl border px-4 py-3 {{ $bg }}">
        <p class="text-xs font-bold {{ $text }}">
            {{ match($item['etapa']) { 'abierto'=>'🟢','revision'=>'👁','finalizado'=>'✅',default=>'🔒' } }}
            {{ $item['label'] }}
        </p>
        <p class="text-xs {{ $text }} opacity-80 mt-0.5">{{ $desc }}</p>
    </div>
    @endforeach
</div>

{{-- Mensaje de aprobación --}}
@if(session('aprobado'))
<div class="p-3 bg-green-100 text-green-800 rounded-xl text-sm font-semibold">
    ✅ {{ session('aprobado') }}
</div>
@endif

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
    $dirInfo2 = isset($caractDirGuardadas[$codigo]) ? $caractDirGuardadas[$codigo] : null;
    $dirOk    = $dirInfo2 !== null;
    $dirEstado = $dirInfo2 ? ($dirInfo2->ESTADO ?? 'pendiente') : 'pendiente';
@endphp

<div class="bg-white rounded-xl shadow overflow-hidden">

    {{-- Cabecera del estudiante --}}
    <div class="bg-gray-700 text-white px-5 py-3 grid grid-cols-[auto_1fr_auto] items-center gap-x-4">
        {{-- Código --}}
        <span class="font-mono text-gray-400 text-xs whitespace-nowrap">{{ $codigo }}</span>
        {{-- Nombre + grado + diagnóstico --}}
        <div class="min-w-0">
            <span class="font-bold text-sm">{{ $nombre }}</span>
            <span class="ml-2 text-gray-300 text-xs">{{ $est->GRADO }} – {{ $est->CURSO }}</span>
            @if($est->DIAGNOSTICO)
                <span class="ml-2 text-gray-400 text-xs italic truncate">· {{ $est->DIAGNOSTICO }}</span>
            @endif
        </div>
        {{-- Espacio derecho vacío (para uniformidad) --}}
        <div></div>
    </div>

    <table class="w-full text-sm table-fixed">
        <thead class="bg-gray-50 text-gray-400 uppercase text-xs">
            <tr>
                <th class="px-4 py-2 text-left w-1/4">Módulo / Materia</th>
                <th class="px-4 py-2 text-center">Caracterización</th>
                <th class="px-4 py-2 text-center">Ajustes y Evaluación</th>
                <th class="px-4 py-2 text-center">Plan Casero</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">

            {{-- Fila dirección de grupo --}}
            @if($esMiDir)
            <tr class="hover:bg-blue-50 bg-blue-50/40">
                <td class="px-4 py-2.5 font-semibold text-blue-800">
                    Dirección de grupo
                </td>
                <td class="px-4 py-3 text-center">
                    <div class="flex flex-wrap items-center justify-center gap-2">
                        {!! badgeEstado($dirEstado, $puedeAprobar) !!}
                        <a href="{{ route('piar.caract.dir.form', $codigo) }}"
                           class="bg-blue-700 hover:bg-blue-600 text-white text-xs px-3 py-1 rounded-lg transition">
                            {{ $dirOk ? 'Editar' : 'Diligenciar' }}
                        </a>
                    </div>
                </td>
                <td class="px-4 py-2.5 text-center text-gray-300 text-xs">—</td>
                <td class="px-4 py-2.5 text-center text-gray-300 text-xs">—</td>
            </tr>
            @endif

            {{-- Mensaje si el director no dicta materias --}}
            @if($esMiDir && $materias->whereNotNull('CODIGO_MAT')->isEmpty())
            <tr>
                <td colspan="4" class="px-4 py-3 text-xs text-gray-400 italic text-center">
                    No dictas materias a este estudiante — solo aplica la caracterización de dirección de grupo.
                </td>
            </tr>
            @endif

            {{-- Filas por materia --}}
            @foreach($materias as $mat)
            @if(!$mat->CODIGO_MAT) @continue @endif
            @php
                $cEstado  = $mat->CARACT_MAT_ESTADO  ?? 'pendiente';
                $aEstado  = $mat->AJUSTES_ESTADO     ?? 'pendiente';
                $pcEstado = $mat->PLAN_CASERO_ESTADO ?? 'pendiente';
                $pcOk     = (int)($mat->PLAN_CASERO_OK ?? 0) === 1;
            @endphp
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-4 text-gray-800 font-medium">{{ $mat->NOMBRE_MAT }}</td>

                {{-- Caracterización --}}
                <td class="px-4 py-4 text-center">
                    <div class="flex flex-wrap items-center justify-center gap-2">
                        {!! badgeEstado($cEstado, $puedeAprobar) !!}
                        <a href="{{ route('piar.caract.mat.form', [$codigo, $mat->CODIGO_MAT]) }}"
                           class="bg-blue-700 hover:bg-blue-600 text-white text-xs px-3 py-1 rounded-lg transition">
                            {{ $mat->CARACT_MAT_OK ? 'Editar' : 'Diligenciar' }}
                        </a>
                    </div>
                </td>

                {{-- Ajustes y Evaluación --}}
                <td class="px-4 py-4 text-center">
                    <div class="flex flex-wrap items-center justify-center gap-2">
                        {!! badgeEstado($aEstado, $puedeAprobar) !!}
                        <a href="{{ route('piar.anexo2.form', [$codigo, $mat->CODIGO_MAT]) }}"
                           class="bg-green-700 hover:bg-green-600 text-white text-xs px-3 py-1 rounded-lg transition">
                            {{ $mat->AJUSTES_OK ? 'Editar' : 'Diligenciar' }}
                        </a>
                    </div>
                </td>

                {{-- Plan Casero --}}
                <td class="px-4 py-4 text-center">
                    @if($etapaPlanCasero === 'cerrado')
                        <span class="inline-flex items-center gap-1 bg-gray-100 text-gray-400 text-xs px-2 py-1 rounded-full">🔒 Cerrado</span>
                    @else
                        <div class="flex flex-wrap items-center justify-center gap-2">
                            {!! badgeEstado($pcEstado, $puedeAprobar) !!}
                            <a href="{{ route('piar.plan_casero.form', [$codigo, $mat->CODIGO_MAT]) }}"
                               class="bg-indigo-700 hover:bg-indigo-600 text-white text-xs px-3 py-1 rounded-lg transition">
                                {{ $pcOk ? 'Editar' : 'Diligenciar' }}
                            </a>
                        </div>
                    @endif
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
