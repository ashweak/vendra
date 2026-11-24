@extends('layouts.dashboard')
@section('title', 'Your account')
@section('heading', 'Your account')
@section('content')
<div class="card mb-6 flex max-w-4xl flex-wrap items-center gap-5">
    <x-avatar :user="$user" class="h-20 w-20 text-2xl" />
    <div class="min-w-0 flex-1">
        <h2 class="h2">Profile picture</h2>
        <p class="mt-1 text-sm text-ink-600">Shown to your team in the dashboard. JPG, PNG or WebP up to 5 MB; we crop it to a square and remove hidden details such as location.</p>
        <div class="mt-3 flex flex-wrap items-center gap-3">
            <form method="POST" action="{{ route('dashboard.account.avatar') }}" enctype="multipart/form-data" class="flex flex-wrap items-center gap-3">
                @csrf
                <label class="sr-only" for="avatar">Choose a picture</label>
                <input id="avatar" type="file" name="avatar" data-shrink="1200" accept="image/jpeg,image/png,image/webp" required class="text-sm">
                <button class="btn btn-primary btn-sm">Upload</button>
            </form>
            @if ($user->avatar_path)
                <form method="POST" action="{{ route('dashboard.account.avatar.remove') }}">@csrf @method('DELETE')<button class="btn btn-secondary btn-sm">Remove</button></form>
            @endif
        </div>
        <x-field-error name="avatar" />
    </div>
</div>
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
<section class="card mt-6 max-w-4xl" aria-labelledby="device-h" id="device-card">
    <h2 id="device-h" class="h2">This phone or computer</h2>
    <p class="hint mt-1">Install Vendra like an app, and get an alert here when a customer books, orders or asks for staff.</p>
    <div class="mt-4 flex flex-wrap items-center gap-3">
        <button type="button" class="btn btn-secondary btn-sm" id="install-btn" hidden>Install the Vendra app</button>
        <p class="text-sm text-ink-600" id="install-ios" hidden>On iPhone or iPad: tap <strong>Share</strong>, then <strong>Add to Home Screen</strong>. Alerts work from the installed app (iOS 16.4 or later).</p>
        @if (\App\Services\WebPush::enabled())
            <button type="button" class="btn btn-primary btn-sm" id="push-on" hidden>Turn on alerts on this device</button>
            <button type="button" class="btn btn-secondary btn-sm" id="push-off" hidden>Turn off alerts on this device</button>
        @endif
        <p class="text-sm text-ink-600" id="push-status" role="status" aria-live="polite">@unless (\App\Services\WebPush::enabled())Device alerts aren’t set up on this server yet (the administrator adds VAPID keys).@endunless</p>
    </div>
    <p class="hint mt-3">Alerts only say that something new arrived; open Vendra to see the details. You can turn them off here or in your browser settings.</p>
</section>
<p class="mt-6 max-w-4xl text-sm text-ink-600">Two-step verification: {{ $user->hasMfa() ? 'on.' : 'not set up.' }} @unless ($user->hasMfa())<a href="{{ route('mfa.setup') }}" class="underline">Set it up</a>.@endunless</p>
@push('scripts')
<script>
(() => {
    const $ = (id) => document.getElementById(id);
    const install = $('install-btn');
    const standalone = matchMedia('(display-mode: standalone)').matches || navigator.standalone;
    const ios = /iP(hone|od|ad)/.test(navigator.userAgent) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
    if (!standalone && ios) $('install-ios').hidden = false;
    const showInstall = () => { if (window.vendraInstall && !standalone) install.hidden = false; };
    document.addEventListener('vendra:installable', showInstall); showInstall();
    install.addEventListener('click', async () => {
        if (!window.vendraInstall) return;
        window.vendraInstall.prompt(); await window.vendraInstall.userChoice; window.vendraInstall = null; install.hidden = true;
    });

    const on = $('push-on'), off = $('push-off'), status = $('push-status');
    if (!on) return;
    const key = @json(\App\Services\WebPush::publicKey());
    const csrf = document.querySelector('meta[name=csrf-token]').content;
    const post = (url, body) => fetch(url, { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf }, body: JSON.stringify(body) });
    const toKey = (b64) => { const s = atob(b64.replace(/-/g, '+').replace(/_/g, '/') + '='.repeat((4 - b64.length % 4) % 4)); return Uint8Array.from(s, (c) => c.charCodeAt(0)); };
    if (!('serviceWorker' in navigator) || !('PushManager' in window) || !('Notification' in window)) {
        status.textContent = ios && !standalone ? 'Install the app first (see above), then turn on alerts from the installed app.' : 'This browser doesn’t support alerts.';
        return;
    }
    const sync = async () => {
        const reg = await navigator.serviceWorker.getRegistration('/app');
        const sub = reg && await reg.pushManager.getSubscription();
        on.hidden = !!sub; off.hidden = !sub;
        status.textContent = sub ? 'Alerts are on for this device.' : (Notification.permission === 'denied' ? 'Alerts are blocked in this browser’s site settings.' : '');
    };
    on.addEventListener('click', async () => {
        on.disabled = true;
        try {
            if (await Notification.requestPermission() !== 'granted') { status.textContent = 'Alerts weren’t allowed.'; return; }
            const reg = await navigator.serviceWorker.register('/sw.js', { scope: '/app' });
            await navigator.serviceWorker.ready;
            const sub = await reg.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: toKey(key) });
            const res = await post(@json(route('dashboard.push.subscribe')), { endpoint: sub.endpoint });
            if (!res.ok) { await sub.unsubscribe(); const j = await res.json().catch(() => ({})); status.textContent = j.message || 'Couldn’t turn on alerts.'; return; }
            await sync();
        } catch (e) { status.textContent = 'Couldn’t turn on alerts on this device.'; }
        finally { on.disabled = false; }
    });
    off.addEventListener('click', async () => {
        const reg = await navigator.serviceWorker.getRegistration('/app');
        const sub = reg && await reg.pushManager.getSubscription();
        if (sub) { await post(@json(route('dashboard.push.unsubscribe')), { endpoint: sub.endpoint }); await sub.unsubscribe(); }
        await sync();
    });
    sync();
})();
</script>
@endpush
@endsection
