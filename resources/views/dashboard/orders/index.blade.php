@extends('layouts.dashboard')
@section('title', 'Orders')
@section('heading', 'Orders')
@section('content')
<div class="flex flex-wrap items-center gap-2">
    <form method="GET"><label for="s" class="sr-only">Status</label><select id="s" name="status" class="input" onchange="this.form.submit()"><option value="">All statuses</option>@foreach (\App\Models\Order::STATUSES as $k => $v)<option value="{{ $k }}" @selected($status === $k)>{{ $v }}</option>@endforeach</select></form>
    <span class="flex-1"></span>
    <a href="{{ route('dashboard.orders.create') }}" class="btn btn-primary">+ Record order</a>
</div>
<div class="card mt-4 overflow-x-auto p-0">
    <table class="table">
        <thead><tr><th>Reference</th><th>Customer</th><th>Total</th><th>Method</th><th>Payment</th><th>Status</th><th>Placed</th></tr></thead>
        <tbody>
        @forelse ($orders as $o)
            <tr>
                <td><a class="link font-mono" href="{{ route('dashboard.orders.show', $o->id) }}">{{ $o->reference }}</a></td>
                <td>{{ $o->customer?->name }}</td>
                <td>{{ peso($o->total_cents) }}@if($o->delivery_fee_cents === null)<span class="block text-[11px] text-amber-700">+ fee TBC</span>@endif</td>
                <td>{{ ucfirst($o->fulfillment_method) }}</td>
                <td><x-status :value="$o->payment_status" :label="$o->payment_status === 'verified' ? 'Verified' : 'Unpaid'" /></td>
                <td><x-status :value="$o->status" :label="$o->statusLabel()" /></td>
                <td class="text-xs text-gray-500">{{ local_time($o->created_at, $currentBusiness->timezone, 'M j g:i A') }}</td>
            </tr>
        @empty
            <tr><td colspan="7" class="py-8 text-center text-gray-500">No orders.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $orders->links() }}</div>
@endsection
