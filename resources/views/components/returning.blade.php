{{-- "Returning · 3 visits" for customers with finished bookings or completed orders. Props: customer, current (record being viewed). --}}
@props(['customer' => null, 'current' => null])
@php $visits = $customer?->pastVisits($current) ?? 0; @endphp
@if ($visits > 0)
    <span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full bg-sun-soft px-2 py-0.5 text-[11px] font-semibold text-ink-800']) }} title="{{ $visits }} finished {{ \Illuminate\Support\Str::plural('booking or order', $visits) }} before">Returning · {{ $visits }} {{ \Illuminate\Support\Str::plural('visit', $visits) }}</span>
@endif
