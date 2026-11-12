@extends('layouts.dashboard')
@section('title', $order->reference)
@section('heading', 'Order')
@section('content')
@php $tz = $currentBusiness->timezone; $o = $order; $next = \App\Services\OrderService::TRANSITIONS[$o->status] ?? []; @endphp
<a href="{{ route('dashboard.orders.index') }}" class="link text-sm">← Orders</a>
<div class="mt-3 grid gap-4 lg:grid-cols-[1fr_20rem]">
    <div class="space-y-4">
        <div class="card">
            <div class="flex flex-wrap items-center gap-3"><h1 class="h1 flex-1 font-mono">{{ $o->reference }}</h1><x-status :value="$o->status" :label="$o->statusLabel()" /></div>
            <p class="text-sm text-gray-600">{{ $o->customer?->name }} · {{ $o->customer?->phone }} · {{ ucfirst($o->fulfillment_method) }} @if($o->delivery_address) to {{ $o->delivery_address }} @endif</p>
            <table class="table mt-4">
                <thead><tr><th>Item</th><th class="text-right">Qty</th><th class="text-right">Price</th><th class="text-right">Total</th></tr></thead>
                <tbody>
                    @foreach ($o->items as $i)<tr><td>{{ $i->name }}</td><td class="text-right">{{ $i->quantity }}</td><td class="text-right">{{ peso($i->unit_price_cents) }}</td><td class="text-right">{{ peso($i->line_total_cents) }}</td></tr>@endforeach
                    <tr><td colspan="3" class="text-right text-gray-500">Subtotal</td><td class="text-right">{{ peso($o->subtotal_cents) }}</td></tr>
                    <tr><td colspan="3" class="text-right text-gray-500">Delivery fee</td><td class="text-right">{{ $o->fulfillment_method === 'delivery' ? peso($o->delivery_fee_cents) : '—' }}</td></tr>
                    <tr><td colspan="3" class="text-right font-semibold">Total</td><td class="text-right font-semibold">{{ peso($o->total_cents) }}</td></tr>
                </tbody>
            </table>
            <p class="mt-3 text-xs text-gray-500">Reservation {{ $o->stock_deducted ? 'converted to deduction at handover' : 'expires '.local_time($o->reservation_expires_at, $tz) }}.</p>
        </div>
        @if ($next)
        <div class="card">
            <h2 class="h2">Actions</h2>
            @if ($o->status === 'pending_approval' && $o->delivery_fee_cents === null && $canApprove)
                <form method="POST" action="{{ route('dashboard.orders.fee', $o->id) }}" class="mt-3 flex flex-wrap items-end gap-2">@csrf
                    <div><label class="label" for="fee">Delivery fee (₱) — required before confirming</label><input id="fee" name="fee" type="number" step="0.01" min="0" class="input" required></div>
                    <button class="btn btn-secondary">Set fee</button>
                </form>
            @endif
            <form method="POST" action="{{ route('dashboard.orders.action', $o->id) }}" class="mt-3 space-y-3">@csrf
                <input name="reason" class="input" placeholder="Reason / note (optional)">
                <div class="flex flex-wrap gap-2">
                    @foreach ($next as $to)
                        @continue(in_array($to, ['confirmed', 'declined'], true) && ! $canApprove)
                        @continue($to === 'expired')
                        @continue($to === 'ready_for_pickup' && $o->fulfillment_method !== 'pickup')
                        @continue($to === 'dispatched' && $o->fulfillment_method !== 'delivery')
                        @continue($to === 'confirmed' && $o->delivery_fee_cents === null)
                        <button name="action" value="{{ $to }}" @class(['btn', 'btn-danger' => in_array($to, ['cancelled', 'declined'], true), 'btn-primary' => ! in_array($to, ['cancelled', 'declined'], true)])
                            @if(in_array($to, ['cancelled', 'declined'], true)) onclick="return confirm('This releases reserved stock. Continue?')" @endif>{{ ['confirmed' => 'Confirm order', 'declined' => 'Decline', 'preparing' => 'Start preparing', 'ready_for_pickup' => 'Ready for pickup', 'dispatched' => 'Mark dispatched', 'completed' => 'Mark completed', 'cancelled' => 'Cancel order'][$to] }}</button>
                    @endforeach
                </div>
            </form>
        </div>
        @endif
    </div>
    <aside class="space-y-4">
        <div class="card text-sm">
            <h2 class="font-semibold">Payment</h2>
            <p class="mt-2"><x-status :value="$o->payment_status" :label="$o->payment_status === 'verified' ? 'Verified' : 'Not verified'" /></p>
            <p class="mt-2 text-xs text-gray-500">Verify only after checking your GCash/bank/cash records. Screenshots or chat messages alone are not proof. The AI can never mark payments.</p>
            @if ($o->payment_status !== 'verified' && $canVerify)
                <form method="POST" action="{{ route('dashboard.orders.verify', $o->id) }}" class="mt-3 space-y-2">@csrf
                    <input name="reason" class="input" placeholder="e.g. GCash ref 1234 checked in app" required>
                    <button class="btn btn-secondary btn-sm">Mark payment verified</button>
                </form>
            @endif
        </div>
        @include('dashboard.partials.assign', ['record' => $o, 'action' => route('dashboard.orders.assign', $o->id)])
        @include('dashboard.partials.history')
    </aside>
</div>
@endsection
