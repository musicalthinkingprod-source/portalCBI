@extends('layouts.app-sidebar')

@section('header', 'PIAR – Informe de diligenciamiento')

@section('slot')

<div class="max-w-7xl mx-auto space-y-4">

    {{-- Encabezado --}}
    <div class="bg-white rounded-xl shadow p-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h2 class="text-lg font-bold text-blue-900">Informe Anexo 2</h2>
            <p class="text-sm text-gray-500 mt-0.5">Estado del Anexo 1, Anexo 2 y caracterizaciones por estudiante.</p>
        </div>
        <div class="flex gap-4 text-sm">
            <span class="inline-flex items-center gap-1.5">
                <span class="w-3 h-3 rounded-full bg-green-500 inline-block"></span> Diligenciado
            </span>
            <span class="inline-flex items-center gap-1.5">
                <span class="w-3 h-3 rounded-full bg-yellow-400 inline-block"></span> Pendiente
            </span>
        </div>
    </div>

    @php
        $totalEstudiantes    = $estudiantes->count();
        $totalAnexo1Ok       = $estudiantes->where('ANEXO1_OK', 1)->count();
        $totalCaractDirOk    = $caractDirs->count();
        $totalMateriasPend   = 0;
        $totalCaractMatsPend = 0;
        foreach($estudiantes as $est) {
            $mats = $asignaciones[$est->CURSO] ?? collect();
            foreach($mats as $mat) {
                if (!isset($piarMats[$est->CODIGO][$mat->CODIGO_MAT]))   $totalMateriasPend++;
                if (!isset($caractMats[$est->CODIGO][$mat->CODIGO_MAT])) $totalCaractMatsPend++;
            }
        }
    @endphp

    {{-- Tarjetas resumen --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div class="bg-white rounded-xl shadow p-4 text-center">
            <p class="text-3xl font-bold text-blue-900">{{ $totalEstudiantes }}</p>
            <p class="text-xs text-gray-500 mt-1 uppercase tracking-wide">Estudiantes con PIAR</p>
        </div>
        <div class="bg-white rounded-xl shadow p-4 text-center">
            <p class="text-3xl font-bold {{ $totalAnexo1Ok === $totalEstudiantes ? 'text-green-600' : 'text-yellow-500' }}">{{ $totalAnexo1Ok }}</p>
            <p class="text-xs text-gray-500 mt-1 uppercase tracking-wide">Anexo 1 completo</p>
        </div>
        <div class="bg-white rounded-xl shadow p-4 text-center">
            <p class="text-3xl font-bold {{ $totalMateriasPend === 0 ? 'text-green-600' : 'text-red-500' }}">{{ $totalMateriasPend }}</p>
            <p class="text-xs text-gray-500 mt-1 uppercase tracking-wide">Ajustes pendientes</p>
        </div>
        <div class="bg-white rounded-xl shadow p-4 text-center">
            <p class="text-3xl font-bold {{ $totalCaractMatsPend === 0 ? 'text-green-600' : 'text-red-500' }}">{{ $totalCaractMatsPend }}</p>
            <p class="text-xs text-gray-500 mt-1 uppercase tracking-wide">Caract. materia pend.</p>
        </div>
    </div>

    @if($estudiantes->isEmpty())
        <div class="bg-white rounded-xl shadow p-8 text-center text-gray-400 text-sm">
            No hay estudiantes con PIAR registrado.
        </div>
    @else

    @php $cursoActual = null; @endphp

    @foreach($estudiantes as $est)
        @php
            $materias  = $asignaciones[$est->CURSO] ?? collect();
            $matsPiar  = $piarMats[$est->CODIGO]    ?? collect();
            $cMats     = $caractMats[$est->CODIGO]  ?? collect();
            $cDirs     = $caractDirs[$est->CODIGO]  ?? collect();
            $totalMats = $materias->count();
            $matsOk    = $materias->filter(fn($m) => isset($matsPiar[$m->CODIGO_MAT]))->count();
            $pctAnexo2 = $totalMats > 0 ? round($matsOk / $totalMats * 100) : 0;
        @endphp

        @if($cursoActual !== $est->CURSO)
            @php $cursoActual = $est->CURSO; @endphp
            <div class="mt-4">
                <h3 class="text-xs font-bold text-blue-400 uppercase tracking-widest px-1 mb-2">
                    Grado {{ $est->GRADO }} – Curso {{ $est->CURSO }}
                </h3>
            </div>
        @endif

        <div class="bg-white rounded-xl shadow overflow-hidden">

            {{-- Cabecera del estudiante --}}
            <div class="flex flex-col sm:flex-row sm:items-center gap-2 px-5 py-3 border-b border-gray-100">
                <div class="flex-1">
                    <span class="font-mono text-xs text-gray-400 mr-2">{{ $est->CODIGO }}</span>
                    <span class="font-semibold text-gray-800 text-sm">
                        {{ $est->APELLIDO1 }} {{ $est->APELLIDO2 }}, {{ $est->NOMBRE1 }} {{ $est->NOMBRE2 }}
                    </span>
                    @if($est->DIAGNOSTICO)
                        <span class="ml-2 text-xs text-gray-400">· {{ $est->DIAGNOSTICO }}</span>
                    @endif
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-xs text-gray-400 font-semibold uppercase">Anexo 1:</span>
                    @if($est->ANEXO1_OK)
                        <span class="inline-flex items-center gap-1 bg-green-100 text-green-700 text-xs px-2 py-0.5 rounded-full font-semibold">✓ Completo</span>
                    @else
                        <span class="inline-flex items-center gap-1 bg-yellow-100 text-yellow-700 text-xs px-2 py-0.5 rounded-full font-semibold">Pendiente</span>
                    @endif
                    <a href="{{ route('piar.crear', $est->CODIGO) }}" class="text-xs text-blue-600 hover:underline ml-1">Ver</a>
                </div>
                <a href="{{ route('piar.anexo2.imprimir.est', $est->CODIGO) }}" target="_blank"
                   class="bg-blue-800 hover:bg-blue-700 text-white text-xs font-semibold px-3 py-1.5 rounded-lg transition whitespace-nowrap">
                    🖨️ Imprimir Anexo 2
                </a>
                <div class="flex items-center gap-2 min-w-[140px]">
                    <span class="text-xs text-gray-400 font-semibold uppercase">Ajustes:</span>
                    <div class="flex-1 bg-gray-200 rounded-full h-2 w-20">
                        <div class="h-2 rounded-full {{ $pctAnexo2 === 100 ? 'bg-green-500' : ($pctAnexo2 > 0 ? 'bg-yellow-400' : 'bg-gray-300') }}"
                             style="width: {{ $pctAnexo2 }}%"></div>
                    </div>
                    <span class="text-xs text-gray-500">{{ $matsOk }}/{{ $totalMats }}</span>
                </div>
            </div>

            {{-- Caracterización por director de grupo --}}
            @if($cDirs->isNotEmpty())
                @foreach($cDirs as $cDir)
                <div class="border-b border-gray-100">
                    <div class="flex items-center gap-3 px-5 py-2 bg-blue-50">
                        <span class="w-2.5 h-2.5 rounded-full flex-shrink-0 bg-blue-500"></span>
                        <span class="font-semibold text-blue-800 text-sm flex-1">Caracterización – Dirección de grupo</span>
                        <span class="text-xs text-gray-400">{{ $cDir->NOMBRE_DOC }}</span>
                        <span class="inline-flex items-center gap-1 bg-green-100 text-green-700 text-xs px-2 py-0.5 rounded-full font-semibold">✓ Guardado</span>
                        <a href="{{ route('piar.caract.dir.form', $est->CODIGO) }}" class="text-xs text-blue-600 hover:underline">Ver / Editar</a>
                    </div>
                </div>
                @endforeach
            @else
                <div class="flex items-center gap-3 px-5 py-2 bg-blue-50/30 border-b border-gray-100">
                    <span class="w-2.5 h-2.5 rounded-full flex-shrink-0 bg-yellow-400"></span>
                    <span class="font-semibold text-blue-800 text-sm flex-1">Caracterización – Dirección de grupo</span>
                    <span class="inline-flex items-center gap-1 bg-yellow-100 text-yellow-700 text-xs px-2 py-0.5 rounded-full font-semibold">Pendiente</span>
                </div>
            @endif

            {{-- Materias --}}
            @if($materias->isNotEmpty())
            <div class="divide-y divide-gray-50">
                @foreach($materias as $mat)
                @php
                    $ajusteOk  = isset($matsPiar[$mat->CODIGO_MAT]);
                    $caractOk  = isset($cMats[$mat->CODIGO_MAT]);
                    $caractTxt = $cMats[$mat->CODIGO_MAT]->CARACTERIZACION ?? null;
                @endphp
                <div class="{{ !$ajusteOk || !$caractOk ? 'bg-yellow-50' : 'bg-white' }}">
                    {{-- Fila resumen de la materia --}}
                    <div class="flex items-center px-5 py-3 text-sm gap-0">
                        {{-- Nombre materia + docente --}}
                        <div class="flex-1 min-w-0 pr-6">
                            <span class="font-medium text-gray-800 block">{{ $mat->NOMBRE_MAT }}</span>
                            <span class="text-xs text-gray-400">{{ $mat->NOMBRE_DOC }}</span>
                        </div>

                        {{-- Ajustes --}}
                        <div class="flex items-center gap-2 border-l border-gray-200 px-6">
                            <span class="text-xs text-gray-400 font-semibold uppercase whitespace-nowrap">Ajustes</span>
                            @if($ajusteOk)
                                <span class="inline-flex items-center gap-1 bg-green-100 text-green-700 text-xs px-2 py-0.5 rounded-full font-semibold">✓ Guardado</span>
                                <a href="{{ route('piar.anexo2.form', [$est->CODIGO, $mat->CODIGO_MAT]) }}" class="text-xs text-blue-600 hover:underline">Ver</a>
                            @else
                                <span class="inline-flex items-center gap-1 bg-yellow-100 text-yellow-700 text-xs px-2 py-0.5 rounded-full font-semibold">Pendiente</span>
                            @endif
                        </div>

                        {{-- Caracterización --}}
                        <div class="flex items-center gap-2 border-l border-gray-200 pl-6">
                            <span class="text-xs text-gray-400 font-semibold uppercase whitespace-nowrap">Caract.</span>
                            @if($caractOk)
                                <span class="inline-flex items-center gap-1 bg-green-100 text-green-700 text-xs px-2 py-0.5 rounded-full font-semibold">✓ Guardado</span>
                                <a href="{{ route('piar.caract.mat.form', [$est->CODIGO, $mat->CODIGO_MAT]) }}" class="text-xs text-blue-600 hover:underline">Ver</a>
                            @else
                                <span class="inline-flex items-center gap-1 bg-yellow-100 text-yellow-700 text-xs px-2 py-0.5 rounded-full font-semibold">Pendiente</span>
                            @endif
                        </div>
                    </div>

                </div>
                @endforeach
            </div>
            @else
            <div class="px-5 py-2 text-xs text-gray-400 italic">Sin materias asignadas en este curso.</div>
            @endif

        </div>
    @endforeach
    @endif

</div>

@endsection
