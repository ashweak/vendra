{{-- How requests work. Params: $class, $gap, $numClass, $itemClass --}}
<ol class="{{ $class ?? '' }} {{ $gap ?? 'space-y-4 text-sm' }}">
    @foreach ($steps as $i => $step)
        <li class="flex gap-3 {{ $itemClass ?? '' }}"><span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-xs font-bold {{ $numClass ?? 'bg-ink-900 text-sun' }}">{{ $i + 1 }}</span><span>{{ $step }}</span></li>
    @endforeach
</ol>
