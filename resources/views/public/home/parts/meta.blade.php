@php
    $metaStock = $item->type === 'product' ? $item->availableStock() : null;
    $metaText = $item->type === 'service' ? __(':minutes min', ['minutes' => $item->duration_minutes])
        : ($metaStock === 0 ? __('Out of stock') : ($metaStock !== null && $metaStock <= 3 ? __('Only :count left', ['count' => $metaStock]) : __('In stock')));
    if (($withDescription ?? true) && $item->description) {
        $metaText .= ' · '.$item->description;
    }
@endphp
{{ $metaText }}
