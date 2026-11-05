<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        {{-- Inicializador instantáneo de Tema Claro/Oscuro (sin parpadeo) + Estilos Dark Mode Globales --}}
        <script>
            if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        </script>
        <style>
            html.dark body,
            html.dark .min-h-screen.bg-gray-100 { background-color: #0f172a !important; color: #f1f5f9 !important; }
            html.dark header.bg-white,
            html.dark nav.bg-white,
            html.dark .bg-white { background-color: #1e293b !important; color: #f1f5f9 !important; border-color: #334155 !important; }
            html.dark .bg-gray-50,
            html.dark .bg-gray-50\/50,
            html.dark .bg-gray-100 { background-color: #0f172a !important; color: #cbd5e1 !important; }
            html.dark .hover\:bg-gray-50\/80:hover { background-color: rgba(51, 65, 85, 0.5) !important; }
            html.dark .text-gray-900,
            html.dark .text-gray-800 { color: #f8fafc !important; }
            html.dark .text-gray-700,
            html.dark .text-gray-600 { color: #cbd5e1 !important; }
            html.dark .text-gray-500,
            html.dark .text-gray-400 { color: #94a3b8 !important; }
            html.dark .border-gray-100,
            html.dark .border-gray-200,
            html.dark .border-gray-300,
            html.dark .divide-gray-100 > :not([hidden]) ~ :not([hidden]),
            html.dark .divide-gray-200 > :not([hidden]) ~ :not([hidden]) { border-color: #334155 !important; }
            html.dark input:not([type="checkbox"]),
            html.dark select,
            html.dark textarea {
                background-color: #0f172a !important;
                border-color: #475569 !important;
                color: #f8fafc !important;
            }
            html.dark .bg-indigo-50\/80,
            html.dark .bg-indigo-50\/40 { background-color: rgba(49, 46, 129, 0.25) !important; border-color: #4338ca !important; }
            html.dark .text-indigo-950 { color: #e0e7ff !important; }
        </style>

    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100">
            <div>
                <a href="/">
                    <x-application-logo class="w-20 h-20 fill-current text-gray-500" />
                </a>
            </div>

            <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-lg">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
