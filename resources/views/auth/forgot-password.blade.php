@extends('layouts.guest')
@section('title', 'Forgot password')
@section('content')
<h1 class="font-display text-3xl font-bold tracking-tight">Forgot your password?</h1>
<p class="mt-1.5 text-sm text-ink-600">Enter your account email and we’ll send you a link to set a new one.</p>
<form method="POST" action="{{ route('password.email') }}" class="mt-6 space-y-4">
    @csrf
    <div><label class="label" for="email">Email</label><input id="email" type="email" name="email" class="input" value="{{ old('email') }}" required autofocus autocomplete="username"><x-field-error name="email" /></div>
    <x-human-check />
    <button class="btn btn-primary w-full">Send reset link</button>
</form>
<p class="mt-5 text-center text-sm text-ink-600"><a href="{{ route('login') }}" class="font-semibold underline decoration-sun decoration-2 underline-offset-4">Back to sign in</a></p>
@endsection
