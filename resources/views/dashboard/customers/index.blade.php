@extends('layouts.dashboard')
@section('title', 'Customers')
@section('heading', 'Customers')
@section('content')
<form method="GET" class="flex gap-2" role="search"><label for="q" class="sr-only">Search</label><input id="q" name="q" value="{{ $q }}" class="input max-w-sm" placeholder="Name, email or phone"><button class="btn btn-secondary">Search</button></form>
<div class="card mt-4 overflow-x-auto p-0">
    <table class="table">
        <thead><tr><th>Name</th><th>Contact</th><th>Activity</th><th>Promotions</th><th>Last active</th></tr></thead>
        <tbody>
        @forelse ($customers as $c)
            <tr>
                <td><a class="link" href="{{ route('dashboard.customers.show', $c->id) }}">{{ $c->name }}</a></td>
                <td class="text-gray-600">{{ $c->email }}<br>{{ $c->phone }}</td>
                <td class="text-xs text-gray-600">{{ $c->appointments_count }} bookings · {{ $c->orders_count }} orders · {{ $c->quotations_count }} quotes · {{ $c->service_requests_count }} requests</td>
                <td>{{ $c->marketing_consent ? 'Opted in' : '—' }}</td>
                <td class="text-xs text-gray-500">{{ $c->last_activity_at?->diffForHumans() }}</td>
            </tr>
        @empty
            <tr><td colspan="5" class="py-8 text-center text-gray-500">No customers yet.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $customers->links() }}</div>
@endsection
