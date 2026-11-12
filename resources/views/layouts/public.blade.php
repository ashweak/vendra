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
<div class="min-h-full bg-paper">
    <div class="sticky top-0 z-30 px-3 pt-3 sm:px-5">
        <nav class="mx-auto flex max-w-6xl flex-wrap items-center gap-2 rounded-[26px] border border-line bg-white/85 py-2 pr-2 pl-2.5 shadow-[0_8px_30px_-18px_rgba(23,22,18,.35)] backdrop-blur md:rounded-full" aria-label="Business">
            <a href="{{ route('public.home', $business) }}" class="mr-auto flex min-w-0 items-center gap-2.5 md:mr-0">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full font-display text-sm font-bold text-white" style="background: var(--accent)">{{ mb_substr($business->name, 0, 1) }}</span>
                <span class="truncate font-display text-[15px] font-bold">{{ $business->name }}</span>
            </a>
            @if ($business->phone)
                <a href="tel:{{ preg_replace('/[^0-9+]/', '', $business->phone) }}" class="btn btn-primary btn-sm md:order-last"><x-icon name="phone" class="h-3.5 w-3.5" /> <span class="hidden sm:inline">{{ $business->phone }}</span><span class="sm:hidden">Call</span></a>
            @endif
            <div class="order-last flex w-full justify-center gap-1 overflow-x-auto text-sm md:order-none md:mx-auto md:w-auto">
                @foreach ($links as [$label, $route])
                    <a href="{{ route($route, $business) }}" @class(['rounded-full px-3.5 py-2 whitespace-nowrap', 'bg-sun-soft font-semibold text-ink-900' => request()->routeIs($route), 'text-ink-600 hover:bg-tile hover:text-ink-900' => ! request()->routeIs($route)])>{{ $label }}</a>
                @endforeach
            </div>
        </nav>
    </div>
    @if (! $business->isAcceptingSubmissions())
        <div class="mx-auto mt-3 max-w-6xl px-3 sm:px-5"><div class="rounded-xl bg-amber-50 px-4 py-2.5 text-center text-sm text-amber-900">{{ $business->status === 'closing' ? 'This business is no longer accepting online requests.' : 'This page is being set up and isn’t accepting requests yet.' }}</div></div>
    @endif
    <main class="mx-auto max-w-6xl px-5 py-10 pb-28 sm:px-8">
        <x-flash />
        @yield('content')
    </main>
    <footer class="border-t border-line bg-white">
        <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-3 px-5 py-6 text-xs text-ink-500 sm:px-8">
            <span>© {{ now()->year }} {{ $business->name }}</span>
            <a href="{{ route('landing') }}" class="inline-flex items-center gap-2 hover:text-ink-900">Powered by <x-logo /></a>
        </div>
    </footer>
</div>
@include('public.partials.chat')
@endsection
