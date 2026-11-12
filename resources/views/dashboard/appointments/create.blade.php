@extends('layouts.dashboard')
@section('title', 'New booking')
@section('heading', 'Appointments')
@section('content')
<a href="{{ route('dashboard.appointments.index') }}" class="link text-sm">← Appointments</a>
<h1 class="h1 mt-2">Record a walk-in or phone booking</h1>
<p class="text-sm text-gray-600">Goes into the same calendar, so online customers can’t take this time.</p>
<form method="POST" action="{{ route('dashboard.appointments.store') }}" class="card mt-4 max-w-2xl space-y-4" onsubmit="this.querySelector('button').disabled=true">
    @csrf <input type="hidden" name="idempotency_key" value="{{ \Illuminate\Support\Str::uuid() }}">
    <div class="grid gap-4 sm:grid-cols-2">
        <div class="sm:col-span-2"><label class="label" for="svc">Service</label><select id="svc" name="catalog_item_id" class="input" required>@foreach ($services as $s)<option value="{{ $s->id }}" @selected(old('catalog_item_id') == $s->id)>{{ $s->name }} · {{ $s->duration_minutes }} min · {{ peso($s->price_cents) }}</option>@endforeach</select></div>
        <div><label class="label" for="date">Date</label><input id="date" type="date" name="date" class="input" required value="{{ old('date', $today) }}"></div>
        <div><label class="label" for="time">Time</label><input id="time" type="time" name="time" step="900" class="input" required value="{{ old('time') }}"></div>
        @if ($currentMembership->isManagerOrOwner())
        <div><label class="label" for="staff">Staff</label><select id="staff" name="staff_id" class="input"><option value="">Any / not assigned</option>@foreach ($team as $t)<option value="{{ $t->id }}" @selected(old('staff_id') == $t->id)>{{ $t->name }}</option>@endforeach</select></div>
        @endif
        <div><label class="label" for="src">Source</label><select id="src" name="source" class="input"><option value="walk_in">Walk-in</option><option value="phone" @selected(old('source') === 'phone')>Phone</option></select></div>
        <div><label class="label" for="name">Customer name</label><input id="name" name="name" class="input" required value="{{ old('name') }}"></div>
        <div><label class="label" for="phone">Mobile</label><input id="phone" name="phone" class="input" value="{{ old('phone') }}"></div>
        <div class="sm:col-span-2"><label class="label" for="email">Email (optional)</label><input id="email" type="email" name="email" class="input" value="{{ old('email') }}"></div>
        <div class="sm:col-span-2"><label class="label" for="notes">Notes</label><textarea id="notes" name="notes" rows="2" class="input">{{ old('notes') }}</textarea></div>
    </div>
    <button class="btn btn-primary">Save booking</button>
</form>
@endsection
