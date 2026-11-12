@extends('layouts.base')
@push('head')
<style>:root{--accent: {{ $business->accent_color }};}</style>
@endpush
@section('body')
@php
    $links = [['Home', 'public.home', true]];
    if ($business->moduleEnabled('appointments')) $links[] = ['Book', 'public.book', true];
    if ($business->moduleEnabled('orders')) $links[] = ['Shop', 'public.order', true];
    if ($business->moduleEnabled('quotations')) $links[] = ['Get a quote', 'public.quote', true];
    if ($business->moduleEnabled('service_requests')) $links[] = ['Request service', 'public.request', true];
    $links[] = ['Track request', 'public.track', true];
@endphp
<div class="min-h-full bg-white">
    <div class="mx-auto max-w-6xl px-3 pt-3 sm:px-5 sm:pt-5">
        <header class="flex flex-wrap items-stretch rounded-2xl border border-line bg-white">
            <a href="{{ route('public.home', $business) }}" class="flex items-center gap-2.5 border-r border-line px-5 py-4">
                <span class="flex h-8 w-8 items-center justify-center rounded-lg text-sm font-semibold text-white" style="background: var(--accent)">{{ mb_substr($business->name, 0, 1) }}</span>
                <span class="max-w-[12rem] truncate text-[15px] font-semibold tracking-tight sm:max-w-none">{{ $business->name }}</span>
            </a>
            <nav class="order-3 flex w-full flex-wrap justify-center gap-x-1 border-t border-line px-3 py-2 text-sm md:order-none md:w-auto md:flex-1 md:border-t-0 md:py-0" aria-label="Business">
                @foreach ($links as [$label, $route])
                    <a href="{{ route($route, $business) }}" @class(['flex items-center rounded-md px-3 py-2 md:py-0', 'font-medium text-ink-900' => request()->routeIs($route), 'text-ink-600 hover:text-ink-900' => ! request()->routeIs($route)])>{{ $label }}</a>
                @endforeach
            </nav>
            @if ($business->phone)
            <a href="tel:{{ preg_replace('/[^0-9+]/', '', $business->phone) }}" class="ml-auto flex items-center gap-3 border-l border-line px-5 py-3 md:ml-0">
                <span class="flex h-9 w-9 items-center justify-center rounded-full bg-tile"><x-icon name="phone" class="h-4 w-4" /></span>
                <span class="leading-tight"><span class="block text-[11px] text-ink-500">Call any time</span><span class="block text-sm font-semibold">{{ $business->phone }}</span></span>
            </a>
            @endif
        </header>
    </div>
    @if (! $business->isAcceptingSubmissions())
        <div class="mx-auto mt-3 max-w-6xl px-3 sm:px-5"><div class="rounded-xl bg-amber-50 px-4 py-2.5 text-center text-sm text-amber-900">{{ $business->status === 'closing' ? 'This business is no longer accepting online requests.' : 'This page is being set up and isn’t accepting requests yet.' }}</div></div>
    @endif
    <main class="mx-auto max-w-6xl px-5 py-10 pb-28 sm:px-8">
        <x-flash />
        @yield('content')
    </main>
    <footer class="border-t border-line">
        <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-3 px-5 py-6 text-xs text-ink-500 sm:px-8">
            <span>© {{ now()->year }} {{ $business->name }}</span>
            <a href="{{ route('landing') }}" class="inline-flex items-center gap-1.5 hover:text-ink-900">Powered by <x-logo /></a>
        </div>
    </footer>
</div>
@include('public.partials.chat')
@endsection
