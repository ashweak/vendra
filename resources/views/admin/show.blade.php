@extends('admin.layout')
@section('title', $business->name)
@section('content')
@php $sub = $business->subscription; @endphp
<a href="{{ route('admin.index') }}" class="link text-sm">← Accounts</a>
<h1 class="h1 mt-2">{{ $business->name }}</h1>
<p class="text-sm text-ink-600">Owner: {{ $owner?->name }} ({{ $owner?->email }}) · {{ $counts['appointments'] }} bookings · {{ $counts['orders'] }} orders · {{ $counts['quotations'] }} quotes · {{ $counts['requests'] }} requests</p>
<div class="mt-4 grid gap-4 lg:grid-cols-2">
    <form method="POST" action="{{ route('admin.subscription', $business) }}" class="card space-y-3">@csrf @method('PUT')
        <h2 class="h2">Subscription record</h2>
        <div class="grid gap-3 sm:grid-cols-2">
            <div><label class="label">Plan name</label><input name="plan_name" class="input" value="{{ $sub?->plan_name ?? 'Pilot' }}" required></div>
            <div><label class="label">Status</label><select name="status" class="input">@foreach (['trial', 'active', 'past_due', 'cancelled'] as $s)<option value="{{ $s }}" @selected($sub?->status === $s)>{{ $s }}</option>@endforeach</select></div>
            <div><label class="label">Monthly fee (₱)</label><input name="monthly_fee" type="number" step="0.01" min="0" class="input" value="{{ ($sub?->monthly_fee_cents ?? 0) / 100 }}"></div>
            <div><label class="label">AI replies / month</label><input name="ai_monthly_limit" type="number" class="input" value="{{ $sub?->ai_monthly_limit ?? 300 }}"></div>
            <div><label class="label">Staff limit</label><input name="staff_limit" type="number" class="input" value="{{ $sub?->staff_limit ?? 3 }}"></div>
            <div><label class="label">Renews on</label><input name="renews_on" type="date" class="input" value="{{ $sub?->renews_on?->toDateString() }}"></div>
        </div>
        <textarea name="notes" class="input" rows="2" placeholder="Agreed extras, support hours…">{{ $sub?->notes }}</textarea>
        <button class="btn btn-primary">Save</button>
        <p class="hint">Prices here are records of what was agreed — not approved pricing (see P10).</p>
    </form>
    <div class="space-y-4">
        <form method="POST" action="{{ route('admin.payment', $business) }}" class="card space-y-2">@csrf
            <h2 class="h2">Record a verified Vendra fee payment</h2>
            <div class="grid gap-2 sm:grid-cols-2"><input name="amount" type="number" step="0.01" class="input" placeholder="Amount ₱" required><input name="reference" class="input" placeholder="Bank/GCash reference" required></div>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="verified" value="1" required> I verified this payment was received</label>
            <button class="btn btn-secondary btn-sm">Record</button>
            @if ($sub && $sub->payments->isNotEmpty())<ul class="mt-2 text-xs text-ink-600">@foreach ($sub->payments as $p)<li>{{ $p->verified_at?->toDateString() }} · {{ peso($p->amount_cents) }} · {{ $p->reference }}</li>@endforeach</ul>@endif
        </form>
        <form method="POST" action="{{ route('admin.status', $business) }}" class="card space-y-2">@csrf
            <h2 class="h2">Account status</h2>
            <input name="reason" class="input" placeholder="Reason" required>
            <div class="flex gap-2"><button name="status" value="active" class="btn btn-secondary btn-sm">Set active</button><button name="status" value="suspended" class="btn btn-danger btn-sm">Suspend</button></div>
        </form>
        <div class="card text-sm">
            <h2 class="h2">Support access</h2>
            @if ($grant)
                <p class="mt-2 text-green-800">Active grant until {{ $grant->expires_at->toDayDateTimeString() }} — “{{ $grant->reason }}”. This view is logged.</p>
                <ul class="mt-2 divide-y divide-line">@foreach ($conversations as $c)<li class="py-1">#{{ $c->id }} · {{ $c->state }} · {{ $c->handoff_summary ? \Illuminate\Support\Str::limit($c->handoff_summary, 80) : 'no summary' }}</li>@endforeach</ul>
            @else
                <p class="mt-2 text-ink-600">No active grant. The owner can grant time-limited access from Settings → Data, support & plan.</p>
            @endif
        </div>
    </div>
</div>
@endsection
