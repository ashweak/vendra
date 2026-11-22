{{-- Roastery: bento hero (big story tile, featured item, dark status tile), scrolling category ribbon,
     menu cards by category, dark "how it works" band, visit cards, FAQ. --}}
@php $slug = fn ($g, $i) => 'grp-'.(\Illuminate\Support\Str::slug($g) ?: $i); @endphp
<section class="mx-auto max-w-6xl px-5 pt-6 pb-12 sm:px-8">
    <x-flash />
    <div class="grid gap-4 lg:grid-cols-[1.6fr_1fr]">
        <div class="relative overflow-hidden rounded-[28px] bg-sun-soft p-7 sm:p-12 lg:row-span-2">
            <span class="eyebrow">{{ $categoryLabel }}</span>
            <h1 class="display mt-6">{{ $business->name }}</h1>
            <p class="mt-4 font-display text-2xl text-ink-800 sm:text-3xl">Ask, review, then <span class="mark">confirm</span>.</p>
            <p class="lead mt-4 max-w-lg">{{ $intro }}</p>
            <div class="mt-8 flex flex-wrap items-center gap-4">
                <a href="{{ $primary[1] }}" class="btn btn-primary px-6">{{ $primary[0] }}</a>
                <button type="button" class="text-sm font-semibold underline decoration-sun-deep decoration-2 underline-offset-4" data-open-chat>Ask in Taglish →</button>
            </div>
            <div class="mt-10 grid max-w-md grid-cols-3 gap-4 border-t border-ink-900/10 pt-6">
                <p><span class="block font-display text-3xl font-semibold">{{ $items->count() }}</span><span class="text-xs text-ink-600">on the menu</span></p>
                <p><span class="block font-display text-3xl font-semibold">{{ $openDays }}</span><span class="text-xs text-ink-600">days open</span></p>
                <p><span class="block font-display text-3xl font-semibold">3</span><span class="text-xs text-ink-600">languages in chat</span></p>
            </div>
        </div>
        @if ($featured)
            <article class="flex flex-col rounded-[28px] border border-line bg-white p-5">
                <span class="w-fit rounded-full bg-tile px-3 py-1 text-[11px] font-semibold tracking-wider text-ink-600 uppercase">Featured</span>
                <div class="mt-4 flex items-center gap-4">
                    <span class="flex h-20 w-20 shrink-0 items-center justify-center rounded-2xl bg-sun font-display text-4xl text-ink-900 italic" aria-hidden="true">{{ mb_substr($featured->name, 0, 1) }}</span>
                    <div class="min-w-0">
                        <h2 class="font-display text-xl font-semibold leading-tight">{{ $featured->name }}</h2>
                        <p class="mt-1 text-xs text-ink-500">@include('public.home.parts.meta', ['item' => $featured, 'withDescription' => false])</p>
                    </div>
                </div>
                <div class="mt-auto flex items-center justify-between pt-5">
                    <span class="font-display text-2xl font-semibold">{{ peso($featured->price_cents) }}</span>
                    @include('public.home.parts.action', ['item' => $featured, 'btn' => 'btn btn-sun btn-sm'])
                </div>
            </article>
        @endif
        <div class="flex flex-col justify-between gap-6 rounded-[28px] bg-ink-900 p-6 text-white">
            <div>
                <p class="text-xs tracking-wider text-white/60 uppercase">Today</p>
                <p class="mt-2 font-display text-2xl font-semibold">{{ $todayLabel }}</p>
                <p class="mt-2 text-sm text-white/70">Staff reply in chat {{ $business->setting('response_hours') }}.</p>
            </div>
            <button type="button" class="btn btn-sun w-full" data-open-chat>Message us</button>
        </div>
    </div>
</section>

@if ($groups->isNotEmpty())
    <div class="overflow-hidden border-y border-line py-5" aria-hidden="true">
        <div class="vendra-marquee flex w-max gap-10 font-display text-4xl whitespace-nowrap text-ink-800 italic sm:text-6xl">
            @foreach (range(1, 2) as $copy)
                @foreach ($groups->keys()->concat($groups->keys())->take(8) as $g)
                    <span class="flex items-center gap-10">{{ $g }} <span class="h-3 w-3 rounded-full bg-sun not-italic"></span></span>
                @endforeach
            @endforeach
        </div>
    </div>
@endif

<section id="catalog" class="mx-auto max-w-6xl px-5 py-16 sm:px-8">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <span class="eyebrow">The menu</span>
            <h2 class="section-title mt-4">Pick your <span class="mark">favorite</span></h2>
        </div>
        @include('public.home.parts.search', ['class' => 'flex w-full gap-2 sm:w-auto', 'inputClass' => 'rounded-full sm:w-64'])
    </div>
    @if ($items->isEmpty())
        <div class="mt-8"><x-empty title="Nothing found">Try another search, or ask us in the chat.</x-empty></div>
    @else
        <nav class="mt-6 flex flex-wrap gap-2" aria-label="Menu sections">
            @foreach ($groups->keys() as $i => $g)
                <a href="#{{ $slug($g, $i) }}" class="rounded-full border border-line bg-white px-4 py-1.5 text-sm font-medium hover:border-ink-900">{{ $g }}</a>
            @endforeach
        </nav>
        @foreach ($groups as $g => $rows)
            <h3 id="{{ $slug($g, $loop->index) }}" class="mt-12 scroll-mt-28 font-display text-2xl font-semibold italic">{{ $g }}</h3>
            <div class="mt-5 grid grid-cols-2 gap-4 lg:grid-cols-4">
                @foreach ($rows as $item)
                    <article class="flex flex-col rounded-[22px] border border-line bg-white p-3">
                        <div class="flex aspect-[4/3] items-center justify-center rounded-2xl bg-tile font-display text-6xl text-sun-deep italic" aria-hidden="true">{{ mb_substr($item->name, 0, 1) }}</div>
                        <h4 class="mt-3 px-1 font-display text-lg leading-snug font-semibold">{{ $item->name }}</h4>
                        <p class="mt-0.5 px-1 text-xs text-ink-500">@include('public.home.parts.meta', ['withDescription' => false])</p>
                        <div class="mt-auto flex flex-wrap items-center justify-between gap-2 px-1 pt-3">
                            <span class="font-display text-lg font-semibold">{{ peso($item->price_cents) }}</span>
                            @include('public.home.parts.action', ['btn' => 'btn btn-secondary btn-sm', 'showUnavailable' => true])
                        </div>
                    </article>
                @endforeach
            </div>
        @endforeach
    @endif
</section>

<section class="bg-ink-900 text-white">
    <div class="mx-auto grid max-w-6xl gap-10 px-5 py-16 sm:px-8 lg:grid-cols-[1fr_1.2fr] lg:items-center">
        <div>
            <h2 class="font-display text-4xl font-semibold sm:text-5xl">How it <span class="text-sun italic">works</span></h2>
            <p class="mt-4 max-w-sm text-white/70">No account needed. You see every detail before anything is sent.</p>
        </div>
        <ol class="space-y-3">
            @foreach ($steps as $i => $step)
                <li class="flex items-center gap-5 rounded-2xl border border-white/10 bg-white/5 p-5"><span class="font-display text-3xl text-sun italic">0{{ $i + 1 }}</span><span>{{ $step }}</span></li>
            @endforeach
        </ol>
    </div>
</section>

<section class="mx-auto grid max-w-6xl gap-5 px-5 py-16 sm:px-8 lg:grid-cols-2">
    <div class="card p-6 sm:p-8"><h2 class="font-display text-2xl font-semibold">Opening hours</h2>@include('public.home.parts.hours', ['class' => 'mt-5'])</div>
    <div class="card p-6 sm:p-8"><h2 class="font-display text-2xl font-semibold">Find us</h2>@include('public.home.parts.contact', ['class' => 'mt-5', 'iconClass' => 'text-sun-deep'])</div>
</section>

@if ($knowledge->isNotEmpty())
    <section class="mx-auto grid max-w-6xl gap-10 px-5 pb-8 sm:px-8 lg:grid-cols-[1fr_1.4fr]">
        <h2 class="section-title">Good to <span class="mark">know</span></h2>
        @include('public.home.parts.faq')
    </section>
@endif
