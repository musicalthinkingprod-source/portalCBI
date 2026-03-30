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
</head>
<body class="font-sans antialiased bg-gray-100">

<div class="flex h-screen overflow-hidden">

    <!-- Overlay para cerrar sidebar en móvil -->
    <div id="sidebar-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-20 hidden lg:hidden" onclick="toggleSidebar()"></div>

    <!-- Barra lateral -->
    <aside id="sidebar" class="fixed lg:static z-30 w-64 h-full lg:h-screen bg-blue-900 text-white flex flex-col transform -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out shrink-0">

        <!-- Logo / Título -->
        <div class="px-6 py-5 border-b border-blue-700 flex items-center justify-between">
            <div>
                <h1 class="text-lg font-bold leading-tight">Portal CBI</h1>
                <p class="text-xs text-blue-300">Colegio Bilingüe Integral</p>
            </div>
            <!-- Botón cerrar sidebar en móvil -->
            <button onclick="toggleSidebar()" class="lg:hidden text-blue-300 hover:text-white">
                ✕
            </button>
        </div>

        <nav class="flex-1 px-4 py-6 space-y-6 overflow-y-auto">

            @auth
                @php $profile = auth()->user()->PROFILE; @endphp

                {{-- Docentes: perfil DOC*** o SuperAd --}}
                @if($profile === 'SuperAd' || str_starts_with($profile, 'DOC'))
                <div>
                    <p class="text-xs font-semibold text-blue-400 uppercase tracking-widest mb-2">Docentes</p>
                    <ul class="space-y-1">
                        <li>
                            <a href="#" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-blue-700 transition text-sm">
                                📋 Notas
                            </a>
                        </li>
                    </ul>
                </div>
                @endif

                {{-- Administrativos: solo SuperAd --}}
                @if($profile === 'SuperAd')
                <div>
                    <p class="text-xs font-semibold text-blue-400 uppercase tracking-widest mb-2">Administrativos</p>
                    <ul class="space-y-1">
                        <li>
                            <a href="#" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-blue-700 transition text-sm">
                                💳 Pagos
                            </a>
                        </li>
                    </ul>
                </div>
                @endif

                {{-- Administradores: solo SuperAd --}}
                @if($profile === 'SuperAd')
                <div>
                    <p class="text-xs font-semibold text-blue-400 uppercase tracking-widest mb-2">Administradores</p>
                    <ul class="space-y-1">
                        <li>
                            <a href="#" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-blue-700 transition text-sm">
                                👤 Crear usuarios
                            </a>
                        </li>
                    </ul>
                </div>
                @endif

                {{-- Padres: solo SuperAd --}}
                @if($profile === 'SuperAd')
                <div>
                    <p class="text-xs font-semibold text-blue-400 uppercase tracking-widest mb-2">Padres</p>
                    <ul class="space-y-1">
                        <li>
                            <a href="#" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-blue-700 transition text-sm">
                                📄 Consultar boletines
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

    <!-- Contenido principal -->
    <div class="flex-1 flex flex-col min-w-0 overflow-y-auto">

        <!-- Topbar -->
        <header class="bg-white shadow px-6 py-4 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <!-- Botón hamburguesa (solo móvil/tablet) -->
                <button onclick="toggleSidebar()" class="lg:hidden text-gray-600 hover:text-gray-900 focus:outline-none">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
                <h2 class="text-xl font-semibold text-gray-700">@yield('header', 'Dashboard')</h2>
            </div>
            <span class="text-sm text-gray-500">{{ auth()->user()->name ?? '' }}</span>
        </header>

        <!-- Página -->
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

</body>
</html>
