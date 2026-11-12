@extends('layouts.guest')
@section('title', 'Sign in')
@section('content')
<h1 class="h1">Sign in</h1>
<form method="POST" action="{{ route('login') }}" class="mt-6 space-y-4">
    @csrf
    <div><label class="label" for="email">Email</label><input id="email" type="email" name="email" class="input" value="{{ old('email') }}" required autofocus autocomplete="username"></div>
    <div><label class="label" for="password">Password</label><input id="password" type="password" name="password" class="input" required autocomplete="current-password"></div>
    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="remember" value="1"> Keep me signed in</label>
    <button class="btn btn-primary w-full">Sign in</button>
</form>
<p class="mt-4 text-center text-sm text-gray-600">New business? <a class="link" href="{{ route('register') }}">Create an account</a></p>
@endsection
