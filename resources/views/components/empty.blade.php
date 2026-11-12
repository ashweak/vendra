@props(['title', 'action' => null, 'href' => null])
<div class="rounded-xl border border-dashed border-gray-300 bg-white px-6 py-10 text-center">
    <p class="font-semibold text-gray-800">{{ $title }}</p>
    <p class="mt-1 text-sm text-gray-500">{{ $slot }}</p>
    @if ($action && $href)<a href="{{ $href }}" class="btn btn-primary mt-4">{{ $action }}</a>@endif
</div>
