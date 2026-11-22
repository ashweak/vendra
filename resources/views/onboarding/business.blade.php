@extends('layouts.guest')
@section('title', 'Set up your business')
@section('content')
<p class="text-xs font-semibold uppercase tracking-wide text-ink-700">Step 1</p>
<h1 class="font-display text-3xl font-bold tracking-tight">Tell us about your business</h1>
<p class="mt-1 text-sm text-ink-600">Pick the closest category — it pre-fills modules you can change later.</p>
<form method="POST" action="{{ route('onboarding.business') }}" class="mt-6 space-y-4">
    @csrf
    <div><label class="label" for="name">Business name</label><input id="name" name="name" class="input" value="{{ old('name') }}" required maxlength="120"></div>
    <fieldset>
        <legend class="label">Category</legend>
        <div class="space-y-2">
            @foreach ($categories as $key => $c)
                <label class="flex items-start gap-3 rounded-lg border border-line p-3 text-sm has-[:checked]:border-ink-500 has-[:checked]:bg-ink-50">
                    <input type="radio" name="category" value="{{ $key }}" @checked(old('category', 'general') === $key) class="mt-0.5">
                    <span><span class="font-medium">{{ $c['label'] }}</span><span class="block text-xs text-ink-500">Starts with: {{ collect($c['modules'])->map(fn ($m) => config('vendra.modules.'.$m))->implode(', ') }}</span></span>
                </label>
            @endforeach
        </div>
    </fieldset>
    <fieldset>
        <legend class="label">Website look</legend>
        <p class="hint -mt-1 mb-2">Pick a theme for your customer page. You can change it anytime in Settings → Appearance.</p>
        <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
            @foreach (\App\Support\Themes::ALL as $key => $t)
                <label class="flex cursor-pointer flex-col gap-2 rounded-lg border border-line p-2.5 text-sm has-[:checked]:border-ink-900 has-[:checked]:bg-ink-50">
                    <span class="flex items-center gap-2"><input type="radio" name="theme" value="{{ $key }}" @checked(old('theme', \App\Support\Themes::DEFAULT) === $key)><span class="font-medium">{{ $t['name'] }}</span></span>
                    <span class="flex gap-1" aria-hidden="true">@foreach ($t['swatches'] as $c)<span class="h-5 flex-1 rounded border border-black/10" style="background: {{ $c }}"></span>@endforeach</span>
                </label>
            @endforeach
        </div>
        <x-field-error name="theme" />
    </fieldset>
    <div><label class="label" for="phone">Phone (optional)</label><input id="phone" name="phone" class="input" value="{{ old('phone') }}"></div>
    <div><label class="label" for="address">Address (optional)</label><input id="address" name="address" class="input" value="{{ old('address') }}"></div>
    <button class="btn btn-primary w-full">Create business</button>
</form>
@endsection
