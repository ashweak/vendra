@extends('layouts.public')
@section('title', $business->name)
@section('content')
@php
    // Shared data for every layout (resources/views/public/home/{layout}.blade.php).
    $layout = \App\Support\Appearance::layout($business);
    $todayDow = now($business->timezone)->dayOfWeek;
    $todayHours = $hours->firstWhere('weekday', $todayDow);
    $openToday = $todayHours && ! $todayHours->is_closed && $todayHours->opens_at;
    $fmt = fn ($t) => \Carbon\Carbon::parse($t)->format('g:i A');
    $todayLabel = $openToday ? 'Open today · '.$fmt($todayHours->opens_at).' – '.$fmt($todayHours->closes_at) : 'Closed today';
    $primary = $business->moduleEnabled('appointments') ? ['Book an appointment', route('public.book', $business)]
        : ($business->moduleEnabled('orders') ? ['Shop now', route('public.order', $business)]
        : ($business->moduleEnabled('service_requests') ? ['Request a service', route('public.request', $business)] : ['Get a quote', route('public.quote', $business)]));
    $featured = $items->first();
    $groups = $items->groupBy(fn ($i) => $i->category ?: ($i->type === 'service' ? 'Services' : 'Products'));
    $categoryLabel = config('vendra.categories.'.$business->category.'.label', 'Local business');
    $intro = $business->description ?: 'Ask a question, check prices and send a request in minutes. English, Tagalog or Taglish is fine.';
    $minPrice = $items->min('price_cents');
    $openDays = $hours->filter(fn ($h) => ! $h->is_closed && $h->opens_at)->count();
    $steps = ['Ask or pick what you need — chat or form.', 'Review every detail, then confirm.', 'We approve or follow up. Track it by email link.'];
@endphp
<div data-layout="{{ $layout }}">
    @include('public.home.'.$layout)
</div>
@endsection
