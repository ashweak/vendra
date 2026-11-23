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
    $todayLabel = $openToday ? __('Open today · :from – :to', ['from' => $fmt($todayHours->opens_at), 'to' => $fmt($todayHours->closes_at)]) : __('Closed today');
    $primary = $business->moduleEnabled('appointments') ? [__('Book an appointment'), route('public.book', $business)]
        : ($business->moduleEnabled('orders') ? [__('Shop now'), route('public.order', $business)]
        : ($business->moduleEnabled('service_requests') ? [__('Request a service'), route('public.request', $business)] : [__('Get a quote'), route('public.quote', $business)]));
    $featured = $items->first();
    $groups = $items->groupBy(fn ($i) => $i->category ?: ($i->type === 'service' ? __('Services') : __('Products')));
    $categoryLabel = __(config('vendra.categories.'.$business->category.'.label', 'Local business'));
    $intro = $business->description ?: __('Ask a question, check prices and send a request in minutes. English, Tagalog or Taglish is fine.');
    $minPrice = $items->min('price_cents');
    $openDays = $hours->filter(fn ($h) => ! $h->is_closed && $h->opens_at)->count();
    $steps = [__('Ask or pick what you need — chat or form.'), __('Review every detail, then confirm.'), __('We approve or follow up. Track it by email link.')];
    $gallery = $business->photos()->get();
    $cover = $business->coverUrl();
@endphp
<div data-layout="{{ $layout }}">
    @include('public.home.'.$layout)
</div>
@endsection
