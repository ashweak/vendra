@extends('layouts.public')
@section('title', 'Order · '.$business->name)
@section('content')
<span class="eyebrow">Step 1 of 2 · Details</span>
<h1 class="section-title mt-4">Order products</h1>
<p class="mt-1 text-sm text-ink-600">Set quantities, then review your order. Stock is reserved only after you submit.</p>
<form method="POST" action="{{ route('public.order.review', $business) }}" class="mt-6 max-w-3xl">
    @csrf
    <div class="card divide-y divide-line p-0">
        @forelse ($products as $p)
            @php $avail = $p->availableStock(); $val = old('items.'.$p->id, $prefill['items'][$p->id] ?? 0); @endphp
            <div class="flex flex-wrap items-center gap-3 p-4">
                <div class="min-w-0 flex-1">
                    <p class="font-medium">{{ $p->name }}</p>
                    <p class="text-xs text-ink-500">{{ $p->category }} · {{ peso($p->price_cents) }} @if($avail !== null) · {{ $avail > 0 ? $avail.' available' : 'out of stock' }} @endif</p>
                </div>
                <label class="sr-only" for="q{{ $p->id }}">Quantity of {{ $p->name }}</label>
                <input id="q{{ $p->id }}" type="number" name="items[{{ $p->id }}]" min="0" max="{{ $avail ?? 99 }}" value="{{ (int) $val }}" class="input w-24" @disabled($avail === 0)>
            </div>
        @empty
            <div class="p-6"><x-empty title="No products yet">Please check back later.</x-empty></div>
        @endforelse
    </div>
    <fieldset class="mt-6 space-y-3">
        <legend class="h2">Fulfillment</legend>
        @foreach ((array) $business->setting('fulfillment_methods') as $m)
            <label class="flex items-center gap-2 text-sm"><input type="radio" name="fulfillment_method" value="{{ $m }}" @checked(old('fulfillment_method', $prefill['method'] ?? 'pickup') === $m) required> {{ $m === 'pickup' ? 'Pick up at the store' : 'Delivery (fee confirmed by staff before your order is confirmed)' }}</label>
        @endforeach
        @if (in_array('delivery', (array) $business->setting('fulfillment_methods'), true))
            <div><label class="label" for="addr">Delivery address (for delivery only)</label><textarea id="addr" name="delivery_address" rows="2" class="input" maxlength="500">{{ old('delivery_address') }}</textarea><x-field-error name="delivery_address" /></div>
        @endif
        <div><label class="label" for="notes">Notes (optional)</label><textarea id="notes" name="notes" rows="2" maxlength="1000" class="input">{{ old('notes') }}</textarea></div>
    </fieldset>
    @include('public.partials.contact', ['data' => [], 'phoneRequired' => true])
    <button class="btn btn-primary mt-6 w-full sm:w-auto">Review order</button>
</form>
@endsection
