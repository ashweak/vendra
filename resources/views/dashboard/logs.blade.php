@extends('layouts.dashboard')
@section('title', 'Activity log')
@section('heading', 'Activity log')
@section('content')
<form method="GET" class="flex gap-2"><label for="a" class="sr-only">Filter</label><select id="a" name="action" class="input max-w-xs" onchange="this.form.submit()"><option value="">All activity</option>@foreach (['appointment', 'order', 'quotation', 'service_request', 'conversation', 'catalog', 'stock', 'settings', 'team', 'support', 'export', 'customer'] as $p)<option value="{{ $p }}" @selected(request('action') === $p)>{{ ucfirst(str_replace('_', ' ', $p)) }}</option>@endforeach</select></form>
<div class="card mt-4 overflow-x-auto p-0">
    <table class="table">
        <thead><tr><th>When</th><th>Who</th><th>Action</th><th>Record</th><th>Details</th></tr></thead>
        <tbody>
        @forelse ($logs as $l)
            <tr>
                <td class="text-xs whitespace-nowrap text-gray-500">{{ local_time($l->created_at, $currentBusiness->timezone) }}</td>
                <td class="text-sm">{{ $l->actor_type === 'user' ? ($l->actor?->name ?? 'User #'.$l->actor_id) : ucfirst($l->actor_type) }}</td>
                <td class="font-mono text-xs">{{ $l->action }}</td>
                <td class="text-xs">{{ $l->subject_type }} {{ $l->subject_id ? '#'.$l->subject_id : '' }}</td>
                <td class="max-w-sm text-xs break-words text-gray-600">{{ $l->reason }} {{ $l->changes ? \Illuminate\Support\Str::limit(json_encode($l->changes, JSON_UNESCAPED_UNICODE), 160) : '' }}</td>
            </tr>
        @empty
            <tr><td colspan="5" class="py-8 text-center text-gray-500">No activity yet.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $logs->links() }}</div>
@endsection
