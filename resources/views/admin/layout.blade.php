@extends('layouts.base')
@section('body')
<div class="mx-auto max-w-6xl px-3 pt-3 sm:px-5 sm:pt-5">
    <header class="flex flex-wrap items-stretch rounded-2xl border border-line bg-white">
        <a href="{{ route('admin.index') }}" class="flex items-center gap-3 border-r border-line px-5 py-4"><x-logo /><span class="eyebrow">Platform admin</span></a>
        <nav class="flex flex-1 items-center gap-1 px-3 text-sm">
            <a class="rounded-md px-3 py-2 {{ request()->routeIs('admin.index', 'admin.show') ? 'font-medium text-ink-900' : 'text-ink-600 hover:text-ink-900' }}" href="{{ route('admin.index') }}">Accounts</a>
            <a class="rounded-md px-3 py-2 {{ request()->routeIs('admin.logs') ? 'font-medium text-ink-900' : 'text-ink-600 hover:text-ink-900' }}" href="{{ route('admin.logs') }}">Admin log</a>
        </nav>
        <div class="flex items-center border-l border-line px-4 py-3"><form method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-secondary btn-sm">Sign out</button></form></div>
    </header>
</div>
<main class="mx-auto max-w-6xl px-5 py-10 sm:px-8"><x-flash />@yield('content')</main>
@endsection
