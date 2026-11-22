@extends('layouts.dashboard')
@section('title', 'Appearance')
@section('heading', 'Settings')
@section('content')
@include('dashboard.settings.nav')
@php $b = $currentBusiness; @endphp
<div class="max-w-5xl">
    <h2 class="h2">Website theme</h2>
    <p class="mt-1 text-sm text-ink-600">Each theme has its own page layout, colors and fonts. You can switch anytime — your menu, bookings and chat stay the same. Use “Preview” to see your real page before saving.</p>

    <form method="POST" action="{{ route('dashboard.settings.appearance') }}" class="mt-6">
        @csrf @method('PUT')
        <fieldset>
            <legend class="sr-only">Website theme</legend>
            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($themes as $key => $t)
                    <div class="relative rounded-[22px] border-2 border-line bg-white p-2 transition has-checked:border-ink-900 has-checked:shadow-[0_12px_30px_-18px_rgba(0,0,0,.45)]">
                        <label class="block cursor-pointer">
                            <input type="radio" name="theme" value="{{ $key }}" class="peer sr-only" @checked(old('theme', $b->theme) === $key)>
                            @include('dashboard.settings.theme-thumb', ['key' => $key])
                            <span class="mt-3 flex items-center justify-between gap-2 px-1">
                                <span class="font-semibold">{{ $t['name'] }} @if ($key === \App\Support\Themes::DEFAULT)<span class="text-xs font-normal text-ink-500">(default)</span>@endif</span>
                                <span class="flex gap-1">@foreach ($t['swatches'] as $c)<span class="h-4 w-4 rounded-full border border-black/10" style="background: {{ $c }}"></span>@endforeach</span>
                            </span>
                            <span class="mt-1 block px-1 text-xs text-ink-600">{{ $t['description'] }}</span>
                            <span class="mt-1 block px-1 text-[11px] text-ink-500">Headlines: {{ $t['font'] }}</span>
                            <span class="absolute -top-2.5 -right-2.5 hidden h-7 w-7 items-center justify-center rounded-full bg-ink-900 text-white ring-4 ring-paper peer-checked:flex"><x-icon name="check" class="h-4 w-4" /></span>
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
