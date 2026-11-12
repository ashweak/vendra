@extends('layouts.dashboard')
@section('title', 'Service requests')
@section('heading', 'Service requests')
@section('content')
<div class="flex flex-wrap items-center gap-2">
    <form method="GET"><label for="s" class="sr-only">Status</label><select id="s" name="status" class="input" onchange="this.form.submit()"><option value="">All statuses</option>@foreach (\App\Models\ServiceRequest::STATUSES as $k => $v)<option value="{{ $k }}" @selected($status === $k)>{{ $v }}</option>@endforeach</select></form>
    <span class="flex-1"></span>
    <details class="relative"><summary class="btn btn-primary cursor-pointer list-none">+ Record request</summary>
        <form method="POST" action="{{ route('dashboard.service-requests.store') }}" class="card absolute right-0 z-10 mt-2 w-80 space-y-2">@csrf
            <input type="hidden" name="idempotency_key" value="{{ \Illuminate\Support\Str::uuid() }}">
            <input name="name" class="input" placeholder="Customer name" required><input name="phone" class="input" placeholder="Mobile">
            <input name="location" class="input" placeholder="Location"><textarea name="description" class="input" rows="3" placeholder="Description" required></textarea><button class="btn btn-primary btn-sm">Save</button>
        </form>
    </details>
</div>
<div class="mt-4 space-y-2">
    @forelse ($requests as $r)
        <a href="{{ route('dashboard.service-requests.show', $r->id) }}" class="card flex flex-wrap items-center gap-3 p-4 hover:border-ink-300">
            <span class="font-mono text-sm">{{ $r->reference }}</span>
            <span class="min-w-0 flex-1"><span class="block truncate">{{ \Illuminate\Support\Str::limit($r->description, 90) }}</span><span class="block text-xs text-ink-500">{{ $r->customer?->name }} · {{ $r->location ?? 'no location' }} · {{ $r->assignee?->name ?? 'Unassigned' }}</span></span>
            <x-status :value="$r->status" :label="$r->statusLabel()" />
        </a>
    @empty
        <x-empty title="No service requests">They appear here when customers submit them.</x-empty>
    @endforelse
</div>
<div class="mt-4">{{ $requests->links() }}</div>
@endsection
