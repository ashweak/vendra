@extends('layouts.public')
@section('title', $business->name)
@section('content')
@php
    $open = $hours->filter(fn ($h) => ! $h->is_closed && $h->opens_at);
    $todayHours = $hours->firstWhere('weekday', now($business->timezone)->dayOfWeek);
    $openToday = $todayHours && ! $todayHours->is_closed && $todayHours->opens_at;
    $fmt = fn ($t) => \Carbon\Carbon::parse($t)->format('g:i A');
    $primary = $business->moduleEnabled('appointments') ? ['Book an appointment', route('public.book', $business)]
        : ($business->moduleEnabled('orders') ? ['Shop now', route('public.order', $business)]
        : ($business->moduleEnabled('service_requests') ? ['Request a service', route('public.request', $business)] : ['Get a quote', route('public.quote', $business)]));
    $services = $items->where('type', 'service')->count();
    $products = $items->where('type', 'product')->count();
@endphp

{{-- Hero --}}
<section class="grid items-center gap-10 lg:grid-cols-2 lg:gap-14">
    <div>
        <span class="eyebrow">{{ config('vendra.categories.'.$business->category.'.label', 'Local business') }}</span>
        <h1 class="display mt-5">{{ $business->name }}</h1>
        <p class="lead mt-5 max-w-lg">{{ $business->description ?: 'Ask a question, check prices and send a request in minutes. English, Tagalog or Taglish is fine.' }}</p>
        <div class="mt-8 flex flex-wrap items-center gap-6">
            <a href="{{ $primary[1] }}" class="btn btn-primary">{{ $primary[0] }}</a>
            <button type="button" class="btn-play" data-open-chat>
                <span class="btn-play-icon"><x-icon name="chat" class="h-4 w-4" /></span> Ask our assistant
            </button>
        </div>
    </div>
    <div class="notch art-warm grain aspect-[5/4] w-full" style="--nw: 38%; --nh: 52px">
        <span class="notch-slot"><span class="eyebrow bg-white shadow-sm ring-1 ring-line"><x-icon name="spark" class="h-3.5 w-3.5" /> AI replies in Taglish</span></span>
        <div class="absolute inset-x-5 bottom-5 z-[6] grid gap-3 sm:inset-x-6 sm:bottom-6 sm:grid-cols-[1fr_auto]">
            <div class="rounded-xl bg-white/90 p-4 shadow-sm backdrop-blur">
                <p class="text-xs text-ink-500">{{ $openToday ? 'Open today' : 'Closed today' }}</p>
                <p class="mt-0.5 text-lg font-semibold tracking-tight">{{ $openToday ? $fmt($todayHours->opens_at).' – '.$fmt($todayHours->closes_at) : 'See weekly hours below' }}</p>
                @if ($business->address)<p class="mt-1 flex items-center gap-1.5 text-xs text-ink-600"><x-icon name="pin" class="h-3.5 w-3.5" /> {{ $business->address }}</p>@endif
            </div>
            <div class="hidden items-center gap-3 rounded-xl bg-ink-900 p-4 text-white sm:flex">
                <x-icon name="chat" class="h-6 w-6" />
                <span class="text-sm leading-tight">“Magkano po?”<br><span class="text-white/60">Answered instantly</span></span>
            </div>
        </div>
    </div>
</section>

{{-- Scrolling strip (like the reference partner logos) --}}
@if ($items->isNotEmpty())
<section class="relative mt-16 mr-[calc(50%-50vw)] ml-[calc(50%-50vw)] w-screen" aria-label="Highlights">
    <div class="absolute inset-x-0 top-0 z-10 flex -translate-y-1/2 justify-center"><span class="eyebrow border border-line bg-white">What we offer</span></div>
    <div class="marquee">
        <div class="marquee-track">
            @foreach ([1, 2] as $_)
                @foreach ($items->take(10) as $i)
                    <span class="text-ink-300" aria-hidden="true">•</span><span>{{ $i->name }}</span>
                @endforeach
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- About / numbers --}}
<section class="pt-20 text-center">
    <span class="eyebrow"><x-icon name="spark" class="h-3.5 w-3.5" /> About us</span>
    <h2 class="section-title mt-4">Get to know {{ $business->name }}</h2>
    <p class="lead mx-auto mt-3 max-w-xl">Browse what we offer, check open times and send a request — our team confirms everything you submit.</p>
</section>
<section class="mt-12 grid gap-6 lg:grid-cols-2">
    <div class="notch notch-bl art-cool grain min-h-[22rem] p-7 text-white" style="--nw: 46%; --nh: 64px">
        <p class="text-sm text-white/60">Opening hours</p>
        <dl class="mt-4 space-y-2.5 text-[15px]">
            @foreach ($hours as $h)
                <div class="flex justify-between gap-4 border-b border-white/10 pb-2.5 last:border-0">
                    <dt class="text-white/70">{{ ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'][$h->weekday] }}</dt>
                    <dd class="font-medium">{{ $h->is_closed || ! $h->opens_at ? 'Closed' : $fmt($h->opens_at).' – '.$fmt($h->closes_at) }}</dd>
                </div>
            @endforeach
        </dl>
    </div>
    <div class="flex flex-col gap-4">
        <div class="grid flex-1 gap-4 sm:grid-cols-2">
            <div class="stat"><div class="flex items-start gap-4"><x-icon name="folder" class="stat-icon" /><div><p class="stat-value">{{ $services + $products }}</p><p class="stat-label">{{ $services ? 'Services' : '' }}{{ $services && $products ? ' & ' : '' }}{{ $products ? 'products' : '' }} listed with live prices</p></div></div></div>
            <div class="stat"><div class="flex items-start gap-4"><x-icon name="briefcase" class="stat-icon" /><div><p class="stat-value">{{ $open->count() }}</p><p class="stat-label">Days a week we’re open</p></div></div></div>
            <div class="stat"><div class="flex items-start gap-4"><x-icon name="globe" class="stat-icon" /><div><p class="stat-value">3</p><p class="stat-label">Languages: English, Tagalog, Taglish</p></div></div></div>
            <div class="stat"><div class="flex items-start gap-4"><x-icon name="shield" class="stat-icon" /><div><p class="stat-value">100%</p><p class="stat-label">Of requests reviewed by you before sending</p></div></div></div>
        </div>
        <div class="flex flex-wrap items-center gap-5">
            <a href="{{ $primary[1] }}" class="btn btn-primary">{{ $primary[0] }}</a>
            @if ($business->phone)
                <span class="flex items-center gap-3"><span class="flex h-10 w-10 items-center justify-center rounded-full bg-tile"><x-icon name="phone" class="h-4 w-4" /></span><span class="leading-tight"><span class="block text-[11px] text-ink-500">Questions?</span><span class="block text-sm font-semibold">{{ $business->phone }}</span></span></span>
            @endif
        </div>
    </div>
</section>

{{-- Catalog --}}
<section class="pt-24" id="catalog">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <span class="eyebrow"><x-icon name="tag" class="h-3.5 w-3.5" /> Services & products</span>
            <h2 class="section-title mt-4">Prices, upfront</h2>
        </div>
        <form method="GET" action="#catalog" class="flex w-full gap-2 sm:w-auto" role="search">
            <label for="q" class="sr-only">Search</label>
            <input id="q" name="q" value="{{ $q }}" class="input sm:w-64" placeholder="Search services…">
            <button class="btn btn-secondary" aria-label="Search"><x-icon name="search" class="h-4 w-4" /></button>
        </form>
    </div>
    @if ($items->isEmpty())
        <div class="mt-8"><x-empty title="Nothing found">Try another search, or ask us in the chat.</x-empty></div>
    @else
        <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($items as $item)
                <article class="tile flex flex-col transition hover:bg-ink-100">
                    <div class="flex items-start justify-between gap-3">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-white"><x-icon :name="$item->type === 'service' ? 'calendar' : 'bag'" class="h-5 w-5" /></span>
                        <span class="text-lg font-semibold tracking-tight">{{ peso($item->price_cents) }}</span>
                    </div>
                    <h3 class="mt-5 text-lg font-semibold">{{ $item->name }}</h3>
                    <p class="mt-0.5 text-xs text-ink-500">{{ $item->category }}{{ $item->type === 'service' ? ' · '.$item->duration_minutes.' min' : '' }}</p>
                    @if ($item->description)<p class="mt-3 flex-1 text-sm leading-relaxed text-ink-600">{{ $item->description }}</p>@else<span class="flex-1"></span>@endif
                    <div class="mt-5 flex items-center justify-between">
                        @if ($item->type === 'service' && $business->moduleEnabled('appointments'))
                            <a href="{{ route('public.book', [$business, 'service' => $item->id]) }}" class="inline-flex items-center gap-1.5 text-sm font-medium">Book this <x-icon name="arrow" class="h-4 w-4" /></a>
                        @elseif ($item->type === 'product')
                            @php $avail = $item->availableStock(); @endphp
                            @if ($avail === 0)<span class="badge badge-gray">Out of stock</span>
                            @else
                                <a href="{{ route('public.order', [$business, 'items' => [$item->id => 1]]) }}" class="inline-flex items-center gap-1.5 text-sm font-medium">Order <x-icon name="arrow" class="h-4 w-4" /></a>
                                @if ($avail !== null && $avail <= 3)<span class="badge badge-amber">Only {{ $avail }} left</span>@endif
                            @endif
                        @else
                            <span></span>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
    @endif
</section>

@if ($knowledge->isNotEmpty())
<section class="grid gap-10 pt-24 lg:grid-cols-[1fr_1.4fr]">
    <div>
        <span class="eyebrow"><x-icon name="doc" class="h-3.5 w-3.5" /> Good to know</span>
        <h2 class="section-title mt-4">Policies & FAQ</h2>
        <p class="lead mt-3">Can’t find an answer? Ask our assistant or message the team.</p>
        <button type="button" class="btn btn-primary mt-6" data-open-chat>Ask a question</button>
    </div>
    <div class="divide-y divide-line border-y border-line">
        @foreach ($knowledge as $k)
            <details class="group py-5">
                <summary class="flex cursor-pointer list-none items-center justify-between gap-4 text-[17px] font-medium">
                    {{ $k->title }}
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-tile text-lg transition group-open:rotate-45" aria-hidden="true">+</span>
                </summary>
                <p class="mt-3 pr-12 text-sm leading-relaxed whitespace-pre-line text-ink-600">{{ $k->body }}</p>
            </details>
        @endforeach
    </div>
</section>
@endif
@endsection
