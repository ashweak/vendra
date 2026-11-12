@extends('layouts.public')
@section('title', $business->name)
@section('content')
<section class="grid gap-6 md:grid-cols-[1fr_18rem]">
    <div>
        <h1 class="text-3xl font-bold tracking-tight">{{ $business->name }}</h1>
        @if ($business->description)<p class="mt-2 max-w-2xl text-gray-600">{{ $business->description }}</p>@endif
        <div class="mt-5 flex flex-wrap gap-2">
            @if ($business->moduleEnabled('appointments'))<a href="{{ route('public.book', $business) }}" class="btn btn-primary" style="background: var(--accent)">Book an appointment</a>@endif
            @if ($business->moduleEnabled('orders'))<a href="{{ route('public.order', $business) }}" class="btn btn-primary" style="background: var(--accent)">Order products</a>@endif
            @if ($business->moduleEnabled('quotations'))<a href="{{ route('public.quote', $business) }}" class="btn btn-secondary">Request a quote</a>@endif
            @if ($business->moduleEnabled('service_requests'))<a href="{{ route('public.request', $business) }}" class="btn btn-secondary">Request a service</a>@endif
        </div>
    </div>
    <aside class="card text-sm">
        <h2 class="font-semibold">Hours</h2>
        <dl class="mt-2 space-y-1">
            @foreach ($hours as $h)
                <div class="flex justify-between gap-2"><dt class="text-gray-600">{{ ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'][$h->weekday] }}</dt><dd>{{ $h->is_closed || ! $h->opens_at ? 'Closed' : \Carbon\Carbon::parse($h->opens_at)->format('g:i A').' – '.\Carbon\Carbon::parse($h->closes_at)->format('g:i A') }}</dd></div>
            @endforeach
        </dl>
        @if ($business->address)<p class="mt-3 text-gray-700">📍 {{ $business->address }}</p>@endif
        @if ($business->phone)<p class="mt-1 text-gray-700">📞 {{ $business->phone }}</p>@endif
        <p class="mt-3 text-xs text-gray-500">Staff reply in chat: {{ $business->setting('response_hours') }}</p>
    </aside>
</section>

<section class="mt-10">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <h2 class="h2">Services & products</h2>
        <form method="GET" class="flex gap-2" role="search">
            <label for="q" class="sr-only">Search</label>
            <input id="q" name="q" value="{{ $q }}" class="input w-56" placeholder="Search…">
            <button class="btn btn-secondary">Search</button>
        </form>
    </div>
    @if ($items->isEmpty())
        <x-empty title="Nothing found">Try another search, or ask us in the chat.</x-empty>
    @else
        <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($items as $item)
                <article class="card flex flex-col">
                    <div class="flex items-start justify-between gap-2">
                        <h3 class="font-semibold">{{ $item->name }}</h3>
                        <span class="font-semibold" style="color: var(--accent)">{{ peso($item->price_cents) }}</span>
                    </div>
                    <p class="mt-0.5 text-xs text-gray-500">{{ $item->category }}{{ $item->type === 'service' ? ' · '.$item->duration_minutes.' min' : '' }}</p>
                    @if ($item->description)<p class="mt-2 flex-1 text-sm text-gray-600">{{ $item->description }}</p>@endif
                    <div class="mt-3">
                        @if ($item->type === 'service' && $business->moduleEnabled('appointments'))
                            <a href="{{ route('public.book', [$business, 'service' => $item->id]) }}" class="link text-sm">Book this →</a>
                        @elseif ($item->type === 'product')
                            @php $avail = $item->availableStock(); @endphp
                            @if ($avail === 0)<span class="badge badge-gray">Out of stock</span>
                            @else<a href="{{ route('public.order', [$business, 'items' => [$item->id => 1]]) }}" class="link text-sm">Order →</a>@if ($avail !== null && $avail <= 3)<span class="badge badge-amber ml-2">Only {{ $avail }} left</span>@endif
                            @endif
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
    @endif
</section>

@if ($knowledge->isNotEmpty())
<section class="mt-10">
    <h2 class="h2">Policies & FAQ</h2>
    <div class="mt-3 space-y-2">
        @foreach ($knowledge as $k)
            <details class="card">
                <summary class="cursor-pointer font-medium">{{ $k->title }} <span class="badge badge-gray ml-1">{{ strtoupper($k->kind) }}</span></summary>
                <p class="mt-2 text-sm whitespace-pre-line text-gray-700">{{ $k->body }}</p>
            </details>
        @endforeach
    </div>
</section>
@endif
@endsection
