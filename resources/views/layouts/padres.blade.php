<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Portal Padres — {{ config('app.name', 'Portal CBI') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-gray-100">

<div class="flex h-screen overflow-hidden">

    <!-- Overlay móvil -->
    <div id="sidebar-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-20 hidden lg:hidden" onclick="toggleSidebar()"></div>

    <!-- Barra lateral -->
    <aside id="sidebar" class="fixed lg:static z-30 w-64 h-full lg:h-screen bg-blue-900 text-white flex flex-col transform -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out shrink-0">

        <div class="px-6 py-5 border-b border-blue-700 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <img src="{{ asset('images/escudoCBI.png') }}" alt="Logo" class="h-9 w-auto opacity-90">
                <div>
                    <h1 class="text-lg font-bold leading-tight">Portal Cebeista</h1>
                    <p class="text-xs text-blue-300">Área de Padres</p>
                </div>
            </div>
            <button onclick="toggleSidebar()" class="lg:hidden text-blue-300 hover:text-white">✕</button>
        </div>

        <!-- Info estudiante -->
        @if(session('padre_estudiante'))
        <div class="px-5 py-4 border-b border-blue-700 bg-blue-800 text-center">
            <p class="text-xs text-blue-300 uppercase tracking-widest mb-1">Estudiante</p>
            <p class="text-sm font-bold leading-tight">{{ session('padre_estudiante')->NOMBRE1 }} {{ session('padre_estudiante')->APELLIDO1 }}</p>
            <p class="text-xs text-blue-300 mt-1">Código: <span class="font-semibold text-white">{{ session('padre_estudiante')->CODIGO }}</span></p>
        </div>
        @endif

        <nav class="flex-1 px-4 py-5 overflow-y-auto">
            @php
                $codigoP       = session('padre_estudiante')->CODIGO ?? null;
                $facturadoP    = $codigoP ? \Illuminate\Support\Facades\DB::table('facturacion')->where('codigo_alumno', $codigoP)->sum('valor') : 0;
                $pagadoP       = $codigoP ? \Illuminate\Support\Facades\DB::table('registro_pagos')->where('codigo_alumno', $codigoP)->sum('valor') : 0;
                $saldoP        = $facturadoP - $pagadoP;
                $exentoP       = $codigoP ? \App\Http\Controllers\ExencionCarteraController::tieneExencion($codigoP) : false;
                $bloqueado     = !$exentoP && $saldoP > 100000;

                $now               = now();
                $abiertoDerrotero  = \Illuminate\Support\Facades\DB::table('FECHAS')->where('CODIGO_FECHA', 'like', 'D%')->where('INICIO', '<=', $now)->where('FIN', '>=', $now)->exists();
                $abiertoSalvavidas = \Illuminate\Support\Facades\DB::table('FECHAS')->where('CODIGO_FECHA', 'like', 'S%')->where('INICIO', '<=', $now)->where('FIN', '>=', $now)->exists();
                $abiertoBoletin    = \Illuminate\Support\Facades\DB::table('FECHAS')->where('CODIGO_FECHA', 'like', 'B%')->where('INICIO', '<=', $now)->where('FIN', '>=', $now)->exists();
                $abiertoNotas      = \Illuminate\Support\Facades\DB::table('FECHAS')->where('CODIGO_FECHA', 'like', 'B%')->where('INICIO', '<=', $now)->exists();
            @endphp

            {{-- Inicio --}}
            <a href="{{ route('padres.portal') }}" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-blue-700 transition text-sm mb-2">
                🏠 Inicio
            </a>

            @if($bloqueado)
                <div class="mb-3 mx-1 p-2 bg-red-900 bg-opacity-50 rounded-lg text-xs text-red-300 text-center">
                    ⚠️ Saldo pendiente de<br>
                    <strong>$ {{ number_format($saldoP, 0, ',', '.') }}</strong><br>
                    Algunos módulos bloqueados.
                </div>
            @endif

            {{-- ── Académico ──────────────────────────────────────────── --}}
            <div class="sidebar-cat mb-1" data-cat="academico">
                <p class="text-xs font-semibold text-blue-400 uppercase tracking-widest px-1 py-2 flex justify-between items-center cursor-pointer select-none hover:text-white transition-colors"
                   onclick="toggleCategoryP(this)">
                    <span>🎓 Académico</span>
                    <svg class="cat-chevron w-3.5 h-3.5 text-blue-400 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                    </svg>
                </p>
                <ul class="space-y-0.5 cat-body overflow-hidden transition-all duration-300" style="max-height:0">

                    @if($bloqueado)
                        <li class="px-3 py-2 rounded-lg bg-blue-950 cursor-not-allowed">
                            <div class="flex items-center gap-2 text-blue-400 text-sm opacity-60">🔒 Consultar promedios</div>
                        </li>
                    @elseif(!$abiertoNotas)
                        <li class="px-3 py-2 rounded-lg bg-blue-950 cursor-not-allowed">
                            <div class="flex items-center gap-2 text-blue-400 text-sm opacity-60">🔒 Consultar promedios</div>
                            <p class="text-xs text-blue-500 mt-0.5 leading-tight">Disponible a partir de la primera entrega de boletines.</p>
                        </li>
                    @else
                        <li><a href="{{ route('padres.notas') }}" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-blue-700 transition text-sm">📋 Consultar promedios</a></li>
                    @endif

                    @if($bloqueado)
                        <li class="px-3 py-2 rounded-lg bg-blue-950 cursor-not-allowed">
                            <div class="flex items-center gap-2 text-blue-400 text-sm opacity-60">🔒 Boletines</div>
                        </li>
                    @elseif(!$abiertoBoletin)
                        <li class="px-3 py-2 rounded-lg bg-blue-950 cursor-not-allowed">
                            <div class="flex items-center gap-2 text-blue-400 text-sm opacity-60">🔒 Boletines</div>
                            <p class="text-xs text-blue-500 mt-0.5 leading-tight">La institución aún no ha publicado los boletines.</p>
                        </li>
                    @else
                        <li><a href="{{ route('padres.boletines') }}" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-blue-700 transition text-sm">📝 Boletines</a></li>
                    @endif

                    @if(!$abiertoSalvavidas)
                        <li class="px-3 py-2 rounded-lg bg-blue-950 cursor-not-allowed">
                            <div class="flex items-center gap-2 text-blue-400 text-sm opacity-60">🔒 Salvavidas</div>
                        </li>
                    @else
                        <li><a href="{{ route('padres.salvavidas') }}" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-blue-700 transition text-sm">🏊 Salvavidas</a></li>
                    @endif

                    @if(!$abiertoDerrotero)
                        <li class="px-3 py-2 rounded-lg bg-blue-950 cursor-not-allowed">
                            <div class="flex items-center gap-2 text-blue-400 text-sm opacity-60">🔒 Recuperaciones</div>
                        </li>
                    @else
                        <li><a href="{{ route('padres.derroteros') }}" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-blue-700 transition text-sm">📌 Recuperaciones</a></li>
                    @endif

                    <li><a href="{{ route('padres.asistencia') }}" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-blue-700 transition text-sm">📅 Asistencia</a></li>
                    <li><a href="{{ route('padres.english_acq') }}" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-blue-700 transition text-sm">🇬🇧 English Acquisition</a></li>
                    <li><a href="{{ route('padres.calendario') }}" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-blue-700 transition text-sm">📆 Calendario académico</a></li>
                </ul>
            </div>

            {{-- ── Comunicaciones ─────────────────────────────────────── --}}
            <div class="sidebar-cat mb-1" data-cat="comunicaciones">
                <p class="text-xs font-semibold text-blue-400 uppercase tracking-widest px-1 py-2 flex justify-between items-center cursor-pointer select-none hover:text-white transition-colors"
                   onclick="toggleCategoryP(this)">
                    <span>📣 Comunicaciones</span>
                    <svg class="cat-chevron w-3.5 h-3.5 text-blue-400 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                    </svg>
                </p>
                <ul class="space-y-0.5 cat-body overflow-hidden transition-all duration-300" style="max-height:0">
                    <li><a href="{{ route('padres.circulares') }}" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-blue-700 transition text-sm">📢 Circulares</a></li>
                    <li><a href="{{ route('padres.documentacion') }}" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-blue-700 transition text-sm">📁 Documentación</a></li>
                </ul>
            </div>

            {{-- ── Financiero ──────────────────────────────────────────── --}}
            <div class="sidebar-cat mb-1" data-cat="financiero">
                <p class="text-xs font-semibold text-blue-400 uppercase tracking-widest px-1 py-2 flex justify-between items-center cursor-pointer select-none hover:text-white transition-colors"
                   onclick="toggleCategoryP(this)">
                    <span>💳 Financiero</span>
                    <svg class="cat-chevron w-3.5 h-3.5 text-blue-400 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                    </svg>
                </p>
                <ul class="space-y-0.5 cat-body overflow-hidden transition-all duration-300" style="max-height:0">
                    <li><a href="{{ route('padres.estado_cuenta') }}" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-blue-700 transition text-sm">📊 Estado de cuenta</a></li>
                </ul>
            </div>

            {{-- ── Contacto ────────────────────────────────────────────── --}}
            <div class="sidebar-cat mb-1" data-cat="contacto">
                <p class="text-xs font-semibold text-blue-400 uppercase tracking-widest px-1 py-2 flex justify-between items-center cursor-pointer select-none hover:text-white transition-colors"
                   onclick="toggleCategoryP(this)">
                    <span>📞 Contacto</span>
                    <svg class="cat-chevron w-3.5 h-3.5 text-blue-400 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                    </svg>
                </p>
                <ul class="space-y-0.5 cat-body overflow-hidden transition-all duration-300" style="max-height:0">
                    <li><a href="{{ route('padres.atencion_docentes') }}" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-blue-700 transition text-sm">🗓 Atención a padres</a></li>
                    <li><a href="{{ route('padres.conducto_regular') }}" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-blue-700 transition text-sm">🗺️ Conducto regular</a></li>
                </ul>
            </div>

        </nav>

        <div class="px-4 py-4 border-t border-blue-700">
            <form method="POST" action="{{ route('padres.salir') }}">
                @csrf
                <button type="submit" class="w-full text-left flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-blue-700 transition text-sm text-blue-200">
                    🚪 Salir del portal
                </button>
            </form>
        </div>

    </aside>

    <!-- Contenido -->
    <div class="flex-1 flex flex-col min-w-0 overflow-y-auto">
        <header class="bg-white shadow px-6 py-4 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <button onclick="toggleSidebar()" class="lg:hidden text-gray-600 hover:text-gray-900">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
                <h2 class="text-xl font-semibold text-gray-700">@yield('header', 'Portal de Padres')</h2>
            </div>
        </header>

        <main class="flex-1 p-6">
            @yield('slot')
        </main>
    </div>

</div>

<script>
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebar-overlay');
        sidebar.classList.toggle('-translate-x-full');
        overlay.classList.toggle('hidden');
    }
</script>

<script>
// ── Acordeón del sidebar de padres ────────────────────────────────────────
function toggleCategoryP(titleEl) {
    var ul      = titleEl.nextElementSibling;
    var chevron = titleEl.querySelector('.cat-chevron');
    var catId   = titleEl.closest('.sidebar-cat').dataset.cat;
    var isOpen  = ul.style.maxHeight && ul.style.maxHeight !== '0px';

    // Cerrar todas las demás
    var saved = JSON.parse(localStorage.getItem('padresCats') || '{}');
    document.querySelectorAll('.sidebar-cat').forEach(function (cat) {
        if (cat.dataset.cat === catId) return;
        cat.querySelector('.cat-body').style.maxHeight = '0px';
        cat.querySelector('.cat-chevron').style.transform = 'rotate(0deg)';
        saved[cat.dataset.cat] = false;
    });

    if (isOpen) {
        ul.style.maxHeight = '0px';
        chevron.style.transform = 'rotate(0deg)';
        saved[catId] = false;
    } else {
        ul.style.maxHeight = ul.scrollHeight + 'px';
        chevron.style.transform = 'rotate(180deg)';
        saved[catId] = true;
    }

    localStorage.setItem('padresCats', JSON.stringify(saved));
}

// Al cargar: abrir la categoría activa (o la guardada)
document.addEventListener('DOMContentLoaded', function () {
    var saved      = JSON.parse(localStorage.getItem('padresCats') || '{}');
    var currentUrl = window.location.href;
    var opened     = false;

    document.querySelectorAll('.sidebar-cat').forEach(function (cat) {
        var catId   = cat.dataset.cat;
        var ul      = cat.querySelector('.cat-body');
        var chevron = cat.querySelector('.cat-chevron');

        var hasActive = Array.from(ul.querySelectorAll('a')).some(function (a) {
            return currentUrl.startsWith(a.href) && a.href !== window.location.origin + '/';
        });

        var shouldOpen = hasActive || (!opened && saved[catId] === true);

        if (shouldOpen && !opened) {
            ul.style.maxHeight = ul.scrollHeight + 'px';
            chevron.style.transform = 'rotate(180deg)';
            opened = true;
        }
    });
});
</script>

</body>
</html>
