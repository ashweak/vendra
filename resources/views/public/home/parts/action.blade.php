{{-- Book / Order link for one catalog item. Params: $btn (classes), $label (optional), $showUnavailable --}}
@php $avail = $item->type === 'product' ? $item->availableStock() : null; @endphp
@if ($item->type === 'service' && $business->moduleEnabled('appointments'))
    <a href="{{ route('public.book', [$business, 'service' => $item->id]) }}" class="{{ $btn }}" aria-label="Book {{ $item->name }}">{{ $label ?? 'Book' }}</a>
@elseif ($item->type === 'product' && $avail !== 0)
    <a href="{{ route('public.order', [$business, 'items' => [$item->id => 1]]) }}" class="{{ $btn }}" aria-label="Order {{ $item->name }}">{{ $label ?? 'Order' }}</a>
@elseif (! empty($showUnavailable))
    <span class="{{ $btn }} pointer-events-none opacity-50">{{ $item->type === 'product' ? 'Out of stock' : 'Ask us' }}</span>
@endif
