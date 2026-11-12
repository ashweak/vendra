@extends('layouts.public')
@section('title', 'Request a quote · '.$business->name)
@section('content')
<span class="eyebrow">Step 1 of 2 · Details</span>
<h1 class="section-title mt-4">Request a quotation</h1>
<p class="lead mt-3 max-w-xl">Tell us what you need. The business reviews it and sends a quote you can accept or decline.</p>
<div class="mt-8 grid gap-10 lg:grid-cols-[1fr_20rem]">
<form method="POST" action="{{ route('public.quote.review', $business) }}" enctype="multipart/form-data" class="min-w-0">
    @csrf
    <div class="card space-y-4">
        <div><label class="label" for="req">What do you need?</label><textarea id="req" name="requirements" rows="5" minlength="10" maxlength="3000" required class="input" placeholder="E.g. hair and makeup for 6 people, Dec 14, at our venue in Quezon City.">{{ old('requirements', $prefill['requirements'] ?? '') }}</textarea><x-field-error name="requirements" /></div>
        <div><label class="label" for="att">Photo or PDF (optional, max 5 MB)</label><input id="att" type="file" name="attachment" accept=".jpg,.jpeg,.png,.pdf" class="block text-sm"><p class="hint">Files are stored privately and deleted after 90 days unless needed for an active request.</p><x-field-error name="attachment" /></div>
    </div>
    @include('public.partials.contact', ['data' => []])
    <button class="btn btn-primary mt-6 w-full sm:w-auto">Review request</button>
</form>
@include('public.partials.aside')
</div>
@endsection
