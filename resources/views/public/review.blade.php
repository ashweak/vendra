@extends('layouts.public')
@section('title', 'Review · '.$business->name)
@section('content')
@php
    $routes = ['book' => 'public.book.store', 'order' => 'public.order.store', 'quote' => 'public.quote.store', 'request' => 'public.request.store'];
    $back = ['book' => 'public.book', 'order' => 'public.order', 'quote' => 'public.quote', 'request' => 'public.request'];
    $flatten = function ($data, $prefix = '') use (&$flatten) {
        $out = [];
        foreach ($data as $k => $v) {
            $name = $prefix === '' ? $k : $prefix.'['.$k.']';
            if (is_array($v)) { $out += $flatten($v, $name); } elseif ($v !== null && $v !== '') { $out[$name] = $v; }
        }
        return $out;
    };
@endphp
<span class="eyebrow">Step 2 of 2 · Review</span>
<h1 class="section-title mt-4">Review and confirm</h1>
<p class="mt-1 text-sm text-ink-600">Please check these details. Nothing is submitted until you confirm.</p>
@if ($error)<div role="alert" class="mt-4 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900">{{ $error }}</div>@endif
<div class="card mt-6 max-w-2xl">
    <dl class="divide-y divide-line text-sm">
        @foreach ($summary as $label => $value)
            <div class="grid grid-cols-3 gap-3 py-2"><dt class="text-ink-500">{{ $label }}</dt><dd class="col-span-2 font-medium whitespace-pre-line">{{ $value }}</dd></div>
        @endforeach
    </dl>
    @if ($notice)<p class="mt-4 rounded-lg bg-tile p-3 text-sm text-ink-700">ℹ️ {{ $notice }}</p>@endif
</div>
<form method="POST" action="{{ route($routes[$type], $business) }}" class="mt-6 max-w-2xl" onsubmit="this.querySelector('button[type=submit]').disabled = true; this.querySelector('button[type=submit]').textContent = 'Submitting…';">
    @csrf
    @foreach ($flatten($data) as $name => $value)
        <input type="hidden" name="{{ $name }}" value="{{ $value }}">
    @endforeach
    <label class="flex items-start gap-2 text-sm"><input type="checkbox" name="confirmed" value="1" required class="mt-1"> <span>I confirm these details are correct.</span></label>
    <div class="mt-4 flex flex-wrap gap-2">
        <button type="submit" class="btn btn-primary">Confirm and submit</button>
        <a href="{{ route($back[$type], $business) }}" class="btn btn-secondary">Go back and edit</a>
    </div>
</form>
@endsection
