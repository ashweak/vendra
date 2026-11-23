@props(['item', 'sizes' => '(min-width: 1024px) 25vw, (min-width: 640px) 50vw, 100vw'])
{{-- Catalog item photo, or the layout's own placeholder (the slot) when there's no photo. Size/shape via class. --}}
@if ($item->image_path)
    <img src="{{ $item->imageUrl() }}" @srcset($item->image_path, $sizes) alt="{{ $item->name }}" loading="lazy" decoding="async" {{ $attributes->merge(['class' => 'object-cover']) }}>
@else
    <div {{ $attributes }}>{{ $slot }}</div>
@endif
