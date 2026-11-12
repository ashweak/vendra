@extends('layouts.public')
@section('title', $business->name)
@section('content')
@php
    $todayDow = now($business->timezone)->dayOfWeek;
    $todayHours = $hours->firstWhere('weekday', $todayDow);
    $openToday = $todayHours && ! $todayHours->is_closed && $todayHours->opens_at;
    $fmt = fn ($t) => \Carbon\Carbon::parse($t)->format('g:i A');
    $primary = $business->moduleEnabled('appointments') ? ['Book an appointment', route('public.book', $business)]
        : ($business->moduleEnabled('orders') ? ['Shop now', route('public.order', $business)]
        : ($business->moduleEnabled('service_requests') ? ['Request a service', route('public.request', $business)] : ['Get a quote', route('public.quote', $business)]));
    $featured = $items->first();
    $groups = $items->groupBy(fn ($i) => $i->category ?: ($i->type === 'service' ? 'Services' : 'Products'));
@endphp

{{-- Hero --}}
<section class="grid items-center gap-12 lg:grid-cols-[1.1fr_1fr]">
    <div>
        <span class="eyebrow">{{ config('vendra.categories.'.$business->category.'.label', 'Local business') }}</span>
        <h1 class="display mt-6">{{ $business->name }}</h1>
        <p class="lead mt-6 max-w-lg">{{ $business->description ?: 'Ask a question, check prices and send a request in minutes. English, Tagalog or Taglish is fine.' }}</p>
        <div class="mt-8 flex flex-wrap items-center gap-3">
            <a href="{{ $primary[1] }}" class="btn btn-sun px-6">{{ $primary[0] }} <x-icon name="arrow" class="h-4 w-4" /></a>
            <button type="button" class="btn-play" data-open-chat><span class="btn-play-icon"><x-icon name="chat" class="h-3.5 w-3.5" /></span> Ask our assistant</button>
        </div>
        <div class="mt-8 flex flex-wrap gap-2">
            <span class="chip"><span class="h-2 w-2 rounded-full {{ $openToday ? 'bg-leaf' : 'bg-ink-300' }}"></span>{{ $openToday ? 'Open today · '.$fmt($todayHours->opens_at).' – '.$fmt($todayHours->closes_at) : 'Closed today' }}</span>
            @if ($business->address)<span class="chip max-w-full"><x-icon name="pin" class="h-4 w-4 shrink-0 text-ink-500" /><span class="truncate">{{ $business->address }}</span></span>@endif
        </div>
    </div>
    <div class="dots relative rounded-[32px] bg-tile px-6 pt-12 pb-10 sm:px-10">
        <x-sticker class="absolute -top-8 right-6 hidden sm:inline-flex" uid="shop" text="ASK IN TAGLISH · 24/7 · " />
        @if ($featured)
            <x-phone-chat :name="$business->name" :ask="'Magkano po ang '.mb_strtolower($featured->name).'?'"
                :answer="'Ang '.$featured->name.' po ay '.peso($featured->price_cents).'. Gusto n’yo po bang mag-'.($featured->type === 'service' ? 'book' : 'order').'?'"
                :item="$featured->name" :price="peso($featured->price_cents)"
                :meta="$featured->type === 'service' ? ($featured->duration_minutes.' min') : 'Pickup or delivery'" />
        @else
            <x-phone-chat :name="$business->name" />
        @endif
    </div>
</section>

{{-- Menu-style price list --}}
<section class="pt-24" id="catalog">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <span class="eyebrow">Menu & prices</span>
            <h2 class="section-title mt-4">What we offer</h2>
        </div>
        <form method="GET" action="#catalog" class="flex w-full gap-2 sm:w-auto" role="search">
            <label for="q" class="sr-only">Search</label>
            <input id="q" name="q" value="{{ $q }}" class="input rounded-full sm:w-64" placeholder="Search the menu…">
            <button class="btn btn-secondary px-3.5" aria-label="Search"><x-icon name="search" class="h-4 w-4" /></button>
        </form>
    </div>
    @if ($items->isEmpty())
        <div class="mt-8"><x-empty title="Nothing found">Try another search, or ask us in the chat.</x-empty></div>
    @else
        <div class="mt-10 grid gap-x-12 gap-y-10 lg:grid-cols-2">
            @foreach ($groups as $group => $rows)
                <div>
                    <h3 class="flex items-center gap-3 text-sm font-semibold tracking-widest text-ink-500 uppercase">{{ $group }} <span class="h-px flex-1 bg-line"></span></h3>
                    <ul class="mt-2">
                        @foreach ($rows as $item)
                            @php $avail = $item->type === 'product' ? $item->availableStock() : null; @endphp
                            <li class="group flex items-center gap-4 border-b border-dashed border-line py-4">
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-baseline gap-3">
                                        <span class="font-display text-lg font-semibold">{{ $item->name }}</span>
                                        <span class="flex-1 border-b-2 border-dotted border-ink-200" aria-hidden="true"></span>
                                        <span class="font-display text-lg font-bold">{{ peso($item->price_cents) }}</span>
                                    </div>
                                    <p class="mt-1 text-sm text-ink-500">
                                        {{ $item->type === 'service' ? $item->duration_minutes.' min' : ($avail === 0 ? 'Out of stock' : ($avail !== null && $avail <= 3 ? 'Only '.$avail.' left' : 'In stock')) }}@if ($item->description) · {{ $item->description }}@endif
                                    </p>
                                </div>
                                @if ($item->type === 'service' && $business->moduleEnabled('appointments'))
                                    <a href="{{ route('public.book', [$business, 'service' => $item->id]) }}" class="btn btn-secondary btn-sm shrink-0 group-hover:border-ink-900">Book</a>
                                @elseif ($item->type === 'product' && $avail !== 0)
                                    <a href="{{ route('public.order', [$business, 'items' => [$item->id => 1]]) }}" class="btn btn-secondary btn-sm shrink-0 group-hover:border-ink-900">Order</a>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>
    @endif
</section>

{{-- Visit us: bento --}}
<section class="pt-24">
    <span class="eyebrow">Visit us</span>
    <h2 class="section-title mt-4">Hours & contact</h2>
    <div class="mt-10 grid gap-4 lg:grid-cols-3">
        <div class="card p-6">
            <p class="text-sm font-semibold">Opening hours</p>
            <dl class="mt-4 space-y-1 text-sm">
                @foreach ($hours->sortBy(fn ($h) => ($h->weekday + 6) % 7) as $h)
                    <div @class(['flex justify-between gap-4 rounded-lg px-3 py-2', 'bg-sun-soft font-semibold' => $h->weekday === $todayDow])>
                        <dt>{{ ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'][$h->weekday] }}</dt>
                        <dd>{{ $h->is_closed || ! $h->opens_at ? 'Closed' : $fmt($h->opens_at).' – '.$fmt($h->closes_at) }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>
        <div class="flex flex-col rounded-[22px] bg-ink-900 p-6 text-white">
            <p class="text-sm font-semibold">Get in touch</p>
            <ul class="mt-5 mb-6 space-y-4 text-sm">
                @if ($business->phone)<li class="flex gap-3"><x-icon name="phone" class="h-5 w-5 shrink-0 text-sun" /><span>{{ $business->phone }}</span></li>@endif
                @if ($business->email)<li class="flex gap-3"><x-icon name="chat" class="h-5 w-5 shrink-0 text-sun" /><span>{{ $business->email }}</span></li>@endif
                @if ($business->address)<li class="flex gap-3"><x-icon name="pin" class="h-5 w-5 shrink-0 text-sun" /><span>{{ $business->address }}</span></li>@endif
                <li class="flex gap-3"><x-icon name="clock" class="h-5 w-5 shrink-0 text-sun" /><span>Staff reply in chat: {{ $business->setting('response_hours') }}</span></li>
            </ul>
            <button type="button" class="btn btn-sun mt-auto w-full" data-open-chat>Message us</button>
        </div>
        <div class="rounded-[22px] bg-sun p-6">
            <p class="text-sm font-semibold">How requests work</p>
            <ol class="mt-5 space-y-4 text-sm">
                @foreach (['Ask or pick what you need — chat or form.', 'Review every detail, then confirm.', 'We approve or follow up. Track it by email link.'] as $i => $step)
                    <li class="flex gap-3"><span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-ink-900 text-xs font-bold text-sun">{{ $i + 1 }}</span>{{ $step }}</li>
                @endforeach
            </ol>
        </div>
    </div>
</section>

@if ($knowledge->isNotEmpty())
<section class="grid gap-10 pt-24 lg:grid-cols-[1fr_1.4fr]">
    <div>
        <span class="eyebrow">Good to know</span>
        <h2 class="section-title mt-4">Policies & FAQ</h2>
        <p class="lead mt-3">Can’t find an answer? Ask our assistant or message the team.</p>
    </div>
    <div class="space-y-3">
        @foreach ($knowledge as $k)
            <details class="group card p-0">
                <summary class="flex cursor-pointer list-none items-center justify-between gap-4 px-5 py-4 font-display text-[17px] font-semibold">
                    {{ $k->title }}
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-sun-soft text-lg transition group-open:rotate-45" aria-hidden="true">+</span>
                </summary>
                <p class="px-5 pb-5 text-sm leading-relaxed whitespace-pre-line text-ink-600">{{ $k->body }}</p>
            </details>
        @endforeach
    </div>
</section>
@endif
@endsection
