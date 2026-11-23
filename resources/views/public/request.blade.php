@extends('layouts.public')
@section('title', __('Request a service').' · '.$business->name)
@section('content')
<x-page-head :eyebrow="__('Step 1 of 2 · Details')" title="{{ __('Request a service') }}" :intro="__('Describe the job. A preferred date is a preference, not a confirmed booking.')" />
<div class="mt-8 grid gap-10 lg:grid-cols-[1fr_20rem]">
<form method="POST" action="{{ route('public.request.review', $business) }}" class="min-w-0">
    @csrf
    <div class="card space-y-4">
        <div><label class="label" for="desc">{{ __('Describe what you need') }}</label><textarea id="desc" name="description" rows="5" minlength="10" maxlength="3000" required class="input">{{ old('description', $prefill['description'] ?? '') }}</textarea><x-field-error name="description" /></div>
        <div><label class="label" for="loc">{{ __('Location (barangay / city)') }}</label><input id="loc" name="location" maxlength="255" class="input" value="{{ old('location', $prefill['location'] ?? '') }}"></div>
        <div><label class="label" for="pd">{{ __('Preferred date (optional)') }}</label><input id="pd" type="date" name="preferred_date" min="{{ now($business->timezone)->toDateString() }}" class="input" value="{{ old('preferred_date', $prefill['preferred_date'] ?? '') }}"><x-field-error name="preferred_date" /></div>
    </div>
    @include('public.partials.contact', ['data' => [], 'phoneRequired' => true])
    <x-human-check />
    <button class="btn btn-primary mt-6 w-full sm:w-auto">{{ __('Review request') }}</button>
</form>
@include('public.partials.aside')
</div>
@endsection
