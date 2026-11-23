@extends('layouts.public')
@section('title', __('Book').' · '.$business->name)
@section('content')
<x-page-head :eyebrow="__('Step 1 of 2 · Details')" title="{{ __('Book an appointment') }}" :intro="__('Choose a service and an open time. You’ll review everything before submitting.')" />
@if ($services->isEmpty())
    <x-empty title="{{ __('No services available yet') }}">{{ __('Please check back later or ask in the chat.') }}</x-empty>
@else
<div class="mt-8 grid gap-10 lg:grid-cols-[1fr_20rem]">
<form method="POST" action="{{ route('public.book.review', $business) }}" class="min-w-0" id="book-form">
    @csrf
    <div class="card space-y-4">
        <div>
            <label class="label" for="service">{{ __('Service') }}</label>
            <select id="service" name="catalog_item_id" class="input" required>
                <option value="">{{ __('Choose a service…') }}</option>
                @foreach ($services as $s)
                    <option value="{{ $s->id }}" @selected((string) old('catalog_item_id', $prefill['service'] ?? '') === (string) $s->id)>{{ $s->name }} — {{ peso($s->price_cents) }} · {{ __(':minutes min', ['minutes' => $s->duration_minutes]) }}</option>
                @endforeach
            </select>
            <x-field-error name="catalog_item_id" />
        </div>
        <div>
            <label class="label" for="date">{{ __('Date') }}</label>
            <input id="date" type="date" name="date" class="input" required min="{{ $today }}" max="{{ $maxDate }}" value="{{ old('date', $prefill['date'] ?? '') }}">
            <x-field-error name="date" />
        </div>
        <div>
            <p class="label" id="slots-label">{{ __('Available times') }}</p>
            <div id="slots" class="grid grid-cols-3 gap-2 sm:grid-cols-4" role="radiogroup" aria-labelledby="slots-label">
                <p class="col-span-full text-sm text-ink-500">{{ __('Choose a service and date to see open times.') }}</p>
            </div>
            <input type="hidden" name="time" id="time" value="{{ old('time', $prefill['time'] ?? '') }}">
            <input type="hidden" name="staff_id" id="staff_id" value="{{ old('staff_id', $prefill['staff'] ?? '') }}">
            <x-field-error name="time" />
        </div>
        <div><label class="label" for="notes">{{ __('Notes (optional)') }}</label><textarea id="notes" name="notes" rows="2" maxlength="1000" class="input">{{ old('notes') }}</textarea></div>
    </div>
    @include('public.partials.contact', ['data' => []])
    <x-human-check />
    <button class="btn btn-primary mt-6 w-full sm:w-auto">{{ __('Review booking') }}</button>
</form>
@include('public.partials.aside')
</div>
@push('scripts')
<script>
(() => {
    const svc = document.getElementById('service'), date = document.getElementById('date'), box = document.getElementById('slots');
    const T = @json(['checking' => __('Checking open times…'), 'none' => __('No open times on this date. Please try another date.'), 'error' => __('We couldn’t check availability. Please try again.'), 'pick' => __('Please choose a time.')]);
    const time = document.getElementById('time'), staff = document.getElementById('staff_id');
    async function load() {
        if (!svc.value || !date.value) return;
        box.innerHTML = '<p class="col-span-full text-sm text-ink-500">' + T.checking + '</p>';
        try {
            const r = await fetch(`{{ route('public.slots', $business) }}?service=${svc.value}&date=${date.value}`, { headers: { Accept: 'application/json' } });
            if (!r.ok) throw new Error();
            const { slots } = await r.json();
            box.innerHTML = '';
            if (!slots.length) { box.innerHTML = '<p class="col-span-full text-sm text-ink-600">' + T.none + '</p>'; return; }
            const seen = new Set();
            slots.forEach(s => {
                const key = s.time + '|' + (s.staff_id ?? '');
                if (seen.has(key)) return; seen.add(key);
                const b = document.createElement('button');
                b.type = 'button'; b.setAttribute('role', 'radio');
                b.className = 'rounded-lg border border-ink-300 px-2 py-2 text-sm hover:border-ink-500 min-h-[44px]';
                b.innerHTML = `<span class="block font-medium">${s.label}</span>` + (s.staff ? `<span class="block text-[11px] opacity-70">${s.staff.replace(/[<>&]/g, '')}</span>` : '');
                const pick = () => { box.querySelectorAll('button').forEach(x => { x.style.background = ''; x.style.color = ''; x.setAttribute('aria-checked', 'false'); });
                    b.style.background = '#111113'; b.style.color = 'white'; b.style.borderColor = '#111113'; b.setAttribute('aria-checked', 'true'); time.value = s.time; staff.value = s.staff_id ?? ''; };
                b.addEventListener('click', pick);
                if (time.value === s.time && (String(staff.value) === String(s.staff_id ?? '') || !staff.value)) pick();
                box.appendChild(b);
            });
        } catch { box.innerHTML = '<p class="col-span-full text-sm text-red-700">' + T.error + '</p>'; }
    }
    svc.addEventListener('change', () => { time.value = ''; load(); });
    date.addEventListener('change', () => { time.value = ''; load(); });
    document.getElementById('book-form').addEventListener('submit', e => { if (!time.value) { e.preventDefault(); alert(T.pick); } });
    load();
})();
</script>
@endpush
@endif
@endsection
