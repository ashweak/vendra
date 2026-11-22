@php
    $metaStock = $item->type === 'product' ? $item->availableStock() : null;
    $metaText = $item->type === 'service' ? $item->duration_minutes.' min'
        : ($metaStock === 0 ? 'Out of stock' : ($metaStock !== null && $metaStock <= 3 ? 'Only '.$metaStock.' left' : 'In stock'));
    if (($withDescription ?? true) && $item->description) {
        $metaText .= ' · '.$item->description;
    }
@endphp
{{ $metaText }}
