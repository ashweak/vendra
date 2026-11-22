@props(['item'])
{{-- Catalog item photo, or the layout's own placeholder (the slot) when there's no photo. Size/shape via class. --}}
@if ($item->image_path)
    <img src="{{ $item->imageUrl() }}" alt="{{ $item->name }}" loading="lazy" {{ $attributes->merge(['class' => 'object-cover']) }}>
@else
    <div {{ $attributes }}>{{ $slot }}</div>
@endif
