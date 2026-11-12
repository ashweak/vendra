@extends('layouts.guest')
@section('title', 'Two-factor authentication')
@section('content')
<h1 class="text-3xl font-semibold tracking-tight">Enter your code</h1>
<p class="mt-1 text-sm text-ink-600">Open your authenticator app and enter the 6-digit code for Vendra.</p>
<form method="POST" action="{{ route('mfa.challenge') }}" class="mt-6 space-y-4">
    @csrf
    <div><label class="label" for="code">Code</label><input id="code" name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="7" class="input font-mono text-lg tracking-widest" required autofocus></div>
    <button class="btn btn-primary w-full">Continue</button>
</form>
<p class="mt-4 text-xs text-ink-500">Lost your device? Account recovery verifies ownership with the platform operator; passwords and codes are never viewable.</p>
<form method="POST" action="{{ route('logout') }}" class="mt-3 text-center">@csrf<button class="link text-sm">Sign out</button></form>
@endsection
