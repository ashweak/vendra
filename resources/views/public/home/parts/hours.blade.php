{{-- Weekly hours, Monday first, today highlighted. Params: $class, $todayClass --}}
<dl class="{{ $class ?? '' }} space-y-1 text-sm">
    @foreach ($hours->sortBy(fn ($h) => ($h->weekday + 6) % 7) as $h)
        <div @class(['flex justify-between gap-4 rounded-lg px-3 py-2', ($todayClass ?? 'bg-sun-soft font-semibold') => $h->weekday === $todayDow])>
            <dt>{{ ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'][$h->weekday] }}</dt>
            <dd>{{ $h->is_closed || ! $h->opens_at ? 'Closed' : $fmt($h->opens_at).' – '.$fmt($h->closes_at) }}</dd>
        </div>
    @endforeach
</dl>
