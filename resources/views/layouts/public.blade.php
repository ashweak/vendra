@extends('layouts.base')
@php
    // ?theme= lets an owner preview another theme without saving; it only changes what that visitor sees.
    $activeTheme = \App\Support\Themes::active($business);
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
    // Each theme pairs its homepage layout with a header and footer style.
    $chrome = ['studio' => ['pill', 'simple'], 'coral' => ['bar', 'dark'], 'ocean' => ['split', 'dark'],
        'roastery' => ['stacked', 'dark'], 'slate' => ['bar', 'simple'], 'market' => ['stacked', 'dark']][$activeTheme];
    $fullBleed = request()->routeIs('public.home');
    $todayRow = $business->hours()->where('weekday', now($business->timezone)->dayOfWeek)->first();
    $statusLine = $todayRow && ! $todayRow->is_closed && $todayRow->opens_at
        ? 'Open today · '.\Carbon\Carbon::parse($todayRow->opens_at)->format('g:i A').' – '.\Carbon\Carbon::parse($todayRow->closes_at)->format('g:i A')
        : 'Closed today';
@endphp
<div class="flex min-h-dvh flex-col bg-paper">
    @include('public.partials.header-'.$chrome[0])
    @if (! $business->isAcceptingSubmissions())
        <div class="mx-auto mt-3 max-w-6xl px-3 sm:px-5"><div class="rounded-xl bg-amber-50 px-4 py-2.5 text-center text-sm text-amber-900">{{ $business->status === 'closing' ? 'This business is no longer accepting online requests.' : 'This page is being set up and isn’t accepting requests yet.' }}</div></div>
    @endif
    {{-- The homepage layouts manage their own widths (some sections run edge to edge). --}}
    @if ($fullBleed)
        <main class="w-full flex-1 pb-28">@yield('content')</main>
    @else
        <main class="mx-auto w-full max-w-6xl flex-1 px-5 py-10 pb-28 sm:px-8">
            <x-flash />
            @yield('content')
        </main>
    @endif
    @include('public.partials.footer-'.$chrome[1])
</div>
@include('public.partials.chat')
@endsection
