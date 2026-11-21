@extends('layouts.guest')
@section('title', 'Set a new password')
@section('content')
<h1 class="font-display text-3xl font-bold tracking-tight">Set a new password</h1>
<form method="POST" action="{{ route('password.update') }}" class="mt-6 space-y-4">
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">
    <div><label class="label" for="email">Email</label><input id="email" type="email" name="email" class="input" value="{{ old('email', $email) }}" required autocomplete="username"><x-field-error name="email" /></div>
    <div class="grid gap-4 sm:grid-cols-2">
        <div><label class="label" for="password">New password</label><input id="password" type="password" name="password" class="input" required minlength="10" autocomplete="new-password"><p class="hint">At least 10 characters.</p><x-field-error name="password" /></div>
        <div><label class="label" for="password_confirmation">Confirm password</label><input id="password_confirmation" type="password" name="password_confirmation" class="input" required autocomplete="new-password"></div>
    </div>
    <button class="btn btn-primary w-full">Save new password</button>
</form>
@endsection
