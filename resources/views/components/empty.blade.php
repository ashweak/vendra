@props(['title', 'action' => null, 'href' => null])
<div class="rounded-2xl bg-tile px-6 py-12 text-center">
    <span class="mx-auto flex h-11 w-11 items-center justify-center rounded-xl bg-white"><x-icon name="folder" class="h-5 w-5" /></span>
    <p class="mt-4 font-semibold">{{ $title }}</p>
    <p class="mt-1 text-sm text-ink-500">{{ $slot }}</p>
    @if ($action && $href)<a href="{{ $href }}" class="btn btn-primary mt-5">{{ $action }}</a>@endif
</div>
