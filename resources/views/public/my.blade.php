@extends('layouts.public')
@section('title', 'My requests · '.$business->name)
@section('content')
@php $tz = $business->timezone; @endphp
<span class="eyebrow">Verified for this browser</span>
<h1 class="section-title mt-4">My requests</h1>
<p class="mt-1 text-sm text-ink-600">Signed in as {{ $customer->name }} for {{ $business->name }} only. This access expires automatically.</p>

@if ($appointments->isNotEmpty())
<h2 class="h2 mt-8">Appointments</h2>
<div class="mt-3 space-y-3">
@foreach ($appointments as $a)
    <div class="card flex flex-wrap items-center gap-3">
        <div class="min-w-0 flex-1">
            <p class="font-semibold">{{ $a->service_name }} <span class="font-mono text-xs text-ink-500">{{ $a->reference }}</span></p>
            <p class="text-sm text-ink-600">{{ local_time($a->starts_at, $tz, 'D, M j, Y g:i A') }} · {{ peso($a->price_cents) }}</p>
            @if ($a->status === 'pending_approval')<p class="text-xs text-amber-800">Decision by {{ local_time($a->approval_deadline_at, $tz) }}</p>@endif
        </div>
        <x-status :value="$a->status" :label="$a->statusLabel()" />
        @if ($a->isOpen() && $a->starts_at->isFuture())
            <form method="POST" action="{{ route('public.my.appointment.cancel', [$business, $a->reference]) }}" onsubmit="return confirm('Cancel this booking?')">@csrf<button class="btn btn-danger btn-sm">Cancel</button></form>
        @endif
    </div>
@endforeach
</div>
<p class="mt-2 text-xs text-ink-500">Cancellations within {{ $business->setting('cancellation_hours') }} hours of the appointment need staff review — message us in the chat. To reschedule, cancel and book again, or ask staff.</p>
@endif

@if ($orders->isNotEmpty())
<h2 class="h2 mt-8">Orders</h2>
<div class="mt-3 space-y-3">
@foreach ($orders as $o)
    <div class="card flex flex-wrap items-center gap-3">
        <div class="min-w-0 flex-1">
            <p class="font-semibold">{{ $o->reference }} · {{ peso($o->total_cents) }}</p>
            <p class="text-sm text-ink-600">{{ ucfirst($o->fulfillment_method) }} · placed {{ local_time($o->created_at, $tz, 'M j, g:i A') }} · Payment: {{ $o->payment_status === 'verified' ? 'verified' : 'not yet verified' }}</p>
            @if ($o->delivery_fee_cents === null)<p class="text-xs text-amber-800">Delivery fee to be confirmed by staff.</p>@endif
        </div>
        <x-status :value="$o->status" :label="$o->statusLabel()" />
        @if (in_array($o->status, ['pending_approval', 'confirmed'], true))
            <form method="POST" action="{{ route('public.my.order.cancel', [$business, $o->reference]) }}" onsubmit="return confirm('Cancel this order?')">@csrf<button class="btn btn-danger btn-sm">Cancel</button></form>
        @endif
    </div>
@endforeach
</div>
@endif

@if ($quotations->isNotEmpty())
<h2 class="h2 mt-8">Quotations</h2>
<div class="mt-3 space-y-3">
@foreach ($quotations as $q)
    @php $v = $q->currentVersion; @endphp
    <div class="card">
        <div class="flex flex-wrap items-center gap-3">
            <p class="min-w-0 flex-1 font-semibold">{{ $q->reference }}</p>
            <x-status :value="$q->status" :label="$q->statusLabel()" />
        </div>
        <p class="mt-1 text-sm text-ink-600">{{ \Illuminate\Support\Str::limit($q->requirements, 160) }}</p>
        @if (in_array($q->status, ['sent', 'accepted', 'declined', 'expired'], true) && $v && $v->sent_at)
            <div class="mt-3 rounded-lg border border-line p-3 text-sm">
                <p class="font-medium">Version {{ $v->version }} · valid until {{ $v->valid_until->toFormattedDateString() }}</p>
                <table class="table mt-2"><tbody>
                    @foreach ($v->line_items as $l)<tr><td>{{ $l['quantity'] }} × {{ $l['description'] }}</td><td class="text-right">{{ peso($l['line_total_cents']) }}</td></tr>@endforeach
                    <tr><td class="font-semibold">Total</td><td class="text-right font-semibold">{{ peso($v->total_cents) }}</td></tr>
                </tbody></table>
                @if ($v->notes)<p class="mt-2 text-ink-600">{{ $v->notes }}</p>@endif
                @if ($q->status === 'sent')
                    <form method="POST" action="{{ route('public.my.quote.respond', [$business, $q->reference]) }}" class="mt-3 space-y-2">
                        @csrf <input type="hidden" name="version_id" value="{{ $v->id }}">
                        <label class="label" for="note{{ $q->id }}">Message (optional)</label>
                        <textarea id="note{{ $q->id }}" name="note" rows="2" class="input" maxlength="1000"></textarea>
                        <div class="flex flex-wrap gap-2">
                            <button name="action" value="accept" class="btn btn-primary" onclick="return confirm('Accept version {{ $v->version }} for {{ peso($v->total_cents) }}?')">Accept v{{ $v->version }}</button>
                            <button name="action" value="revise" class="btn btn-secondary">Request changes</button>
                            <button name="action" value="decline" class="btn btn-danger">Decline</button>
                        </div>
                        <p class="text-xs text-ink-500">Accepting does not reserve a date unless the quotation says so.</p>
                    </form>
                @endif
            </div>
        @endif
    </div>
@endforeach
</div>
@endif

@if ($requests->isNotEmpty())
<h2 class="h2 mt-8">Service requests</h2>
<div class="mt-3 space-y-3">
@foreach ($requests as $r)
    <div class="card flex flex-wrap items-center gap-3">
        <div class="min-w-0 flex-1">
            <p class="font-semibold">{{ $r->reference }}</p>
            <p class="text-sm text-ink-600">{{ \Illuminate\Support\Str::limit($r->description, 140) }}</p>
        </div>
        <x-status :value="$r->status" :label="$r->statusLabel()" />
    </div>
@endforeach
</div>
@endif
@endsection
