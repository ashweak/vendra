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
        <div><label class="label" for="phone">Phone</label><input id="phone" name="phone" class="input" value="{{ old('phone', $b->phone) }}" autocomplete="tel"><x-field-error name="phone" /></div>
        <div><label class="label" for="email">Email</label><input id="email" type="email" name="email" class="input" value="{{ old('email', $b->email) }}"><x-field-error name="email" /></div>
        <div class="sm:col-span-2"><label class="label" for="address">Physical address</label><input id="address" name="address" class="input" maxlength="255" value="{{ old('address', $b->address) }}" placeholder="Street, barangay, city"><x-field-error name="address" /></div>
        <div><label class="label" for="legal_name">Registered business name <span class="font-normal text-ink-500">(if different)</span></label><input id="legal_name" name="legal_name" class="input" maxlength="190" value="{{ old('legal_name', $b->legal_name) }}"></div>
        <div><label class="label" for="registration_no">DTI or SEC registration no. <span class="font-normal text-ink-500">(optional)</span></label><input id="registration_no" name="registration_no" class="input" maxlength="60" value="{{ old('registration_no', $b->registration_no) }}"></div>
        <div class="sm:col-span-2"><label class="label" for="privacy_contact">Privacy contact email <span class="font-normal text-ink-500">(defaults to the business email)</span></label><input id="privacy_contact" type="email" name="privacy_contact" class="input" maxlength="190" value="{{ old('privacy_contact', $b->privacy_contact) }}"><p class="hint">Customers write here to ask for a copy of their data or to have it corrected or deleted.</p><x-field-error name="privacy_contact" /></div>
        <p class="hint sm:col-span-2">The Internet Transactions Act asks online sellers to show their business name, physical address and contact details. Your page shows all of these. Not legal advice — check with DTI or a lawyer if unsure.</p>
        <div><label class="label" for="accent">Brand color</label><input id="accent" type="color" name="accent_color" class="h-11 w-24 rounded border border-ink-300" value="{{ old('accent_color', $b->accent_color) }}"><p class="hint">Choose a dark enough color for white text.</p></div>
        <div><p class="label">Customer page</p><a class="link text-sm break-all" href="{{ route('public.home', $b) }}" target="_blank">{{ route('public.home', $b) }}</a></div>
    </div>
    <button class="btn btn-primary">Save</button>
</form>
@endsection
