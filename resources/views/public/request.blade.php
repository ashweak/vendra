@extends('layouts.public')
@section('title', 'Request a service · '.$business->name)
@section('content')
<span class="eyebrow">Step 1 of 2 · Details</span>
<h1 class="section-title mt-4">Request a service</h1>
<p class="mt-1 text-sm text-ink-600">Describe the job. A preferred date is a preference, not a confirmed booking.</p>
<form method="POST" action="{{ route('public.request.review', $business) }}" class="mt-6 max-w-2xl">
    @csrf
    <div class="card space-y-4">
        <div><label class="label" for="desc">Describe what you need</label><textarea id="desc" name="description" rows="5" minlength="10" maxlength="3000" required class="input">{{ old('description', $prefill['description'] ?? '') }}</textarea><x-field-error name="description" /></div>
        <div><label class="label" for="loc">Location (barangay / city)</label><input id="loc" name="location" maxlength="255" class="input" value="{{ old('location', $prefill['location'] ?? '') }}"></div>
        <div><label class="label" for="pd">Preferred date (optional)</label><input id="pd" type="date" name="preferred_date" min="{{ now($business->timezone)->toDateString() }}" class="input" value="{{ old('preferred_date', $prefill['preferred_date'] ?? '') }}"><x-field-error name="preferred_date" /></div>
    </div>
    @include('public.partials.contact', ['data' => [], 'phoneRequired' => true])
    <button class="btn btn-primary mt-6 w-full sm:w-auto">Review request</button>
</form>
@endsection
