@extends('layouts.dashboard')
@section('title', 'Data & support')
@section('heading', 'Settings')
@section('content')
@include('dashboard.settings.nav')
@php $b = $currentBusiness; $tz = $b->timezone; @endphp
<div class="grid gap-4 lg:grid-cols-2">
    <section class="card">
        <h2 class="h2">Plan</h2>
        @if ($subscription)
            <dl class="mt-3 grid grid-cols-2 gap-2 text-sm">
                <dt class="text-ink-500">Plan</dt><dd>{{ $subscription->plan_name }} <x-status :value="$subscription->status" /></dd>
                <dt class="text-ink-500">Monthly fee</dt><dd>{{ peso($subscription->monthly_fee_cents) }}</dd>
                <dt class="text-ink-500">AI replies / month</dt><dd>{{ $subscription->ai_monthly_limit }}</dd>
                <dt class="text-ink-500">Team members</dt><dd>{{ $subscription->staff_limit }} + owner</dd>
                <dt class="text-ink-500">Renews</dt><dd>{{ $subscription->renews_on?->toFormattedDateString() ?? '—' }}</dd>
            </dl>
            <p class="hint mt-3">No unlimited usage and no surprise charges. Vendra fees are separate from your customers’ payments.</p>
        @endif
    </section>
    <section class="card">
        <h2 class="h2">Export your data</h2>
        <p class="hint">Exports are logged and exclude passwords and platform-only details.</p>
        <div class="mt-3 flex flex-wrap gap-2">
            @foreach (['customers' => 'Customers', 'appointments' => 'Appointments', 'orders' => 'Orders', 'quotations' => 'Quotations', 'service_requests' => 'Service requests'] as $k => $l)
                <a href="{{ route('dashboard.settings.export', $k) }}" class="btn btn-secondary btn-sm">⬇ {{ $l }} (CSV)</a>
            @endforeach
        </div>
    </section>
    <section class="card">
        <h2 class="h2">Platform support access</h2>
        <p class="hint">Vendra support can’t see your business content unless you grant time-limited access. Every access is logged and you can revoke it anytime.</p>
        @if ($admins->isNotEmpty())
        <form method="POST" action="{{ route('dashboard.settings.support.grant') }}" class="mt-3 space-y-2">@csrf
            <select name="admin_user_id" class="input" aria-label="Support person">@foreach ($admins as $a)<option value="{{ $a->id }}">{{ $a->name }}</option>@endforeach</select>
            <select name="hours" class="input" aria-label="Duration"><option value="1">1 hour</option><option value="4">4 hours</option><option value="24">24 hours</option><option value="72">3 days</option></select>
            <input name="reason" class="input" placeholder="Why? e.g. help with booking issue" required>
            <button class="btn btn-secondary btn-sm">Grant access</button>
        </form>
        @endif
        <ul class="mt-4 divide-y divide-line text-sm">
            @foreach ($grants as $g)
                <li class="flex items-center gap-2 py-2"><span class="flex-1">{{ $g->admin?->name }} · {{ $g->reason }}<span class="block text-xs text-ink-500">until {{ local_time($g->expires_at, $tz) }}</span></span>
                    @if ($g->isActive())<form method="POST" action="{{ route('dashboard.settings.support.revoke', $g->id) }}">@csrf<button class="btn btn-danger btn-sm">Revoke</button></form>@else<span class="badge badge-gray">{{ $g->revoked_at ? 'Revoked' : 'Expired' }}</span>@endif
                </li>
            @endforeach
        </ul>
    </section>
    <section class="card border-red-200">
        <h2 class="h2 text-red-800">Close this business account</h2>
        <p class="mt-1 text-sm text-ink-600">Stops new submissions immediately. Pending work is <strong>not</strong> completed automatically. You get 30 days to export or cancel; then personal data is removed.</p>
        <form method="POST" action="{{ route('dashboard.settings.close') }}" class="mt-3 space-y-2">@csrf
            <input name="confirm_name" class="input" placeholder="Type “{{ $b->name }}” to confirm" required>
            @if ($b->status === 'closing')<button name="cancel" value="1" class="btn btn-secondary btn-sm">Cancel closure</button>
            @else<button class="btn btn-danger btn-sm">Request closure</button>@endif
        </form>
    </section>
</div>
@endsection
