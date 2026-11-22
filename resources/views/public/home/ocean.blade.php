{{-- Ocean: headline with stats and a layered visual, swipeable "popular" row, ring diagram for steps, info cards, FAQ grid. --}}
<section class="mx-auto max-w-6xl px-5 pt-6 pb-16 sm:px-8 lg:pt-12">
    <x-flash />
    <div class="grid items-center gap-12 lg:grid-cols-[1.05fr_1fr]">
        <div>
            <span class="inline-flex items-center gap-2 rounded-full bg-white px-4 py-1.5 text-sm font-medium text-ink-700 shadow-sm ring-1 ring-line"><span class="h-2 w-2 rounded-full {{ $openToday ? 'bg-leaf' : 'bg-ink-300' }}"></span>{{ $todayLabel }}</span>
            <h1 class="mt-6 font-display text-[2.5rem] leading-[1.05] font-extrabold text-ink-900 sm:text-6xl">{{ $business->name }}</h1>
            <p class="lead mt-5 max-w-lg">{{ $intro }}</p>
            <div class="mt-8 flex flex-wrap items-center gap-3">
                <a href="{{ $primary[1] }}" class="btn btn-primary px-6">{{ $primary[0] }}</a>
                <button type="button" class="btn-play" data-open-chat><span class="btn-play-icon"><x-icon name="chat" class="h-3.5 w-3.5" /></span> Ask our assistant</button>
            </div>
            <dl class="mt-10 grid max-w-md grid-cols-3 gap-4 border-t border-line pt-6">
                <div><dt class="text-xs text-ink-500">{{ $business->moduleEnabled('appointments') ? 'Services' : 'Items' }}</dt><dd class="font-display text-2xl font-extrabold">{{ $items->count() }}</dd></div>
                <div><dt class="text-xs text-ink-500">Starts at</dt><dd class="font-display text-2xl font-extrabold">{{ $minPrice !== null ? peso($minPrice) : '—' }}</dd></div>
                <div><dt class="text-xs text-ink-500">Open days</dt><dd class="font-display text-2xl font-extrabold">{{ $openDays }}/7</dd></div>
            </dl>
        </div>
        <div class="relative mx-auto w-full max-w-md" aria-hidden="true">
            <div class="relative overflow-hidden rounded-[40px] bg-sun px-6 py-10 sm:px-10">
                <div class="pointer-events-none absolute -top-24 -right-24 h-72 w-72 rounded-full border-[28px] border-white/25"></div>
                <div class="pointer-events-none absolute -bottom-20 -left-16 h-56 w-56 rounded-full border-[22px] border-ink-900/10"></div>
                <div class="relative">@include('public.home.parts.phone')</div>
            </div>
            <div class="absolute -top-5 left-6 hidden items-center gap-2 rounded-2xl bg-white px-4 py-3 text-sm font-semibold shadow-[0_18px_40px_-20px_rgba(0,0,0,.4)] sm:flex"><span class="flex h-7 w-7 items-center justify-center rounded-lg bg-sun-soft"><x-icon name="check" class="h-4 w-4" /></span>Request confirmed</div>
            <div class="absolute right-6 -bottom-5 hidden items-center gap-2 rounded-2xl bg-white px-4 py-3 text-sm font-semibold shadow-[0_18px_40px_-20px_rgba(0,0,0,.4)] sm:flex"><span class="flex h-7 w-7 items-center justify-center rounded-lg bg-sun-soft"><x-icon name="users" class="h-4 w-4" /></span>Staff online</div>
        </div>
    </div>
</section>

<section id="catalog" class="border-y border-line bg-white py-16">
    <div class="mx-auto max-w-6xl px-5 sm:px-8">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h2 class="section-title">Popular right now</h2>
                <p class="mt-2 text-sm text-ink-600 sm:hidden">Swipe to see more →</p>
            </div>
            @include('public.home.parts.search', ['class' => 'flex w-full gap-2 sm:w-auto', 'inputClass' => 'rounded-xl sm:w-64', 'buttonClass' => 'btn btn-primary px-3.5'])
        </div>
        @if ($items->isEmpty())
            <div class="mt-8"><x-empty title="Nothing found">Try another search, or ask us in the chat.</x-empty></div>
        @else
            <div class="-mx-5 mt-8 flex snap-x snap-mandatory gap-4 overflow-x-auto px-5 pb-4 sm:-mx-8 sm:px-8" tabindex="0" aria-label="Catalog">
                @foreach ($items as $item)
                    <article class="flex w-64 shrink-0 snap-start flex-col rounded-3xl border border-line bg-paper p-3">
                        <div class="relative flex h-32 items-center justify-center rounded-2xl bg-tile">
                            <span class="font-display text-6xl font-extrabold text-sun-deep" aria-hidden="true">{{ mb_substr($item->name, 0, 1) }}</span>
                            <span class="absolute top-3 left-3 rounded-full bg-white px-2.5 py-0.5 text-[11px] font-semibold text-ink-700">{{ $item->category ?: ($item->type === 'service' ? 'Service' : 'Product') }}</span>
                        </div>
                        <h3 class="mt-4 px-1 font-semibold leading-snug">{{ $item->name }}</h3>
                        <p class="mt-1 px-1 text-xs text-ink-500">@include('public.home.parts.meta', ['withDescription' => false])</p>
                        <div class="mt-auto flex items-center justify-between gap-2 px-1 pt-4">
                            <span class="font-display text-xl font-extrabold">{{ peso($item->price_cents) }}</span>
                            @include('public.home.parts.action', ['btn' => 'btn btn-primary btn-sm shrink-0', 'showUnavailable' => true])
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </div>
</section>

<section class="mx-auto grid max-w-6xl items-center gap-12 px-5 py-16 sm:px-8 lg:grid-cols-2">
    <div class="relative mx-auto flex h-72 w-72 items-center justify-center sm:h-80 sm:w-80" aria-hidden="true">
        <div class="absolute inset-0 rounded-full border-[22px] border-sun-soft"></div>
        <div class="absolute inset-10 rounded-full border-[18px] border-sun"></div>
        <div class="absolute inset-[5.5rem] flex items-center justify-center rounded-full bg-ink-900 text-white"><x-icon name="chat" class="h-8 w-8" /></div>
        @foreach ([['top-2 left-1/2 -translate-x-1/2', 1], ['bottom-10 left-2', 2], ['bottom-10 right-2', 3]] as [$pos, $n])
            <span class="absolute {{ $pos }} flex h-11 w-11 items-center justify-center rounded-full bg-white font-display text-lg font-extrabold shadow-[0_10px_24px_-12px_rgba(0,0,0,.4)]">{{ $n }}</span>
        @endforeach
    </div>
    <div>
        <h2 class="section-title">How it works</h2>
        <p class="lead mt-3">Three steps, and you stay in control at each one.</p>
        @include('public.home.parts.steps', ['class' => 'mt-8', 'gap' => 'space-y-5 text-base', 'numClass' => 'bg-sun text-ink-900'])
    </div>
</section>

<section class="bg-tile">
    <div class="mx-auto grid max-w-6xl gap-5 px-5 py-16 sm:px-8 lg:grid-cols-3">
        <div class="card p-6"><h2 class="font-display text-xl font-extrabold">Opening hours</h2>@include('public.home.parts.hours', ['class' => 'mt-4'])</div>
        <div class="card p-6"><h2 class="font-display text-xl font-extrabold">Contact</h2>@include('public.home.parts.contact', ['class' => 'mt-5', 'iconClass' => 'text-sun-deep'])</div>
        <div class="flex flex-col rounded-[26px] bg-ink-900 p-6 text-white">
            <h2 class="font-display text-xl font-extrabold">Need help now?</h2>
            <p class="mt-2 text-sm text-white/75">Ask in English, Tagalog or Taglish. Our auto-assistant answers right away, and staff reply {{ $business->setting('response_hours') }}.</p>
            <button type="button" class="btn btn-sun mt-auto w-full" data-open-chat>Chat with us</button>
        </div>
    </div>
</section>

@if ($knowledge->isNotEmpty())
    <section class="mx-auto max-w-6xl px-5 pt-16 pb-8 sm:px-8">
        <h2 class="section-title">Good to know</h2>
        <div class="mt-8 grid gap-4 sm:grid-cols-2">
            @foreach ($knowledge as $k)
                <article class="card p-6">
                    <h3 class="font-display text-lg font-extrabold">{{ $k->title }}</h3>
                    <p class="mt-2 text-sm leading-relaxed whitespace-pre-line text-ink-600">{{ $k->body }}</p>
                </article>
            @endforeach
        </div>
    </section>
@endif
