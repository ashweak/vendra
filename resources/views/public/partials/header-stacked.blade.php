{{-- Header: info strip + main row (Café, Shop). Shop adds a search box. --}}
<header class="sticky top-0 z-30">
    <div class="bg-ink-900 text-[12px] text-white">
        <div class="mx-auto flex h-8 max-w-6xl items-center justify-center gap-3 px-4 sm:justify-between sm:px-8">
            <span class="flex items-center gap-2 truncate"><span class="h-1.5 w-1.5 shrink-0 rounded-full {{ str_starts_with($statusLine, 'Open') ? 'bg-sun' : 'bg-white/40' }}"></span>{{ $statusLine }}</span>
            <span class="hidden truncate text-white/70 sm:inline">{{ $business->address ?: 'Ask us anything in English, Tagalog or Taglish' }}</span>
        </div>
    </div>
    <div class="border-b border-line bg-paper/95 backdrop-blur">
        <nav class="mx-auto flex h-16 max-w-6xl items-center gap-2 px-3 sm:gap-4 sm:px-8" aria-label="Business">
            <a href="{{ route('public.home', $business) }}" class="flex min-w-0 shrink-0 items-center gap-2.5" aria-label="{{ $business->name }} home">
                <x-biz-mark :business="$business" outline class="h-9 w-9 rounded-full text-sm" />
                <span class="hidden max-w-[13rem] truncate font-display text-lg font-bold sm:inline">{{ $business->name }}</span>
            </a>
            <div class="flex flex-1 items-center justify-center gap-1 lg:gap-0">
                @include('public.partials.nav-links', ['variant' => 'stacked'])
            </div>
            @if ($activeLayout === 'shop')
                <form method="GET" action="{{ route('public.home', $business) }}#catalog" class="hidden items-center xl:flex" role="search">
                    <label for="q-header" class="sr-only">Search products</label>
                    <input id="q-header" name="q" value="{{ request()->query('q') }}" class="input h-10 w-48 rounded-full py-1.5" placeholder="Search products…">
                </form>
            @endif
            @include('public.partials.call-button', ['style' => 'btn-primary'])
        </nav>
    </div>
</header>
