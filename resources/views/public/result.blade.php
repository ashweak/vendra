@extends('layouts.public')
@section('title', $record->reference.' · '.$business->name)
@section('content')
@php $tz = $business->timezone; @endphp
<div class="mx-auto max-w-xl text-center">
    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-ink-900 text-2xl text-white" aria-hidden="true">✓</div>
    <h1 class="section-title mt-5">Request received</h1>
    <p class="mt-1 text-ink-600">Reference <span class="font-mono font-semibold">{{ $record->reference }}</span></p>
    <div class="mt-3"><x-status :value="$record->status" :label="$record->statusLabel()" /></div>
</div>
<div class="card mx-auto mt-6 max-w-xl text-sm">
    @if ($type === 'appointment')
        <p class="font-semibold">{{ $record->service_name }}</p>
        <p>{{ local_time($record->starts_at, $tz, 'l, F j, Y \a\t g:i A') }}</p>
        <p>{{ peso($record->price_cents) }}</p>
        @if ($record->status === 'pending_approval')
            <p class="mt-3 rounded-lg bg-amber-50 p-3 text-amber-900">This booking is waiting for approval. The business will decide by <strong>{{ local_time($record->approval_deadline_at, $tz) }}</strong>. If not approved by then, it expires and the time is released.</p>
        @else
            <p class="mt-3 rounded-lg bg-green-50 p-3 text-green-900">Your booking is confirmed.</p>
        @endif
    @elseif ($type === 'order')
        <p>Total so far: <strong>{{ peso($record->total_cents) }}</strong> @if($record->delivery_fee_cents === null) <span class="text-ink-500">(delivery fee to be confirmed)</span>@endif</p>
        <p class="mt-2">Payment: not collected online. The business will tell you how to pay and will verify it.</p>
        <p class="mt-2 text-ink-600">Items are reserved until {{ local_time($record->reservation_expires_at, $tz) }}.</p>
    @elseif ($type === 'quotation')
        <p>The business will review your request and send a quotation. You can accept or decline it from “Track my request”.</p>
    @else
        <p>The business will review your request and contact you. Your preferred date is not a booking yet.</p>
    @endif
</div>
<div class="mt-6 flex flex-wrap justify-center gap-2">
    <a href="{{ route('public.my', $business) }}" class="btn btn-secondary">View my requests</a>
    <a href="{{ route('public.home', $business) }}" class="btn btn-secondary">Back to {{ $business->name }}</a>
</div>
<p class="mt-4 text-center text-xs text-ink-500">Later, use “Track my request” with your reference and email to get a secure link.</p>
@endsection
