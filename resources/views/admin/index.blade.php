@extends('admin.layout')
@section('title', 'Platform admin')
@section('content')
<div class="grid gap-3 sm:grid-cols-3">
    <div class="card"><p class="text-xs text-gray-500">Businesses</p><p class="text-2xl font-bold">{{ $businesses->count() }}</p></div>
    <div class="card"><p class="text-xs text-gray-500">Queued jobs</p><p class="text-2xl font-bold">{{ $pendingJobs }}</p></div>
    <div class="card"><p class="text-xs text-gray-500">Failed jobs</p><p class="text-2xl font-bold {{ $failedJobs ? 'text-red-700' : '' }}">{{ $failedJobs }}</p></div>
</div>
<div class="card mt-4 overflow-x-auto p-0">
    <table class="table">
        <thead><tr><th>Business</th><th>Status</th><th>Plan</th><th>Team</th><th>Customers</th><th>AI this month</th></tr></thead>
        <tbody>
        @foreach ($businesses as $b)
            @php $u = $usage[$b->id] ?? null; @endphp
            <tr>
                <td><a class="link" href="{{ route('admin.show', $b) }}">{{ $b->name }}</a><span class="block text-xs text-gray-500">{{ $b->slug }}</span></td>
                <td><x-status :value="$b->status" /> @unless($b->activated_at)<span class="badge badge-gray">setup</span>@endunless</td>
                <td class="text-sm">{{ $b->subscription?->plan_name ?? '—' }} <span class="block text-xs text-gray-500">{{ $b->subscription?->status }}</span></td>
                <td>{{ $b->memberships_count }}</td>
                <td>{{ $b->customers_count }}</td>
                <td class="text-sm">{{ $u->requests ?? 0 }} / {{ $b->subscription?->ai_monthly_limit ?? '—' }} @if(($u->failures ?? 0) > 0)<span class="block text-xs text-red-700">{{ $u->failures }} failures</span>@endif</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
<p class="mt-3 text-xs text-gray-500">Customer conversations are not visible here. Business content requires an owner-granted, time-limited support grant.</p>
@endsection
