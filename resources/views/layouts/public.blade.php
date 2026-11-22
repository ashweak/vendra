@extends('layouts.base')
@php
    // ?layout= and ?palette= let an owner preview without saving; they only change what that visitor sees.
    $activeLayout = \App\Support\Appearance::layout($business);
    $activePalette = \App\Support\Appearance::palette($business);
@endphp
@section('layout', $activeLayout)
@section('palette', $activePalette)
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
    // Each layout pairs its homepage with a header and footer style.
    $chrome = \App\Support\Appearance::CHROME[$activeLayout];
    $fullBleed = request()->routeIs('public.home');
    $todayRow = $business->hours()->where('weekday', now($business->timezone)->dayOfWeek)->first();
    $statusLine = $todayRow && ! $todayRow->is_closed && $todayRow->opens_at
        ? 'Open today · '.\Carbon\Carbon::parse($todayRow->opens_at)->format('g:i A').' – '.\Carbon\Carbon::parse($todayRow->closes_at)->format('g:i A')
        : 'Closed today';
@endphp
<div class="flex min-h-dvh flex-col bg-paper">
    @include('public.partials.header-'.$chrome[0])
    @php $previewing = \App\Support\Appearance::preview($business); @endphp
    @if ($previewing)
        <div class="border-b border-ink-900 bg-ink-900 px-4 py-2 text-center text-xs text-white sm:text-sm" role="status">
            Previewing <strong>{{ \App\Support\Appearance::LAYOUTS[$activeLayout]['name'] }}</strong> layout with <strong>{{ \App\Support\Appearance::PALETTES[$activePalette]['name'] }}</strong> colors — only you see this.
            @auth<a href="{{ route('dashboard.settings.appearance', ['layout' => $activeLayout, 'palette' => $activePalette]) }}" class="ml-2 font-semibold underline underline-offset-2">Use this look</a>@endauth
            <a href="{{ request()->fullUrlWithQuery(['preview' => 'off', 'layout' => null, 'palette' => null]) }}" class="ml-2 font-semibold underline underline-offset-2">Exit preview</a>
        </div>
    @endif
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
