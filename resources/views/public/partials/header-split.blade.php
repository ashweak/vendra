{{-- Header: open layout, name left and links right, no bar (Spotlight). --}}
<header class="sticky top-0 z-30 bg-paper/85 backdrop-blur">
    <nav class="mx-auto flex h-[4.5rem] max-w-6xl items-center gap-2 px-3 sm:px-8" aria-label="{{ __('Business') }}">
        <a href="{{ route('public.home', $business) }}" class="flex min-w-0 shrink-0 items-center gap-3" aria-label="{{ __(':business home', ['business' => $business->name]) }}">
            <x-biz-mark :business="$business" class="h-10 w-10 rotate-6 rounded-xl text-sm shadow-[0_8px_18px_-8px_rgba(0,0,0,.35)]" />
            <span class="hidden max-w-[14rem] truncate text-base font-bold text-ink-900 sm:inline">{{ $business->name }}</span>
        </a>
        <div class="flex flex-1 items-center justify-center gap-1 lg:justify-end">
            @include('public.partials.nav-links', ['variant' => 'split'])
        </div>
        @include('public.partials.lang-switch', ['class' => 'hidden sm:flex'])
        @include('public.partials.call-button', ['style' => 'btn-sun'])
    </nav>
</header>
