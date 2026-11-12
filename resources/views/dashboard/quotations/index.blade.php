@extends('layouts.dashboard')
@section('title', 'Quotations')
@section('heading', 'Quotations')
@section('content')
<div class="flex flex-wrap items-center gap-2">
    <form method="GET"><label for="s" class="sr-only">Status</label><select id="s" name="status" class="input" onchange="this.form.submit()"><option value="">All statuses</option>@foreach (\App\Models\Quotation::STATUSES as $k => $v)<option value="{{ $k }}" @selected($status === $k)>{{ $v }}</option>@endforeach</select></form>
    <span class="flex-1"></span>
    <details class="relative"><summary class="btn btn-primary cursor-pointer list-none">+ Record request</summary>
        <form method="POST" action="{{ route('dashboard.quotations.store') }}" class="card absolute right-0 z-10 mt-2 w-80 space-y-2">@csrf
            <input type="hidden" name="idempotency_key" value="{{ \Illuminate\Support\Str::uuid() }}">
            <input name="name" class="input" placeholder="Customer name" required><input name="phone" class="input" placeholder="Mobile">
            <textarea name="requirements" class="input" rows="3" placeholder="Requirements" required></textarea><button class="btn btn-primary btn-sm">Save</button>
        </form>
    </details>
</div>
<div class="card mt-4 overflow-x-auto p-0">
    <table class="table">
        <thead><tr><th>Reference</th><th>Customer</th><th>Request</th><th>Latest version</th><th>Assigned</th><th>Status</th></tr></thead>
        <tbody>
        @forelse ($quotes as $q)
            <tr>
                <td><a class="link font-mono" href="{{ route('dashboard.quotations.show', $q->id) }}">{{ $q->reference }}</a></td>
                <td>{{ $q->customer?->name }}</td>
                <td class="max-w-xs truncate text-gray-600">{{ \Illuminate\Support\Str::limit($q->requirements, 70) }}</td>
                <td>{{ $q->currentVersion ? 'v'.$q->currentVersion->version.' · '.peso($q->currentVersion->total_cents) : '—' }}</td>
                <td class="text-xs">{{ $q->assignee?->name ?? '—' }}</td>
                <td><x-status :value="$q->status" :label="$q->statusLabel()" /></td>
            </tr>
        @empty
            <tr><td colspan="6" class="py-8 text-center text-gray-500">No quotations.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $quotes->links() }}</div>
@endsection
