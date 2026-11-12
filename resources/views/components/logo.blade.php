@props(['dark' => false])
<span class="inline-flex items-center gap-2 font-display text-xl font-bold tracking-tight {{ $dark ? 'text-white' : 'text-ink-900' }}">
    <svg width="26" height="26" viewBox="0 0 32 32" aria-hidden="true">
        <path d="M16 3a13 13 0 0 1 13 13 13 13 0 0 1-13 13H5.5a1.5 1.5 0 0 1-1.2-2.4L6.6 24A13 13 0 0 1 16 3z" fill="#ffc83d"/>
        <path d="M10.5 12.5l5.5 8 5.5-8" fill="none" stroke="#171612" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
    <span>vendra</span>
</span>
