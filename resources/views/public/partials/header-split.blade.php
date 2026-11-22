{{-- Header: open layout, name left and links right, no bar (Spotlight). --}}
<header class="sticky top-0 z-30 bg-paper/85 backdrop-blur">
    <nav class="mx-auto flex h-[4.5rem] max-w-6xl items-center gap-2 px-3 sm:px-8" aria-label="Business">
        <a href="{{ route('public.home', $business) }}" class="flex min-w-0 shrink-0 items-center gap-3" aria-label="{{ $business->name }} home">
            <span class="flex h-10 w-10 shrink-0 rotate-6 items-center justify-center rounded-xl font-display text-sm font-bold text-white shadow-[0_8px_18px_-8px_var(--accent)]" style="background: var(--accent)"><span class="-rotate-6">{{ mb_substr($business->name, 0, 1) }}</span></span>
            <span class="hidden max-w-[14rem] truncate text-base font-bold text-ink-900 sm:inline">{{ $business->name }}</span>
        </a>
        <div class="flex flex-1 items-center justify-center gap-1 lg:justify-end">
            @include('public.partials.nav-links', ['variant' => 'split'])
        </div>
        @include('public.partials.call-button', ['style' => 'btn-sun'])
    </nav>
</header>
