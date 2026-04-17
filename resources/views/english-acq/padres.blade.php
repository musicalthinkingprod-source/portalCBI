@extends('layouts.padres')

@section('header', 'English Acquisition')

@section('slot')

    {{-- Notas por período --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
        @foreach([1,2,3,4] as $p)
        @php
            $nota = $notas[$p] ?? 10;
            $notaRedondeada = round($nota, 1, PHP_ROUND_HALF_UP);
            $iniciado = in_array($p, $periodosIniciados);
        @endphp
        @if($iniciado)
        <div class="bg-white rounded-xl shadow p-4 text-center">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">Período {{ $p }}</p>
            <p class="text-3xl font-bold {{ $notaRedondeada < 7 ? 'text-red-600' : ($notaRedondeada < 8 ? 'text-yellow-500' : 'text-green-600') }}">
                {{ number_format($notaRedondeada, 1) }}
            </p>
            <p class="text-xs text-gray-400 mt-1">/10</p>
        </div>
        @else
        <div class="bg-gray-50 rounded-xl border border-gray-200 p-4 text-center opacity-60">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">Período {{ $p }}</p>
            <p class="text-2xl text-gray-300 mt-1">🔒</p>
            <p class="text-xs text-gray-400 mt-1">No iniciado</p>
        </div>
        @endif
        @endforeach
    </div>

    {{-- Historial de descuentos --}}
    <div class="bg-white rounded-xl shadow overflow-hidden">
        <div class="px-5 py-3 bg-blue-800 text-white flex items-center justify-between flex-wrap gap-3">
            <div>
                <h3 class="font-bold text-sm uppercase tracking-wide">Historial de descuentos — {{ $anio }}</h3>
                <p class="text-blue-300 text-xs mt-0.5">Cada registro representa −0.25 puntos sobre la nota de 10</p>
            </div>
            {{-- Selector de período --}}
            <div class="flex gap-1">
                @foreach($periodosIniciados as $p)
                <button type="button"
                    onclick="filtrarPeriodo({{ $p }})"
                    id="btn-p{{ $p }}"
                    class="px-3 py-1 rounded-lg text-xs font-semibold transition
                        {{ $p == $periodoActual ? 'bg-white text-blue-800' : 'bg-blue-700 text-blue-200 hover:bg-blue-600' }}">
                    P{{ $p }}
                </button>
                @endforeach
            </div>
        </div>

        @php $detalleIniciados = array_values(array_filter($detalle, fn($d) => in_array($d['periodo'], $periodosIniciados))); @endphp
        @if(empty($detalleIniciados))
            <div id="sin-descuentos" class="px-5 py-8 text-center text-gray-400 text-sm">
                Sin descuentos registrados este año.
            </div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                    <tr>
                        <th class="px-4 py-3 text-left">Fecha</th>
                        <th class="px-4 py-3 text-center w-28">Descuento</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100" id="tbody-descuentos">
                    @foreach($detalleIniciados as $d)
                    <tr class="hover:bg-gray-50 fila-descuento" data-periodo="{{ $d['periodo'] }}">
                        <td class="px-4 py-2 text-gray-600">
                            {{ \Carbon\Carbon::parse($d['fecha'])->format('d/m/Y H:i') }}
                        </td>
                        <td class="px-4 py-2 text-center font-semibold text-red-600">−0.25</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            <div id="sin-descuentos-periodo" class="hidden px-5 py-8 text-center text-gray-400 text-sm">
                Sin descuentos en este período.
            </div>
        </div>
        @endif
    </div>

<script>
    const periodoActual = {{ $periodoActual }};

    function filtrarPeriodo(p) {
        // Actualizar botones
        document.querySelectorAll('[id^="btn-p"]').forEach(btn => {
            btn.classList.remove('bg-white', 'text-blue-800');
            btn.classList.add('bg-blue-700', 'text-blue-200');
        });
        const btnActivo = document.getElementById('btn-p' + p);
        if (btnActivo) {
            btnActivo.classList.add('bg-white', 'text-blue-800');
            btnActivo.classList.remove('bg-blue-700', 'text-blue-200');
        }

        // Filtrar filas
        const filas = document.querySelectorAll('.fila-descuento');
        let visibles = 0;
        filas.forEach(fila => {
            if (parseInt(fila.dataset.periodo) === p) {
                fila.classList.remove('hidden');
                visibles++;
            } else {
                fila.classList.add('hidden');
            }
        });

        const sinDesc = document.getElementById('sin-descuentos-periodo');
        if (sinDesc) sinDesc.classList.toggle('hidden', visibles > 0);
    }

    // Aplicar filtro por defecto al cargar
    document.addEventListener('DOMContentLoaded', () => filtrarPeriodo(periodoActual));
</script>

@endsection
