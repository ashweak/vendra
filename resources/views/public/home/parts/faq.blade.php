{{-- Policies & FAQ accordion. Params: $class, $itemClass --}}
<div class="{{ $class ?? 'space-y-3' }}">
    @foreach ($knowledge as $k)
        <details class="group {{ $itemClass ?? 'card p-0' }}">
            <summary class="flex cursor-pointer list-none items-center justify-between gap-4 px-5 py-4 font-display text-[17px] font-semibold">
                {{ $k->title }}
                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-sun-soft text-lg transition group-open:rotate-45" aria-hidden="true">+</span>
            </summary>
            <p class="px-5 pb-5 text-sm leading-relaxed whitespace-pre-line text-ink-600">{{ $k->body }}</p>
        </details>
    @endforeach
</div>
