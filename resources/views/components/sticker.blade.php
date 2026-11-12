@props(['text' => 'AI · TAGLISH · BOOKINGS · ORDERS · ', 'tone' => 'sun', 'uid' => 'a'])
@php $cls = $attributes->get('class', ''); $positioned = str_contains($cls, 'absolute'); $hidden = preg_match('/(^|\s)hidden(\s|$)/', $cls); @endphp
<span {{ $attributes->merge(['class' => 'sticker '.($hidden ? '' : 'inline-flex ').'items-center justify-center rounded-full text-ink-900 '.($positioned ? '' : 'relative ').($tone === 'white' ? 'bg-white' : 'bg-sun')]) }} aria-hidden="true">
    <svg class="sticker-ring absolute inset-0" viewBox="0 0 120 120">
        <defs><path id="sticker-path-{{ $uid }}" d="M60,60 m-45,0 a45,45 0 1,1 90,0 a45,45 0 1,1 -90,0"/></defs>
        <text font-size="9.6" font-weight="700" letter-spacing="1.7" fill="currentColor" font-family="Inter, sans-serif"><textPath href="#sticker-path-{{ $uid }}">{{ $text }}</textPath></text>
    </svg>
    <span class="flex h-11 w-11 items-center justify-center rounded-full bg-ink-900 text-sun"><x-icon name="spark" class="h-5 w-5" /></span>
</span>
