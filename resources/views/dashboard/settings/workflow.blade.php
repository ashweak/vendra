@extends('layouts.dashboard')
@section('title', 'Workflow')
@section('heading', 'Settings')
@section('content')
@include('dashboard.settings.nav')
@php $b = $currentBusiness; $s = fn ($k) => old($k, $b->setting($k)); @endphp
<form method="POST" action="{{ route('dashboard.settings.workflow') }}" class="max-w-3xl space-y-4">
    @csrf @method('PUT')
    <fieldset class="card">
        <legend class="h2">Modules</legend>
        <p class="hint">Turning a module off stops new submissions. Existing records stay available.</p>
        <div class="mt-3 grid gap-2 sm:grid-cols-2">
            @foreach ($modules as $k => $label)
                <label class="flex items-center gap-2 rounded-lg border border-gray-200 p-3 text-sm"><input type="checkbox" name="modules[]" value="{{ $k }}" @checked($b->moduleEnabled($k))> {{ $label }}</label>
            @endforeach
        </div>
    </fieldset>
    <fieldset class="card grid gap-4 sm:grid-cols-2">
        <legend class="h2 sm:col-span-2">Appointments</legend>
        <div><label class="label" for="ba">Online bookings</label><select id="ba" name="booking_approval" class="input"><option value="auto" @selected($s('booking_approval') === 'auto')>Confirm automatically</option><option value="manual" @selected($s('booking_approval') === 'manual')>Owner/manager approves</option></select></div>
        <div><label class="label" for="pd">Approval deadline (hours)</label><input id="pd" type="number" name="pending_deadline_hours" class="input" value="{{ $s('pending_deadline_hours') }}"><p class="hint">Shown to the customer. Unapproved bookings then expire.</p></div>
        <div><label class="label" for="si">Time slot interval</label><select id="si" name="slot_interval_minutes" class="input">@foreach ([15, 30, 60] as $v)<option value="{{ $v }}" @selected((int) $s('slot_interval_minutes') === $v)>{{ $v }} minutes</option>@endforeach</select></div>
        <div><label class="label" for="bl">Earliest booking (minutes from now)</label><input id="bl" type="number" name="booking_lead_minutes" class="input" value="{{ $s('booking_lead_minutes') }}"></div>
        <div><label class="label" for="bh">Book up to (days ahead)</label><input id="bh" type="number" name="booking_horizon_days" class="input" value="{{ $s('booking_horizon_days') }}"></div>
        <div><label class="label" for="ch">Customer self-cancel until (hours before)</label><input id="ch" type="number" name="cancellation_hours" class="input" value="{{ $s('cancellation_hours') }}"></div>
    </fieldset>
    <fieldset class="card grid gap-4 sm:grid-cols-2">
        <legend class="h2 sm:col-span-2">Orders</legend>
        <div><label class="label" for="oa">Order approval</label><select id="oa" name="order_approval" class="input"><option value="manual" @selected($s('order_approval') === 'manual')>Owner/manager approves</option><option value="auto" @selected($s('order_approval') === 'auto')>Confirm automatically (pickup only)</option></select></div>
        <div><label class="label" for="or">Reservation expiry (hours)</label><input id="or" type="number" name="order_reservation_hours" class="input" value="{{ $s('order_reservation_hours') }}"></div>
        <div class="sm:col-span-2"><p class="label">Fulfillment methods</p>
            @foreach (['pickup' => 'Pickup', 'delivery' => 'Delivery (fee set by staff per order)'] as $k => $l)<label class="mr-4 text-sm"><input type="checkbox" name="fulfillment_methods[]" value="{{ $k }}" @checked(in_array($k, (array) $b->setting('fulfillment_methods'), true))> {{ $l }}</label>@endforeach
        </div>
    </fieldset>
    <fieldset class="card grid gap-4 sm:grid-cols-2">
        <legend class="h2 sm:col-span-2">Quotations & messaging</legend>
        <div><label class="label" for="qv">Default quote validity (days)</label><input id="qv" type="number" name="quote_valid_days" class="input" value="{{ $s('quote_valid_days') }}"></div>
        <div><label class="label" for="rh">Staff response hours (shown to customers)</label><input id="rh" name="response_hours" class="input" value="{{ $s('response_hours') }}"><p class="hint">Displayed as hours only — not a reply-time guarantee.</p></div>
    </fieldset>
    <button class="btn btn-primary">Save workflow</button>
</form>
@endsection
