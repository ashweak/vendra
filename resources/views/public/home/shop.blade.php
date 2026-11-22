{{-- Shop: dark promo banner with search and price tags, two offer tiles, category shelf,
     product grid by category, perks strip, visit cards, FAQ. --}}
@php $slug = fn ($g, $i) => 'grp-'.(\Illuminate\Support\Str::slug($g) ?: $i); @endphp
<section class="mx-auto max-w-6xl px-5 pt-6 sm:px-8">
    <x-flash />
    <div class="relative overflow-hidden rounded-[28px] bg-ink-900 p-7 text-white sm:p-12">
        <div class="pointer-events-none absolute -right-16 -bottom-24 h-80 w-80 rounded-full bg-sun/20" aria-hidden="true"></div>
        <div class="relative grid items-center gap-10 lg:grid-cols-[1.3fr_1fr]">
            <div>
                <span class="inline-flex rounded-full bg-white/10 px-3 py-1 text-xs font-medium">{{ $categoryLabel }}</span>
                <h1 class="mt-5 font-display text-[2.5rem] leading-[1.04] font-bold sm:text-6xl">{{ $business->name }}</h1>
                <p class="mt-5 max-w-lg text-white/75">{{ $intro }}</p>
                @include('public.home.parts.search', ['class' => 'mt-7 flex max-w-md gap-2', 'inputClass' => 'rounded-full border-0', 'buttonClass' => 'btn btn-sun px-4', 'placeholder' => 'What are you looking for?', 'searchId' => 'hero'])
                <div class="mt-5 flex flex-wrap items-center gap-4">
                    <a href="{{ $primary[1] }}" class="btn btn-sun px-6">{{ $primary[0] }}</a>
                    <button type="button" class="text-sm font-semibold text-white underline decoration-sun decoration-2 underline-offset-4" data-open-chat>Ask in Taglish</button>
                </div>
            </div>
            @if ($cover)
            <img src="{{ $cover }}" alt="{{ $business->name }}" class="aspect-[4/3] w-full rounded-2xl object-cover shadow-[0_18px_40px_-24px_rgba(0,0,0,.6)]">
            @else
            <div class="hidden grid-cols-2 gap-3 sm:grid" aria-hidden="true">
                @foreach ($items->take(4)->values() as $i => $tag)
                    <div @class(['rounded-2xl p-4 text-ink-900 shadow-[0_18px_40px_-24px_rgba(0,0,0,.6)]', 'bg-sun -rotate-2' => $i === 0, 'bg-white rotate-1' => $i === 1, 'bg-sun-soft rotate-2' => $i === 2, 'bg-white -rotate-1' => $i === 3])>
                        <span class="flex h-10 w-10 items-center justify-center rounded-full bg-ink-900/10 font-display font-bold">{{ mb_substr($tag->name, 0, 1) }}</span>
                        <p class="mt-3 truncate text-sm font-medium">{{ $tag->name }}</p>
                        <p class="font-display text-xl font-bold">{{ peso($tag->price_cents) }}</p>
                    </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>
    <div class="mt-4 grid gap-4 sm:grid-cols-2">
        <a href="{{ $primary[1] }}" class="flex items-center justify-between gap-4 rounded-[22px] bg-sun p-6 text-ink-900">
            <span><span class="block font-display text-xl font-bold">{{ $business->moduleEnabled('orders') ? 'Order ahead, pick up fast' : 'Reserve your slot' }}</span><span class="text-sm">Review everything before you confirm.</span></span>
            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-ink-900 text-white"><x-icon name="arrow" class="h-4 w-4" /></span>
        </a>
        <button type="button" data-open-chat class="flex items-center justify-between gap-4 rounded-[22px] bg-sun-soft p-6 text-left text-ink-900">
            <span><span class="block font-display text-xl font-bold">Questions? Ask in Taglish</span><span class="text-sm">Prices and stock straight from our list.</span></span>
            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-ink-900 text-white"><x-icon name="chat" class="h-4 w-4" /></span>
        </button>
    </div>
</section>

@if ($groups->count() > 1)
    <section class="mx-auto max-w-6xl px-5 pt-14 sm:px-8">
        <h2 class="font-display text-2xl font-bold sm:text-3xl">Shop by category</h2>
        <div class="-mx-5 mt-5 flex gap-3 overflow-x-auto px-5 pb-2 sm:mx-0 sm:px-0">
            @foreach ($groups as $g => $rows)
                <a href="#{{ $slug($g, $loop->index) }}" class="flex w-36 shrink-0 flex-col items-center rounded-2xl border border-line bg-white p-4 text-center hover:border-ink-900">
                    <span class="flex h-14 w-14 items-center justify-center rounded-full bg-tile font-display text-xl font-bold text-ink-700" aria-hidden="true">{{ mb_substr($g, 0, 1) }}</span>
                    <span class="mt-3 text-sm font-semibold">{{ $g }}</span>
                    <span class="text-xs text-ink-500">{{ $rows->count() }} {{ \Illuminate\Support\Str::plural('item', $rows->count()) }}</span>
                </a>
            @endforeach
        </div>
    </section>
@endif

<section id="catalog" class="mx-auto max-w-6xl px-5 pt-14 sm:px-8">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <h2 class="font-display text-2xl font-bold sm:text-3xl">{{ $q !== '' ? 'Results for “'.$q.'”' : 'All items' }}</h2>
        <span class="text-sm text-ink-500">{{ $items->count() }} {{ \Illuminate\Support\Str::plural('item', $items->count()) }}@if ($q !== '') · <a href="{{ route('public.home', $business) }}#catalog" class="underline">Clear search</a>@endif</span>
    </div>
    @if ($items->isEmpty())
        <div class="mt-6"><x-empty title="Nothing found">Try another search, or ask us in the chat.</x-empty></div>
    @else
        @foreach ($groups as $g => $rows)
            <h3 id="{{ $slug($g, $loop->index) }}" class="mt-8 scroll-mt-32 text-sm font-semibold tracking-wider text-ink-600 uppercase">{{ $g }}</h3>
            <div class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                @foreach ($rows as $item)
                    @php $left = $item->type === 'product' ? $item->availableStock() : null; @endphp
                    <article class="flex flex-col rounded-2xl border border-line bg-white p-3">
                        <div class="relative">
                            <x-item-visual :item="$item" class="flex aspect-square w-full items-center justify-center rounded-xl bg-tile">
                                <span class="font-display text-5xl font-bold text-ink-700" aria-hidden="true">{{ mb_substr($item->name, 0, 1) }}</span>
                            </x-item-visual>
                            @if ($left !== null && $left > 0 && $left <= 3)<span class="absolute top-2 left-2 rounded-md bg-sun px-2 py-0.5 text-[11px] font-semibold text-ink-900">Only {{ $left }} left</span>@endif
                            @if ($left === 0)<span class="absolute top-2 left-2 rounded-md bg-ink-900 px-2 py-0.5 text-[11px] font-semibold text-white">Sold out</span>@endif
                        </div>
                        <p class="mt-3 text-xs text-ink-500">{{ $item->type === 'service' ? $item->duration_minutes.' min' : ($item->category ?: 'Product') }}</p>
                        <h4 class="mt-0.5 text-sm leading-snug font-semibold">{{ $item->name }}</h4>
                        <p class="mt-2 font-display text-xl font-bold">{{ peso($item->price_cents) }}</p>
                        <div class="mt-auto pt-3">@include('public.home.parts.action', ['btn' => 'btn btn-primary btn-sm w-full', 'showUnavailable' => true])</div>
                    </article>
                @endforeach
            </div>
        @endforeach
    @endif
</section>

@include('public.home.parts.gallery', ['title' => 'From our store', 'sectionClass' => 'mx-auto max-w-6xl px-5 pt-14 sm:px-8'])

<section class="mx-auto max-w-6xl px-5 pt-14 sm:px-8">
    <div class="grid grid-cols-1 gap-3 rounded-[28px] border-2 border-sun p-3 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ([['check', 'Review before you confirm', 'Nothing is sent until you say so.'], ['search', 'Track by email link', 'Secure link, no account needed.'], ['clock', 'Staff reply hours', $business->setting('response_hours')], ['chat', 'Taglish is fine', 'English, Tagalog or a mix.']] as [$icon, $title, $text])
            <div class="rounded-2xl bg-white p-4">
                <x-icon :name="$icon" class="h-5 w-5 text-ink-700" />
                <p class="mt-3 text-sm font-semibold">{{ $title }}</p>
                <p class="mt-1 text-xs text-ink-600">{{ $text }}</p>
            </div>
        @endforeach
    </div>
</section>

<section class="mx-auto grid max-w-6xl gap-5 px-5 pt-14 sm:px-8 lg:grid-cols-2">
    <div class="card p-6"><h2 class="font-display text-xl font-bold">Store hours</h2>@include('public.home.parts.hours', ['class' => 'mt-4'])</div>
    <div class="card p-6"><h2 class="font-display text-xl font-bold">Contact</h2>@include('public.home.parts.contact', ['class' => 'mt-5', 'iconClass' => 'text-ink-700'])</div>
</section>

@if ($knowledge->isNotEmpty())
    <section class="mx-auto max-w-6xl px-5 pt-14 pb-8 sm:px-8">
        <h2 class="font-display text-2xl font-bold sm:text-3xl">Good to know</h2>
        @include('public.home.parts.faq', ['class' => 'mt-5 grid gap-3 lg:grid-cols-2 lg:items-start'])
    </section>
@endif
