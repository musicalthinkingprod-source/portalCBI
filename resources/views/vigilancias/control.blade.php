@extends('layouts.app-sidebar')

@section('header', 'Control de Vigilancias')

@section('slot')
@php
    $horaActual = now()->format('H:i');
    if ($horaActual < '08:50')      $descansoActivo = 1;
    elseif ($horaActual < '12:15')  $descansoActivo = 2;
    else                            $descansoActivo = null;
@endphp

<div class="flex flex-col gap-4" style="height: calc(100vh - 112px);">

    {{-- Info día + descanso --}}
    <div class="flex flex-wrap items-center gap-3 shrink-0">
        @if($diaHoy)
            <span class="bg-blue-50 border border-blue-200 text-blue-800 text-sm font-semibold px-3 py-2 rounded-xl">
                📅 Día <span class="bg-blue-700 text-white rounded-full px-2 py-0.5 text-xs ml-1">{{ $diaHoy }}</span>
            </span>
        @else
            <span class="bg-yellow-50 border border-yellow-200 text-yellow-700 text-sm px-3 py-2 rounded-xl">
                Sin día de ciclo registrado para hoy
            </span>
        @endif

        @if($descansoActivo)
            <span class="text-sm font-semibold px-3 py-2 rounded-xl
                {{ $descansoActivo == 1 ? 'bg-blue-100 text-blue-700' : 'bg-orange-100 text-orange-700' }}">
                Descanso {{ $descansoActivo }} activo
            </span>
        @else
            <span class="bg-gray-100 text-gray-500 text-sm px-3 py-2 rounded-xl">Vigilancias finalizadas</span>
        @endif

        <button id="btn-gps"
            class="ml-auto flex items-center gap-2 bg-green-600 hover:bg-green-700 text-white font-semibold text-sm px-4 py-2 rounded-xl transition shadow">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3"/>
            </svg>
            Activar GPS
        </button>
    </div>

    {{-- Layout: mapa + panel lateral --}}
    <div class="flex flex-col lg:flex-row gap-4 flex-1 min-h-0">

        {{-- MAPA --}}
        <div class="relative flex-1 rounded-xl overflow-hidden shadow border border-gray-200 min-h-[280px] isolate">
            <div id="mapa-control" class="w-full h-full"></div>
        </div>

        {{-- PANEL DERECHO --}}
        <div class="lg:w-80 flex flex-col gap-3 shrink-0 overflow-y-auto">

            {{-- Card posición más cercana --}}
            <div class="bg-white rounded-xl shadow border border-gray-200 p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-2">Posición más cercana</p>
                <div id="card-cercana" class="text-center py-3">
                    <p class="text-sm text-gray-400 italic">GPS no activo</p>
                </div>
            </div>

            {{-- Lista de posiciones cercanas --}}
            <div class="bg-white rounded-xl shadow border border-gray-200 p-4 flex-1">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-3">Posiciones cercanas</p>
                <div id="lista-cercanas">
                    <p class="text-sm text-gray-400 italic">Activa el GPS para ver las posiciones ordenadas por distancia.</p>
                </div>
            </div>

        </div>
    </div>

</div>
@endsection

@push('scripts')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<style>
    .mc-asignado {
        width:32px;height:32px;background:#1d4ed8;border:2px solid white;border-radius:50%;
        display:flex;align-items:center;justify-content:center;
        color:white;font-size:11px;font-weight:800;
        box-shadow:0 2px 6px rgba(29,78,216,.5);cursor:pointer;
    }
    .mc-libre {
        width:24px;height:24px;background:#9ca3af;border:2px solid white;border-radius:50%;
        display:flex;align-items:center;justify-content:center;
        color:white;font-size:10px;font-weight:700;
        box-shadow:0 1px 4px rgba(0,0,0,.3);cursor:pointer;
    }
    .mc-cercano {
        width:36px;height:36px;background:#16a34a;border:3px solid white;border-radius:50%;
        display:flex;align-items:center;justify-content:center;
        color:white;font-size:13px;font-weight:900;
        box-shadow:0 2px 10px rgba(22,163,74,.7);cursor:pointer;
        animation: pulso 1.5s infinite;
    }
    .mc-yo {
        width:18px;height:18px;background:#f59e0b;border:3px solid white;border-radius:50%;
        box-shadow:0 0 0 5px rgba(245,158,11,.25);
        animation: pulso 1.5s infinite;
    }
    .mc-escuela {
        width:28px;height:28px;background:#dc2626;border:2px solid white;border-radius:6px;
        display:flex;align-items:center;justify-content:center;font-size:14px;
        box-shadow:0 2px 5px rgba(0,0,0,.3);
    }
    @keyframes pulso {
        0%,100% { box-shadow:0 2px 10px rgba(22,163,74,.7); }
        50%      { box-shadow:0 2px 18px rgba(22,163,74,.9), 0 0 0 8px rgba(22,163,74,.1); }
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const puntos         = @json($puntosMapa);
    const posicionDoc    = @json($posicionDocente);  // {"5A":{docente,descanso}, ...}
    const descansoActivo = @json($descansoActivo);   // 1, 2 o null

    // ── Mapa ──────────────────────────────────────────────────────────────
    const map = L.map('mapa-control');

    L.tileLayer(
        'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
        { attribution:'Tiles &copy; Esri', maxZoom:21, maxNativeZoom:19 }
    ).addTo(map);
    L.tileLayer(
        'https://services.arcgisonline.com/ArcGIS/rest/services/Reference/World_Boundaries_and_Places/MapServer/tile/{z}/{y}/{x}',
        { maxZoom:21, maxNativeZoom:19, opacity:0.8 }
    ).addTo(map);

    // Sedes
    [
        { lat:4.597329430079155, lng:-74.10431720923572, label:'🏫 Sede A' },
        { lat:4.596231989229937, lng:-74.10479238842063, label:'🏫 Sede B' },
    ].forEach(s => L.marker([s.lat,s.lng],{
        icon: L.divIcon({ className:'', html:'<div class="mc-escuela">🏫</div>', iconSize:[28,28], iconAnchor:[14,14] })
    }).bindPopup(`<b>${s.label}</b>`).addTo(map));

    // Marcadores de posiciones
    const marcadores = {};
    puntos.forEach(p => {
        const info  = posicionDoc[p.id] ?? null;
        const clase = info ? 'mc-asignado' : 'mc-libre';
        const size  = info ? 32 : 24;

        const icon = L.divIcon({
            className:'',
            html:`<div class="${clase}" id="mk-${p.id}">${p.numero}</div>`,
            iconSize:[size,size], iconAnchor:[size/2,size/2],
        });

        let popup = `<b>${p.id}</b>`;
        if (p.desc) popup += `<br><small style="color:#555">${p.desc}</small>`;
        if (info)   popup += `<br><b style="color:#1d4ed8">${info.docente}</b><br><small>Descanso ${info.descanso}</small>`;
        else        popup += `<br><small style="color:#9ca3af">Sin asignar</small>`;

        marcadores[p.id] = { marker: L.marker([p.lat,p.lng],{icon}).bindPopup(popup).addTo(map), punto:p, info };
    });

    if (puntos.length) map.fitBounds(puntos.map(p=>[p.lat,p.lng]), {padding:[30,30]});

    // ── GPS ──────────────────────────────────────────────────────────────
    let markerYo   = null;
    let watchId    = null;
    let idCercano  = null;

    const btnGps        = document.getElementById('btn-gps');
    const cardCercana   = document.getElementById('card-cercana');
    const listaCercanas = document.getElementById('lista-cercanas');

    btnGps.addEventListener('click', () => {
        if (!navigator.geolocation) { alert('Tu dispositivo no soporta GPS.'); return; }

        if (watchId !== null) {
            navigator.geolocation.clearWatch(watchId);
            watchId = null;
            if (markerYo) { map.removeLayer(markerYo); markerYo = null; }
            restaurarMarcadorCercano();
            idCercano = null;
            cardCercana.innerHTML  = '<p class="text-sm text-gray-400 italic">GPS no activo</p>';
            listaCercanas.innerHTML = '<p class="text-sm text-gray-400 italic">Activa el GPS para ver las posiciones ordenadas por distancia.</p>';
            btnGps.innerHTML = `<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3"/></svg> Activar GPS`;
            btnGps.classList.replace('bg-red-600','bg-green-600');
            btnGps.classList.replace('hover:bg-red-700','hover:bg-green-700');
            return;
        }

        btnGps.textContent = 'Obteniendo GPS…';
        btnGps.disabled = true;

        watchId = navigator.geolocation.watchPosition(pos => {
            const lat = pos.coords.latitude;
            const lng = pos.coords.longitude;

            if (markerYo) {
                markerYo.setLatLng([lat, lng]);
            } else {
                markerYo = L.marker([lat, lng], {
                    icon: L.divIcon({ className:'', html:'<div class="mc-yo"></div>', iconSize:[18,18], iconAnchor:[9,9] }),
                    zIndexOffset: 1000,
                }).bindPopup('<b>Tu ubicación</b>').addTo(map);
                map.setView([lat, lng], 19);
                btnGps.disabled = false;
                btnGps.innerHTML = `<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3"/></svg> Detener GPS`;
                btnGps.classList.replace('bg-green-600','bg-red-600');
                btnGps.classList.replace('hover:bg-green-700','hover:bg-red-700');
            }

            // Ordenar todos los puntos por distancia
            const conDist = puntos.map(p => ({
                ...p,
                dist: haversine(lat, lng, p.lat, p.lng),
                info: posicionDoc[p.id] ?? null,
            })).sort((a,b) => a.dist - b.dist);

            const nuevo = conDist[0];

            // Actualizar marcador cercano
            if (idCercano !== nuevo.id) {
                restaurarMarcadorCercano();
                idCercano = nuevo.id;
                const m = marcadores[nuevo.id];
                m.marker.setIcon(L.divIcon({
                    className:'',
                    html:`<div class="mc-cercano">${nuevo.numero}</div>`,
                    iconSize:[36,36], iconAnchor:[18,18],
                }));
            }

            // Card principal
            const metros = Math.round(nuevo.dist);
            const docente = nuevo.info ? nuevo.info.docente : 'Sin asignar';
            const desc    = nuevo.info
                ? `<p class="text-sm font-semibold text-blue-700 mt-1">${docente}</p>
                   <p class="text-xs text-gray-400">Descanso ${nuevo.info.descanso}</p>`
                : `<p class="text-sm text-gray-400 italic mt-1">Sin asignar</p>`;
            const colorDist = metros<=25?'text-green-600':metros<=80?'text-yellow-600':'text-red-600';
            const iconoDist = metros<=25?'✅':metros<=80?'⚠️':'📍';

            cardCercana.innerHTML = `
                <p class="text-4xl font-black text-green-600">${nuevo.id}</p>
                ${desc}
                <p class="mt-2 text-lg font-bold ${colorDist}">${iconoDist} ${metros < 1000 ? metros+'m' : (nuevo.dist/1000).toFixed(1)+'km'}</p>
                ${nuevo.desc ? `<p class="text-xs text-gray-400 mt-1">${nuevo.desc}</p>` : ''}
            `;

            // Lista de los 8 más cercanos
            listaCercanas.innerHTML = conDist.slice(0,8).map((p,i) => {
                const m2     = Math.round(p.dist);
                const color  = m2<=25?'text-green-600':m2<=80?'text-yellow-600':'text-red-600';
                const icono  = m2<=25?'✅':m2<=80?'⚠️':'📍';
                const doc    = p.info ? `<span class="text-blue-600 font-medium">${p.info.docente}</span>` : `<span class="text-gray-400">Sin asignar</span>`;
                const bg     = i===0 ? 'bg-green-50 border border-green-200' : 'bg-gray-50';
                return `
                <div class="flex items-center gap-3 rounded-lg px-3 py-2 mb-1 ${bg}">
                    <span class="font-black text-base w-10 text-center ${i===0?'text-green-600':'text-gray-700'}">${p.id}</span>
                    <div class="flex-1 min-w-0">
                        <div class="text-xs truncate">${doc}</div>
                    </div>
                    <span class="text-xs font-semibold ${color} whitespace-nowrap">${icono} ${m2<1000?m2+'m':(p.dist/1000).toFixed(1)+'km'}</span>
                </div>`;
            }).join('');

        }, err => {
            btnGps.textContent = 'Activar GPS';
            btnGps.disabled = false;
            watchId = null;
            if (err.code === 1) alert('Permiso de ubicación denegado.');
            else alert('No se pudo obtener la ubicación. Intenta de nuevo.');
        }, { enableHighAccuracy:true, maximumAge:3000, timeout:15000 });
    });

    function restaurarMarcadorCercano() {
        if (!idCercano) return;
        const m = marcadores[idCercano];
        if (!m) return;
        const info  = m.info;
        const clase = info ? 'mc-asignado' : 'mc-libre';
        const size  = info ? 32 : 24;
        m.marker.setIcon(L.divIcon({
            className:'',
            html:`<div class="${clase}">${m.punto.numero}</div>`,
            iconSize:[size,size], iconAnchor:[size/2,size/2],
        }));
    }

    function haversine(lat1,lng1,lat2,lng2) {
        const R = 6371000, r = x => x*Math.PI/180;
        const dL = r(lat2-lat1), dG = r(lng2-lng1);
        const a  = Math.sin(dL/2)**2 + Math.cos(r(lat1))*Math.cos(r(lat2))*Math.sin(dG/2)**2;
        return R*2*Math.atan2(Math.sqrt(a),Math.sqrt(1-a));
    }
});
</script>
@endpush
