{{-- Header: full-width bar with underlined tabs (Brochure, Panel). --}}
<header class="sticky top-0 z-30 border-b border-line bg-white/90 backdrop-blur">
    <nav class="mx-auto flex h-16 max-w-6xl items-center gap-2 px-3 sm:gap-4 sm:px-8" aria-label="Business">
        <a href="{{ route('public.home', $business) }}" class="flex min-w-0 shrink-0 items-center gap-2.5" aria-label="{{ $business->name }} home">
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg font-display text-sm font-bold text-white" style="background: var(--accent)">{{ mb_substr($business->name, 0, 1) }}</span>
            <span class="hidden max-w-[14rem] truncate font-display text-base font-bold sm:inline">{{ $business->name }}</span>
        </a>
        <div class="flex flex-1 items-center justify-center gap-1 lg:ml-6 lg:justify-start lg:gap-2">
            @include('public.partials.nav-links', ['variant' => 'bar'])
        </div>
        @include('public.partials.call-button', ['style' => 'btn-sun'])
    </nav>
</header>
