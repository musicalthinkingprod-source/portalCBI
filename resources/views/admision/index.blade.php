@extends('layouts.app-sidebar')

@section('header', 'Exámenes de Admisión')

@section('slot')

@if(session('success'))
<div class="mb-5 bg-green-50 border border-green-300 text-green-800 px-4 py-3 rounded-lg text-sm font-medium">
    ✅ {{ session('success') }}
</div>
@endif
@if(session('error'))
<div class="mb-5 bg-red-50 border border-red-300 text-red-800 px-4 py-3 rounded-lg text-sm font-medium">
    ⚠️ {{ session('error') }}
</div>
@endif

{{-- ── Selección de grado ─────────────────────────────────────────────── --}}
<div class="bg-white rounded-xl shadow p-5 mb-6">
    <h2 class="text-base font-semibold text-gray-800 mb-1">Nueva evaluación</h2>
    <p class="text-xs text-gray-500 mb-4">
        Elige el grado al que aspira ingresar el estudiante para abrir la planilla de respuestas.
    </p>

    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
        @foreach($grados as $g)
        <div class="border border-gray-200 rounded-xl p-3 hover:border-blue-400 hover:shadow-sm transition">
            <div class="flex items-center justify-between mb-1">
                <span class="font-semibold text-gray-800 text-sm">{{ $g['nombre'] }}</span>
                @if($g['tipo'] === 'opcion_multiple')
                    <span class="text-[10px] bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full">A–D</span>
                @else
                    <span class="text-[10px] bg-amber-100 text-amber-700 px-2 py-0.5 rounded-full">L/P/N</span>
                @endif
            </div>
            <p class="text-[11px] text-gray-400 mb-2">{{ $g['total'] }} preguntas · {{ count($g['materias']) }} materias</p>
            <div class="flex gap-2">
                <a href="{{ route('admision.create', ['grado' => $g['key']]) }}"
                   class="flex-1 text-center bg-blue-800 hover:bg-blue-700 text-white text-xs font-semibold px-2 py-1.5 rounded-lg transition">
                    Evaluar
                </a>
                @if($g['tipo'] === 'opcion_multiple')
                <a href="{{ route('admision.hoja', ['grado' => $g['key']]) }}" target="_blank"
                   title="Imprimir hoja de respuestas OMR"
                   class="text-center bg-gray-100 hover:bg-gray-200 text-gray-600 text-xs px-2 py-1.5 rounded-lg transition">
                    🖨️
                </a>
                @endif
            </div>
        </div>
        @endforeach
    </div>
</div>

{{-- ── Histórico ──────────────────────────────────────────────────────── --}}
<div class="bg-white rounded-xl shadow p-5">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
        <h2 class="text-base font-semibold text-gray-800">Evaluaciones registradas</h2>
        <form method="GET" action="{{ route('admision.index') }}" class="flex gap-2">
            <input type="text" name="buscar" value="{{ request('buscar') }}" placeholder="Nombre o documento…"
                   class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm">
            <select name="grado" class="border border-gray-300 rounded-lg px-2 py-1.5 text-sm">
                <option value="">Todos los grados</option>
                @foreach($grados as $g)
                    <option value="{{ $g['key'] }}" @selected(request('grado')===$g['key'])>{{ $g['nombre'] }}</option>
                @endforeach
            </select>
            <button class="bg-gray-800 hover:bg-gray-700 text-white px-4 py-1.5 rounded-lg text-sm font-semibold">Filtrar</button>
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-xs uppercase tracking-wide text-gray-400 border-b">
                    <th class="py-2 pr-3">Documento</th>
                    <th class="py-2 pr-3">Aspirante</th>
                    <th class="py-2 pr-3">Grado</th>
                    <th class="py-2 pr-3">Fecha</th>
                    <th class="py-2 pr-3 text-center">Resultado</th>
                    <th class="py-2 pr-3 text-center">Nivel</th>
                    <th class="py-2 pr-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($evaluaciones as $ev)
                @php $nivel = $ev->resultados['total']['nivel'] ?? '—'; @endphp
                <tr class="hover:bg-gray-50">
                    <td class="py-2 pr-3 text-gray-500">{{ $ev->aspirante_documento ?: '—' }}</td>
                    <td class="py-2 pr-3 font-medium text-gray-800">{{ $ev->aspirante_nombre }}</td>
                    <td class="py-2 pr-3">{{ $ev->grado_nombre }}</td>
                    <td class="py-2 pr-3 text-gray-500">{{ optional($ev->fecha_examen)->format('d/m/Y') ?? $ev->created_at->format('d/m/Y') }}</td>
                    <td class="py-2 pr-3 text-center">
                        @if($ev->tipo === 'opcion_multiple')
                            <span class="font-semibold">{{ $ev->puntaje }}/{{ $ev->total_preguntas }}</span>
                        @endif
                        <span class="text-gray-400">({{ rtrim(rtrim(number_format($ev->porcentaje,1),'0'),'.') }}%)</span>
                    </td>
                    <td class="py-2 pr-3 text-center">
                        <span class="text-[11px] px-2 py-0.5 rounded-full
                            @class([
                                'bg-green-100 text-green-700' => in_array($nivel,['Superior','Alto']),
                                'bg-amber-100 text-amber-700' => $nivel==='Básico',
                                'bg-red-100 text-red-700' => $nivel==='Bajo',
                                'bg-gray-100 text-gray-500' => !in_array($nivel,['Superior','Alto','Básico','Bajo']),
                            ])">{{ $nivel }}</span>
                    </td>
                    <td class="py-2 pr-3 text-right">
                        <a href="{{ route('admision.show', $ev) }}"
                           class="text-blue-700 hover:underline text-xs font-semibold">Ver reporte →</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="py-8 text-center text-gray-400 text-sm">Aún no hay evaluaciones registradas.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $evaluaciones->links() }}</div>
</div>

@endsection
