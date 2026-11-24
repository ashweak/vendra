@extends('layouts.public')
@section('robots', 'noindex')
@section('title', __('My requests').' · '.$business->name)
@section('content')
@php $tz = $business->timezone; @endphp
<x-page-head :eyebrow="__('Verified for this browser')" title="{{ __('My requests') }}" />
<p class="mt-1 text-sm text-ink-600">{{ __('Signed in as :name for :business only. This access expires automatically.', ['name' => $customer->name, 'business' => $business->name]) }}</p>

@if ($appointments->isNotEmpty())
<h2 class="h2 mt-8">{{ __('Appointments') }}</h2>
<div class="mt-3 space-y-3">
@foreach ($appointments as $a)
    <div class="card flex flex-wrap items-center gap-3">
        <div class="min-w-0 flex-1">
            <p class="font-semibold">{{ $a->service_name }} <span class="font-mono text-xs text-ink-500">{{ $a->reference }}</span></p>
            <p class="text-sm text-ink-600">{{ local_time($a->starts_at, $tz, 'D, M j, Y g:i A') }} · {{ peso($a->price_cents) }}</p>
            @if ($a->status === 'pending_approval')<p class="text-xs text-amber-800">{{ __('Decision by :time', ['time' => local_time($a->approval_deadline_at, $tz)]) }}</p>@endif
        </div>
        <x-status :value="$a->status" :label="$a->statusLabel()" />
        @if ($a->isOpen() && $a->starts_at->isFuture())
            <a href="{{ route('public.my.appointment.ics', [$business, $a->reference]) }}" class="btn btn-secondary btn-sm">{{ __('Add to calendar') }}</a>
            <form method="POST" action="{{ route('public.my.appointment.cancel', [$business, $a->reference]) }}" onsubmit="return confirm({{ \Illuminate\Support\Js::from(__('Cancel this booking?')) }})">@csrf<button class="btn btn-danger btn-sm">{{ __('Cancel') }}</button></form>
        @endif
    </div>
@endforeach
</div>
<p class="mt-2 text-xs text-ink-500">{{ __('Cancellations within :hours hours of the appointment need staff review — message us in the chat. To reschedule, cancel and book again, or ask staff.', ['hours' => $business->setting('cancellation_hours')]) }}</p>
@endif

@if ($orders->isNotEmpty())
<h2 class="h2 mt-8">{{ __('Orders') }}</h2>
<div class="mt-3 space-y-3">
@foreach ($orders as $o)
    <div class="card flex flex-wrap items-center gap-3">
        <div class="min-w-0 flex-1">
            <p class="font-semibold">{{ $o->reference }} · {{ peso($o->total_cents) }}</p>
            <p class="text-sm text-ink-600">{{ $o->fulfillment_method === 'delivery' ? __('Delivery') : __('Pickup') }} · {{ __('placed :time', ['time' => local_time($o->created_at, $tz, 'M j, g:i A')]) }} · {{ $o->payment_status === 'verified' ? __('Payment: verified') : __('Payment: not yet verified') }}</p>
            @if ($o->delivery_fee_cents === null)<p class="text-xs text-amber-800">{{ __('Delivery fee to be confirmed by staff.') }}</p>@endif
        </div>
        <x-status :value="$o->status" :label="$o->statusLabel()" />
        @if (in_array($o->status, ['pending_approval', 'confirmed'], true))
            <form method="POST" action="{{ route('public.my.order.cancel', [$business, $o->reference]) }}" onsubmit="return confirm({{ \Illuminate\Support\Js::from(__('Cancel this order?')) }})">@csrf<button class="btn btn-danger btn-sm">{{ __('Cancel') }}</button></form>
        @endif
    </div>
@endforeach
</div>
@endif

@if ($quotations->isNotEmpty())
<h2 class="h2 mt-8">{{ __('Quotations') }}</h2>
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
                <p class="font-medium">{{ __('Version :v · valid until :date', ['v' => $v->version, 'date' => local_time($v->valid_until, $tz, 'M j, Y')]) }}</p>
                <table class="table mt-2"><tbody>
                    @foreach ($v->line_items as $l)<tr><td>{{ $l['quantity'] }} × {{ $l['description'] }}</td><td class="text-right">{{ peso($l['line_total_cents']) }}</td></tr>@endforeach
                    <tr><td class="font-semibold">{{ __('Total') }}</td><td class="text-right font-semibold">{{ peso($v->total_cents) }}</td></tr>
                </tbody></table>
                @if ($v->notes)<p class="mt-2 text-ink-600">{{ $v->notes }}</p>@endif
                @if ($q->status === 'sent')
                    <form method="POST" action="{{ route('public.my.quote.respond', [$business, $q->reference]) }}" class="mt-3 space-y-2">
                        @csrf <input type="hidden" name="version_id" value="{{ $v->id }}">
                        <label class="label" for="note{{ $q->id }}">{{ __('Message (optional)') }}</label>
                        <textarea id="note{{ $q->id }}" name="note" rows="2" class="input" maxlength="1000"></textarea>
                        <div class="flex flex-wrap gap-2">
                            <button name="action" value="accept" class="btn btn-primary" onclick="return confirm({{ \Illuminate\Support\Js::from(__('Accept version :v for :total?', ['v' => $v->version, 'total' => peso($v->total_cents)])) }})">{{ __('Accept v:v', ['v' => $v->version]) }}</button>
                            <button name="action" value="revise" class="btn btn-secondary">{{ __('Request changes') }}</button>
                            <button name="action" value="decline" class="btn btn-danger">{{ __('Decline') }}</button>
                        </div>
                        <p class="text-xs text-ink-500">{{ __('Accepting does not reserve a date unless the quotation says so.') }}</p>
                    </form>
                @endif
            </div>
        @endif
    </div>
@endforeach
</div>
@endif

@if ($requests->isNotEmpty())
<h2 class="h2 mt-8">{{ __('Service requests') }}</h2>
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
<section class="mt-10 rounded-[22px] border border-line bg-white p-6" aria-labelledby="privacy-h">
    <h2 id="privacy-h" class="h2">{{ __('Your information') }}</h2>
    <p class="mt-1 text-sm text-ink-600">{{ __('Under the Data Privacy Act you can get a copy of your details, or ask :business to correct or delete them.', ['business' => $business->name]) }} <a href="{{ route('public.privacy', $business) }}" class="link">{{ __('Privacy notice') }}</a></p>
    <div class="mt-4 flex flex-wrap gap-3">
        <a href="{{ route('public.my.data', $business) }}" class="btn btn-secondary btn-sm">{{ __('Download my data (JSON)') }}</a>
    </div>
    <form method="POST" action="{{ route('public.my.privacy', $business) }}" class="mt-5 grid gap-3 sm:grid-cols-[auto_1fr_auto] sm:items-end">
        @csrf
        <div><label class="label" for="pr-type">{{ __('Request') }}</label>
            <select id="pr-type" name="type" class="input">@foreach (\App\Models\PrivacyRequest::TYPES as $k => $label)<option value="{{ $k }}">{{ __($label) }}</option>@endforeach</select></div>
        <div><label class="label" for="pr-details">{{ __('Details') }} <span class="font-normal text-ink-500">{{ __('(optional)') }}</span></label><input id="pr-details" name="details" maxlength="1000" class="input" placeholder="{{ __('e.g. my new mobile number is …') }}"></div>
        <button class="btn btn-primary btn-sm">{{ __('Send request') }}</button>
        <x-human-check />
    </form>
    <p class="mt-3 text-xs text-ink-600">{{ __('Deleting your details doesn’t cancel open bookings or orders, and some records may be kept where the law requires (for example, tax records).') }}</p>
</section>
@endsection
