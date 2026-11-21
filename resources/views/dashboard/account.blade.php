@extends('layouts.dashboard')
@section('title', 'Your account')
@section('heading', 'Your account')
@section('content')
<div class="grid max-w-4xl gap-6 lg:grid-cols-2">
    <form method="POST" action="{{ route('dashboard.account.update') }}" class="card space-y-4">
        @csrf @method('PUT')
        <h2 class="h2">Profile</h2>
        <div><label class="label" for="name">Your name</label><input id="name" name="name" class="input" required maxlength="120" value="{{ old('name', $user->name) }}" autocomplete="name"><x-field-error name="name" /></div>
        <div>
            <label class="label" for="email">Email</label>
            <input id="email" type="email" name="email" class="input" required value="{{ old('email', $user->email) }}" autocomplete="username"><x-field-error name="email" />
            <p class="hint">
                @if ($user->hasVerifiedEmail()) ✓ Verified.
                @else Not verified yet. <a href="{{ route('verification.notice') }}" class="underline">Send a verification link</a>.
                @endif
                Changing it needs your current password and a new verification.
            </p>
        </div>
        <div><label class="label" for="current_password_profile">Current password <span class="font-normal text-ink-500">(only to change email)</span></label><input id="current_password_profile" type="password" name="current_password" class="input" autocomplete="current-password"><x-field-error name="current_password" /></div>
        <button class="btn btn-primary">Save profile</button>
    </form>

    <form method="POST" action="{{ route('dashboard.account.password') }}" class="card space-y-4">
        @csrf @method('PUT')
        <h2 class="h2">Password</h2>
        <div><label class="label" for="current_password">Current password</label><input id="current_password" type="password" name="current_password" class="input" required autocomplete="current-password"></div>
        <div><label class="label" for="password">New password</label><input id="password" type="password" name="password" class="input" required minlength="10" autocomplete="new-password"><p class="hint">At least 10 characters. Use a passphrase you don’t use anywhere else.</p><x-field-error name="password" /></div>
        <div><label class="label" for="password_confirmation">Confirm new password</label><input id="password_confirmation" type="password" name="password_confirmation" class="input" required autocomplete="new-password"></div>
        <button class="btn btn-primary">Change password</button>
    </form>
</div>
<p class="mt-6 max-w-4xl text-sm text-ink-600">Two-step verification: {{ $user->hasMfa() ? 'on.' : 'not set up.' }} @unless ($user->hasMfa())<a href="{{ route('mfa.setup') }}" class="underline">Set it up</a>.@endunless</p>
@endsection
