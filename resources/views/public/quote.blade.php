@extends('layouts.public')
@section('title', 'Request a quote · '.$business->name)
@section('content')
<h1 class="h1">Request a quotation</h1>
<p class="mt-1 text-sm text-gray-600">Tell us what you need. The business reviews it and sends a quote you can accept or decline.</p>
<form method="POST" action="{{ route('public.quote.review', $business) }}" enctype="multipart/form-data" class="mt-6 max-w-2xl">
    @csrf
    <div class="card space-y-4">
        <div><label class="label" for="req">What do you need?</label><textarea id="req" name="requirements" rows="5" minlength="10" maxlength="3000" required class="input" placeholder="E.g. hair and makeup for 6 people, Dec 14, at our venue in Quezon City.">{{ old('requirements', $prefill['requirements'] ?? '') }}</textarea><x-field-error name="requirements" /></div>
        <div><label class="label" for="att">Photo or PDF (optional, max 5 MB)</label><input id="att" type="file" name="attachment" accept=".jpg,.jpeg,.png,.pdf" class="block text-sm"><p class="hint">Files are stored privately and deleted after 90 days unless needed for an active request.</p><x-field-error name="attachment" /></div>
    </div>
    @include('public.partials.contact', ['data' => []])
    <button class="btn btn-primary mt-6 w-full sm:w-auto" style="background: var(--accent)">Review request</button>
</form>
@endsection
