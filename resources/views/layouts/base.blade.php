<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Vendra') · Vendra</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'><rect width='32' height='32' rx='8' fill='%234f46e5'/><path d='M9 10l7 13 7-13' stroke='white' stroke-width='3.2' fill='none' stroke-linecap='round' stroke-linejoin='round'/></svg>">
    <link rel="stylesheet" href="{{ asset('css/vendra.css') }}?v={{ @filemtime(public_path('css/vendra.css')) }}">
    @stack('head')
</head>
<body class="h-full font-sans text-gray-900 antialiased">
    @yield('body')
    @stack('scripts')
</body>
</html>
