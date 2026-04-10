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

    <nav class="flex-1 px-4 py-6 space-y-6 overflow-y-auto">

        @auth
            @php
                $profile = auth()->user()->PROFILE;
                $puedeVerAlumnos = in_array($profile, ['SuperAd', 'Admin', 'Ori']) ||
                                   str_starts_with($profile, 'Sec');
            @endphp

            {{-- Estudiantes: SuperAd, Admin, Ori, Sec* --}}
            @if($puedeVerAlumnos)
            <div>
                <p class="text-xs font-semibold text-blue-400 uppercase tracking-widest mb-2">Estudiantes</p>
                <ul class="space-y-1">
                    <li>
                        <a href="{{ route('alumnos.index') }}" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-blue-700 transition text-sm">
                            🎒 Consultar estudiante
                        </a>
                    </li>
                    @if(in_array($profile, ['SuperAd', 'Admin']))
                    <li>
                        <a href="{{ route('alumnos.create') }}" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-blue-700 transition text-sm">
                            ➕ Matricular estudiante
                        </a>
                    </li>
                    @endif
                </ul>
            </div>
            @endif

            {{-- PIAR: SuperAd, Ori y DOC* --}}
            @if(in_array($profile, ['SuperAd', 'Ori', 'Admin']) || str_starts_with($profile, 'DOC'))
            <div>
                <p class="text-xs font-semibold text-blue-400 uppercase tracking-widest mb-2">PIAR</p>
                <ul class="space-y-1">
                    @if(in_array($profile, ['SuperAd', 'Ori']))
                    <li>
                        <a href="{{ route('piar.buscar') }}" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-blue-700 transition text-sm">
                            📋 Crear / Editar PIAR
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('piar.informe') }}" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-blue-700 transition text-sm">
                            📊 Informe Anexo 2
                        </a>
                    </li>
                    @endif
                    <li>
                        <a href="{{ route('piar.anexo2.index') }}" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-blue-700 transition text-sm">
                            📝 PIAR Anexo 2
                        </a>
                    </li>
                </ul>
            </div>
            @endif

            {{-- Docentes: perfil DOC*** o SuperAd --}}
            @if($profile === 'SuperAd' || str_starts_with($profile, 'DOC'))
            <div>
                <p class="text-xs font-semibold text-blue-400 uppercase tracking-widest mb-2">Docentes</p>
                <ul class="space-y-1">
                    <li>
                        <a href="{{ route('notas.index') }}" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-blue-700 transition text-sm">
                            📋 Notas
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('notas.v2.index') }}" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-blue-700 transition text-sm">
                            🧪 Planilla ponderada
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('correcciones.index') }}" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-blue-700 transition text-sm">
                            🔧 Corrección de notas
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('english-acq.docente') }}" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-blue-700 transition text-sm">
                            🇬🇧 English Acquisition
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('salvavidas.index') }}" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-blue-700 transition text-sm">
                            🏊 Salvavidas
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('derroteros.docente') }}" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-blue-700 transition text-sm">
                            📌 Recuperaciones
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('derroteros.horarios') }}" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-blue-700 transition text-sm">
                            📅 Horarios recuperación
                        </a>
                    </li>
                    @if(str_starts_with($profile, 'DOC'))
                    <li>
                        <a href="{{ route('horarios.mi_horario') }}" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-blue-700 transition text-sm">
                            🗓️ Mi Horario
                        </a>
                    </li>
                    @endif
                    <li>
                        <a href="{{ route('vigilancias.docente') }}" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-blue-700 transition text-sm">
                            🗺️ Vigilancias
                        </a>
                    </li>
                    <li>
                        <a href="{{ str_starts_with($profile, 'DOC') ? route('calendario.docente') : route('calendario.index') }}"
                           class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-blue-700 transition text-sm">
                            📆 Calendario académico
                        </a>
                    </li>
                    @if($profile === 'SuperAd')
                    <li>
                        <a href="{{ route('horarios.index') }}" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-blue-700 transition text-sm">
                            🗓️ Horarios
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('horarios.disponibilidad') }}" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-blue-700 transition text-sm">
                            🟢 Disponibilidad docentes
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('notas.reporte') }}" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-blue-700 transition text-sm">
                            📊 Informe de digitación
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('ciclos.informe') }}" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-blue-700 transition text-sm">
                            📋 Control de planilla
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('english-acq.informe') }}" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-blue-700 transition text-sm">
                            📊 Informe English ACQ
                        </a>
                    </li>
                    @endif
                </ul>
            </div>
            @endif

            {{-- Asistencia: oculto para Contab --}}
            @if($profile !== 'Contab')
            <div>
                <p class="text-xs font-semibold text-blue-400 uppercase tracking-widest mb-2">Asistencia</p>
                <ul class="space-y-1">
                    @if(str_starts_with($profile, 'Sec') || $profile === 'SuperAd')
                    <li>
                        <a href="{{ route('asistencia.registro') }}" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-blue-700 transition text-sm">
                            ✏️ Registrar asistencia
                        </a>
                    </li>
                    @endif
                    @if($profile === 'SecA' || $profile === 'SuperAd' || $profile === 'Admin')
                    <li>
                        <a href="{{ route('asistencia-personal.index') }}" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-blue-700 transition text-sm">
                            👥 Asistencia personal
                        </a>
                    </li>
                    @if($profile === 'SecA')
                    <li>
                        <a href="{{ route('asistencia-personal.registro') }}" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-blue-700 transition text-sm">
                            ✏️ Registrar personal
                        </a>
                    </li>
                    @endif
                    @endif
                    <li>
                        <a href="{{ route('asistencia.reporte') }}" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-blue-700 transition text-sm">
                            📋 Reporte de asistencia
                        </a>
                    </li>
                    @if(in_array($profile, ['SuperAd', 'Admin']) || str_starts_with($profile, 'Sec'))
                    <li>
                        <a href="{{ route('llamadas.index') }}" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-blue-700 transition text-sm">
                            📞 Llamadas por inasistencia
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('llamadas.reporte') }}" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-blue-700 transition text-sm">
                            📊 Reporte de llamadas
                        </a>
                    </li>
                    @endif
                </ul>
            </div>
            @endif

            {{-- SecC100: Calendario + Cartera --}}
            @if($profile === 'SecC100')
            <div>
                <p class="text-xs font-semibold text-blue-400 uppercase tracking-widest mb-2">Académico</p>
                <ul class="space-y-1">
                    <li>
                        <a href="{{ route('calendario.index') }}" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-blue-700 transition text-sm">
                            📆 Calendario académico
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('horarios.index') }}" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-blue-700 transition text-sm">
                            🗓️ Horarios
                        </a>
                    </li>
                </ul>
            </div>
            <div>
                <p class="text-xs font-semibold text-blue-400 uppercase tracking-widest mb-2">Cartera</p>
                <ul class="space-y-1">
                    <li>
                        <a href="{{ route('cartera.seguimiento.informe') }}" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-blue-700 transition text-sm">
                            📋 Seguimiento cartera
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('cartera.por_cc') }}" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-blue-700 transition text-sm">
                            🪪 Cartera por CC
                        </a>
                    </li>
                </ul>
            </div>
            @endif

            {{-- Control: SuperAd, Admin y Contab --}}
            @if(in_array($profile, ['SuperAd', 'Admin', 'Contab']))
            <div>
                <p class="text-xs font-semibold text-blue-400 uppercase tracking-widest mb-2">Control de Pagos</p>
                <ul class="space-y-1">
                    <li>
                        <a href="{{ route('control.estudiante') }}" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-blue-700 transition text-sm">
                            🔍 Consultar estudiante
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('pagos.index') }}" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-blue-700 transition text-sm">
                            💳 Pagos
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('facturacion.index') }}" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-blue-700 transition text-sm">
                            🧾 Facturación
                        </a>
                    </li>
                    @if($profile !== 'Contab')
                    <li>
                        <a href="{{ route('facturacion.auto') }}" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-blue-700 transition text-sm">
                            ⚡ Facturación automática
                        </a>
                    </li>
                    @endif
                    <li>
                        <a href="{{ route('cartera.index') }}" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-blue-700 transition text-sm">
                            📊 Informe de cartera
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('cartera.deudores') }}" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-blue-700 transition text-sm">
                            🔴 Cartera / Anticipos
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('cartera.seguimiento.informe') }}" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-blue-700 transition text-sm">
                            📋 Seguimiento cartera
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('cartera.por_cc') }}" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-blue-700 transition text-sm">
                            🪪 Cartera por CC
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('parametros.index') }}" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-blue-700 transition text-sm">
                            ⚙️ Parametros facturacion
                        </a>
                    </li>
                    @if($profile !== 'Contab')
                    <li>
                        <a href="{{ route('world-office.index') }}" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-blue-700 transition text-sm">
                            📄 Plantilla World Office
                        </a>
                    </li>
                    @endif
                </ul>
            </div>
            @endif

            {{-- Nómina: SuperAd --}}
            @if($profile === 'SuperAd')
            <div>
                <p class="text-xs font-semibold text-blue-400 uppercase tracking-widest mb-2">Nómina</p>
                <ul class="space-y-1">
                    <li>
                        <a href="{{ route('nomina.index') }}" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-blue-700 transition text-sm">
                            👥 Gestión de personal
                        </a>
                    </li>
                </ul>
            </div>
            @endif

            {{-- Circulares: SuperAd --}}
            @if($profile === 'SuperAd')
            <div>
                <p class="text-xs font-semibold text-blue-400 uppercase tracking-widest mb-2">Comunicaciones</p>
                <ul class="space-y-1">
                    <li>
                        <a href="{{ route('circulares.index') }}" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-blue-700 transition text-sm">
                            📄 Circulares
                        </a>
                    </li>
                </ul>
            </div>
            @endif

            {{-- Transporte: SuperAd, Admin y Sec* --}}
            @if(in_array($profile, ['SuperAd', 'Admin']) || str_starts_with($profile, 'Sec'))
            <div>
                <p class="text-xs font-semibold text-blue-400 uppercase tracking-widest mb-2">Transporte</p>
                <ul class="space-y-1">
                    <li>
                        <a href="{{ route('rutas.index') }}" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-blue-700 transition text-sm">
                            🚌 Listado de rutas
                        </a>
                    </li>
                </ul>
            </div>
            @endif

            {{-- Seguimiento Académico: SuperAd, Admin y Sec* --}}
            @if(in_array($profile, ['SuperAd', 'Admin']) || str_starts_with($profile, 'Sec'))
            <div>
                <p class="text-xs font-semibold text-blue-400 uppercase tracking-widest mb-2">Seguimiento Académico</p>
                <ul class="space-y-1">
                    <li>
                        <a href="{{ route('derroteros.index') }}" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-blue-700 transition text-sm">
                            📊 Informe derroteros
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('salvavidas.reporte') }}" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-blue-700 transition text-sm">
                            📊 Reporte salvavidas
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('derroteros.horarios') }}" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-blue-700 transition text-sm">
                            📅 Horarios recuperación
                        </a>
                    </li>
                </ul>
            </div>
            @endif

            {{-- Informes Académicos --}}
            @if(in_array($profile, ['SuperAd', 'Admin']) || str_starts_with($profile, 'DOC'))
            <div>
                <p class="text-xs font-semibold text-blue-400 uppercase tracking-widest mb-2">Informes Académicos</p>
                <ul class="space-y-1">
                    <li>
                        <a href="{{ route('informes.boletin') }}" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-blue-700 transition text-sm">
                            📋 Boletines
                        </a>
                    </li>
                </ul>
            </div>
            @endif

            {{-- Control de vigilancias: ConvCor28 --}}
            @if($profile === 'ConvCor28')
            <div>
                <p class="text-xs font-semibold text-blue-400 uppercase tracking-widest mb-2">Vigilancias</p>
                <ul class="space-y-1">
                    <li>
                        <a href="{{ route('vigilancias.control') }}" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-blue-700 transition text-sm">
                            🔍 Control de vigilancias
                        </a>
                    </li>
                </ul>
            </div>
            @endif

            @if($profile === 'SuperAd')
            <div>
                <p class="text-xs font-semibold text-blue-400 uppercase tracking-widest mb-2">Panel de Control</p>
                <ul class="space-y-1">
                    <li>
                        <a href="{{ route('ciclos.index') }}" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-blue-700 transition text-sm">
                            🗂️ Control de ciclos
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin.usuarios') }}" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-blue-700 transition text-sm">
                            👤 Gestión de usuarios
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin.directores') }}" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-blue-700 transition text-sm">
                            🏫 Directores de grupo
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin.asignaciones') }}" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-blue-700 transition text-sm">
                            🔄 Asignaciones
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin.fechas') }}" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-blue-700 transition text-sm">
                            📅 Fechas
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('listados.index') }}" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-blue-700 transition text-sm">
                            🗂️ Listados especiales
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('vigilancias.admin') }}" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-blue-700 transition text-sm">
                            🗺️ Gestión de vigilancias
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('asistencia-personal.index') }}" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-blue-700 transition text-sm">
                            👥 Asistencia personal
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('asistencia-personal.permisos') }}" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-blue-700 transition text-sm">
                            📋 Permisos
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('asistencia-personal.reemplazos') }}" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-blue-700 transition text-sm">
                            🔄 Reemplazos
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('vigilancias.control') }}" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-blue-700 transition text-sm">
                            🔍 Control de vigilancias
                        </a>
                    </li>
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

@stack('scripts')

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
    document.addEventListener('click', function (e) {
        const wrap = document.getElementById('notif-wrap');
        if (wrap && !wrap.contains(e.target) && panelAbierto) {
            panelAbierto = false;
            document.getElementById('notif-panel').classList.add('hidden');
        }
    });

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
    window.marcarLeida = function (id) {
        fetch(urlLeer(id), {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
        }).then(() => pollAhora());
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

        lista.innerHTML = notifs.map(n => {
            const fecha = new Date(n.created_at).toLocaleString('es-CO', {
                day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit'
            });
            return `<li class="px-4 py-3 hover:bg-blue-50 cursor-pointer group" onclick="marcarLeida(${n.id})">
                <div class="flex items-start gap-2">
                    <div class="mt-0.5 w-2 h-2 rounded-full bg-blue-500 flex-shrink-0"></div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-semibold text-gray-800 truncate">${escHtml(n.titulo)}</p>
                        <p class="text-xs text-gray-500 mt-0.5 leading-snug">${escHtml(n.mensaje)}</p>
                        <p class="text-[10px] text-gray-400 mt-1">${fecha}</p>
                    </div>
                </div>
            </li>`;
        }).join('');
    }

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
                        mostrarToast(notificaciones[0].titulo, notificaciones[0].mensaje);
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
