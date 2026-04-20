@extends('layouts.app-sidebar')

@section('header', 'PIAR – Informe de diligenciamiento')

@section('slot')

@php
$esOriSuperAd = auth()->user()->PROFILE === 'SuperAd' || auth()->user()->PROFILE === 'Piar' || str_starts_with(auth()->user()->PROFILE, 'Ori');

// ── Helper: badge de estado ───────────────────────────────────────────────
if (!function_exists('estadoBadge')) {
    function estadoBadge(string $estado): string {
        return match($estado) {
            'aprobado'         => '<span class="inline-flex items-center gap-1 bg-green-100 text-green-700 text-xs px-2 py-0.5 rounded-full font-semibold whitespace-nowrap">✓ Aprobado</span>',
            'revision'         => '<span class="inline-flex items-center gap-1 bg-blue-100 text-blue-700 text-xs px-2 py-0.5 rounded-full font-semibold whitespace-nowrap">👁 En revisión</span>',
            'con_observaciones'=> '<span class="inline-flex items-center gap-1 bg-orange-100 text-orange-700 text-xs px-2 py-0.5 rounded-full font-semibold whitespace-nowrap">💬 Con observaciones</span>',
            'cerrado'          => '<span class="inline-flex items-center gap-1 bg-gray-200 text-gray-600 text-xs px-2 py-0.5 rounded-full font-semibold whitespace-nowrap">🔒 Cerrado</span>',
            default            => '<span class="inline-flex items-center gap-1 bg-yellow-100 text-yellow-700 text-xs px-2 py-0.5 rounded-full font-semibold whitespace-nowrap">Pendiente</span>',
        };
    }
}

// ── Helper: estado efectivo (cerrado enmascara pendiente) ─────────────────
if (!function_exists('estadoEfectivo')) {
    function estadoEfectivo(string $estado, string $etapa): string {
        if ($etapa === 'cerrado' && $estado === 'pendiente') return 'cerrado';
        return $estado;
    }
}

// ── Totales resumen ───────────────────────────────────────────────────────
$totalEstudiantes   = $estudiantes->count();
$totalAnexo1Ok      = $estudiantes->where('ANEXO1_OK', 1)->count();
$cuentaEstados = ['pendiente' => 0, 'revision' => 0, 'con_observaciones' => 0, 'aprobado' => 0, 'cerrado' => 0];

foreach ($estudiantes as $est) {
    $mats   = $asignaciones[$est->CODIGO] ?? collect();
    $cDirs  = $caractDirs[$est->CODIGO] ?? collect();

    // Estado caract. director
    $cDirReg   = $cDirs->first();
    $dirEstado = estadoEfectivo($cDirReg ? ($cDirReg->ESTADO ?? 'pendiente') : 'pendiente', $estadosEtapa['caract']);
    $cuentaEstados[$dirEstado]++;

    foreach ($mats as $mat) {
        $cmReg = ($caractMats[$est->CODIGO] ?? collect())[$mat->CODIGO_MAT] ?? null;
        $pmReg = ($piarMats[$est->CODIGO]   ?? collect())[$mat->CODIGO_MAT] ?? null;

        $cmEstado = estadoEfectivo($cmReg ? ($cmReg->ESTADO ?? 'pendiente') : 'pendiente', $estadosEtapa['caract']);
        $pmEstado = estadoEfectivo($pmReg ? ($pmReg->ESTADO ?? 'pendiente') : 'pendiente', $estadosEtapa['ajustes']);
        $pcEstado = estadoEfectivo($pmReg ? ($pmReg->ESTADO_CASERO ?? 'pendiente') : 'pendiente', $estadosEtapa['plan_casero']);

        $cuentaEstados[$cmEstado]++;
        $cuentaEstados[$pmEstado]++;
        $cuentaEstados[$pcEstado]++;
    }
}

$totalItems    = array_sum($cuentaEstados);
// Progreso: solo cuenta los ítems de etapas abiertas (excluye los 'cerrado')
$itemsActivos  = $totalItems - $cuentaEstados['cerrado'];
$pctAprobado   = $itemsActivos > 0 ? round($cuentaEstados['aprobado']  / $itemsActivos * 100) : 0;
$pctRevision   = $itemsActivos > 0 ? round(($cuentaEstados['revision'] + $cuentaEstados['con_observaciones']) / $itemsActivos * 100) : 0;
$pctPendiente  = max(0, 100 - $pctAprobado - $pctRevision);
@endphp

<div class="max-w-7xl mx-auto space-y-4">

{{-- Encabezado --}}
<div class="bg-blue-900 text-white rounded-xl px-6 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
    <div>
        <h2 class="text-lg font-bold tracking-wide uppercase">Informe PIAR</h2>
        <p class="text-blue-200 text-xs mt-0.5">
            Estado de caracterizaciones y ajustes razonables. Período actual:
            <span class="font-semibold text-white">P{{ $periodoActual }}</span>
        </p>
    </div>
    <div class="flex flex-wrap gap-3 text-xs">
        <span class="inline-flex items-center gap-1.5 bg-white/10 rounded-full px-3 py-1">
            <span class="w-2 h-2 rounded-full bg-green-400 inline-block"></span> Aprobado
        </span>
        <span class="inline-flex items-center gap-1.5 bg-white/10 rounded-full px-3 py-1">
            <span class="w-2 h-2 rounded-full bg-blue-300 inline-block"></span> En revisión
        </span>
        <span class="inline-flex items-center gap-1.5 bg-white/10 rounded-full px-3 py-1">
            <span class="w-2 h-2 rounded-full bg-yellow-300 inline-block"></span> Pendiente
        </span>
    </div>
</div>

{{-- Tarjetas resumen --}}
<div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
    <div class="bg-white rounded-xl shadow p-4 text-center">
        <p class="text-3xl font-bold text-blue-900">{{ $totalEstudiantes }}</p>
        <p class="text-xs text-gray-500 mt-1 uppercase tracking-wide">Estudiantes con PIAR</p>
    </div>
    <div class="bg-white rounded-xl shadow p-4 text-center">
        <p class="text-3xl font-bold {{ $cuentaEstados['aprobado'] === $totalItems ? 'text-green-600' : 'text-green-500' }}">
            {{ $cuentaEstados['aprobado'] }}
        </p>
        <p class="text-xs text-gray-500 mt-1 uppercase tracking-wide">Aprobados</p>
    </div>
    <div class="bg-white rounded-xl shadow p-4 text-center">
        <p class="text-3xl font-bold text-blue-600">{{ $cuentaEstados['revision'] + $cuentaEstados['con_observaciones'] }}</p>
        <p class="text-xs text-gray-500 mt-1 uppercase tracking-wide">En revisión</p>
    </div>
    <div class="bg-white rounded-xl shadow p-4 text-center">
        <p class="text-3xl font-bold {{ $cuentaEstados['pendiente'] === 0 ? 'text-green-600' : 'text-yellow-500' }}">
            {{ $cuentaEstados['pendiente'] }}
        </p>
        <p class="text-xs text-gray-500 mt-1 uppercase tracking-wide">Pendientes</p>
    </div>
</div>

{{-- Barra de progreso global --}}
<div class="bg-white rounded-xl shadow px-5 py-4">
    <div class="flex items-center justify-between text-xs text-gray-500 mb-2">
        <span class="font-semibold uppercase tracking-wide">Progreso general de aprobación</span>
        <span>
            {{ $cuentaEstados['aprobado'] }} / {{ $itemsActivos }} ítems aprobados
            @if($cuentaEstados['cerrado'] > 0)
                <span class="text-gray-400">· {{ $cuentaEstados['cerrado'] }} en etapas cerradas (excluidos)</span>
            @endif
        </span>
    </div>
    <div class="w-full h-3 bg-gray-100 rounded-full overflow-hidden flex">
        <div class="h-3 bg-green-500 transition-all" style="width: {{ $pctAprobado }}%"></div>
        <div class="h-3 bg-blue-400 transition-all"  style="width: {{ $pctRevision }}%"></div>
        <div class="h-3 bg-yellow-300 transition-all" style="width: {{ $pctPendiente }}%"></div>
    </div>
    <div class="flex gap-4 text-xs text-gray-400 mt-1.5">
        <span>{{ $pctAprobado }}% aprobado</span>
        <span>{{ $pctRevision }}% en revisión</span>
        <span>{{ $pctPendiente }}% pendiente</span>
    </div>
</div>

@if(session('aprobado'))
<div class="p-3 bg-green-100 text-green-800 rounded-xl text-sm font-semibold">✅ {{ session('aprobado') }}</div>
@endif

@if($estudiantes->isEmpty())
    <div class="bg-white rounded-xl shadow p-8 text-center text-gray-400 text-sm">
        No hay estudiantes con PIAR registrado.
    </div>
@else

@php $cursoActual = null; @endphp

@foreach($estudiantes as $est)
@php
    $materias = $asignaciones[$est->CODIGO] ?? collect();
    $matsPiar = $piarMats[$est->CODIGO]    ?? collect();
    $cMats    = $caractMats[$est->CODIGO]  ?? collect();
    $cDirs    = $caractDirs[$est->CODIGO]  ?? collect();
    $fechasP  = [1 => $est->FECHA_P1, 2 => $est->FECHA_P2, 3 => $est->FECHA_P3];
    $personasP= [1 => $est->PERSONA_P1, 2 => $est->PERSONA_P2, 3 => $est->PERSONA_P3];
@endphp

@if($cursoActual !== $est->CURSO)
    @php $cursoActual = $est->CURSO; @endphp
    <div class="mt-2">
        <h3 class="text-xs font-bold text-blue-400 uppercase tracking-widest px-1 mb-2">
            Grado {{ $est->GRADO }} – Curso {{ $est->CURSO }}
        </h3>
    </div>
@endif

<div class="bg-white rounded-xl shadow overflow-hidden">

    {{-- Cabecera estudiante --}}
    <div class="bg-gray-700 text-white px-5 py-3 grid grid-cols-[auto_1fr_auto] items-center gap-x-4">
        {{-- Código --}}
        <span class="font-mono text-gray-400 text-xs whitespace-nowrap">{{ $est->CODIGO }}</span>
        {{-- Nombre + grado + diagnóstico --}}
        <div class="min-w-0">
            <span class="font-bold text-sm">{{ $est->APELLIDO1 }} {{ $est->APELLIDO2 }}, {{ $est->NOMBRE1 }} {{ $est->NOMBRE2 }}</span>
            <span class="ml-2 text-gray-300 text-xs">{{ $est->GRADO }} – {{ $est->CURSO }}</span>
            @if($est->DIAGNOSTICO)
                <span class="ml-2 text-gray-400 text-xs italic">· {{ $est->DIAGNOSTICO }}</span>
            @endif
        </div>
        {{-- Acciones --}}
        <div class="flex items-center gap-3 whitespace-nowrap">
            {{-- Menú imprimir --}}
            <div class="relative" style="display:inline-block;">
                <button onclick="toggleMenuImp(this)"
                    class="bg-blue-700 hover:bg-blue-600 text-white text-xs font-semibold px-3 py-1.5 rounded-lg transition flex items-center gap-1">
                    🖨️ Imprimir ▾
                </button>
                <div class="menu-imp hidden absolute right-0 mt-1 w-44 bg-white rounded-lg shadow-xl border border-gray-200 z-50 py-1 text-gray-800">
                    <a href="{{ route('piar.imprimir', $est->CODIGO) }}" target="_blank"
                       class="flex items-center gap-2 px-4 py-2 text-xs hover:bg-gray-100">
                        📄 Solo Anexo 1
                    </a>
                    <a href="{{ route('piar.anexo2.imprimir.est', $est->CODIGO) }}" target="_blank"
                       class="flex items-center gap-2 px-4 py-2 text-xs hover:bg-gray-100">
                        📄 Solo Anexo 2
                    </a>
                    <div class="border-t border-gray-100 my-1"></div>
                    <a href="{{ route('piar.imprimir.todos', $est->CODIGO) }}" target="_blank"
                       class="flex items-center gap-2 px-4 py-2 text-xs hover:bg-blue-50 font-semibold text-blue-700">
                        🖨️ Ambos (1 + 2)
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabla de módulos --}}
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-100">
            <tr class="text-xs text-gray-400 uppercase tracking-wide">
                <th class="px-4 py-2 text-left">Módulo / Materia</th>
                <th class="px-4 py-2 text-left">Docente</th>
                <th class="px-4 py-2 text-center w-40">Caracterización</th>
                <th class="px-4 py-2 text-center w-40">Ajustes + Evaluación</th>
                <th class="px-4 py-2 text-center w-40">Plan Casero</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">

        {{-- Fila director de grupo --}}
        @php
            $cDir      = $cDirs->first();
            $dirTiene  = $cDir && !empty($cDir->CARACTERIZACION);
            $dirEstado = estadoEfectivo($cDir ? ($cDir->ESTADO ?? 'pendiente') : 'pendiente', $estadosEtapa['caract']);
        @endphp
        <tr class="bg-blue-50/50">
            <td class="px-4 py-3 font-semibold text-blue-800 text-sm">Dirección de grupo</td>
            <td class="px-4 py-3 text-xs text-gray-500">{{ $cDir->NOMBRE_DOC ?? '—' }}</td>

            {{-- Estado caracterización dir --}}
            <td class="px-4 py-3 text-center">
                <div class="flex flex-col items-center gap-1">
                    {!! estadoBadge($dirEstado) !!}
                    @if($esOriSuperAd)
                        <a href="{{ route('piar.caract.dir.form', $est->CODIGO) }}"
                           class="text-xs font-semibold text-orange-600 hover:text-orange-800 hover:underline">👁 Revisar</a>
                    @elseif($dirTiene)
                        <a href="{{ route('piar.caract.dir.form', $est->CODIGO) }}"
                           class="text-xs text-blue-500 hover:underline">Ver</a>
                    @endif
                </div>
            </td>

            <td class="px-4 py-3 text-center text-gray-300 text-xs">—</td>
            <td class="px-4 py-3 text-center text-gray-300 text-xs">—</td>
        </tr>

        {{-- Filas por materia --}}
        @forelse($materias as $mat)
        @php
            $cmReg    = $cMats[$mat->CODIGO_MAT] ?? null;
            $pmReg    = $matsPiar[$mat->CODIGO_MAT] ?? null;
            $cmTiene  = $cmReg && !empty($cmReg->CARACTERIZACION);
            $pmTiene  = $pmReg && !empty($pmReg->BARRERAS);
            $cmEstado = estadoEfectivo($cmReg ? ($cmReg->ESTADO ?? 'pendiente') : 'pendiente', $estadosEtapa['caract']);
            $pmEstado = estadoEfectivo($pmReg ? ($pmReg->ESTADO ?? 'pendiente') : 'pendiente', $estadosEtapa['ajustes']);
            $pcEstado = estadoEfectivo($pmReg ? ($pmReg->ESTADO_CASERO ?? 'pendiente') : 'pendiente', $estadosEtapa['plan_casero']);
            $pcTiene  = $pmReg && (!empty($pmReg->ESTRAG_CASERA) || !empty($pmReg->FREC_CASERA));
            $filaOk  = $cmEstado === 'aprobado' && $pmEstado === 'aprobado' && $pcEstado === 'aprobado';
            $filaMal = $cmEstado === 'pendiente' || $pmEstado === 'pendiente' || $pcEstado === 'pendiente';
        @endphp
        <tr class="{{ $filaOk ? 'bg-green-50/30' : ($filaMal ? 'bg-yellow-50/40' : '') }}">
            <td class="px-4 py-3 text-gray-800 font-medium">{{ $mat->NOMBRE_MAT }}</td>
            <td class="px-4 py-3 text-xs text-gray-500">{{ $mat->NOMBRE_DOC }}</td>

            {{-- Caracterización --}}
            <td class="px-4 py-3 text-center">
                <div class="flex flex-col items-center gap-1">
                    {!! estadoBadge($cmEstado) !!}
                    @if($esOriSuperAd)
                        <a href="{{ route('piar.caract.mat.form', [$est->CODIGO, $mat->CODIGO_MAT]) }}"
                           class="text-xs font-semibold text-orange-600 hover:text-orange-800 hover:underline">👁 Revisar</a>
                    @elseif($cmTiene)
                        <a href="{{ route('piar.caract.mat.form', [$est->CODIGO, $mat->CODIGO_MAT]) }}"
                           class="text-xs text-blue-500 hover:underline">Ver</a>
                    @endif
                </div>
            </td>

            {{-- Ajustes --}}
            <td class="px-4 py-3 text-center">
                <div class="flex flex-col items-center gap-1">
                    {!! estadoBadge($pmEstado) !!}
                    @if($esOriSuperAd)
                        <a href="{{ route('piar.anexo2.form', [$est->CODIGO, $mat->CODIGO_MAT]) }}"
                           class="text-xs font-semibold text-orange-600 hover:text-orange-800 hover:underline">👁 Revisar</a>
                    @elseif($pmTiene)
                        <a href="{{ route('piar.anexo2.form', [$est->CODIGO, $mat->CODIGO_MAT]) }}"
                           class="text-xs text-blue-500 hover:underline">Ver</a>
                    @endif
                </div>
            </td>

            {{-- Plan Casero --}}
            <td class="px-4 py-3 text-center">
                <div class="flex flex-col items-center gap-1">
                    {!! estadoBadge($pcEstado) !!}
                    @if($esOriSuperAd)
                        <a href="{{ route('piar.plan_casero.form', [$est->CODIGO, $mat->CODIGO_MAT]) }}"
                           class="text-xs font-semibold text-orange-600 hover:text-orange-800 hover:underline">👁 Revisar</a>
                    @elseif($pcTiene)
                        <a href="{{ route('piar.plan_casero.form', [$est->CODIGO, $mat->CODIGO_MAT]) }}"
                           class="text-xs text-blue-500 hover:underline">Ver</a>
                    @endif
                </div>
            </td>

        </tr>
        @empty
        <tr>
            <td colspan="5" class="px-4 py-3 text-xs text-gray-400 italic">Sin materias asignadas en este curso.</td>
        </tr>
        @endforelse

        </tbody>
    </table>

</div>
@endforeach
@endif

</div>

@push('scripts')
<script>
function toggleMenuImp(btn) {
    const menu = btn.nextElementSibling;
    const isOpen = !menu.classList.contains('hidden');
    // Cerrar todos los menús abiertos
    document.querySelectorAll('.menu-imp').forEach(m => m.classList.add('hidden'));
    if (!isOpen) menu.classList.remove('hidden');
}
// Cerrar al hacer clic fuera
document.addEventListener('click', function(e) {
    if (!e.target.closest('.relative')) {
        document.querySelectorAll('.menu-imp').forEach(m => m.classList.add('hidden'));
    }
});
</script>
@endpush

@endsection
