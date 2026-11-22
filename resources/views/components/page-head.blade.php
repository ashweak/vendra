@props(['eyebrow' => null, 'title', 'intro' => null])
{{-- Title block for business inner pages, styled to match the active layout (see App\Support\Appearance). --}}
@php
    $biz = request()->route('business');
    $lay = $biz instanceof \App\Models\Business ? \App\Support\Appearance::layout($biz) : \App\Support\Appearance::DEFAULT_LAYOUT;
@endphp
@switch($lay)
    @case('brochure')
        <div {{ $attributes->merge(['class' => 'rounded-[28px] bg-[linear-gradient(180deg,var(--color-sun-soft),transparent)] px-6 pt-10 pb-6 text-center']) }}>
            @if ($eyebrow)<span class="eyebrow justify-center">{{ $eyebrow }}</span>@endif
            <h1 class="section-title mt-4">{{ $title }}</h1>
            @if ($intro)<p class="lead mx-auto mt-3 max-w-xl">{{ $intro }}</p>@endif
        </div>
        @break
    @case('spotlight')
        <div {{ $attributes->merge(['class' => 'relative overflow-hidden']) }}>
            <div class="pointer-events-none absolute -top-10 -right-10 hidden h-40 w-40 rounded-full border-[18px] border-sun-soft sm:block" aria-hidden="true"></div>
            @if ($eyebrow)<span class="inline-flex items-center gap-2 rounded-full bg-white px-4 py-1.5 text-sm font-medium text-ink-700 shadow-sm ring-1 ring-line"><span class="h-2 w-2 rounded-full bg-sun"></span>{{ $eyebrow }}</span>@endif
            <h1 class="mt-5 font-display text-4xl font-extrabold text-ink-900 sm:text-5xl">{{ $title }}</h1>
            @if ($intro)<p class="lead mt-3 max-w-xl">{{ $intro }}</p>@endif
        </div>
        @break
    @case('cafe')
        <div {{ $attributes->merge(['class' => 'rounded-[28px] bg-sun-soft px-6 py-8 sm:px-10']) }}>
            @if ($eyebrow)<span class="eyebrow">{{ $eyebrow }}</span>@endif
            <h1 class="section-title mt-4">{{ $title }}</h1>
            @if ($intro)<p class="lead mt-3 max-w-xl">{{ $intro }}</p>@endif
        </div>
        @break
    @case('panel')
        <div {{ $attributes->merge(['class' => 'border-b border-line pb-5']) }}>
            <p class="text-sm text-ink-500"><a href="{{ $biz ? route('public.home', $biz) : '#' }}" class="hover:text-ink-900">Home</a>@if ($eyebrow) <span aria-hidden="true">›</span> {{ $eyebrow }}@endif</p>
            <h1 class="mt-2 font-display text-3xl font-bold">{{ $title }}</h1>
            @if ($intro)<p class="mt-2 max-w-xl text-ink-600">{{ $intro }}</p>@endif
        </div>
        @break
    @case('shop')
        <div {{ $attributes->merge(['class' => 'rounded-[22px] bg-ink-900 px-6 py-8 text-white sm:px-10']) }}>
            @if ($eyebrow)<span class="inline-flex rounded-full bg-white/10 px-3 py-1 text-xs font-medium">{{ $eyebrow }}</span>@endif
            <h1 class="mt-4 font-display text-3xl font-bold sm:text-4xl">{{ $title }}</h1>
            @if ($intro)<p class="mt-3 max-w-xl text-white/75">{{ $intro }}</p>@endif
        </div>
        @break
    @default
        <div {{ $attributes }}>
            @if ($eyebrow)<span class="eyebrow">{{ $eyebrow }}</span>@endif
            <h1 class="section-title mt-4">{{ $title }}</h1>
            @if ($intro)<p class="lead mt-3 max-w-xl">{{ $intro }}</p>@endif
        </div>
@endswitch
