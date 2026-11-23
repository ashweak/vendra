@extends('layouts.public')
@section('title', __('Track').' · '.$business->name)
@section('content')
<div class="mx-auto max-w-md">
    <x-page-head :eyebrow="__('Secure access')" title="{{ __('Track my request') }}" :intro="__('Enter your reference number and the email you used. We’ll email you a secure one-time link.')" />
    <form method="POST" action="{{ route('public.track.send', $business) }}" class="card mt-6 space-y-4">
        @csrf
        <div><label class="label" for="ref">{{ __('Reference number') }}</label><input id="ref" name="reference" class="input font-mono uppercase" placeholder="{{ __('APT-XXXXXX') }}" required maxlength="20" value="{{ old('reference') }}"></div>
        <div><label class="label" for="em">{{ __('Email') }}</label><input id="em" type="email" name="email" class="input" required value="{{ old('email') }}"></div>
        <x-human-check />
        <button class="btn btn-primary w-full">{{ __('Email me a link') }}</button>
    </form>
    <p class="mt-3 text-xs text-ink-500">{{ __('For your privacy, a reference number alone never shows your details.') }}</p>
</div>
@endsection
