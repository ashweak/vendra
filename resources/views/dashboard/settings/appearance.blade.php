@extends('layouts.dashboard')
@section('title', 'Appearance')
@section('heading', 'Settings')
@section('content')
@include('dashboard.settings.nav')
@php $b = $currentBusiness; @endphp
<div class="max-w-5xl">
    <h2 class="h2">Website theme</h2>
    <p class="mt-1 text-sm text-ink-600">Choose how your customer page looks. You can switch anytime — your menu, bookings and chat stay the same. Use “Preview” to see your real page before saving.</p>

    <form method="POST" action="{{ route('dashboard.settings.appearance') }}" class="mt-6">
        @csrf @method('PUT')
        <fieldset>
            <legend class="sr-only">Website theme</legend>
            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($themes as $key => $t)
                    <div class="relative rounded-[22px] border-2 border-line bg-white p-2 transition has-checked:border-ink-900 has-checked:shadow-[0_12px_30px_-18px_rgba(0,0,0,.45)]">
                        <label class="block cursor-pointer">
                            <input type="radio" name="theme" value="{{ $key }}" class="peer sr-only" @checked(old('theme', $b->theme) === $key)>
                            {{-- Mini preview rendered with the theme's own tokens --}}
                            <div data-theme="{{ $key }}" class="overflow-hidden rounded-2xl border border-line bg-paper" aria-hidden="true">
                                <div class="flex items-center justify-between gap-2 border-b border-line bg-white/70 px-3 py-2">
                                    <span class="flex items-center gap-1.5 text-[11px] font-semibold text-ink-900"><span class="h-4 w-4 rounded-full" style="background: {{ $b->accent_color }}"></span>{{ \Illuminate\Support\Str::limit($b->name, 22) }}</span>
                                    <span class="rounded-full bg-ink-900 px-2 py-0.5 text-[9px] font-semibold text-white">Call</span>
                                </div>
                                <div class="dots grid grid-cols-[1.3fr_1fr] gap-2 bg-tile/40 p-3">
                                    <div>
                                        <span class="eyebrow text-[8px]">Menu & prices</span>
                                        <p class="mt-1.5 font-display text-lg leading-tight font-bold text-ink-900">Book in <span class="mark">minutes</span></p>
                                        <span class="btn btn-sun btn-sm mt-2 min-h-0 px-3 py-1 text-[10px]">Book now</span>
                                    </div>
                                    <div class="card space-y-1.5 p-2 text-[10px]">
                                        <p class="flex justify-between font-semibold text-ink-900"><span>Haircut</span><span>₱250</span></p>
                                        <p class="text-ink-500">With Joy · 45 min</p>
                                        <span class="block rounded-full bg-sun-soft px-2 py-0.5 text-center font-semibold text-ink-900">Review</span>
                                    </div>
                                </div>
                            </div>
                            <span class="mt-3 flex items-center justify-between gap-2 px-1">
                                <span class="font-semibold">{{ $t['name'] }} @if ($key === \App\Support\Themes::DEFAULT)<span class="text-xs font-normal text-ink-500">(default)</span>@endif</span>
                                <span class="flex gap-1">@foreach ($t['swatches'] as $c)<span class="h-4 w-4 rounded-full border border-black/10" style="background: {{ $c }}"></span>@endforeach</span>
                            </span>
                            <span class="mt-1 block px-1 text-xs text-ink-600">{{ $t['description'] }}</span>
                            <span class="mt-1 block px-1 text-[11px] text-ink-500">Headlines: {{ $t['font'] }}</span>
                            <span class="absolute top-4 right-4 hidden h-7 w-7 items-center justify-center rounded-full bg-ink-900 text-white peer-checked:flex"><x-icon name="check" class="h-4 w-4" /></span>
                        </label>
                        <a href="{{ route('public.home', [$b, 'theme' => $key]) }}" target="_blank" rel="noopener" class="mt-2 mb-1 ml-1 inline-flex text-xs font-semibold underline decoration-sun decoration-2 underline-offset-4">Preview my page ↗</a>
                    </div>
                @endforeach
            </div>
        </fieldset>
        <div class="mt-6 flex flex-wrap items-center gap-3">
            <button class="btn btn-primary">Save theme</button>
            <a href="{{ route('public.home', $b) }}" target="_blank" rel="noopener" class="btn btn-secondary">View my page</a>
            <span class="text-xs text-ink-500">Your brand color ({{ $b->accent_color }}) and logo letter stay the same in every theme.</span>
        </div>
    </form>
</div>
@endsection
