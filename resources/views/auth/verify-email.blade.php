@extends('layouts.guest')
@section('title', 'Verify your email')
@section('content')
<h1 class="font-display text-3xl font-bold tracking-tight">Verify your email</h1>
<p class="mt-1.5 text-sm text-ink-600">We sent a link to <strong>{{ auth()->user()->email }}</strong>. Open it to confirm the address is yours. You need a verified email before your customer page can go live.</p>
<form method="POST" action="{{ route('verification.send') }}" class="mt-6">
    @csrf
    <button class="btn btn-primary w-full">Send the link again</button>
</form>
<p class="mt-5 text-center text-sm text-ink-600"><a href="{{ route('dashboard.overview') }}" class="font-semibold underline decoration-sun decoration-2 underline-offset-4">Continue to the dashboard</a></p>
@endsection
