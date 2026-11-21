@extends('layouts.dashboard')
@section('title', $customer->name)
@section('heading', 'Customer')
@section('content')
@php $tz = $currentBusiness->timezone; @endphp
<a href="{{ route('dashboard.customers.index') }}" class="link text-sm">← Customers</a>
<div class="mt-3 grid gap-4 lg:grid-cols-[1fr_20rem]">
    <div class="space-y-4">
        <div class="card"><h1 class="h1">{{ $customer->name }}</h1><p class="text-sm text-ink-600">{{ $customer->email }} · {{ $customer->phone }}</p><p class="mt-1 text-xs text-ink-500">Customer since {{ local_time($customer->created_at, $tz, 'M j, Y') }}. This record belongs to {{ $currentBusiness->name }} only.</p></div>
        @foreach ([['Appointments', $appointments, 'dashboard.appointments.show'], ['Orders', $orders, 'dashboard.orders.show'], ['Quotations', $quotations, 'dashboard.quotations.show'], ['Service requests', $requests, 'dashboard.service-requests.show']] as [$title, $rows, $route])
            @continue($rows->isEmpty())
            <div class="card"><h2 class="h2">{{ $title }}</h2>
                <ul class="mt-2 divide-y divide-line text-sm">
                    @foreach ($rows as $r)<li class="flex items-center gap-3 py-2"><a class="link font-mono" href="{{ route($route, $r->id) }}">{{ $r->reference }}</a><span class="flex-1 truncate text-ink-600">{{ $r->service_name ?? (isset($r->total_cents) ? peso($r->total_cents) : \Illuminate\Support\Str::limit($r->requirements ?? $r->description ?? '', 60)) }}</span><x-status :value="$r->status" :label="$r->statusLabel()" /></li>@endforeach
                </ul>
            </div>
        @endforeach
    </div>
    <aside class="space-y-4">
        @if ($privacyRequests->isNotEmpty())
        <div class="card text-sm" id="privacy-requests"><h2 class="font-semibold">Privacy requests</h2>
            @foreach ($privacyRequests as $pr)
                <div class="mt-3 border-t border-line pt-3">
                    <p><strong>{{ \App\Models\PrivacyRequest::TYPES[$pr->type] ?? $pr->type }}</strong> · <x-status :value="$pr->status" :label="ucfirst($pr->status)" /></p>
                    @if ($pr->details)<p class="mt-1 text-ink-700">“{{ $pr->details }}”</p>@endif
                    <p class="text-xs text-ink-500">Received {{ local_time($pr->created_at, $tz) }}@if ($pr->handled_at) · handled {{ local_time($pr->handled_at, $tz) }} by {{ $pr->handler?->name }}@endif</p>
                    @if ($pr->resolution)<p class="mt-1 text-xs text-ink-600">{{ $pr->resolution }}</p>@endif
                    @if ($pr->status === 'open' && $currentMembership->can('exports.create'))
                        <form method="POST" action="{{ route('dashboard.customers.privacy', [$customer->id, $pr->id]) }}" class="mt-2 space-y-2">
                            @csrf
                            <input name="resolution" class="input" maxlength="255" placeholder="What you did / told the customer" required>
                            <div class="flex gap-2"><button name="status" value="done" class="btn btn-primary btn-sm">Mark done</button><button name="status" value="declined" class="btn btn-secondary btn-sm">Decline</button></div>
                        </form>
                    @endif
                </div>
            @endforeach
            <p class="mt-3 text-xs text-ink-500">Reply to the customer by email. For deletion, use “Remove personal details” below.</p>
        </div>
        @endif
        <div class="card text-sm"><h2 class="font-semibold">Consent history</h2>
            @forelse ($consents as $cs)<p class="mt-2">{{ ucfirst($cs->type) }}: <strong>{{ $cs->granted ? 'granted' : 'withdrawn' }}</strong> <span class="block text-xs text-ink-500">{{ local_time($cs->recorded_at, $tz) }} via {{ str_replace('_', ' ', $cs->source) }}</span></p>
            @empty<p class="mt-2 text-ink-500">No consent records.</p>@endforelse
        </div>
        @if ($currentMembership->can('exports.create'))
        <form method="POST" action="{{ route('dashboard.customers.anonymize', $customer->id) }}" class="card space-y-2 text-sm">
            @csrf
            <h2 class="font-semibold">Deletion request</h2>
            <p class="text-xs text-ink-600">After verifying the customer’s identity, remove their personal details. Transaction records are kept without identity.</p>
            <input name="reason" class="input" placeholder="Reason / verification note" required>
            <label class="flex items-center gap-2 text-xs"><input type="checkbox" name="confirm" value="1" required> I verified this request</label>
            <button class="btn btn-danger btn-sm">Remove personal details</button>
        </form>
        @endif
    </aside>
</div>
@endsection
