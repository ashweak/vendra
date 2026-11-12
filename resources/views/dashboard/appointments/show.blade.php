@extends('layouts.dashboard')
@section('title', $appointment->reference)
@section('heading', 'Appointment')
@section('content')
@php $tz = $currentBusiness->timezone; $a = $appointment; @endphp
<a href="{{ route('dashboard.appointments.index', ['date' => local_time($a->starts_at, $tz, 'Y-m-d')]) }}" class="link text-sm">← Appointments</a>
<div class="mt-3 grid gap-4 lg:grid-cols-[1fr_20rem]">
    <div class="space-y-4">
        <div class="card">
            <div class="flex flex-wrap items-center gap-3"><h1 class="h1 flex-1">{{ $a->service_name }}</h1><x-status :value="$a->status" :label="$a->statusLabel()" /></div>
            <p class="font-mono text-sm text-gray-500">{{ $a->reference }}</p>
            <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                <div><dt class="text-gray-500">When</dt><dd class="font-medium">{{ local_time($a->starts_at, $tz, 'l, F j, Y') }}<br>{{ local_time($a->starts_at, $tz, 'g:i A') }} – {{ local_time($a->ends_at, $tz, 'g:i A') }}</dd></div>
                <div><dt class="text-gray-500">Customer</dt><dd class="font-medium">{{ $a->customer?->name }}<br><span class="font-normal text-gray-600">{{ $a->customer?->phone }} {{ $a->customer?->email }}</span></dd></div>
                <div><dt class="text-gray-500">Staff</dt><dd>{{ $a->staff?->name ?? 'Any' }}</dd></div>
                <div><dt class="text-gray-500">Agreed price</dt><dd>{{ peso($a->price_cents) }}</dd></div>
                <div><dt class="text-gray-500">Source</dt><dd>{{ ucfirst(str_replace('_', '-', $a->source)) }} @if($a->creator) · by {{ $a->creator->name }} @endif</dd></div>
                @if ($a->status === 'pending_approval')<div><dt class="text-gray-500">Approve by</dt><dd class="font-medium text-amber-800">{{ local_time($a->approval_deadline_at, $tz) }}</dd></div>@endif
                @if ($a->notes)<div class="sm:col-span-2"><dt class="text-gray-500">Notes</dt><dd>{{ $a->notes }}</dd></div>@endif
                @if ($a->status_reason)<div class="sm:col-span-2"><dt class="text-gray-500">Reason</dt><dd>{{ $a->status_reason }}</dd></div>@endif
            </dl>
        </div>
        @if ($a->isOpen())
        <div class="card">
            <h2 class="h2">Actions</h2>
            <form method="POST" action="{{ route('dashboard.appointments.action', $a->id) }}" class="mt-3 space-y-3">
                @csrf
                <div><label class="label" for="reason">Reason (required to decline or cancel)</label><input id="reason" name="reason" class="input" maxlength="255"></div>
                <div class="flex flex-wrap gap-2">
                    @if ($a->status === 'pending_approval' && $canApprove)
                        <button name="action" value="confirmed" class="btn btn-primary">Approve</button>
                        <button name="action" value="declined" class="btn btn-danger">Decline</button>
                    @endif
                    @if ($a->status === 'confirmed')
                        <button name="action" value="completed" class="btn btn-primary">Mark completed</button>
                        <button name="action" value="no_show" class="btn btn-secondary">No-show</button>
                    @endif
                    <button name="action" value="cancelled" class="btn btn-danger" onclick="return confirm('Cancel this booking and release the time?')">Cancel booking</button>
                </div>
            </form>
            @if ($canApprove)
            <details class="mt-4"><summary class="cursor-pointer text-sm font-medium text-indigo-700">Reschedule</summary>
                <form method="POST" action="{{ route('dashboard.appointments.reschedule', $a->id) }}" class="mt-3 grid gap-3 sm:grid-cols-3">
                    @csrf
                    <input type="date" name="date" class="input" required value="{{ local_time($a->starts_at, $tz, 'Y-m-d') }}">
                    <input type="time" name="time" step="900" class="input" required>
                    <input name="reason" class="input" placeholder="Reason">
                    <button class="btn btn-secondary sm:col-span-3">Move booking</button>
                    <p class="hint sm:col-span-3">The current booking is kept unless the new time is secured.</p>
                </form>
            </details>
            @endif
        </div>
        @endif
    </div>
    <aside class="space-y-4">
        @include('dashboard.partials.assign', ['record' => $a, 'action' => route('dashboard.appointments.assign', $a->id)])
        @include('dashboard.partials.history')
    </aside>
</div>
@endsection
