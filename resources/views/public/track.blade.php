@extends('layouts.public')
@section('title', 'Track · '.$business->name)
@section('content')
<div class="mx-auto max-w-md">
    <span class="eyebrow">Secure access</span>
    <h1 class="section-title mt-4">Track my request</h1>
    <p class="mt-1 text-sm text-ink-600">Enter your reference number and the email you used. We’ll email you a secure one-time link.</p>
    <form method="POST" action="{{ route('public.track.send', $business) }}" class="card mt-6 space-y-4">
        @csrf
        <div><label class="label" for="ref">Reference number</label><input id="ref" name="reference" class="input font-mono uppercase" placeholder="APT-XXXXXX" required maxlength="20" value="{{ old('reference') }}"></div>
        <div><label class="label" for="em">Email</label><input id="em" type="email" name="email" class="input" required value="{{ old('email') }}"></div>
        <button class="btn btn-primary w-full">Email me a link</button>
    </form>
    <p class="mt-3 text-xs text-ink-500">For your privacy, a reference number alone never shows your details.</p>
</div>
@endsection
