@extends('layouts.dashboard')
@section('title', 'Messenger')
@section('heading', 'Settings')
@section('content')
@include('dashboard.settings.nav')
<div class="max-w-2xl">
    <p class="text-sm text-ink-600">Connect your Facebook Page and its Messenger chats come into the same inbox as your website chat: the auto-assistant answers, prepares drafts customers confirm with a button, and hands off to your team. Staff replies from Vendra are sent back to Messenger.</p>

    @if (! $enabled)
        <div class="card mt-5">
            <h2 class="h2">Not available on this server yet</h2>
            <p class="mt-2 text-sm text-ink-600">The administrator needs to create a Meta app and add <code>FB_APP_SECRET</code> and <code>FB_VERIFY_TOKEN</code> to the server settings (see “Messenger” in the README).</p>
        </div>
    @elseif ($b->messengerConnected())
        <div class="card mt-5">
            <h2 class="h2">Connected</h2>
            <p class="mt-2 text-sm text-ink-600">Page ID <strong>{{ $b->messenger_page_id }}</strong>. Facebook only allows replies within 24 hours of the customer’s last message; Vendra tells you if a reply couldn’t be delivered.</p>
            <form method="POST" action="{{ route('dashboard.settings.messenger.disconnect') }}" class="mt-4">@csrf<button class="btn btn-secondary btn-sm" onclick="return confirm('Disconnect Messenger?')">Disconnect</button></form>
        </div>
    @else
        <ol class="card mt-5 list-decimal space-y-2 pl-9 text-sm text-ink-700">
            <li>In Meta for Developers, open the platform’s app → Messenger → Settings and add your Page.</li>
            <li>Generate a <strong>Page access token</strong> for the Page and copy it.</li>
            <li>Find the <strong>Page ID</strong> (Page → About → Page transparency).</li>
            <li>Paste both below. Vendra checks them with Facebook before saving; the token is stored encrypted.</li>
        </ol>
        <form method="POST" action="{{ route('dashboard.settings.messenger') }}" class="card mt-4 space-y-3">
            @csrf
            <div><label class="label" for="page_id">Page ID</label><input id="page_id" name="page_id" class="input" inputmode="numeric" value="{{ old('page_id') }}" required><x-field-error name="page_id" /></div>
            <div><label class="label" for="token">Page access token</label><input id="token" name="token" type="password" class="input" autocomplete="off" required><x-field-error name="token" /></div>
            <button class="btn btn-primary">Connect Messenger</button>
        </form>
    @endif

    <div class="card mt-5 text-sm">
        <h2 class="h2">For the administrator</h2>
        <p class="mt-2 text-ink-600">Webhook callback URL (subscribe to <code>messages</code>):</p>
        <p class="mt-1 break-all rounded-lg bg-tile px-3 py-2 font-mono text-xs">{{ route('webhooks.messenger') }}</p>
    </div>
</div>
@endsection
