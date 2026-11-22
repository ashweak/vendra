@extends('layouts.base')
@php
    // ?theme= lets an owner preview another theme without saving; it only changes what that visitor sees.
    $previewTheme = request()->query('theme');
    $activeTheme = is_string($previewTheme) && isset(\App\Support\Themes::ALL[$previewTheme]) ? $previewTheme : \App\Support\Themes::resolve($business->theme);
@endphp
@section('theme', $activeTheme)
@section('description', \Illuminate\Support\Str::limit(trim(($business->description ?: $business->name).' Book, order or ask in English, Tagalog or Taglish.'), 155))
@push('head')
<style>:root{--accent: {{ $business->accent_color }};}</style>
@endpush
@section('body')
@php
    $links = [['Home', 'public.home', 'home']];
    if ($business->moduleEnabled('appointments')) $links[] = ['Book', 'public.book', 'calendar'];
    if ($business->moduleEnabled('orders')) $links[] = ['Shop', 'public.order', 'bag'];
    if ($business->moduleEnabled('quotations')) $links[] = ['Get a quote', 'public.quote', 'doc'];
    if ($business->moduleEnabled('service_requests')) $links[] = ['Request service', 'public.request', 'tool'];
    $links[] = ['Track request', 'public.track', 'search'];
@endphp
<div class="flex min-h-dvh flex-col bg-paper">
    <div class="sticky top-0 z-30 px-3 pt-3 sm:px-5">
        <nav class="mx-auto flex max-w-6xl items-center gap-1 rounded-full border border-line bg-white/85 py-1.5 pr-1.5 pl-1.5 shadow-[0_8px_30px_-18px_rgba(23,22,18,.35)] backdrop-blur sm:gap-2 sm:py-2 sm:pr-2 sm:pl-2.5" aria-label="Business">
            <a href="{{ route('public.home', $business) }}" class="flex min-w-0 shrink-0 items-center gap-2.5" aria-label="{{ $business->name }} home">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full font-display text-sm font-bold text-white" style="background: var(--accent)">{{ mb_substr($business->name, 0, 1) }}</span>
                <span class="hidden truncate font-display text-[15px] font-bold sm:inline">{{ $business->name }}</span>
            </a>
            <div class="flex flex-1 items-center justify-center gap-2 text-sm sm:gap-3 lg:gap-1">
                @foreach ($links as [$label, $route, $icon])
                    @php $active = request()->routeIs($route); @endphp
                    <a href="{{ route($route, $business) }}" title="{{ $label }}" aria-label="{{ $label }}" @if($active) aria-current="page" @endif
                       @class(['flex h-10 w-10 shrink-0 items-center justify-center rounded-full whitespace-nowrap lg:h-9 lg:w-auto lg:px-4', 'bg-sun-soft font-semibold text-ink-900' => $active, 'text-ink-600 hover:bg-tile hover:text-ink-900' => ! $active])>
                        <x-icon :name="$icon" class="h-[18px] w-[18px] shrink-0 lg:hidden" /><span class="hidden lg:inline">{{ $label }}</span>
                    </a>
                @endforeach
            </div>
            @if ($business->phone)
                <a href="tel:{{ preg_replace('/[^0-9+]/', '', $business->phone) }}" class="btn btn-primary btn-sm h-10 min-h-0 w-10 shrink-0 px-0 sm:w-auto sm:px-4" aria-label="Call {{ $business->phone }}"><x-icon name="phone" class="h-4 w-4" /><span class="hidden sm:inline">{{ $business->phone }}</span></a>
            @endif
        </nav>
    </div>
    @if (! $business->isAcceptingSubmissions())
        <div class="mx-auto mt-3 max-w-6xl px-3 sm:px-5"><div class="rounded-xl bg-amber-50 px-4 py-2.5 text-center text-sm text-amber-900">{{ $business->status === 'closing' ? 'This business is no longer accepting online requests.' : 'This page is being set up and isn’t accepting requests yet.' }}</div></div>
    @endif
    <main class="mx-auto w-full max-w-6xl flex-1 px-5 py-10 pb-28 sm:px-8">
        <x-flash />
        @yield('content')
    </main>
    <footer class="border-t border-line bg-white">
        <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-3 py-6 pr-24 pl-5 text-xs text-ink-500 sm:px-8">
            <span class="flex flex-col gap-1">
                <span>© {{ now()->year }} {{ $business->legal_name ?: $business->name }}@if ($business->registration_no) · Reg. no. {{ $business->registration_no }}@endif</span>
                @if ($business->address)<span>{{ $business->address }}</span>@endif
                <a href="{{ route('public.privacy', $business) }}" class="w-fit underline underline-offset-2 hover:text-ink-900">Privacy notice</a>
            </span>
            <a href="{{ route('landing') }}" class="inline-flex items-center gap-2 hover:text-ink-900">Powered by <x-logo /></a>
        </div>
    </footer>
</div>
@include('public.partials.chat')
@endsection
