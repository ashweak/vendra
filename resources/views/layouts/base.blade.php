<!DOCTYPE html>
<html lang="en" class="h-full bg-paper">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script>/* iOS Safari zooms into form fields on focus; maximum-scale stops that there while pinch-zoom still works (iOS ignores it for pinch). */
    if (/iP(hone|od|ad)/.test(navigator.userAgent) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1)) document.querySelector('meta[name=viewport]').content = 'width=device-width, initial-scale=1, maximum-scale=1';</script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Vendra') · Vendra</title>
    <meta name="description" content="@yield('description', 'Bookings, orders and quotes for small Philippine businesses, with an offline auto-assistant that answers in English, Tagalog and Taglish.')">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Vendra">
    <meta property="og:title" content="@yield('title', 'Vendra')">
    <meta property="og:description" content="@yield('description', 'Bookings, orders and quotes for small Philippine businesses, with an offline auto-assistant that answers in English, Tagalog and Taglish.')">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta name="theme-color" content="#fbfaf6">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'><path d='M16 3a13 13 0 0 1 13 13 13 13 0 0 1-13 13H5.5a1.5 1.5 0 0 1-1.2-2.4L6.6 24A13 13 0 0 1 16 3z' fill='%23ffc83d'/><path d='M10.5 12.5l5.5 8 5.5-8' fill='none' stroke='%23171612' stroke-width='2.6' stroke-linecap='round' stroke-linejoin='round'/></svg>">
    <link rel="preload" href="{{ asset('fonts/bricolage-grotesque-latin-wght-normal.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="{{ asset('css/vendra.css') }}?v={{ @filemtime(public_path('css/vendra.css')) }}">
    @stack('head')
</head>
<body class="h-full overflow-x-clip font-sans text-ink-900 antialiased">
    @yield('body')
    @stack('scripts')
</body>
</html>
