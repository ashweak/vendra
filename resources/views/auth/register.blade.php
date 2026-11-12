@extends('layouts.guest')
@section('title', 'Create account')
@section('content')
<h1 class="h1">Create your account</h1>
<p class="mt-1 text-sm text-gray-600">You’ll set up your business next.</p>
<form method="POST" action="{{ route('register') }}" class="mt-6 space-y-4">
    @csrf
    <div><label class="label" for="name">Your name</label><input id="name" name="name" class="input" value="{{ old('name') }}" required autocomplete="name"></div>
    <div><label class="label" for="email">Email</label><input id="email" type="email" name="email" class="input" value="{{ old('email') }}" required autocomplete="email"></div>
    <div><label class="label" for="password">Password</label><input id="password" type="password" name="password" class="input" required minlength="10" autocomplete="new-password"><p class="hint">At least 10 characters.</p></div>
    <div><label class="label" for="password_confirmation">Confirm password</label><input id="password_confirmation" type="password" name="password_confirmation" class="input" required autocomplete="new-password"></div>
    <button class="btn btn-primary w-full">Create account</button>
</form>
<p class="mt-4 text-center text-sm text-gray-600">Already have one? <a class="link" href="{{ route('login') }}">Sign in</a></p>
@endsection
