@extends('layouts.app-sidebar')

@section('header', 'Conflictos de horario por docente')

@section('slot')

<div class="mb-4 flex items-center gap-4">
    <a href="{{ route('horarios.index') }}" class="text-blue-600 hover:underline text-sm">← Volver a horarios</a>
</div>

@if($porDocente->isEmpty())
    <div class="bg-green-50 border border-green-200 rounded-xl p-6 text-center text-green-800 font-semibold">
        ✅ No se encontraron conflictos. Todos los docentes tienen como máximo una clase por hora.
    </div>
@else
    <div class="mb-4 bg-red-50 border border-red-200 rounded-xl px-5 py-3 text-sm text-red-800">
        ⚠ Se encontraron <strong>{{ $porDocente->count() }} docente(s)</strong> con conflictos de horario
        (más de una clase asignada en el mismo Día + Hora, incluyendo duplicados).
    </div>

    <div class="space-y-6">
    @foreach($porDocente as $codigoDoc => $filas)
        @php $primer = $filas->first(); @endphp
        <div class="bg-white rounded-xl shadow overflow-hidden">
            <div class="bg-red-600 text-white px-5 py-3 flex items-center justify-between">
                <div>
                    <span class="font-bold text-base">{{ $primer->NOMBRE_DOC ?? $codigoDoc }}</span>
                    <span class="ml-3 text-red-200 text-xs font-mono">{{ $codigoDoc }}</span>
                </div>
                <span class="bg-white text-red-700 text-xs font-bold px-3 py-1 rounded-full">
                    {{ $filas->count() }} conflicto(s)
                </span>
            </div>
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase w-20">Día</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase w-24">Hora</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase">Cursos en conflicto</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($filas as $fila)
                    <tr class="hover:bg-red-50">
                        <td class="px-4 py-2 font-medium text-gray-700">{{ $dias[$fila->DIA] ?? 'Día '.$fila->DIA }}</td>
                        <td class="px-4 py-2 text-gray-600">{{ $horas[$fila->HORA] ?? $fila->HORA.'ª' }}</td>
                        <td class="px-4 py-2 text-red-800 font-medium">{{ $fila->detalle }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="px-5 py-2 text-right">
                <a href="{{ route('horarios.por_docente', ['docente' => $codigoDoc]) }}"
                   class="text-blue-600 hover:underline text-xs">Ver horario completo →</a>
            </div>
        </div>
    @endforeach
    </div>
@endif

@endsection
