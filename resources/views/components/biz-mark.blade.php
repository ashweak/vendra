@props(['business', 'outline' => false])
{{-- Business logo, or its first letter on the brand color when there's no logo. Size and shape come from the class. --}}
@if ($business->logo_path)
    <img src="{{ $business->logoUrl() }}" alt="" {{ $attributes->merge(['class' => 'shrink-0 bg-white object-cover ring-1 ring-black/5']) }}>
@elseif ($outline)
    <span {{ $attributes->merge(['class' => 'flex shrink-0 items-center justify-center border-2 border-ink-900 font-display font-bold text-ink-900']) }} aria-hidden="true">{{ mb_substr($business->name, 0, 1) }}</span>
@else
    <span {{ $attributes->merge(['class' => 'flex shrink-0 items-center justify-center font-display font-bold text-white']) }} style="background: {{ $business->accent_color }}" aria-hidden="true">{{ mb_substr($business->name, 0, 1) }}</span>
@endif
