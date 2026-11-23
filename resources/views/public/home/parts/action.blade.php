{{-- Book / Order link for one catalog item. Params: $btn (classes), $label (optional), $showUnavailable --}}
@php $avail = $item->type === 'product' ? $item->availableStock() : null; @endphp
@if ($item->type === 'service' && $business->moduleEnabled('appointments'))
    <a href="{{ route('public.book', [$business, 'service' => $item->id]) }}" class="{{ $btn }}" aria-label="{{ __('Book :item', ['item' => $item->name]) }}">{{ $label ?? __('Book') }}</a>
@elseif ($item->type === 'product' && $avail !== 0)
    <a href="{{ route('public.order', [$business, 'items' => [$item->id => 1]]) }}" class="{{ $btn }}" aria-label="{{ __('Order :item', ['item' => $item->name]) }}">{{ $label ?? __('Order') }}</a>
@elseif (! empty($showUnavailable))
    <span class="{{ $btn }} pointer-events-none opacity-50">{{ $item->type === 'product' ? __('Out of stock') : __('Ask us') }}</span>
@endif
