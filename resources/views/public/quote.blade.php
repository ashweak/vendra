@extends('layouts.public')
@section('title', __('Request a quote').' · '.$business->name)
@section('content')
<x-page-head :eyebrow="__('Step 1 of 2 · Details')" title="{{ __('Request a quotation') }}" :intro="__('Tell us what you need. The business reviews it and sends a quote you can accept or decline.')" />
<div class="mt-8 grid gap-10 lg:grid-cols-[1fr_20rem]">
<form method="POST" action="{{ route('public.quote.review', $business) }}" enctype="multipart/form-data" class="min-w-0">
    @csrf
    <div class="card space-y-4">
        <div><label class="label" for="req">{{ __('What do you need?') }}</label><textarea id="req" name="requirements" rows="5" minlength="10" maxlength="3000" required class="input" placeholder="{{ __('E.g. hair and makeup for 6 people, Dec 14, at our venue in Quezon City.') }}">{{ old('requirements', $prefill['requirements'] ?? '') }}</textarea><x-field-error name="requirements" /></div>
        <div><label class="label" for="att">{{ __('Photo or PDF (optional, max 5 MB)') }}</label><input id="att" type="file" name="attachment" accept=".jpg,.jpeg,.png,.pdf" class="block text-sm"><p class="hint">{{ __('Files are stored privately and deleted after 90 days unless needed for an active request.') }}</p><x-field-error name="attachment" /></div>
    </div>
    @include('public.partials.contact', ['data' => []])
    <x-human-check />
    <button class="btn btn-primary mt-6 w-full sm:w-auto">{{ __('Review request') }}</button>
</form>
@include('public.partials.aside')
</div>
@endsection
