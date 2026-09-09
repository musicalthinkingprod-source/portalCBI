{{--
    Filtro de rango de fechas + descarga a Excel para informes de contabilidad.

    Parámetros:
      $rutaInforme  nombre de la ruta del informe (GET)
      $rutaExport   nombre de la ruta de exportación a Excel
      $fechaDesde   valor actual del filtro (o null)
      $fechaHasta   valor actual del filtro (o null)
      $extra        (opcional) parámetros adicionales que deben conservarse en los enlaces
      $exportSiempre (opcional, por defecto true) si es false, oculta el botón de Excel
--}}
@php
    $extra         = $extra ?? [];
    $exportSiempre = $exportSiempre ?? true;
    $paramsFechas  = array_filter(array_merge($extra, [
        'fecha_desde' => $fechaDesde,
        'fecha_hasta' => $fechaHasta,
    ]), fn($v) => $v !== null && $v !== '');
@endphp

<div class="bg-white rounded-xl shadow px-5 py-4 mb-5 flex flex-wrap items-end justify-between gap-4">
    <form method="GET" action="{{ route($rutaInforme) }}" class="flex flex-wrap items-end gap-3">
        @foreach($extra as $campo => $valor)
            <input type="hidden" name="{{ $campo }}" value="{{ $valor }}">
        @endforeach
        <div>
            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Desde</label>
            <input type="date" name="fecha_desde" value="{{ $fechaDesde ?? '' }}"
                class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
        </div>
        <div>
            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Hasta</label>
            <input type="date" name="fecha_hasta" value="{{ $fechaHasta ?? '' }}"
                class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
        </div>
        <button type="submit"
            class="bg-blue-800 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-1.5 rounded-lg transition">
            Aplicar
        </button>
        @if($fechaDesde || $fechaHasta)
            <a href="{{ route($rutaInforme, array_filter($extra, fn($v) => $v !== null && $v !== '')) }}"
                class="text-xs text-red-500 hover:text-red-700 font-semibold whitespace-nowrap pb-2">✕ Quitar fechas</a>
        @endif
    </form>

    <div class="flex flex-col items-end gap-1">
        @if($exportSiempre)
        <a href="{{ route($rutaExport, $paramsFechas) }}"
            class="bg-green-700 hover:bg-green-800 text-white text-sm font-semibold px-4 py-1.5 rounded-lg transition whitespace-nowrap">
            ⬇️ Descargar Excel
        </a>
        @endif
        @if($fechaDesde || $fechaHasta)
            <p class="text-xs text-gray-500">
                Filtrado
                @if($fechaDesde) desde <strong>{{ $fechaDesde }}</strong> @endif
                @if($fechaHasta) hasta <strong>{{ $fechaHasta }}</strong> @endif
            </p>
        @else
            <p class="text-xs text-gray-400">Sin filtro de fechas: acumulado total</p>
        @endif
    </div>
</div>
