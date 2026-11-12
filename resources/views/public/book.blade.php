@extends('layouts.public')
@section('title', 'Book · '.$business->name)
@section('content')
<h1 class="h1">Book an appointment</h1>
<p class="mt-1 text-sm text-gray-600">Choose a service and an open time. You’ll review everything before submitting.</p>
@if ($services->isEmpty())
    <x-empty title="No services available yet">Please check back later or ask in the chat.</x-empty>
@else
<form method="POST" action="{{ route('public.book.review', $business) }}" class="mt-6 max-w-2xl" id="book-form">
    @csrf
    <div class="card space-y-4">
        <div>
            <label class="label" for="service">Service</label>
            <select id="service" name="catalog_item_id" class="input" required>
                <option value="">Choose a service…</option>
                @foreach ($services as $s)
                    <option value="{{ $s->id }}" @selected((string) old('catalog_item_id', $prefill['service'] ?? '') === (string) $s->id)>{{ $s->name }} — {{ peso($s->price_cents) }} · {{ $s->duration_minutes }} min</option>
                @endforeach
            </select>
            <x-field-error name="catalog_item_id" />
        </div>
        <div>
            <label class="label" for="date">Date</label>
            <input id="date" type="date" name="date" class="input" required min="{{ $today }}" max="{{ $maxDate }}" value="{{ old('date', $prefill['date'] ?? '') }}">
            <x-field-error name="date" />
        </div>
        <div>
            <p class="label" id="slots-label">Available times</p>
            <div id="slots" class="grid grid-cols-3 gap-2 sm:grid-cols-4" role="radiogroup" aria-labelledby="slots-label">
                <p class="col-span-full text-sm text-gray-500">Choose a service and date to see open times.</p>
            </div>
            <input type="hidden" name="time" id="time" value="{{ old('time', $prefill['time'] ?? '') }}">
            <input type="hidden" name="staff_id" id="staff_id" value="{{ old('staff_id', $prefill['staff'] ?? '') }}">
            <x-field-error name="time" />
        </div>
        <div><label class="label" for="notes">Notes (optional)</label><textarea id="notes" name="notes" rows="2" maxlength="1000" class="input">{{ old('notes') }}</textarea></div>
    </div>
    @include('public.partials.contact', ['data' => []])
    <button class="btn btn-primary mt-6 w-full sm:w-auto" style="background: var(--accent)">Review booking</button>
</form>
@push('scripts')
<script>
(() => {
    const svc = document.getElementById('service'), date = document.getElementById('date'), box = document.getElementById('slots');
    const time = document.getElementById('time'), staff = document.getElementById('staff_id');
    async function load() {
        if (!svc.value || !date.value) return;
        box.innerHTML = '<p class="col-span-full text-sm text-gray-500">Checking open times…</p>';
        try {
            const r = await fetch(`{{ route('public.slots', $business) }}?service=${svc.value}&date=${date.value}`, { headers: { Accept: 'application/json' } });
            if (!r.ok) throw new Error();
            const { slots } = await r.json();
            box.innerHTML = '';
            if (!slots.length) { box.innerHTML = '<p class="col-span-full text-sm text-gray-600">No open times on this date. Please try another date.</p>'; return; }
            const seen = new Set();
            slots.forEach(s => {
                const key = s.time + '|' + (s.staff_id ?? '');
                if (seen.has(key)) return; seen.add(key);
                const b = document.createElement('button');
                b.type = 'button'; b.setAttribute('role', 'radio');
                b.className = 'rounded-lg border border-gray-300 px-2 py-2 text-sm hover:border-gray-500 min-h-[44px]';
                b.innerHTML = `<span class="block font-medium">${s.label}</span>` + (s.staff ? `<span class="block text-[11px] text-gray-500">${s.staff.replace(/[<>&]/g, '')}</span>` : '');
                const pick = () => { box.querySelectorAll('button').forEach(x => { x.style.background = ''; x.style.color = ''; x.setAttribute('aria-checked', 'false'); });
                    b.style.background = 'var(--accent)'; b.style.color = 'white'; b.setAttribute('aria-checked', 'true'); time.value = s.time; staff.value = s.staff_id ?? ''; };
                b.addEventListener('click', pick);
                if (time.value === s.time && (String(staff.value) === String(s.staff_id ?? '') || !staff.value)) pick();
                box.appendChild(b);
            });
        } catch { box.innerHTML = '<p class="col-span-full text-sm text-red-700">We couldn’t check availability. Please try again.</p>'; }
    }
    svc.addEventListener('change', () => { time.value = ''; load(); });
    date.addEventListener('change', () => { time.value = ''; load(); });
    document.getElementById('book-form').addEventListener('submit', e => { if (!time.value) { e.preventDefault(); alert('Please choose a time.'); } });
    load();
})();
</script>
@endpush
@endif
@endsection
