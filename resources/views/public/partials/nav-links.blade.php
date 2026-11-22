{{-- Business page links. Icons on phones and tablets, text from lg. Params: $variant (bar|split|stacked) --}}
@foreach ($links as [$label, $route, $icon])
    @php $active = request()->routeIs($route); @endphp
    <a href="{{ route($route, $business) }}" title="{{ $label }}" aria-label="{{ $label }}" @if ($active) aria-current="page" @endif
       @class([
           'flex h-10 w-10 shrink-0 items-center justify-center whitespace-nowrap lg:w-auto',
           'rounded-lg lg:h-16 lg:rounded-none lg:border-b-2 lg:px-3' => $variant === 'bar',
           'border-transparent text-ink-600 hover:text-ink-900' => $variant === 'bar' && ! $active,
           'bg-sun-soft font-semibold text-ink-900 lg:border-sun lg:bg-transparent' => $variant === 'bar' && $active,
           'rounded-full lg:h-9 lg:px-4' => $variant === 'split',
           'bg-ink-900 font-semibold text-white' => $variant === 'split' && $active,
           'text-ink-600 hover:bg-white hover:text-ink-900' => $variant === 'split' && ! $active,
           'rounded-full lg:h-9 lg:px-3' => $variant === 'stacked',
           'font-semibold text-ink-900 underline decoration-sun decoration-2 underline-offset-8' => $variant === 'stacked' && $active,
           'text-ink-600 hover:text-ink-900' => $variant === 'stacked' && ! $active,
       ])>
        <x-icon :name="$icon" class="h-[18px] w-[18px] shrink-0 lg:hidden" /><span class="hidden text-sm lg:inline">{{ $label }}</span>
    </a>
@endforeach
