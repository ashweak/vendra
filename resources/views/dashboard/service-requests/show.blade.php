@extends('layouts.dashboard')
@section('title', $sr->reference)
@section('heading', 'Service request')
@section('content')
<a href="{{ route('dashboard.service-requests.index') }}" class="link text-sm">← Service requests</a>
<div class="mt-3 grid gap-4 lg:grid-cols-[1fr_20rem]">
    <div class="space-y-4">
        <div class="card">
            <div class="flex flex-wrap items-center gap-3"><h1 class="h1 flex-1 font-mono">{{ $sr->reference }}</h1><x-status :value="$sr->status" :label="$sr->statusLabel()" /></div>
            <p class="text-sm text-ink-600">{{ $sr->customer?->name }} · {{ $sr->customer?->phone }} · {{ $sr->customer?->email }}</p>
            <p class="mt-4 whitespace-pre-line">{{ $sr->description }}</p>
            <dl class="mt-4 grid gap-2 text-sm sm:grid-cols-2">
                <div><dt class="text-ink-500">Location</dt><dd>{{ $sr->location ?? '—' }}</dd></div>
                <div><dt class="text-ink-500">Preferred date (not a booking)</dt><dd>{{ $sr->preferred_date?->toFormattedDateString() ?? '—' }}</dd></div>
                @if ($sr->quotation)<div><dt class="text-ink-500">From quotation</dt><dd><a class="link" href="{{ route('dashboard.quotations.show', $sr->quotation->id) }}">{{ $sr->quotation->reference }}</a></dd></div>@endif
                @if ($sr->status_reason)<div><dt class="text-ink-500">Note</dt><dd>{{ $sr->status_reason }}</dd></div>@endif
            </dl>
        </div>
        @if ($transitions)
        <form method="POST" action="{{ route('dashboard.service-requests.action', $sr->id) }}" class="card space-y-3">@csrf
            <h2 class="h2">Next step</h2>
            <input name="reason" class="input" placeholder="Note (e.g. what you need from the customer)">
            <div class="flex flex-wrap gap-2">
                @foreach ($transitions as $to)
                    @php $needsApproval = in_array($to, ['accepted', 'declined', 'under_review', 'awaiting_customer', 'cancelled'], true); @endphp
                    @continue($needsApproval && ! $canApprove)
                    <button name="action" value="{{ $to }}" @class(['btn', 'btn-danger' => in_array($to, ['declined', 'cancelled'], true), 'btn-secondary' => ! in_array($to, ['declined', 'cancelled'], true)])>{{ ['under_review' => 'Start review', 'awaiting_customer' => 'Ask customer for details', 'accepted' => 'Accept', 'in_progress' => 'Start work', 'completed' => 'Mark completed', 'declined' => 'Decline', 'cancelled' => 'Cancel'][$to] }}</button>
                @endforeach
            </div>
            @if ($currentBusiness->moduleEnabled('appointments'))<p class="hint">Need a reserved time? Create a linked booking from <a class="link" href="{{ route('dashboard.appointments.create') }}">Appointments</a>.</p>@endif
        </form>
        @endif
    </div>
    <aside class="space-y-4">
        @include('dashboard.partials.assign', ['record' => $sr, 'action' => route('dashboard.service-requests.assign', $sr->id)])
    </aside>
</div>
@endsection
