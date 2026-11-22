@extends('layouts.base')
@section('body')
<main class="dots flex min-h-dvh flex-col items-center justify-center bg-tile px-6 py-12 text-center">
    <a href="{{ url('/') }}" class="mb-10"><x-logo /></a>
    <p class="font-display text-7xl font-bold text-sun-ink">@yield('code')</p>
    <h1 class="mt-4 font-display text-3xl font-bold tracking-tight">@yield('heading')</h1>
    <p class="mt-3 max-w-md text-ink-600">@yield('message')</p>
    <div class="mt-8 flex flex-wrap justify-center gap-3">
        <a href="{{ url()->previous() !== url()->current() ? url()->previous() : url('/') }}" class="btn btn-secondary">Go back</a>
        <a href="{{ url('/') }}" class="btn btn-primary">Home</a>
    </div>
</main>
@endsection
