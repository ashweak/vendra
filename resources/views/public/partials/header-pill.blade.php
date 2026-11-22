{{-- Header: floating pill (Classic). Icons on phones, labels from lg. --}}
    <div class="sticky top-0 z-30 px-3 pt-3 sm:px-5">
        <nav class="mx-auto flex max-w-6xl items-center gap-1 rounded-full border border-line bg-white/85 py-1.5 pr-1.5 pl-1.5 shadow-[0_8px_30px_-18px_rgba(23,22,18,.35)] backdrop-blur sm:gap-2 sm:py-2 sm:pr-2 sm:pl-2.5" aria-label="Business">
            <a href="{{ route('public.home', $business) }}" class="flex min-w-0 shrink-0 items-center gap-2.5" aria-label="{{ $business->name }} home">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full font-display text-sm font-bold text-white" style="background: var(--accent)">{{ mb_substr($business->name, 0, 1) }}</span>
                <span class="hidden truncate font-display text-[15px] font-bold sm:inline">{{ $business->name }}</span>
            </a>
            <div class="flex flex-1 items-center justify-center gap-2 text-sm sm:gap-3 lg:gap-1">
                @foreach ($links as [$label, $route, $icon])
                    @php $active = request()->routeIs($route); @endphp
                    <a href="{{ route($route, $business) }}" title="{{ $label }}" aria-label="{{ $label }}" @if($active) aria-current="page" @endif
                       @class(['flex h-10 w-10 shrink-0 items-center justify-center rounded-full whitespace-nowrap lg:h-9 lg:w-auto lg:px-4', 'bg-sun-soft font-semibold text-ink-900' => $active, 'text-ink-600 hover:bg-tile hover:text-ink-900' => ! $active])>
                        <x-icon :name="$icon" class="h-[18px] w-[18px] shrink-0 lg:hidden" /><span class="hidden lg:inline">{{ $label }}</span>
                    </a>
                @endforeach
            </div>
            @if ($business->phone)
                <a href="tel:{{ preg_replace('/[^0-9+]/', '', $business->phone) }}" class="btn btn-primary btn-sm h-10 min-h-0 w-10 shrink-0 px-0 sm:w-auto sm:px-4" aria-label="Call {{ $business->phone }}"><x-icon name="phone" class="h-4 w-4" /><span class="hidden sm:inline">{{ $business->phone }}</span></a>
            @endif
        </nav>
    </div>
