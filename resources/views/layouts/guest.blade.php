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

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen font-sans">
<div class="pointer-events-none fixed inset-0 bg-[radial-gradient(ellipse_at_top_left,rgba(124,58,237,0.30),transparent_50%),radial-gradient(ellipse_at_bottom_right,rgba(250,204,21,0.12),transparent_45%)]"></div>

<div class="relative z-10 flex min-h-screen flex-col items-center justify-center px-4 py-10">
    <a href="/" class="flex items-center gap-3">
        <div class="grid h-12 w-12 place-items-center rounded-2xl bg-yellow-400 text-lg font-bold text-ink">DH</div>
        <span class="text-2xl font-semibold text-white">DarkHoundoom</span>
    </a>

    <div class="dh-panel mt-8 w-full p-6 sm:max-w-md sm:p-8 backdrop-blur">
        {{ $slot }}
    </div>
</div>
</body>
</html>
