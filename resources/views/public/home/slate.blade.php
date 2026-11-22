{{-- Slate: app-like panel. Sticky business card on the left; stats, catalog table, steps and FAQ on the right. --}}
@php $slug = fn ($g, $i) => 'grp-'.(\Illuminate\Support\Str::slug($g) ?: $i); @endphp
<div class="mx-auto grid max-w-6xl gap-6 px-5 py-8 sm:px-8 lg:grid-cols-[300px_minmax(0,1fr)]">
    <aside class="space-y-4 lg:sticky lg:top-24 lg:self-start">
        <div class="card p-5">
            <div class="flex items-center gap-3">
                <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl font-display text-lg font-bold text-white" style="background: var(--accent)">{{ mb_substr($business->name, 0, 1) }}</span>
                <div class="min-w-0"><p class="truncate font-semibold">{{ $business->name }}</p><p class="truncate text-xs text-ink-500">{{ $categoryLabel }}</p></div>
            </div>
            <p class="mt-4 flex items-center gap-2 rounded-lg bg-tile px-3 py-2 text-sm"><span class="h-2 w-2 rounded-full {{ $openToday ? 'bg-leaf' : 'bg-ink-300' }}"></span>{{ $todayLabel }}</p>
            @include('public.home.parts.contact', ['class' => 'mt-4', 'gap' => 'space-y-3', 'iconClass' => 'text-ink-500'])
            <div class="mt-5 grid gap-2">
                <a href="{{ $primary[1] }}" class="btn btn-primary w-full">{{ $primary[0] }}</a>
                <button type="button" class="btn btn-secondary w-full" data-open-chat>Ask our assistant</button>
            </div>
        </div>
        <div class="card p-5">
            <p class="text-sm font-semibold">This week</p>
            @include('public.home.parts.hours', ['class' => 'mt-3 text-xs', 'todayClass' => 'bg-sun-soft font-semibold'])
        </div>
    </aside>

    <div class="min-w-0 space-y-6">
        <x-flash />
        <header>
            <p class="text-sm text-ink-500">Home <span aria-hidden="true">›</span> {{ $categoryLabel }}</p>
            <h1 class="mt-2 font-display text-3xl font-bold sm:text-4xl">{{ $business->name }}</h1>
            <p class="mt-2 max-w-2xl text-ink-600">{{ $intro }}</p>
        </header>

        <div class="grid gap-4 sm:grid-cols-3">
            <div class="card p-5"><p class="text-sm text-ink-500">{{ $business->moduleEnabled('appointments') ? 'Services' : 'Items' }} available</p><p class="mt-2 font-display text-3xl font-bold">{{ $items->count() }}</p></div>
            <div class="card p-5"><p class="text-sm text-ink-500">Starting price</p><p class="mt-2 font-display text-3xl font-bold">{{ $minPrice !== null ? peso($minPrice) : '—' }}</p></div>
            <div class="card p-5"><p class="text-sm text-ink-500">Staff reply hours</p><p class="mt-2 text-sm font-semibold">{{ $business->setting('response_hours') }}</p></div>
        </div>

        <section id="catalog" class="card overflow-hidden p-0">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-5 py-4">
                <h2 class="text-lg font-semibold">Prices</h2>
                @include('public.home.parts.search', ['class' => 'flex w-full gap-2 sm:w-auto', 'inputClass' => 'sm:w-60', 'searchId' => 'slate'])
            </div>
            @if ($items->isEmpty())
                <div class="p-5"><x-empty title="Nothing found">Try another search, or ask us in the chat.</x-empty></div>
            @else
                <div class="hidden grid-cols-[minmax(0,1.5fr)_minmax(0,1fr)_7rem_6rem] gap-4 border-b border-line bg-tile px-5 py-2.5 text-xs font-semibold tracking-wider text-ink-500 uppercase sm:grid">
                    <span>Item</span><span>Details</span><span class="text-right">Price</span><span></span>
                </div>
                @foreach ($groups as $g => $rows)
                    <p id="{{ $slug($g, $loop->index) }}" class="border-b border-line bg-paper px-5 py-2 text-xs font-semibold tracking-wider text-ink-600 uppercase">{{ $g }}</p>
                    @foreach ($rows as $item)
                        <div class="grid grid-cols-[minmax(0,1fr)_auto] items-center gap-x-4 gap-y-1 border-b border-line px-5 py-3.5 last:border-0 sm:grid-cols-[minmax(0,1.5fr)_minmax(0,1fr)_7rem_6rem]">
                            <span class="col-start-1 row-start-1 font-medium sm:col-start-auto sm:row-start-auto">{{ $item->name }}</span>
                            <span class="col-start-1 row-start-2 text-sm text-ink-500 sm:col-start-auto sm:row-start-auto">@include('public.home.parts.meta', ['withDescription' => false])</span>
                            <span class="col-start-2 row-start-1 text-right font-semibold whitespace-nowrap sm:col-start-auto sm:row-start-auto">{{ peso($item->price_cents) }}</span>
                            <span class="col-start-2 row-start-2 text-right sm:col-start-auto sm:row-start-auto">@include('public.home.parts.action', ['btn' => 'btn btn-secondary btn-sm'])</span>
                        </div>
                    @endforeach
                @endforeach
            @endif
        </section>

        <section class="card p-5">
            <h2 class="text-lg font-semibold">How requests work</h2>
            <ol class="mt-4 grid gap-3 sm:grid-cols-3">
                @foreach ($steps as $i => $step)
                    <li class="rounded-xl border border-line p-4 text-sm"><span class="text-xs font-semibold text-sun-deep">Step {{ $i + 1 }}</span><p class="mt-1">{{ $step }}</p></li>
                @endforeach
            </ol>
        </section>

        @if ($knowledge->isNotEmpty())
            <section class="card p-0">
                <h2 class="border-b border-line px-5 py-4 text-lg font-semibold">Policies & FAQ</h2>
                @include('public.home.parts.faq', ['class' => '', 'itemClass' => 'border-b border-line last:border-0'])
            </section>
        @endif
    </div>
</div>
