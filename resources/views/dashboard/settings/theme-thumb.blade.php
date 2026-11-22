{{-- Wireframe of each theme's homepage layout, drawn with that theme's own colors. Decorative. --}}
@php $bar = 'rounded-full bg-ink-200'; @endphp
<div data-theme="{{ $key }}" class="h-36 overflow-hidden rounded-2xl border border-line bg-paper p-2.5" aria-hidden="true">
    @switch($key)
        @case('coral')
            <div class="flex h-3 items-center justify-between border-b border-line pb-1.5"><span class="h-2 w-10 {{ $bar }}"></span><span class="h-2 w-6 rounded-full bg-sun"></span></div>
            <div class="mt-2 flex flex-col items-center gap-1"><span class="h-2 w-24 rounded-full bg-ink-800"></span><span class="h-1.5 w-32 {{ $bar }}"></span><span class="mt-1 h-3 w-12 rounded-full bg-sun"></span></div>
            <div class="mt-2.5 grid grid-cols-3 gap-1.5">@foreach (range(1, 3) as $i)<span class="h-9 rounded-lg border border-line bg-white"></span>@endforeach</div>
            <div class="mt-1.5 grid grid-cols-3 gap-1.5">@foreach (range(1, 3) as $i)<span class="h-6 rounded-lg bg-sun-soft"></span>@endforeach</div>
            @break
        @case('ocean')
            <div class="flex justify-end gap-1"><span class="h-2 w-5 rounded-full bg-ink-900"></span><span class="h-2 w-5 {{ $bar }}"></span><span class="h-2 w-6 rounded-full bg-sun"></span></div>
            <div class="mt-2 grid grid-cols-2 gap-2">
                <div class="space-y-1"><span class="block h-2.5 w-20 rounded-full bg-ink-800"></span><span class="block h-2.5 w-14 rounded-full bg-ink-800"></span><span class="block h-1.5 w-20 {{ $bar }}"></span><div class="flex gap-1 pt-1">@foreach (range(1, 3) as $i)<span class="h-3 w-5 rounded bg-ink-100"></span>@endforeach</div></div>
                <span class="h-14 rounded-xl bg-sun"></span>
            </div>
            <div class="mt-2 flex gap-1.5">@foreach (range(1, 4) as $i)<span class="h-10 w-12 shrink-0 rounded-lg border border-line bg-white"></span>@endforeach</div>
            @break
        @case('roastery')
            <span class="block h-1.5 w-full rounded bg-ink-900"></span>
            <div class="mt-2 grid grid-cols-[1.6fr_1fr] gap-1.5">
                <div class="row-span-2 rounded-lg bg-sun-soft p-1.5"><span class="block h-2.5 w-14 rounded-full bg-ink-800"></span><span class="mt-1 block h-1.5 w-10 rounded-full bg-sun-deep"></span><span class="mt-3 block h-2.5 w-9 rounded-full bg-ink-900"></span></div>
                <span class="h-7 rounded-lg border border-line bg-white"></span><span class="h-7 rounded-lg bg-ink-900"></span>
            </div>
            <span class="mt-1.5 block h-2 w-full rounded bg-ink-100"></span>
            <div class="mt-1.5 grid grid-cols-4 gap-1">@foreach (range(1, 4) as $i)<span class="h-7 rounded-md bg-tile"></span>@endforeach</div>
            @break
        @case('slate')
            <div class="flex h-3 items-center gap-1 border-b border-line pb-1.5"><span class="h-2 w-8 {{ $bar }}"></span><span class="ml-auto h-2 w-6 rounded bg-sun"></span></div>
            <div class="mt-2 grid grid-cols-[1fr_2.2fr] gap-1.5">
                <div class="space-y-1 rounded-lg border border-line bg-white p-1.5"><span class="block h-3 w-3 rounded bg-ink-700"></span><span class="block h-1.5 w-full {{ $bar }}"></span><span class="block h-1.5 w-3/4 {{ $bar }}"></span><span class="block h-3 w-full rounded bg-ink-900"></span></div>
                <div class="space-y-1.5"><div class="grid grid-cols-3 gap-1">@foreach (range(1, 3) as $i)<span class="h-5 rounded border border-line bg-white"></span>@endforeach</div>
                    <div class="space-y-0.5 rounded-lg border border-line bg-white p-1">@foreach (range(1, 4) as $i)<span class="flex justify-between"><span class="h-1.5 w-12 {{ $bar }}"></span><span class="h-1.5 w-5 rounded-full bg-ink-700"></span></span>@endforeach</div></div>
            </div>
            @break
        @case('market')
            <span class="block h-1.5 w-full rounded bg-ink-900"></span>
            <div class="mt-1.5 flex items-center gap-1.5 rounded-lg bg-ink-900 p-2"><div class="flex-1 space-y-1"><span class="block h-2 w-16 rounded-full bg-white"></span><span class="block h-2.5 w-20 rounded-full bg-white/80"></span></div><span class="h-7 w-7 -rotate-6 rounded bg-sun"></span><span class="h-7 w-7 rotate-3 rounded bg-white"></span></div>
            <div class="mt-1.5 grid grid-cols-2 gap-1"><span class="h-3 rounded bg-sun"></span><span class="h-3 rounded bg-sun-soft"></span></div>
            <div class="mt-1.5 grid grid-cols-4 gap-1">@foreach (range(1, 4) as $i)<span class="flex h-9 flex-col justify-end rounded-md border border-line bg-white p-0.5"><span class="h-1.5 rounded-sm bg-ink-900"></span></span>@endforeach</div>
            @break
        @default
            <div class="flex h-3 items-center justify-between rounded-full border border-line bg-white px-1"><span class="h-1.5 w-8 {{ $bar }}"></span><span class="h-1.5 w-5 rounded-full bg-ink-900"></span></div>
            <div class="mt-2 grid grid-cols-[1.2fr_1fr] gap-2">
                <div class="space-y-1 pt-1"><span class="block h-2.5 w-20 rounded-full bg-ink-800"></span><span class="block h-2.5 w-14 rounded-full bg-ink-800"></span><span class="block h-1.5 w-20 {{ $bar }}"></span><span class="mt-1.5 block h-3 w-12 rounded-full bg-sun"></span></div>
                <div class="dots flex justify-center rounded-lg bg-tile pt-1.5"><span class="h-12 w-8 rounded-t-md border-2 border-b-0 border-ink-900 bg-white"></span></div>
            </div>
            <div class="mt-2.5 grid grid-cols-2 gap-3">@foreach (range(1, 2) as $c)<div class="space-y-1">@foreach (range(1, 3) as $i)<span class="flex items-center gap-1"><span class="h-1.5 w-10 {{ $bar }}"></span><span class="h-px flex-1 border-b border-dotted border-ink-300"></span><span class="h-1.5 w-4 rounded-full bg-ink-700"></span></span>@endforeach</div>@endforeach</div>
    @endswitch
</div>
