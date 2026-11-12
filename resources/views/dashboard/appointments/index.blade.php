@extends('layouts.dashboard')
@section('title', 'Appointments')
@section('heading', 'Appointments')
@section('content')
@php $tz = $currentBusiness->timezone; @endphp
<div class="flex flex-wrap items-center gap-2">
    <a href="{{ route('dashboard.appointments.index', ['date' => $date->subDay()->toDateString()]) }}" class="btn btn-secondary btn-sm" aria-label="Previous day">←</a>
    <form method="GET"><label for="d" class="sr-only">Date</label><input id="d" type="date" name="date" value="{{ $date->toDateString() }}" class="input" onchange="this.form.submit()"></form>
    <a href="{{ route('dashboard.appointments.index', ['date' => $date->addDay()->toDateString()]) }}" class="btn btn-secondary btn-sm" aria-label="Next day">→</a>
    <a href="{{ route('dashboard.appointments.index') }}" class="btn btn-secondary btn-sm">Today</a>
    <a href="{{ route('dashboard.appointments.index', ['status' => 'pending_approval']) }}" class="btn btn-secondary btn-sm">Pending approval ({{ $pendingCount }})</a>
    <span class="flex-1"></span>
    <a href="{{ route('dashboard.appointments.create') }}" class="btn btn-primary">+ Walk-in / phone booking</a>
</div>
<h1 class="h2 mt-5">{{ $status ? (\App\Models\Appointment::STATUSES[$status] ?? $status) : $date->format('l, F j, Y') }}</h1>
<div class="mt-3 space-y-2">
    @forelse ($appointments as $a)
        <a href="{{ route('dashboard.appointments.show', $a->id) }}" class="card flex flex-wrap items-center gap-3 p-4 hover:border-ink-300">
            <span class="w-28 shrink-0"><span class="block font-mono font-semibold">{{ local_time($a->starts_at, $tz, 'g:i A') }}</span><span class="block text-xs text-ink-500">{{ $status ? local_time($a->starts_at, $tz, 'D M j') : 'to '.local_time($a->ends_at, $tz, 'g:i A') }}</span></span>
            <span class="min-w-0 flex-1"><span class="block font-medium">{{ $a->service_name }}</span><span class="block text-xs text-ink-500">{{ $a->customer?->name }} · {{ $a->staff?->name ?? 'Any staff' }} · {{ str_replace('_', '-', $a->source) }}</span></span>
            <x-status :value="$a->status" :label="$a->statusLabel()" />
        </a>
    @empty
        <x-empty title="No appointments">Nothing scheduled here.</x-empty>
    @endforelse
</div>
@endsection
