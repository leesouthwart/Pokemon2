<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>DarkHoundoom</title>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
        <script src="https://kit.fontawesome.com/ba1920b5f6.js" crossorigin="anonymous"></script>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="min-h-screen font-sans">
        <div class="pointer-events-none fixed inset-0 -z-10 bg-[radial-gradient(ellipse_at_top_left,rgba(124,58,237,0.25),transparent_50%),radial-gradient(ellipse_at_bottom_right,rgba(250,204,21,0.10),transparent_45%)]"></div>

        @include('layouts.navigation')

        @if (isset($header))
            <header class="mx-auto max-w-[1400px] px-4 sm:px-6 pt-8">
                {{ $header }}
            </header>
        @endif

        <main>
            {{ $slot }}
        </main>

        @livewireScripts
    </body>
</html>
