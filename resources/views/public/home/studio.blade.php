{{-- Studio: split hero with phone preview, two-column price list, bento visit block, FAQ. --}}
<div class="mx-auto max-w-6xl px-5 py-10 sm:px-8">
<x-flash />
{{-- Hero --}}
<section class="grid items-center gap-12 lg:grid-cols-[1.1fr_1fr]">
    <div>
        <span class="eyebrow">{{ $categoryLabel }}</span>
        <h1 class="display mt-6">{{ $business->name }}</h1>
        <p class="lead mt-6 max-w-lg">{{ $intro }}</p>
        <div class="mt-8 flex flex-wrap items-center gap-3">
            <a href="{{ $primary[1] }}" class="btn btn-sun px-6">{{ $primary[0] }} <x-icon name="arrow" class="h-4 w-4" /></a>
            <button type="button" class="btn-play" data-open-chat><span class="btn-play-icon"><x-icon name="chat" class="h-3.5 w-3.5" /></span> Ask our assistant</button>
        </div>
        <div class="mt-8 flex flex-wrap gap-2">
            <span class="chip"><span class="h-2 w-2 rounded-full {{ $openToday ? 'bg-leaf' : 'bg-ink-300' }}"></span>{{ $todayLabel }}</span>
            @if ($business->address)<span class="chip max-w-full"><x-icon name="pin" class="h-4 w-4 shrink-0 text-ink-500" /><span class="truncate">{{ $business->address }}</span></span>@endif
        </div>
    </div>
    <div class="dots relative rounded-[32px] bg-tile px-6 pt-12 pb-10 sm:px-10">
        <x-sticker class="absolute -top-8 right-6 hidden sm:inline-flex" uid="shop" text="ASK IN TAGLISH · 24/7 · " />
        @include('public.home.parts.phone')
    </div>
</section>

{{-- Menu-style price list --}}
<section class="pt-24" id="catalog">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <span class="eyebrow">Menu & prices</span>
            <h2 class="section-title mt-4">What we offer</h2>
        </div>
        @include('public.home.parts.search', ['class' => 'flex w-full gap-2 sm:w-auto', 'inputClass' => 'rounded-full sm:w-64'])
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
                            <li class="group flex flex-wrap items-center gap-x-4 gap-y-3 border-b border-dashed border-line py-4">
                                <div class="min-w-0 flex-1 basis-full sm:basis-0">
                                    <div class="flex items-baseline gap-3">
                                        <span class="min-w-0 flex-1 font-display text-lg leading-snug font-semibold sm:flex-none">{{ $item->name }}</span>
                                        <span class="hidden flex-1 border-b-2 border-dotted border-ink-200 sm:block" aria-hidden="true"></span>
                                        <span class="shrink-0 font-display text-lg font-bold whitespace-nowrap">{{ peso($item->price_cents) }}</span>
                                    </div>
                                    <p class="mt-1 text-sm text-ink-500">@include('public.home.parts.meta')</p>
                                </div>
                                @include('public.home.parts.action', ['btn' => 'btn btn-secondary btn-sm shrink-0 group-hover:border-ink-900'])
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
            @include('public.home.parts.hours', ['class' => 'mt-4'])
        </div>
        <div class="flex flex-col rounded-[22px] bg-ink-900 p-6 text-white">
            <p class="text-sm font-semibold">Get in touch</p>
            @include('public.home.parts.contact', ['class' => 'mt-5 mb-6', 'iconClass' => 'text-sun'])
            <button type="button" class="btn btn-sun mt-auto w-full" data-open-chat>Message us</button>
        </div>
        <div class="rounded-[22px] bg-sun p-6">
            <p class="text-sm font-semibold">How requests work</p>
            @include('public.home.parts.steps', ['class' => 'mt-5', 'numClass' => 'bg-ink-900 text-sun'])
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
    @include('public.home.parts.faq')
</section>
@endif
</div>
