@extends('admin.layout')
@section('title', 'Admin log')
@section('content')
<h1 class="h1">Platform administrative log</h1>
<div class="card mt-4 overflow-x-auto p-0"><table class="table"><thead><tr><th>When (UTC)</th><th>Actor</th><th>Action</th><th>Details</th></tr></thead><tbody>
@foreach ($logs as $l)<tr><td class="text-xs">{{ $l->created_at }}</td><td class="text-xs">{{ $l->actor_type }} #{{ $l->actor_id }}</td><td class="font-mono text-xs">{{ $l->action }}</td><td class="text-xs text-gray-600">{{ $l->reason }} {{ \Illuminate\Support\Str::limit(json_encode($l->changes), 140) }}</td></tr>@endforeach
</tbody></table></div>
<div class="mt-4">{{ $logs->links() }}</div>
@endsection
