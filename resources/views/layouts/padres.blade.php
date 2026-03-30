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
            <div>
                <h1 class="text-lg font-bold leading-tight">Portal CBI</h1>
                <p class="text-xs text-blue-300">Área de Padres</p>
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

        <nav class="flex-1 px-4 py-6 space-y-1 overflow-y-auto">
            <a href="{{ route('padres.portal') }}" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-blue-700 transition text-sm">
                🏠 Inicio
            </a>
            <a href="#" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-blue-700 transition text-sm">
                📋 Consultar
            </a>
            <a href="#" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-blue-700 transition text-sm">
                🏊 Salvavidas
            </a>
            <a href="#" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-blue-700 transition text-sm">
                🗺️ Derroteros
            </a>
            <a href="#" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-blue-700 transition text-sm">
                📝 Notas
            </a>
            <a href="#" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-blue-700 transition text-sm">
                📊 Estado de cuenta
            </a>
            <a href="#" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-blue-700 transition text-sm">
                💳 Pagos realizados
            </a>
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

</body>
</html>
