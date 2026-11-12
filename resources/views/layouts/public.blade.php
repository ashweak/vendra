@extends('layouts.base')
@push('head')
<style>:root{--accent: {{ $business->accent_color }};}</style>
@endpush
@section('body')
<div class="min-h-full bg-white">
    <header class="border-b border-gray-200 bg-white">
        <div class="mx-auto flex max-w-5xl flex-wrap items-center gap-x-6 gap-y-2 px-4 py-4">
            <a href="{{ route('public.home', $business) }}" class="flex items-center gap-3">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl text-lg font-bold text-white" style="background: var(--accent)">{{ mb_substr($business->name, 0, 1) }}</span>
                <span class="text-lg font-bold">{{ $business->name }}</span>
            </a>
            <nav class="flex flex-1 flex-wrap justify-end gap-1 text-sm" aria-label="Business">
                <a class="rounded-lg px-3 py-2 hover:bg-gray-100" href="{{ route('public.home', $business) }}">Home</a>
                @if ($business->moduleEnabled('appointments'))<a class="rounded-lg px-3 py-2 hover:bg-gray-100" href="{{ route('public.book', $business) }}">Book</a>@endif
                @if ($business->moduleEnabled('orders'))<a class="rounded-lg px-3 py-2 hover:bg-gray-100" href="{{ route('public.order', $business) }}">Order</a>@endif
                @if ($business->moduleEnabled('quotations'))<a class="rounded-lg px-3 py-2 hover:bg-gray-100" href="{{ route('public.quote', $business) }}">Get a quote</a>@endif
                @if ($business->moduleEnabled('service_requests'))<a class="rounded-lg px-3 py-2 hover:bg-gray-100" href="{{ route('public.request', $business) }}">Request service</a>@endif
                <a class="rounded-lg px-3 py-2 hover:bg-gray-100" href="{{ route('public.track', $business) }}">Track my request</a>
            </nav>
        </div>
    </header>
    @if (! $business->isAcceptingSubmissions())
        <div class="bg-amber-50 px-4 py-2 text-center text-sm text-amber-900">{{ $business->status === 'closing' ? 'This business is no longer accepting online requests.' : 'This page is being set up and isn’t accepting requests yet.' }}</div>
    @endif
    <main class="mx-auto max-w-5xl px-4 py-8 pb-28">
        <x-flash />
        @yield('content')
    </main>
    <footer class="border-t border-gray-100 py-6 text-center text-xs text-gray-500">
        {{ $business->name }} · Powered by <a href="{{ route('landing') }}" class="link">Vendra</a> — from inquiry to opportunity
    </footer>
</div>
@include('public.partials.chat')
@endsection
