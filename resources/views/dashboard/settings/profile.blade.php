@extends('layouts.dashboard')
@section('title', 'Settings')
@section('heading', 'Settings')
@section('content')
@include('dashboard.settings.nav')
@php $b = $currentBusiness; @endphp
<form method="POST" action="{{ route('dashboard.settings.profile') }}" class="card max-w-2xl space-y-4">
    @csrf @method('PUT')
    <div><label class="label" for="name">Business name</label><input id="name" name="name" class="input" required value="{{ old('name', $b->name) }}"></div>
    <div><label class="label" for="desc">Short description</label><textarea id="desc" name="description" rows="3" class="input">{{ old('description', $b->description) }}</textarea></div>
    <div class="grid gap-4 sm:grid-cols-2">
        <div><label class="label" for="phone">Phone</label><input id="phone" name="phone" class="input" value="{{ old('phone', $b->phone) }}"></div>
        <div><label class="label" for="email">Email</label><input id="email" type="email" name="email" class="input" value="{{ old('email', $b->email) }}"></div>
        <div class="sm:col-span-2"><label class="label" for="address">Address</label><input id="address" name="address" class="input" value="{{ old('address', $b->address) }}"></div>
        <div><label class="label" for="accent">Brand color</label><input id="accent" type="color" name="accent_color" class="h-11 w-24 rounded border border-ink-300" value="{{ old('accent_color', $b->accent_color) }}"><p class="hint">Choose a dark enough color for white text.</p></div>
        <div><p class="label">Customer page</p><a class="link text-sm break-all" href="{{ route('public.home', $b) }}" target="_blank">{{ route('public.home', $b) }}</a></div>
    </div>
    <button class="btn btn-primary">Save</button>
</form>
@endsection
