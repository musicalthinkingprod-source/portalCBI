@extends('layouts.app-sidebar')

@section('header', 'Vigilancias')

@section('slot')

{{-- Lógica de tiempo --}}
@php
    $pos1   = $posHoy[1] ?? null;
    $pos2   = $posHoy[2] ?? null;
    $horaActual = now()->format('H:i');

    if ($horaActual < '08:50') {
        $mostrar    = 1;
        $labelCard  = 'Vigilancia · Descanso 1';
        $posActiva  = $pos1;
        $distId     = 'dist-d1';
        $colorBorde = 'border-blue-500 bg-blue-50';
        $colorTexto = 'text-blue-700';
        $posHoyFiltrado = $pos1 ? [1 => $pos1] : [];
    } elseif ($horaActual < '12:15') {
        $mostrar    = 2;
        $labelCard  = 'Próxima vigilancia · Descanso 2';
        $posActiva  = $pos2;
        $distId     = 'dist-d2';
        $colorBorde = 'border-orange-500 bg-orange-50';
        $colorTexto = 'text-orange-600';
        $posHoyFiltrado = $pos2 ? [2 => $pos2] : [];
    } else {
        $mostrar    = null;
        $posActiva  = null;
        $distId     = null;
        $posHoyFiltrado = [];
    }
@endphp

<div class="flex flex-col gap-4" style="height: calc(100vh - 112px);">

    {{-- ── CARDS DE HOY ── --}}
    <div class="flex flex-wrap gap-3 shrink-0">

        {{-- Aviso si no hay día configurado --}}
        @if(!$diaHoy)
        <div class="w-full rounded-xl bg-yellow-50 border border-yellow-200 px-4 py-3 text-sm text-yellow-700">
            No hay día de ciclo registrado para hoy. Consulta con la coordinación.
        </div>
        @elseif($mostrar === null)
        <div class="w-full rounded-xl bg-gray-50 border border-gray-200 px-4 py-3 text-sm text-gray-500">
            ✅ Las vigilancias de hoy han finalizado.
        </div>
        @else
        <div class="flex items-center gap-2 bg-blue-50 border border-blue-200 rounded-xl px-4 py-2 text-sm font-semibold text-blue-800">
            📅 Día académico <span class="ml-1 bg-blue-700 text-white rounded-full px-2 py-0.5 text-xs">{{ $diaHoy }}</span>
        </div>

        {{-- Próxima vigilancia según calendario --}}
        @if($proximaFechaVig)
        <div class="flex items-center gap-2 bg-gray-50 border border-gray-200 rounded-xl px-4 py-2 text-xs text-gray-500">
            📆 Próxima:
            <span class="font-semibold text-gray-700">
                {{ \Carbon\Carbon::parse($proximaFechaVig->fecha)->locale('es')->isoFormat('ddd D MMM') }}
            </span>
            · Día <span class="font-bold text-blue-700">{{ $proximaFechaVig->dia_ciclo }}</span>
        </div>
        @endif

        {{-- Card única según la hora --}}
        <div id="card-d{{ $mostrar }}"
            class="flex-1 min-w-[160px] rounded-xl border-2 px-4 py-3 flex flex-col items-center text-center transition-all {{ $posActiva ? $colorBorde : 'border-gray-200 bg-gray-50' }}">
            <span class="text-xs font-semibold uppercase tracking-wide text-gray-500 mb-1">{{ $labelCard }}</span>
            @if($posActiva)
                <span class="text-3xl font-black {{ $colorTexto }}">{{ $posActiva }}</span>
                <span id="{{ $distId }}" class="mt-1 text-xs font-medium text-gray-400">— GPS no activo</span>
            @else
                <span class="text-sm text-gray-400 italic">Sin asignación</span>
            @endif
        </div>
        @endif

        {{-- Botón GPS (solo si hay vigilancia activa) --}}
        @if($mostrar !== null && $diaHoy)
        <button id="btn-gps"
            class="shrink-0 flex items-center gap-2 bg-green-600 hover:bg-green-700 active:bg-green-800 text-white font-semibold text-sm px-4 py-3 rounded-xl transition shadow">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3"/>
                <path d="M12 8a4 4 0 100 8 4 4 0 000-8z" stroke-linecap="round"/>
            </svg>
            Ubicarme
        </button>
        @endif
    </div>

    {{-- ── MAPA ── --}}
    <div class="relative flex-1 rounded-xl overflow-hidden shadow border border-gray-200 min-h-[300px] isolate">
        <div id="mapa-vigilancias" class="w-full h-full"></div>

        {{-- Leyenda --}}
        <div class="absolute bottom-4 left-4 z-[1000] bg-white bg-opacity-90 rounded-lg shadow px-3 py-2 text-xs space-y-1">
            <div class="flex items-center gap-2">
                <span class="inline-block w-5 h-5 rounded-full bg-blue-600 border-2 border-white shadow text-white text-center font-bold leading-5 text-[10px]">N</span>
                <span class="text-gray-600">Tu posición Descanso 1</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="inline-block w-5 h-5 rounded-full bg-orange-500 border-2 border-white shadow text-white text-center font-bold leading-5 text-[10px]">N</span>
                <span class="text-gray-600">Tu posición Descanso 2</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="inline-block w-5 h-5 rounded-full bg-gray-400 border-2 border-white shadow text-white text-center font-bold leading-5 text-[10px]">N</span>
                <span class="text-gray-600">Otras posiciones</span>
            </div>
        </div>
    </div>

    {{-- ── TABLA RESUMEN (ciclo completo, colapsable) ── --}}
    <details class="shrink-0 bg-white rounded-xl shadow border border-gray-100">
        <summary class="px-4 py-3 text-sm font-semibold text-gray-700 cursor-pointer select-none">
            Ver ciclo completo — {{ $anio }}
        </summary>
        <div class="overflow-x-auto px-4 pb-4">
            <table class="min-w-full text-sm mt-2">
                <thead>
                    <tr class="bg-blue-900 text-white">
                        <th class="px-3 py-2 text-left font-medium rounded-tl-lg">Descanso</th>
                        @for($dia = 1; $dia <= 6; $dia++)
                            <th class="px-3 py-2 text-center font-medium {{ $dia === 6 ? 'rounded-tr-lg' : '' }}">
                                Día {{ $dia }}
                                @if($diaHoy == $dia)
                                    <span class="ml-1 bg-yellow-400 text-yellow-900 text-[10px] font-bold px-1 rounded">Hoy</span>
                                @endif
                            </th>
                        @endfor
                    </tr>
                </thead>
                <tbody>
                    @foreach([1,2] as $d)
                    <tr class="{{ $d % 2 == 0 ? 'bg-gray-50' : 'bg-white' }}">
                        <td class="px-3 py-2 font-semibold text-gray-600 whitespace-nowrap">Descanso {{ $d }}</td>
                        @for($dia = 1; $dia <= 6; $dia++)
                            @php $pos = $asignaciones[$dia][$d] ?? null; @endphp
                            <td class="px-3 py-2 text-center {{ $diaHoy == $dia ? 'bg-blue-50 font-semibold text-blue-800' : '' }}">
                                {{ $pos ?? '—' }}
                            </td>
                        @endfor
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </details>

</div>

@endsection

@push('scripts')
{{-- Leaflet CSS --}}
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
{{-- Leaflet JS --}}
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<style>
    .marker-normal {
        width: 28px; height: 28px;
        background: #9ca3af;
        border: 2px solid white;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        color: white; font-size: 11px; font-weight: 700;
        box-shadow: 0 1px 4px rgba(0,0,0,0.4);
        cursor: pointer;
    }
    .marker-d1 {
        width: 34px; height: 34px;
        background: #1d4ed8;
        border: 3px solid white;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        color: white; font-size: 13px; font-weight: 900;
        box-shadow: 0 2px 8px rgba(29,78,216,0.6);
        cursor: pointer;
        animation: pulse-blue 2s infinite;
    }
    .marker-d2 {
        width: 34px; height: 34px;
        background: #ea580c;
        border: 3px solid white;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        color: white; font-size: 13px; font-weight: 900;
        box-shadow: 0 2px 8px rgba(234,88,12,0.6);
        cursor: pointer;
        animation: pulse-orange 2s infinite;
    }
    .marker-escuela {
        width: 32px; height: 32px;
        background: #dc2626;
        border: 3px solid white;
        border-radius: 6px;
        display: flex; align-items: center; justify-content: center;
        color: white; font-size: 14px;
        box-shadow: 0 2px 6px rgba(0,0,0,0.4);
    }
    .marker-yo {
        width: 20px; height: 20px;
        background: #16a34a;
        border: 3px solid white;
        border-radius: 50%;
        box-shadow: 0 0 0 4px rgba(22,163,74,0.3);
        animation: pulse-gps 1.5s infinite;
    }
    @keyframes pulse-blue {
        0%,100% { box-shadow: 0 2px 8px rgba(29,78,216,0.6); }
        50% { box-shadow: 0 2px 16px rgba(29,78,216,0.9), 0 0 0 6px rgba(29,78,216,0.15); }
    }
    @keyframes pulse-orange {
        0%,100% { box-shadow: 0 2px 8px rgba(234,88,12,0.6); }
        50% { box-shadow: 0 2px 16px rgba(234,88,12,0.9), 0 0 0 6px rgba(234,88,12,0.15); }
    }
    @keyframes pulse-gps {
        0%,100% { box-shadow: 0 0 0 4px rgba(22,163,74,0.3); }
        50% { box-shadow: 0 0 0 10px rgba(22,163,74,0.1); }
    }
    .leaflet-popup-content b { font-size: 15px; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const puntos  = @json($puntosMapa);
    const posHoy  = @json($posHoyFiltrado);   // solo el descanso vigente según la hora
    const diaHoy  = @json($diaHoy);   // número o null

    // ─── Inicializar mapa ───────────────────────────────────────────────────
    const map = L.map('mapa-vigilancias', { zoomControl: true });

    // Tiles satélite Esri (gratis, sin API key)
    L.tileLayer(
        'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
        { attribution: 'Tiles &copy; Esri', maxZoom: 21, maxNativeZoom: 19 }
    ).addTo(map);

    // Capa de etiquetas sobre satélite (calles, nombres)
    L.tileLayer(
        'https://services.arcgisonline.com/ArcGIS/rest/services/Reference/World_Boundaries_and_Places/MapServer/tile/{z}/{y}/{x}',
        { attribution: '', maxZoom: 21, maxNativeZoom: 19, opacity: 0.8 }
    ).addTo(map);

    // ─── Marcadores de sedes (escuela) ─────────────────────────────────────
    [
        { lat: 4.597329430079155, lng: -74.10431720923572, label: '🏫 Sede A' },
        { lat: 4.596231989229937, lng: -74.10479238842063, label: '🏫 Sede B' },
    ].forEach(s => {
        L.marker([s.lat, s.lng], {
            icon: L.divIcon({
                className: '', html: `<div class="marker-escuela">🏫</div>`,
                iconSize: [32, 32], iconAnchor: [16, 16],
            })
        }).bindPopup(`<b>${s.label}</b>`).addTo(map);
    });

    // ─── Marcadores de posiciones ───────────────────────────────────────────
    const marcadores = {};

    puntos.forEach(p => {
        const esD1 = posHoy[1] === p.id;
        const esD2 = posHoy[2] === p.id;

        let clase = 'marker-normal';
        if (esD1) clase = 'marker-d1';
        if (esD2) clase = 'marker-d2';

        const size   = (esD1 || esD2) ? 34 : 28;
        const anchor = size / 2;

        const icono = L.divIcon({
            className: '',
            html: `<div class="${clase}">${p.numero}</div>`,
            iconSize: [size, size],
            iconAnchor: [anchor, anchor],
        });

        let popupContent = `<b>${p.id}</b>`;
        if (p.desc) popupContent += `<br><span style="font-size:12px;color:#555">${p.desc}</span>`;
        if (esD1) popupContent += `<br><span style="color:#1d4ed8;font-weight:600">▶ Tu posición — Descanso 1</span>`;
        if (esD2) popupContent += `<br><span style="color:#ea580c;font-weight:600">▶ Tu posición — Descanso 2</span>`;

        const marker = L.marker([p.lat, p.lng], { icon: icono })
            .bindPopup(popupContent)
            .addTo(map);

        marcadores[p.id] = { marker, punto: p, esD1, esD2 };
    });

    // ─── Fit bounds ─────────────────────────────────────────────────────────
    if (puntos.length > 0) {
        const bounds = puntos.map(p => [p.lat, p.lng]);
        map.fitBounds(bounds, { padding: [30, 30] });
    }

    // Si hay posiciones asignadas hoy, centrar en ellas
    const asignadosHoy = Object.values(marcadores).filter(m => m.esD1 || m.esD2);
    if (asignadosHoy.length > 0) {
        const bHoy = asignadosHoy.map(m => [m.punto.lat, m.punto.lng]);
        map.fitBounds(bHoy, { padding: [80, 80], maxZoom: 19 });
    }

    // ─── GPS / Proximidad ───────────────────────────────────────────────────
    let markerYo = null;
    let watchId  = null;

    const btnGps  = document.getElementById('btn-gps');
    const distEl  = document.getElementById('{{ $distId }}');
    // compatibilidad con variables dist-d1/dist-d2 usadas abajo
    const distD1  = {{ $mostrar == 1 ? 'distEl' : 'null' }};
    const distD2  = {{ $mostrar == 2 ? 'distEl' : 'null' }};

    btnGps.addEventListener('click', () => {
        if (!navigator.geolocation) {
            alert('Tu dispositivo no soporta GPS.');
            return;
        }

        if (watchId !== null) {
            // Si ya estaba activo, apagarlo
            navigator.geolocation.clearWatch(watchId);
            watchId = null;
            if (markerYo) { map.removeLayer(markerYo); markerYo = null; }
            btnGps.textContent = 'Ubicarme';
            btnGps.classList.replace('bg-red-600', 'bg-green-600');
            btnGps.classList.replace('hover:bg-red-700', 'hover:bg-green-700');
            if (distD1) distD1.textContent = '— GPS no activo';
            if (distD2) distD2.textContent = '— GPS no activo';
            return;
        }

        btnGps.textContent = 'Obteniendo GPS…';
        btnGps.disabled = true;

        watchId = navigator.geolocation.watchPosition(
            pos => {
                const myLat = pos.coords.latitude;
                const myLng = pos.coords.longitude;

                // Actualizar o crear marcador "yo"
                if (markerYo) {
                    markerYo.setLatLng([myLat, myLng]);
                } else {
                    markerYo = L.marker([myLat, myLng], {
                        icon: L.divIcon({
                            className: '',
                            html: '<div class="marker-yo"></div>',
                            iconSize: [20, 20],
                            iconAnchor: [10, 10],
                        }),
                        zIndexOffset: 1000,
                    }).bindPopup('<b>Tu ubicación</b>').addTo(map);
                    map.setView([myLat, myLng], 19);

                    btnGps.innerHTML = `
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3"/>
                        </svg> Detener GPS`;
                    btnGps.disabled = false;
                    btnGps.classList.replace('bg-green-600', 'bg-red-600');
                    btnGps.classList.replace('hover:bg-green-700', 'hover:bg-red-700');
                }

                // Calcular distancia a posiciones asignadas hoy
                [[1, distD1, posHoy[1]], [2, distD2, posHoy[2]]].forEach(([desc, el, posId]) => {
                    if (!el || !posId) return;
                    const m = marcadores[posId];
                    if (!m) return;
                    const dist = haversine(myLat, myLng, m.punto.lat, m.punto.lng);
                    const metros = Math.round(dist);

                    let color, icono;
                    if (metros <= 25) {
                        color = 'text-green-600'; icono = '✅';
                    } else if (metros <= 80) {
                        color = 'text-yellow-600'; icono = '⚠️';
                    } else {
                        color = 'text-red-600'; icono = '📍';
                    }
                    el.className = `mt-1 text-xs font-semibold ${color}`;
                    el.textContent = `${icono} ${metros < 1000 ? metros + ' m' : (dist/1000).toFixed(1) + ' km'}`;
                });
            },
            err => {
                btnGps.textContent = 'Ubicarme';
                btnGps.disabled = false;
                watchId = null;
                if (err.code === 1) {
                    alert('Permiso de ubicación denegado. Actívalo en la configuración del navegador.');
                } else {
                    alert('No se pudo obtener la ubicación. Intenta de nuevo.');
                }
            },
            { enableHighAccuracy: true, maximumAge: 5000, timeout: 15000 }
        );
    });

    // ─── Haversine ──────────────────────────────────────────────────────────
    function haversine(lat1, lng1, lat2, lng2) {
        const R = 6371000;
        const toRad = x => x * Math.PI / 180;
        const dLat = toRad(lat2 - lat1);
        const dLng = toRad(lng2 - lng1);
        const a = Math.sin(dLat/2)**2
                + Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.sin(dLng/2)**2;
        return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    }

});
</script>
@endpush
