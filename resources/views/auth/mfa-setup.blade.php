@extends('layouts.guest')
@section('title', 'Set up two-factor authentication')
@section('content')
<h1 class="h1">Turn on two-factor authentication</h1>
<p class="mt-2 text-sm text-gray-600">Owners and platform admins must use an authenticator app (Google Authenticator, Microsoft Authenticator, Aegis, 2FAS…).</p>
<ol class="mt-4 list-decimal space-y-2 pl-5 text-sm">
    <li>In your app, choose <strong>Add account → Enter a setup key</strong>.</li>
    <li>Account: <span class="font-mono">{{ auth()->user()->email }}</span>, type <strong>Time-based</strong>.</li>
    <li>Key: <span class="font-mono break-all rounded bg-gray-100 px-1.5 py-0.5 select-all">{{ trim(chunk_split($secret, 4, ' ')) }}</span></li>
</ol>
<details class="mt-3 text-xs text-gray-500"><summary class="cursor-pointer">Setup link (otpauth URI)</summary><p class="mt-1 font-mono break-all select-all">{{ $uri }}</p></details>
<form method="POST" action="{{ route('mfa.setup') }}" class="mt-6 space-y-4">
    @csrf
    <div><label class="label" for="code">6-digit code from the app</label><input id="code" name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="7" class="input font-mono text-lg tracking-widest" required autofocus></div>
    <button class="btn btn-primary w-full">Verify and turn on</button>
</form>
<form method="POST" action="{{ route('logout') }}" class="mt-3 text-center">@csrf<button class="link text-sm">Sign out</button></form>
@endsection
