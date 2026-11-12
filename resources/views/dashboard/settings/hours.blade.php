@extends('layouts.dashboard')
@section('title', 'Hours')
@section('heading', 'Settings')
@section('content')
@if ($currentMembership->can('settings.manage')) @include('dashboard.settings.nav') @endif
<form method="POST" action="{{ route('dashboard.settings.hours') }}" class="card max-w-xl">
    @csrf @method('PUT')
    <h1 class="h2">Opening hours</h1>
    <p class="hint">Leave a day blank to mark it closed. Staff schedules are set under Team.</p>
    <div class="mt-4 space-y-2">
        @foreach (['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'] as $d => $label)
            @php $h = $hours[$d] ?? null; @endphp
            <div class="grid grid-cols-[6rem_1fr_1fr] items-center gap-2">
                <span class="text-sm font-medium">{{ $label }}</span>
                <input type="time" name="days[{{ $d }}][opens_at]" class="input" value="{{ $h && ! $h->is_closed ? substr($h->opens_at, 0, 5) : '' }}" aria-label="{{ $label }} opens">
                <input type="time" name="days[{{ $d }}][closes_at]" class="input" value="{{ $h && ! $h->is_closed ? substr($h->closes_at, 0, 5) : '' }}" aria-label="{{ $label }} closes">
            </div>
        @endforeach
    </div>
    <button class="btn btn-primary mt-4">Save hours</button>
</form>
@endsection
