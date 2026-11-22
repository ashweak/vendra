@extends('layouts.dashboard')
@section('title', 'Appearance')
@section('heading', 'Settings')
@section('content')
@include('dashboard.settings.nav')
@php
    $b = $currentBusiness;
    // Pre-select from a preview ("Use this look") when given, otherwise the saved choice.
    $qLayout = request()->query('layout');
    $qPalette = request()->query('palette');
    $selLayout = old('layout', is_string($qLayout) && isset($layouts[$qLayout]) ? $qLayout : \App\Support\Appearance::resolveLayout($b->layout));
    $selPalette = old('palette', is_string($qPalette) && isset($palettes[$qPalette]) ? $qPalette : \App\Support\Appearance::resolvePalette($b->palette));
@endphp
<form method="POST" action="{{ route('dashboard.settings.appearance') }}" class="max-w-5xl" id="appearance-form">
    @csrf @method('PUT')
    <p class="text-sm text-ink-600">Choose a page layout and a color palette — separately, in any combination. You can change both anytime; your menu, bookings and chat stay the same.</p>

    <fieldset class="mt-6">
        <legend class="h2">1. Layout</legend>
        <p class="mt-1 text-sm text-ink-600">The structure of your page, its header and footer, and its headline font. Shown here in your selected colors.</p>
        <div class="mt-4 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($layouts as $key => $l)
                <label class="relative block cursor-pointer rounded-[22px] border-2 border-line bg-white p-2 transition has-checked:border-ink-900 has-checked:shadow-[0_12px_30px_-18px_rgba(0,0,0,.45)]">
                    <input type="radio" name="layout" value="{{ $key }}" class="peer sr-only" @checked($selLayout === $key)>
                    <span data-thumb-palette>@include('dashboard.settings.layout-thumb', ['key' => $key, 'palette' => $selPalette])</span>
                    <span class="mt-3 block px-1 font-semibold">{{ $l['name'] }} @if ($key === \App\Support\Appearance::DEFAULT_LAYOUT)<span class="text-xs font-normal text-ink-500">(default)</span>@endif</span>
                    <span class="mt-1 block px-1 text-xs text-ink-600">{{ $l['description'] }}</span>
                    <span class="mt-1 mb-1 block px-1 text-[11px] text-ink-500">Headlines: {{ $l['font'] }}</span>
                    <span class="absolute -top-2.5 -right-2.5 hidden h-7 w-7 items-center justify-center rounded-full bg-ink-900 text-white ring-4 ring-paper peer-checked:flex"><x-icon name="check" class="h-4 w-4" /></span>
                </label>
            @endforeach
        </div>
        <x-field-error name="layout" />
    </fieldset>

    <fieldset class="mt-10">
        <legend class="h2">2. Colors</legend>
        <p class="mt-1 text-sm text-ink-600">The color mood of your page. Every palette is checked for readable text.</p>
        <div class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-4">
            @foreach ($palettes as $key => $p)
                <label class="relative block cursor-pointer rounded-[20px] border-2 border-line bg-white p-2 transition has-checked:border-ink-900 has-checked:shadow-[0_12px_30px_-18px_rgba(0,0,0,.45)]">
                    <input type="radio" name="palette" value="{{ $key }}" class="peer sr-only" @checked($selPalette === $key)>
                    <span data-palette="{{ $key }}" class="block overflow-hidden rounded-2xl border border-line bg-paper p-3" aria-hidden="true">
                        <span class="flex items-center justify-between"><span class="h-2 w-12 rounded-full bg-ink-800"></span><span class="h-3 w-3 rounded-full bg-sun"></span></span>
                        <span class="mt-2 block h-1.5 w-20 rounded-full bg-ink-300"></span>
                        <span class="mt-3 flex items-center gap-1.5"><span class="rounded-full bg-sun px-2 py-0.5 text-[10px] font-semibold text-ink-900">Book</span><span class="rounded-full bg-ink-900 px-2 py-0.5 text-[10px] font-semibold text-white">Call</span></span>
                        <span class="mt-3 grid grid-cols-3 gap-1"><span class="h-5 rounded-md bg-tile"></span><span class="h-5 rounded-md bg-sun-soft"></span><span class="h-5 rounded-md bg-white ring-1 ring-line"></span></span>
                    </span>
                    <span class="mt-2 flex items-center justify-between gap-2 px-1">
                        <span class="text-sm font-semibold">{{ $p['name'] }}</span>
                        <span class="flex gap-0.5">@foreach ($p['swatches'] as $c)<span class="h-3.5 w-3.5 rounded-full border border-black/10" style="background: {{ $c }}"></span>@endforeach</span>
                    </span>
                    <span class="mt-0.5 mb-1 block px-1 text-[11px] text-ink-600">{{ $p['description'] }}</span>
                    <span class="absolute -top-2.5 -right-2.5 hidden h-7 w-7 items-center justify-center rounded-full bg-ink-900 text-white ring-4 ring-paper peer-checked:flex"><x-icon name="check" class="h-4 w-4" /></span>
                </label>
            @endforeach
        </div>
        <x-field-error name="palette" />
    </fieldset>

    <div class="sticky bottom-0 mt-8 flex flex-wrap items-center gap-3 border-t border-line bg-paper/95 py-4 backdrop-blur">
        <button class="btn btn-primary">Save appearance</button>
        <a id="appearance-preview" href="{{ route('public.home', [$b, 'layout' => $selLayout, 'palette' => $selPalette]) }}" target="_blank" rel="noopener" class="btn btn-secondary">Preview this combination ↗</a>
        <span class="text-xs text-ink-500">Your brand color ({{ $b->accent_color }}) and logo letter stay the same.</span>
    </div>
</form>
<script>
(() => {
    const form = document.getElementById('appearance-form');
    const preview = document.getElementById('appearance-preview');
    const base = @json(route('public.home', $b));
    function update() {
        const layout = form.querySelector('input[name=layout]:checked')?.value;
        const palette = form.querySelector('input[name=palette]:checked')?.value;
        if (!layout || !palette) return;
        preview.href = base + '?layout=' + encodeURIComponent(layout) + '&palette=' + encodeURIComponent(palette);
        // Show the layout sketches in the chosen colors.
        form.querySelectorAll('[data-thumb-palette] [data-palette]').forEach(el => el.setAttribute('data-palette', palette));
    }
    form.addEventListener('change', update);
})();
</script>
@endsection
