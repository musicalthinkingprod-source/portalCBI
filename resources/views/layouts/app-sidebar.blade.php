<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Portal CBI') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        /*
         * Crear stacking context explícito en el contenedor de Leaflet.
         * Sin esto, los panes internos (z-index 1000) participan en el stacking
         * context raíz y pueden quedar sobre el sidebar.
         */
        .leaflet-container { z-index: 0; }

        /*
         * Sidebar oculto en móvil via 'left' (sin transform).
         * Sin transform no se crea capa GPU separada, el z-index CSS puro rige.
         */
        #sidebar { left: -16rem; }
        @media (min-width: 1024px) {
            #sidebar { left: 0; }
            #main-wrapper { padding-left: 16rem; transition: padding-left 300ms ease-in-out; }
        }
    </style>
</head>
<body class="font-sans antialiased bg-gray-100">

{{--
    IMPORTANTE: el contenido principal va PRIMERO en el DOM.
    El sidebar y overlay van AL FINAL, después del contenido.
    Chrome pinta las capas GPU en orden de creación en el DOM cuando hay
    conflictos de compositing. Al colocar el sidebar al final, tanto el
    z-index CSS (2000) como el orden DOM garantizan que quede encima del mapa.
--}}

<div id="main-wrapper" class="flex h-screen overflow-hidden">

    <!-- Contenido principal -->
    <div class="flex-1 flex flex-col min-w-0 overflow-y-auto">

        <!-- Topbar -->
        <header class="bg-white shadow px-6 py-4 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <!-- Botón hamburguesa -->
                <button onclick="toggleSidebar()" class="text-gray-600 hover:text-gray-900 focus:outline-none">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
                <h2 class="text-xl font-semibold text-gray-700">@yield('header', 'Dashboard')</h2>
            </div>
            <div class="flex items-center gap-3">
                {{-- Campana de notificaciones --}}
                <div class="relative" id="notif-wrap">
                    <button id="notif-btn" onclick="toggleNotifPanel()"
                        class="relative text-gray-500 hover:text-blue-600 focus:outline-none transition-colors"
                        title="Notificaciones">
                        <svg id="notif-icon" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 10-12 0v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                        <span id="notif-badge"
                            class="absolute -top-1 -right-1 bg-red-500 text-white text-[10px] font-bold rounded-full min-w-[16px] h-4 flex items-center justify-center px-1 hidden">
                            0
                        </span>
                    </button>

                    {{-- Panel desplegable --}}
                    <div id="notif-panel"
                        class="hidden absolute right-0 mt-2 w-80 bg-white rounded-2xl shadow-xl border border-gray-100 z-50 overflow-hidden"
                        style="top:calc(100% + 6px);">
                        <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                            <span class="text-sm font-bold text-gray-800">Notificaciones</span>
                            <button onclick="marcarTodas()" class="text-xs text-blue-600 hover:underline">Marcar todas leídas</button>
                        </div>
                        <ul id="notif-list" class="max-h-80 overflow-y-auto divide-y divide-gray-50">
                            <li class="px-4 py-6 text-center text-xs text-gray-400 italic" id="notif-empty">Sin notificaciones nuevas.</li>
                        </ul>
                    </div>
                </div>

                <span class="text-sm text-gray-500">{{ auth()->user()->USER ?? '' }}</span>
            </div>
        </header>

        <!-- Página -->
        <main class="flex-1 p-6">
            @yield('slot')
        </main>

    </div>

</div>

<!-- ============================================================
     SIDEBAR Y OVERLAY AL FINAL DEL DOM
     Colocarlos después del contenido garantiza que Chrome los
     pinte encima en el orden de compositing de capas GPU,
     sumado al z-index CSS 2000 / 1999.
     ============================================================ -->

<!-- Overlay para cerrar sidebar en móvil -->
<div id="sidebar-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-[1999] hidden lg:hidden" onclick="toggleSidebar()"></div>

<!-- Barra lateral -->
<aside id="sidebar" class="fixed top-0 left-0 z-[2000] w-64 h-full bg-blue-900 text-white flex flex-col transition-all duration-300 ease-in-out shrink-0 overflow-hidden">

    <!-- Logo / Título -->
    <div class="px-6 py-5 border-b border-blue-700 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <img src="{{ asset('images/escudoCBI.png') }}" alt="Logo" class="h-9 w-auto opacity-90">
            <div>
                <h1 class="text-lg font-bold leading-tight">Portal Cebeista</h1>
                <p class="text-xs text-blue-300">Colegio Bilingüe Integral</p>
            </div>
        </div>
        <!-- Botón cerrar sidebar -->
        <button onclick="toggleSidebar()" class="text-blue-300 hover:text-white">
            ✕
        </button>
    </div>

    {{-- Modificado: acordeón por categoría + restructura + filtrado por rol --}}
    <nav class="flex-1 px-4 py-4 overflow-y-auto" id="sidebar-nav">

        @auth
        @php
            $profile    = auth()->user()->PROFILE;
            $isDoc      = str_starts_with($profile, 'DOC');
            $isSec      = str_starts_with($profile, 'Sec');
            $isContab   = $profile === 'Contab';
            $isSuperAd  = $profile === 'SuperAd';
            $isAdmin    = $profile === 'Admin';
            $isAdminLike = $isSuperAd || $isAdmin;
            $isPiar     = $profile === 'Piar';
            // "Otros" = ConvCor*, Ori, SecC100, SecA, etc. → ven todo excepto Panel de Control
        @endphp

        @php
        // Helper: renderiza un ítem de menú
        function sidebarLink($href, $label) {
            $active = request()->is(ltrim(parse_url($href, PHP_URL_PATH), '/') . '*')
                   || request()->fullUrlIs($href)
                   || request()->url() === $href;
            $cls = $active
                ? 'flex items-center gap-2 px-3 py-2 rounded-lg bg-blue-700 text-white text-sm font-semibold'
                : 'flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-blue-700 transition text-sm';
            return "<li><a href=\"{$href}\" class=\"{$cls}\">{$label}</a></li>";
        }
        @endphp

        {{-- ── Estudiantes: SuperAd, Admin, Ori*, Sec* ── --}}
        @if(!$isDoc && !$isContab && ($isSec || $isAdminLike || str_starts_with($profile, 'Ori')))
        @php $catId = 'estudiantes'; @endphp
        <div class="sidebar-cat mb-1" data-cat="{{ $catId }}">
            <p class="text-xs font-semibold text-blue-400 uppercase tracking-widest px-1 py-2 flex justify-between items-center cursor-pointer select-none hover:text-white transition-colors"
               onclick="toggleCategory(this)">
                <span>Estudiantes</span>
                <svg class="cat-chevron w-3.5 h-3.5 text-blue-400 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                </svg>
            </p>
            <ul class="space-y-1 cat-body overflow-hidden transition-all duration-300" style="max-height:0">
                {!! sidebarLink(route('alumnos.index'), '🎒 Consultar estudiante') !!}
                @if($isAdminLike)
                {!! sidebarLink(route('alumnos.create'), '➕ Matricular estudiante') !!}
                @endif
            </ul>
        </div>
        @endif

        {{-- ── Orientación: Ori* ── --}}
        @if(str_starts_with($profile, 'Ori'))
        @php $catId = 'orientacion'; @endphp
        <div class="sidebar-cat mb-1" data-cat="{{ $catId }}">
            <p class="text-xs font-semibold text-blue-400 uppercase tracking-widest px-1 py-2 flex justify-between items-center cursor-pointer select-none hover:text-white transition-colors"
               onclick="toggleCategory(this)">
                <span>Orientación</span>
                <svg class="cat-chevron w-3.5 h-3.5 text-blue-400 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                </svg>
            </p>
            <ul class="space-y-1 cat-body overflow-hidden transition-all duration-300" style="max-height:0">
                {!! sidebarLink(route('orientacion.directores'), '🏫 Directores de grupo') !!}
            </ul>
        </div>
        @endif

        {{-- ── PIAR: SuperAd, Admin, Ori*, Piar, DOC* ── --}}
        @if($isAdminLike || str_starts_with($profile, 'Ori') || $isPiar || $isDoc)
        @php $catId = 'piar'; @endphp
        <div class="sidebar-cat mb-1" data-cat="{{ $catId }}">
            <p class="text-xs font-semibold text-blue-400 uppercase tracking-widest px-1 py-2 flex justify-between items-center cursor-pointer select-none hover:text-white transition-colors"
               onclick="toggleCategory(this)">
                <span>PIAR</span>
                <svg class="cat-chevron w-3.5 h-3.5 text-blue-400 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                </svg>
            </p>
            <ul class="space-y-1 cat-body overflow-hidden transition-all duration-300" style="max-height:0">
                @if($isAdminLike || str_starts_with($profile, 'Ori') || $isPiar)
                {!! sidebarLink(route('piar.buscar'), '📋 Crear / Editar PIAR') !!}
                @if(!$isSuperAd)
                {!! sidebarLink(route('piar.informe'), '📊 Informe PIAR') !!}
                @endif
                {!! sidebarLink(route('control.piar_fechas'), '🎛️ Control de etapas PIAR') !!}
                @endif
                {!! sidebarLink(route('piar.anexo2.index'), '📝 Diligenciamiento PIAR') !!}
            </ul>
        </div>
        @endif

        {{-- ── Trabajo Docente: SuperAd, Admin, DOC* ── --}}
        @if($isSuperAd || $isAdmin || $isDoc)
        @php $catId = 'trabajo-docente'; @endphp
        <div class="sidebar-cat mb-1" data-cat="{{ $catId }}">
            <p class="text-xs font-semibold text-blue-400 uppercase tracking-widest px-1 py-2 flex justify-between items-center cursor-pointer select-none hover:text-white transition-colors"
               onclick="toggleCategory(this)">
                <span>Trabajo Docente</span>
                <svg class="cat-chevron w-3.5 h-3.5 text-blue-400 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                </svg>
            </p>
            <ul class="space-y-1 cat-body overflow-hidden transition-all duration-300" style="max-height:0">
                @if($isSuperAd || $isDoc)
                {!! sidebarLink(route('notas.index'), '📋 Notas') !!}
                {!! sidebarLink(route('notas.v2.index'), '🧪 Planilla ponderada') !!}
                {!! sidebarLink(route('english-acq.docente'), '🇬🇧 English Acquisition') !!}
                {!! sidebarLink(route('salvavidas.index'), '🏊 Salvavidas') !!}
                {!! sidebarLink(route('derroteros.docente'), '📌 Recuperaciones') !!}
                @if($isDoc)
                {!! sidebarLink(route('horarios.mi_horario'), '🗓️ Mi Horario') !!}
                @endif
                @endif
                {!! sidebarLink(route('observaciones.index'), '📝 Observaciones 2026') !!}
            </ul>
        </div>
        @endif

        {{-- ── Gestión Docente: SuperAd, Admin, DOC* ── --}}
        @if($isSuperAd || $isAdmin || $isDoc)
        @php $catId = 'gestion-docente'; @endphp
        <div class="sidebar-cat mb-1" data-cat="{{ $catId }}">
            <p class="text-xs font-semibold text-blue-400 uppercase tracking-widest px-1 py-2 flex justify-between items-center cursor-pointer select-none hover:text-white transition-colors"
               onclick="toggleCategory(this)">
                <span>Gestión Docente</span>
                <svg class="cat-chevron w-3.5 h-3.5 text-blue-400 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                </svg>
            </p>
            <ul class="space-y-1 cat-body overflow-hidden transition-all duration-300" style="max-height:0">
                {!! sidebarLink(route('correcciones.index'), '🔧 Corrección de notas') !!}
                @if($isDoc)
                {!! sidebarLink(route('vigilancias.docente'), '🗺️ Vigilancias') !!}
                @endif
                @if($isSuperAd)
                {!! sidebarLink(route('horarios.index'), '🗓️ Horarios') !!}
                {!! sidebarLink(route('horarios.disponibilidad'), '🟢 Disponibilidad docentes') !!}
                {!! sidebarLink(route('asistencia-personal.reemplazos'), '🔄 Reemplazos') !!}
                @endif
            </ul>
        </div>
        @endif

        {{-- ── Asistencia: todos excepto Contab y Piar ── --}}
        @if(!$isContab && !$isPiar)
        @php $catId = 'asistencia'; @endphp
        <div class="sidebar-cat mb-1" data-cat="{{ $catId }}">
            <p class="text-xs font-semibold text-blue-400 uppercase tracking-widest px-1 py-2 flex justify-between items-center cursor-pointer select-none hover:text-white transition-colors"
               onclick="toggleCategory(this)">
                <span>Asistencia</span>
                <svg class="cat-chevron w-3.5 h-3.5 text-blue-400 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                </svg>
            </p>
            <ul class="space-y-1 cat-body overflow-hidden transition-all duration-300" style="max-height:0">
                @if($isSec || $isSuperAd)
                {!! sidebarLink(route('asistencia.registro'), '✏️ Registrar asistencia') !!}
                @endif
                @if($profile === 'SecA' || $isAdmin)
                {!! sidebarLink(route('asistencia-personal.index'), '👥 Asistencia personal') !!}
                @endif
                @if($profile === 'SecA')
                {!! sidebarLink(route('asistencia-personal.registro'), '✏️ Registrar personal') !!}
                @endif
                @if(!$isSuperAd)
                {!! sidebarLink(route('asistencia.reporte'), '📋 Reporte de asistencia') !!}
                @endif
                @if($isAdminLike || $isSec)
                {!! sidebarLink(route('llamadas.index'), '📞 Llamadas por inasistencia') !!}
                @if(!$isSuperAd)
                {!! sidebarLink(route('llamadas.reporte'), '📊 Reporte de llamadas') !!}
                @endif
                @endif
            </ul>
        </div>
        @endif

        {{-- ── Control de Pagos: SuperAd, Admin, Contab, SecC100 ── --}}
        @if(in_array($profile, ['SuperAd','Admin','Contab','SecC100']))
        @php $catId = 'control-pagos'; @endphp
        <div class="sidebar-cat mb-1" data-cat="{{ $catId }}">
            <p class="text-xs font-semibold text-blue-400 uppercase tracking-widest px-1 py-2 flex justify-between items-center cursor-pointer select-none hover:text-white transition-colors"
               onclick="toggleCategory(this)">
                <span>Control de Pagos</span>
                <svg class="cat-chevron w-3.5 h-3.5 text-blue-400 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                </svg>
            </p>
            <ul class="space-y-1 cat-body overflow-hidden transition-all duration-300" style="max-height:0">
                @if($profile !== 'SecC100')
                {!! sidebarLink(route('control.estudiante'), '🔍 Consultar estudiante') !!}
                {!! sidebarLink(route('pagos.index'), '💳 Pagos') !!}
                {!! sidebarLink(route('facturacion.index'), '🧾 Facturación') !!}
                @if(!$isContab)
                {!! sidebarLink(route('facturacion.auto'), '⚡ Facturación automática') !!}
                @endif
                {!! sidebarLink(route('cartera.index'), '📊 Informe de cartera') !!}
                {!! sidebarLink(route('cartera.deudores'), '🔴 Cartera / Anticipos') !!}
                {!! sidebarLink(route('listado-estudiantes.index'), '🎓 Listado estudiantes') !!}
                {!! sidebarLink(route('world-office.index'), '📄 Plantilla World Office') !!}
                {!! sidebarLink(route('parametros.index'), '⚙️ Parámetros facturación') !!}
                @endif
                {!! sidebarLink(route('cartera.seguimiento.informe'), '📋 Seguimiento cartera') !!}
                {!! sidebarLink(route('cartera.por_cc'), '🪪 Cartera por CC') !!}
                @if($isAdminLike)
                {!! sidebarLink(route('admin.exenciones-cartera.index'), '🔓 Exenciones portal padres') !!}
                @endif
            </ul>
        </div>
        @endif

        {{-- ── Corrección de notas: Ori* (sección propia) ── --}}
        @if(str_starts_with($profile, 'Ori'))
        @php $catId = 'correcciones-ori'; @endphp
        <div class="sidebar-cat mb-1" data-cat="{{ $catId }}">
            <p class="text-xs font-semibold text-blue-400 uppercase tracking-widest px-1 py-2 flex justify-between items-center cursor-pointer select-none hover:text-white transition-colors"
               onclick="toggleCategory(this)">
                <span>Gestión Académica</span>
                <svg class="cat-chevron w-3.5 h-3.5 text-blue-400 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                </svg>
            </p>
            <ul class="space-y-1 cat-body overflow-hidden transition-all duration-300" style="max-height:0">
                {!! sidebarLink(route('correcciones.index'), '🔧 Solicitudes de corrección') !!}
                {!! sidebarLink(route('correcciones.create'), '➕ Nueva corrección') !!}
            </ul>
        </div>
        @endif

        {{-- ── Seguimiento Académico: Admin, Sec*, DOC*, Ori* (no SuperAd, usa sección Informes) ── --}}
        @if(!$isSuperAd && ($isAdmin || $isSec || $isDoc || str_starts_with($profile, 'Ori')))
        @php $catId = 'seguimiento-academico'; @endphp
        <div class="sidebar-cat mb-1" data-cat="{{ $catId }}">
            <p class="text-xs font-semibold text-blue-400 uppercase tracking-widest px-1 py-2 flex justify-between items-center cursor-pointer select-none hover:text-white transition-colors"
               onclick="toggleCategory(this)">
                <span>Seguimiento Académico</span>
                <svg class="cat-chevron w-3.5 h-3.5 text-blue-400 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                </svg>
            </p>
            <ul class="space-y-1 cat-body overflow-hidden transition-all duration-300" style="max-height:0">
                @if($isAdmin || $isSec)
                {!! sidebarLink(route('derroteros.index'), '📊 Informe derroteros') !!}
                {!! sidebarLink(route('salvavidas.reporte'), '📊 Reporte salvavidas') !!}
                @endif
                {!! sidebarLink(route('informes.boletin'), '📋 Boletines') !!}
            </ul>
        </div>
        @endif

        {{-- ── SecC100: Académico ── --}}
        @if($profile === 'SecC100')
        @php $catId = 'academico'; @endphp
        <div class="sidebar-cat mb-1" data-cat="{{ $catId }}">
            <p class="text-xs font-semibold text-blue-400 uppercase tracking-widest px-1 py-2 flex justify-between items-center cursor-pointer select-none hover:text-white transition-colors"
               onclick="toggleCategory(this)">
                <span>Académico</span>
                <svg class="cat-chevron w-3.5 h-3.5 text-blue-400 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                </svg>
            </p>
            <ul class="space-y-1 cat-body overflow-hidden transition-all duration-300" style="max-height:0">
                {!! sidebarLink(route('horarios.index'), '🗓️ Horarios') !!}
            </ul>
        </div>
        @endif

        {{-- ── ConvCor28: Vigilancias ── --}}
        @if($profile === 'ConvCor28')
        @php $catId = 'vigilancias'; @endphp
        <div class="sidebar-cat mb-1" data-cat="{{ $catId }}">
            <p class="text-xs font-semibold text-blue-400 uppercase tracking-widest px-1 py-2 flex justify-between items-center cursor-pointer select-none hover:text-white transition-colors"
               onclick="toggleCategory(this)">
                <span>Vigilancias</span>
                <svg class="cat-chevron w-3.5 h-3.5 text-blue-400 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                </svg>
            </p>
            <ul class="space-y-1 cat-body overflow-hidden transition-all duration-300" style="max-height:0">
                {!! sidebarLink(route('vigilancias.control'), '🔍 Control de vigilancias') !!}
            </ul>
        </div>
        @endif

        {{-- ── Vigilancias: solo SuperAd ── --}}
        @if($isSuperAd)
        @php $catId = 'vigilancias-admin'; @endphp
        <div class="sidebar-cat mb-1" data-cat="{{ $catId }}">
            <p class="text-xs font-semibold text-blue-400 uppercase tracking-widest px-1 py-2 flex justify-between items-center cursor-pointer select-none hover:text-white transition-colors"
               onclick="toggleCategory(this)">
                <span>Vigilancias</span>
                <svg class="cat-chevron w-3.5 h-3.5 text-blue-400 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                </svg>
            </p>
            <ul class="space-y-1 cat-body overflow-hidden transition-all duration-300" style="max-height:0">
                {!! sidebarLink(route('vigilancias.admin'), '🗺️ Gestión de vigilancias') !!}
                {!! sidebarLink(route('vigilancias.control'), '🔍 Control de vigilancias') !!}
            </ul>
        </div>
        @endif

        {{-- ── Gestión Personal: solo SuperAd ── --}}
        @if($isSuperAd)
        @php $catId = 'gestion-personal'; @endphp
        <div class="sidebar-cat mb-1" data-cat="{{ $catId }}">
            <p class="text-xs font-semibold text-blue-400 uppercase tracking-widest px-1 py-2 flex justify-between items-center cursor-pointer select-none hover:text-white transition-colors"
               onclick="toggleCategory(this)">
                <span>Gestión Personal</span>
                <svg class="cat-chevron w-3.5 h-3.5 text-blue-400 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                </svg>
            </p>
            <ul class="space-y-1 cat-body overflow-hidden transition-all duration-300" style="max-height:0">
                {!! sidebarLink(route('asistencia-personal.index'), '👥 Asistencia personal') !!}
                {!! sidebarLink(route('asistencia-personal.permisos'), '📋 Permisos') !!}
            </ul>
        </div>
        @endif

        {{-- ── Comunicaciones: SuperAd, Admin, Ori*, SecC100 ── --}}
        @if($isSuperAd || $isAdmin || str_starts_with($profile, 'Ori') || $profile === 'SecC100')
        @php $catId = 'comunicaciones'; @endphp
        <div class="sidebar-cat mb-1" data-cat="{{ $catId }}">
            <p class="text-xs font-semibold text-blue-400 uppercase tracking-widest px-1 py-2 flex justify-between items-center cursor-pointer select-none hover:text-white transition-colors"
               onclick="toggleCategory(this)">
                <span>Comunicaciones</span>
                <svg class="cat-chevron w-3.5 h-3.5 text-blue-400 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                </svg>
            </p>
            <ul class="space-y-1 cat-body overflow-hidden transition-all duration-300" style="max-height:0">
                {!! sidebarLink(route('circulares.index'), '📄 Circulares') !!}
                @if($isSuperAd || $isAdmin || str_starts_with($profile, 'Ori'))
                {!! sidebarLink(route('documentacion.index'), '📁 Documentación') !!}
                {!! sidebarLink(route('informes.boletin'), '📋 Boletines') !!}
                @endif
            </ul>
        </div>
        @endif

        {{-- ── Informes: solo SuperAd ── --}}
        @if($isSuperAd)
        @php $catId = 'informes'; @endphp
        <div class="sidebar-cat mb-1" data-cat="{{ $catId }}">
            <p class="text-xs font-semibold text-blue-400 uppercase tracking-widest px-1 py-2 flex justify-between items-center cursor-pointer select-none hover:text-white transition-colors"
               onclick="toggleCategory(this)">
                <span>Informes</span>
                <svg class="cat-chevron w-3.5 h-3.5 text-blue-400 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                </svg>
            </p>
            <ul class="space-y-1 cat-body overflow-hidden transition-all duration-300" style="max-height:0">
                {!! sidebarLink(route('piar.informe'), '📊 Informe PIAR') !!}
                {!! sidebarLink(route('notas.reporte'), '📊 Informe de digitación') !!}
                {!! sidebarLink(route('english-acq.informe'), '📊 Informe English ACQ') !!}
                {!! sidebarLink(route('derroteros.index'), '📊 Informe derroteros') !!}
                {!! sidebarLink(route('salvavidas.reporte'), '📊 Reporte salvavidas') !!}
                {!! sidebarLink(route('asistencia.reporte'), '📋 Reporte de asistencia') !!}
                {!! sidebarLink(route('llamadas.reporte'), '📊 Reporte de llamadas') !!}
                {!! sidebarLink(route('control.planilla'), '📋 Informe de planilla') !!}
            </ul>
        </div>
        @endif

        {{-- ── Panel de Control: solo SuperAd ── --}}
        @if($isSuperAd)
        @php $catId = 'panel-control'; @endphp
        <div class="sidebar-cat mb-1" data-cat="{{ $catId }}">
            <p class="text-xs font-semibold text-blue-400 uppercase tracking-widest px-1 py-2 flex justify-between items-center cursor-pointer select-none hover:text-white transition-colors"
               onclick="toggleCategory(this)">
                <span>Panel de Control</span>
                <svg class="cat-chevron w-3.5 h-3.5 text-blue-400 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                </svg>
            </p>
            <ul class="space-y-1 cat-body overflow-hidden transition-all duration-300" style="max-height:0">
{!! sidebarLink(route('admin.usuarios'), '👤 Gestión de usuarios') !!}
                {!! sidebarLink(route('admin.directores'), '🏫 Directores de grupo') !!}
                {!! sidebarLink(route('admin.asignaciones'), '🔄 Asignaciones') !!}
                {!! sidebarLink(route('admin.fechas'), '📅 Fechas') !!}
                {!! sidebarLink(route('calendario.index'), '📆 Calendario académico') !!}
                {!! sidebarLink(route('listados.index'), '🗂️ Listados especiales') !!}
                {!! sidebarLink(route('nomina.index'), '👥 Gestión de personal') !!}
                {!! sidebarLink(route('rutas.index'), '🚌 Listado de rutas') !!}
                {!! sidebarLink(route('backup.index'), '💾 Copia de seguridad') !!}
            </ul>
        </div>
        @endif

        {{-- ── Copia de Seguridad: Admin, Contab, Sec* (no SuperAd, ya lo tiene arriba) ── --}}
        @if(!$isSuperAd && ($isAdmin || $isContab || $isSec))
        @php $catId = 'copia-seguridad'; @endphp
        <div class="sidebar-cat mb-1" data-cat="{{ $catId }}">
            <p class="text-xs font-semibold text-blue-400 uppercase tracking-widest px-1 py-2 flex justify-between items-center cursor-pointer select-none hover:text-white transition-colors"
               onclick="toggleCategory(this)">
                <span>Copia de Seguridad</span>
                <svg class="cat-chevron w-3.5 h-3.5 text-blue-400 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                </svg>
            </p>
            <ul class="space-y-1 cat-body overflow-hidden transition-all duration-300" style="max-height:0">
                {!! sidebarLink(route('backup.index'), '💾 Descargar copia') !!}
            </ul>
        </div>
        @endif

        @endauth

    </nav>

    <!-- Sesión -->
    <div class="px-4 py-4 border-t border-blue-700">
        @auth
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="w-full text-left flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-blue-700 transition text-sm text-blue-200">
                    🚪 Cerrar sesión
                </button>
            </form>
        @else
            <a href="{{ route('login') }}" class="w-full text-left flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-blue-700 transition text-sm text-blue-200">
                🔑 Iniciar sesión
            </a>
        @endauth
    </div>

</aside>

<script>
    function toggleSidebar() {
        const sidebar     = document.getElementById('sidebar');
        const overlay     = document.getElementById('sidebar-overlay');
        const mainWrapper = document.getElementById('main-wrapper');
        const isDesktop   = window.innerWidth >= 1024;

        if (isDesktop) {
            // Escritorio: sidebar empuja el contenido via padding-left
            const collapsed = sidebar.dataset.collapsed === 'true';
            if (collapsed) {
                sidebar.style.width           = '16rem';
                mainWrapper.style.paddingLeft = '16rem';
                sidebar.dataset.collapsed     = 'false';
                localStorage.setItem('sidebarCollapsed', 'false');
            } else {
                sidebar.style.width           = '0';
                mainWrapper.style.paddingLeft = '0';
                sidebar.dataset.collapsed     = 'true';
                localStorage.setItem('sidebarCollapsed', 'true');
            }
        } else {
            // Móvil/Tablet: sidebar se superpone (overlay)
            const abierto = sidebar.dataset.mobileOpen === 'true';
            if (abierto) {
                sidebar.style.left         = '';
                sidebar.dataset.mobileOpen = 'false';
                overlay.classList.add('hidden');
            } else {
                sidebar.style.left         = '0';
                sidebar.dataset.mobileOpen = 'true';
                overlay.classList.remove('hidden');
            }
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        const sidebar     = document.getElementById('sidebar');
        const mainWrapper = document.getElementById('main-wrapper');

        if (window.innerWidth >= 1024) {
            if (localStorage.getItem('sidebarCollapsed') === 'true') {
                // Suprimir transición en carga inicial
                sidebar.style.transition      = 'none';
                mainWrapper.style.transition  = 'none';
                sidebar.style.width           = '0';
                mainWrapper.style.paddingLeft = '0';
                sidebar.dataset.collapsed     = 'true';
                requestAnimationFrame(() => requestAnimationFrame(() => {
                    sidebar.style.transition     = '';
                    mainWrapper.style.transition = '';
                }));
            }
            // Si no está colapsado, el CSS media query aplica padding-left: 16rem
        } else {
            sidebar.dataset.mobileOpen = 'false';
        }
    });
</script>

<script>
// ── Acordeón del sidebar ──────────────────────────────────────────────────
function toggleCategory(titleEl) {
    var ul      = titleEl.nextElementSibling;
    var chevron = titleEl.querySelector('.cat-chevron');
    var catId   = titleEl.closest('.sidebar-cat').dataset.cat;
    var isOpen  = ul.style.maxHeight && ul.style.maxHeight !== '0px';

    // Cerrar todas las demás categorías
    var saved = JSON.parse(localStorage.getItem('sidebarCategories') || '{}');
    document.querySelectorAll('.sidebar-cat').forEach(function (cat) {
        if (cat.dataset.cat === catId) return;
        var otherUl      = cat.querySelector('.cat-body');
        var otherChevron = cat.querySelector('.cat-chevron');
        otherUl.style.maxHeight = '0px';
        otherChevron.style.transform = 'rotate(0deg)';
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

    localStorage.setItem('sidebarCategories', JSON.stringify(saved));
}

// Al cargar: restaurar estado guardado y abrir la categoría activa
document.addEventListener('DOMContentLoaded', function () {
    var saved      = JSON.parse(localStorage.getItem('sidebarCategories') || '{}');
    var currentUrl = window.location.href;

    document.querySelectorAll('.sidebar-cat').forEach(function (cat) {
        var catId   = cat.dataset.cat;
        var ul      = cat.querySelector('.cat-body');
        var chevron = cat.querySelector('.cat-chevron');
        var titleEl = cat.querySelector('p');

        // ¿Contiene el enlace activo?
        var hasActive = Array.from(ul.querySelectorAll('a')).some(function (a) {
            return currentUrl.startsWith(a.href) && a.href !== window.location.origin + '/';
        });

        var shouldOpen = hasActive || saved[catId] === true;

        if (shouldOpen) {
            ul.style.maxHeight = ul.scrollHeight + 'px';
            chevron.style.transform = 'rotate(180deg)';
        }
    });
});

// ── Command Palette (Ctrl+K) ──────────────────────────────────────────────
(function () {
    var overlay   = document.getElementById('cmd-overlay');
    var input     = document.getElementById('cmd-input');
    var list      = document.getElementById('cmd-list');
    var modules   = [];
    var selected  = -1;

    // Indexar módulos desde el DOM del sidebar al cargar
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.sidebar-cat').forEach(function (cat) {
            var category = cat.querySelector('p > span') ? cat.querySelector('p > span').textContent.trim()
                         : cat.querySelector('p').textContent.trim();
            cat.querySelectorAll('ul a').forEach(function (a) {
                modules.push({
                    label:    a.textContent.trim(),
                    href:     a.href,
                    category: category
                });
            });
        });
    });

    window.abrirPaleta = function () {
        overlay.classList.remove('hidden');
        overlay.classList.add('flex');
        input.value = '';
        selected = -1;
        renderList('');
        setTimeout(function () { input.focus(); }, 50);
    };

    window.cerrarPaleta = function () {
        overlay.classList.add('hidden');
        overlay.classList.remove('flex');
    };

    function renderList(query) {
        var q = query.toLowerCase().trim();
        var results = q === ''
            ? modules.slice(0, 12)
            : modules.filter(function (m) {
                return m.label.toLowerCase().includes(q) || m.category.toLowerCase().includes(q);
              }).slice(0, 12);

        if (results.length === 0) {
            list.innerHTML = '<li class="px-4 py-6 text-center text-sm text-gray-400 italic">Sin resultados.</li>';
            selected = -1;
            return;
        }

        list.innerHTML = results.map(function (m, i) {
            return '<li><a href="' + m.href + '" class="cmd-item flex items-center justify-between px-4 py-2.5 hover:bg-blue-50 cursor-pointer" data-idx="' + i + '">'
                + '<span class="text-sm text-gray-800">' + escHtml(m.label) + '</span>'
                + '<span class="text-[10px] font-semibold text-gray-400 bg-gray-100 px-2 py-0.5 rounded-full ml-2 shrink-0">' + escHtml(m.category) + '</span>'
                + '</a></li>';
        }).join('');

        selected = -1;
    }

    function escHtml(s) {
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }

    function setSelected(idx) {
        var items = list.querySelectorAll('.cmd-item');
        items.forEach(function (el) { el.classList.remove('bg-blue-50'); el.style.background=''; });
        if (idx >= 0 && idx < items.length) {
            selected = idx;
            items[idx].classList.add('bg-blue-50');
            items[idx].scrollIntoView({ block: 'nearest' });
        }
    }

    input.addEventListener('input', function () {
        renderList(input.value);
        setSelected(0);
    });

    input.addEventListener('keydown', function (e) {
        var items = list.querySelectorAll('.cmd-item');
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            setSelected(Math.min(selected + 1, items.length - 1));
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            setSelected(Math.max(selected - 1, 0));
        } else if (e.key === 'Enter') {
            if (selected >= 0 && items[selected]) {
                window.location.href = items[selected].href;
            }
        } else if (e.key === 'Escape') {
            cerrarPaleta();
        }
    });

    document.addEventListener('keydown', function (e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            if (overlay.classList.contains('hidden')) {
                abrirPaleta();
            } else {
                cerrarPaleta();
            }
        }
        if (e.key === 'Escape' && !overlay.classList.contains('hidden')) {
            cerrarPaleta();
        }
    });
})();
</script>

@stack('scripts')

{{-- ── Modal Command Palette (Ctrl+K) ── --}}
<div id="cmd-overlay" class="fixed inset-0 bg-black/50 z-[3000] hidden items-start justify-center pt-24 px-4"
     onclick="if(event.target===this)cerrarPaleta()">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden">
        <div class="flex items-center gap-3 px-4 py-3 border-b border-gray-100">
            <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z"/>
            </svg>
            <input id="cmd-input" type="text" placeholder="Buscar módulo..."
                class="flex-1 outline-none text-sm text-gray-700 placeholder-gray-400 bg-transparent">
            <kbd class="text-[10px] bg-gray-100 text-gray-400 px-1.5 py-0.5 rounded font-mono flex-shrink-0">Esc</kbd>
        </div>
        <ul id="cmd-list" class="max-h-72 overflow-y-auto py-2"></ul>
        <div class="px-4 py-2 border-t border-gray-50 flex gap-4 text-[10px] text-gray-400">
            <span><kbd class="bg-gray-100 px-1 rounded">↑↓</kbd> navegar</span>
            <span><kbd class="bg-gray-100 px-1 rounded">Enter</kbd> ir</span>
            <span><kbd class="bg-gray-100 px-1 rounded">Esc</kbd> cerrar</span>
        </div>
    </div>
</div>

{{-- ── Sistema de notificaciones en tiempo real (polling) ── --}}
<script>
(function () {
    const POLL_MS   = 30000; // cada 30 segundos
    const urlNuevas = '{{ route('notificaciones.nuevas') }}';
    const urlLeer   = (id) => `/notificaciones/${id}/leer`;
    const urlTodas  = '{{ route('notificaciones.leer_todas') }}';
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    let panelAbierto = false;

    // ── Toggle panel ──
    window.toggleNotifPanel = function () {
        const panel = document.getElementById('notif-panel');
        panelAbierto = !panelAbierto;
        panel.classList.toggle('hidden', !panelAbierto);
        if (panelAbierto) pollAhora();
    };

    // ── Cerrar panel al hacer click fuera ──
    // Usa fase de CAPTURA con el target original para evitar falsos negativos
    // si el target se desmonta antes del bubble (p. ej. al quitar un <li> leído).
    document.addEventListener('click', function (e) {
        const wrap = document.getElementById('notif-wrap');
        if (wrap && !wrap.contains(e.target) && panelAbierto) {
            panelAbierto = false;
            document.getElementById('notif-panel').classList.add('hidden');
        }
    }, true);

    // ── Marcar todas como leídas ──
    window.marcarTodas = function () {
        fetch(urlTodas, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
        }).then(() => {
            actualizarUI(0, []);
        });
    };

    // ── Marcar una como leída ──
    // Envía el marcado con sendBeacon (sobrevive a la navegación) y
    // si el usuario permanece en la página, refresca la lista.
    window.marcarLeida = function (id) {
        const url = urlLeer(id);
        let enviado = false;
        try {
            if (navigator.sendBeacon) {
                const fd = new FormData();
                fd.append('_token', csrfToken);
                enviado = navigator.sendBeacon(url, fd);
            }
        } catch (e) { enviado = false; }

        if (!enviado) {
            fetch(url, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                keepalive: true
            }).catch(() => {});
        }

        // UI optimista: quitar el li inmediatamente
        const li = document.querySelector(`[data-notif-id="${id}"]`);
        if (li) li.remove();
        // Actualizar contador local
        const badge = document.getElementById('notif-badge');
        if (badge) {
            const actual = parseInt(badge.textContent, 10) || 0;
            const nuevo  = Math.max(0, actual - 1);
            badge.textContent = nuevo > 9 ? '9+' : nuevo;
            badge.classList.toggle('hidden', nuevo === 0);
            if (nuevo === 0) {
                const lista = document.getElementById('notif-list');
                if (lista) lista.innerHTML = '<li class="px-4 py-6 text-center text-xs text-gray-400 italic" id="notif-empty">Sin notificaciones nuevas.</li>';
            }
        }
    };

    // ── Actualizar badge y lista ──
    function actualizarUI(total, notifs) {
        const badge = document.getElementById('notif-badge');
        const lista = document.getElementById('notif-list');
        const empty = document.getElementById('notif-empty');

        badge.textContent = total > 9 ? '9+' : total;
        badge.classList.toggle('hidden', total === 0);

        if (total === 0) {
            lista.innerHTML = '<li class="px-4 py-6 text-center text-xs text-gray-400 italic" id="notif-empty">Sin notificaciones nuevas.</li>';
            return;
        }

        const urlBackup = '{{ route('backup.index') }}';
        lista.innerHTML = notifs.map(n => {
            const fecha = new Date(n.created_at).toLocaleString('es-CO', {
                day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit'
            });
            const esBackup = n.tipo === 'backup';
            const esPiar   = typeof n.tipo === 'string' && n.tipo.startsWith('piar_');
            const destino  = esBackup ? urlBackup : (n.url || null);

            let dotColor = 'bg-blue-500';
            let prefijo  = '';
            if (esBackup) {
                dotColor = 'bg-amber-500';
                prefijo  = '💾 ';
            } else if (esPiar) {
                if (n.tipo.endsWith('_aprob'))       { dotColor = 'bg-green-500';  prefijo = '✅ '; }
                else if (n.tipo.endsWith('_observ')) { dotColor = 'bg-orange-500'; prefijo = '💬 '; }
                else if (n.tipo.endsWith('_entreg')) { dotColor = 'bg-indigo-500'; prefijo = '📥 '; }
            }

            const urlAttr = destino ? ` data-notif-url="${escHtml(destino)}"` : '';
            return `<li data-notif-id="${n.id}"${urlAttr} class="px-4 py-3 hover:bg-blue-50 cursor-pointer group">
                <div class="flex items-start gap-2">
                    <div class="mt-0.5 w-2 h-2 rounded-full ${dotColor} flex-shrink-0"></div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-semibold text-gray-800 truncate">${prefijo}${escHtml(n.titulo)}</p>
                        <p class="text-xs text-gray-500 mt-0.5 leading-snug">${escHtml(n.mensaje)}</p>
                        <p class="text-[10px] text-gray-400 mt-1">${fecha}</p>
                    </div>
                </div>
            </li>`;
        }).join('');
    }

    // ── Delegación: manejar click en cualquier notificación ──
    (function () {
        const lista = document.getElementById('notif-list');
        if (!lista || lista.dataset.delegado === '1') return;
        lista.dataset.delegado = '1';
        lista.addEventListener('click', function (e) {
            const li = e.target.closest('li[data-notif-id]');
            if (!li) return;
            const id  = li.getAttribute('data-notif-id');
            const url = li.getAttribute('data-notif-url');
            if (id) window.marcarLeida(parseInt(id, 10));
            if (url) window.location.href = url;
        });
    })();

    function escHtml(str) {
        return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    // ── Toast ──
    let toastCount = 0;
    function mostrarToast(titulo, mensaje) {
        const id = 'toast-' + (++toastCount);
        const div = document.createElement('div');
        div.id = id;
        div.style.cssText = [
            'position:fixed','bottom:24px','right:24px','z-index:9999',
            'background:#1d4ed8','color:#fff','border-radius:14px',
            'padding:14px 18px','max-width:320px','box-shadow:0 8px 32px rgba(0,0,0,.2)',
            'font-size:13px','line-height:1.4','cursor:pointer',
            'animation:slideInToast .25s ease-out',
        ].join(';');
        div.innerHTML = `<strong style="display:block;margin-bottom:3px;">🔔 ${escHtml(titulo)}</strong>${escHtml(mensaje)}`;
        div.onclick = () => div.remove();
        document.body.appendChild(div);
        setTimeout(() => div && div.remove(), 6000);
    }

    // ── Toast de backup (color ámbar) ──
    let backupToastMostrado = false;
    function mostrarToastBackup() {
        if (backupToastMostrado) return;
        // Solo una vez por sesión de pestaña
        if (sessionStorage.getItem('backup_recordado_hoy')) return;

        backupToastMostrado = true;
        sessionStorage.setItem('backup_recordado_hoy', '1');

        const id  = 'toast-backup';
        const div = document.createElement('div');
        div.id    = id;
        div.style.cssText = [
            'position:fixed','bottom:24px','right:24px','z-index:9999',
            'background:#92400e','color:#fff','border-radius:14px',
            'padding:14px 18px','max-width:340px','box-shadow:0 8px 32px rgba(0,0,0,.25)',
            'font-size:13px','line-height:1.4','cursor:pointer',
            'animation:slideInToast .25s ease-out',
        ].join(';');
        const urlBackup = '{{ route('backup.index') }}';
        div.innerHTML = `<strong style="display:block;margin-bottom:4px;">⚠️ Copia de seguridad pendiente</strong>`
            + `No has descargado la copia de seguridad hoy. `
            + `<a href="${urlBackup}" style="color:#fde68a;text-decoration:underline;font-weight:600;">Ir al módulo</a>`;
        div.onclick = (e) => { if (!e.target.closest('a')) div.remove(); };
        document.body.appendChild(div);
        setTimeout(() => div && div.remove(), 12000);
    }

    // ── Polling ──
    let ultimoTotal = null;

    function pollAhora() {
        fetch(urlNuevas, { headers: { 'Accept': 'application/json' } })
            .then(r => r.json())
            .then(data => {
                const { total, notificaciones } = data;
                actualizarUI(total, notificaciones);

                // Toast solo si hay nuevas respecto a la última consulta
                if (ultimoTotal !== null && total > ultimoTotal && !panelAbierto) {
                    const nuevas = total - ultimoTotal;
                    if (nuevas === 1 && notificaciones[0]) {
                        const esBackup = notificaciones[0].tipo === 'backup';
                        if (esBackup) {
                            mostrarToastBackup();
                        } else {
                            mostrarToast(notificaciones[0].titulo, notificaciones[0].mensaje);
                        }
                    } else {
                        mostrarToast('Nuevas notificaciones', `Tienes ${nuevas} notificaciones nuevas.`);
                    }
                }
                ultimoTotal = total;
            })
            .catch(() => {}); // silencioso si hay error de red
    }

    // Cargar al inicio y cada 30 s
    document.addEventListener('DOMContentLoaded', function () {
        pollAhora();
        setInterval(pollAhora, POLL_MS);
    });
})();
</script>

<style>
@keyframes slideInToast {
    from { opacity: 0; transform: translateY(16px); }
    to   { opacity: 1; transform: translateY(0); }
}
</style>
</body>
</html>
