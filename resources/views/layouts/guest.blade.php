<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
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

        <div class="min-h-screen flex">

            <!-- Panel izquierdo azul -->
            <div class="hidden lg:flex w-1/2 bg-blue-900 flex-col items-center justify-center px-12">
                <a href="/">
                    <x-application-logo class="w-24 h-24 fill-current text-white mb-6" />
                </a>
                <h1 class="text-3xl font-bold text-white text-center leading-tight">Portal CBI</h1>
                <p class="text-blue-300 text-sm mt-2 text-center">Colegio Bilingüe Integral</p>
            </div>

            <!-- Panel derecho formulario -->
            <div class="flex-1 flex flex-col items-center justify-center px-6 py-12">

                <!-- Logo visible solo en móvil -->
                <div class="mb-6 lg:hidden">
                    <a href="/">
                        <x-application-logo class="w-16 h-16 fill-current text-blue-900" />
                    </a>
                </div>

                <div class="w-full max-w-md">
                    <h2 class="text-2xl font-bold text-blue-800 mb-1">Iniciar sesión</h2>
                    <p class="text-sm text-gray-500 mb-6">Ingresa tus credenciales para acceder al portal.</p>

                    <div class="bg-white rounded-xl shadow p-6">
                        {{ $slot }}
                    </div>
                </div>

            </div>

        </div>

    </body>
</html>
