@extends('layouts.guest')
@section('title', 'Set up your business')
@section('content')
<p class="text-xs font-semibold uppercase tracking-wide text-indigo-700">Step 1</p>
<h1 class="h1">Tell us about your business</h1>
<p class="mt-1 text-sm text-gray-600">Pick the closest category — it pre-fills modules you can change later.</p>
<form method="POST" action="{{ route('onboarding.business') }}" class="mt-6 space-y-4">
    @csrf
    <div><label class="label" for="name">Business name</label><input id="name" name="name" class="input" value="{{ old('name') }}" required maxlength="120"></div>
    <fieldset>
        <legend class="label">Category</legend>
        <div class="space-y-2">
            @foreach ($categories as $key => $c)
                <label class="flex items-start gap-3 rounded-lg border border-gray-200 p-3 text-sm has-[:checked]:border-indigo-500 has-[:checked]:bg-indigo-50">
                    <input type="radio" name="category" value="{{ $key }}" @checked(old('category', 'general') === $key) class="mt-0.5">
                    <span><span class="font-medium">{{ $c['label'] }}</span><span class="block text-xs text-gray-500">Starts with: {{ collect($c['modules'])->map(fn ($m) => config('vendra.modules.'.$m))->implode(', ') }}</span></span>
                </label>
            @endforeach
        </div>
    </fieldset>
    <div><label class="label" for="phone">Phone (optional)</label><input id="phone" name="phone" class="input" value="{{ old('phone') }}"></div>
    <div><label class="label" for="address">Address (optional)</label><input id="address" name="address" class="input" value="{{ old('address') }}"></div>
    <button class="btn btn-primary w-full">Create business</button>
</form>
@endsection
