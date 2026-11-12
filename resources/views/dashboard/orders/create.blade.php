@extends('layouts.dashboard')
@section('title', 'Record order')
@section('heading', 'Orders')
@section('content')
<a href="{{ route('dashboard.orders.index') }}" class="link text-sm">← Orders</a>
<h1 class="h1 mt-2">Record an order</h1>
<form method="POST" action="{{ route('dashboard.orders.store') }}" class="mt-4 max-w-2xl space-y-4" onsubmit="this.querySelector('button').disabled=true">
    @csrf <input type="hidden" name="idempotency_key" value="{{ \Illuminate\Support\Str::uuid() }}">
    <div class="card divide-y divide-gray-100 p-0">
        @foreach ($products as $p)
            <div class="flex items-center gap-3 p-3"><span class="flex-1 text-sm">{{ $p->name }} · {{ peso($p->price_cents) }} @if($p->availableStock() !== null)<span class="text-xs text-gray-500">({{ $p->availableStock() }} available)</span>@endif</span>
                <input type="number" name="items[{{ $p->id }}]" min="0" value="0" class="input w-24" aria-label="Quantity of {{ $p->name }}"></div>
        @endforeach
    </div>
    <div class="card grid gap-4 sm:grid-cols-2">
        <div><label class="label" for="m">Fulfillment</label><select id="m" name="fulfillment_method" class="input">@foreach ((array) $currentBusiness->setting('fulfillment_methods') as $m)<option value="{{ $m }}">{{ ucfirst($m) }}</option>@endforeach</select></div>
        <div><label class="label" for="addr">Delivery address</label><input id="addr" name="delivery_address" class="input"></div>
        <div><label class="label" for="name">Customer name</label><input id="name" name="name" class="input" required></div>
        <div><label class="label" for="phone">Mobile</label><input id="phone" name="phone" class="input"></div>
        <div class="sm:col-span-2"><label class="label" for="notes">Notes</label><textarea id="notes" name="notes" class="input" rows="2"></textarea></div>
    </div>
    <button class="btn btn-primary">Save order</button>
</form>
@endsection
