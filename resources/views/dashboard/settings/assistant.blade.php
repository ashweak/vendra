@extends('layouts.dashboard')
@section('title', 'AI assistant')
@section('heading', 'Settings')
@section('content')
@include('dashboard.settings.nav')
@php $b = $currentBusiness; @endphp
<div class="grid gap-4 lg:grid-cols-2">
    <form method="POST" action="{{ route('dashboard.settings.assistant') }}" class="card space-y-4">
        @csrf @method('PUT')
        <h1 class="h2">Assistant settings</h1>
        <p class="text-xs text-ink-500">Engine: {{ $provider }}. Platform safety rules always apply and can’t be overridden by these settings.</p>
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="ai_enabled" value="1" @checked($b->ai_enabled)> AI assistant on (forms and staff replies work either way)</label>
        <div><label class="label" for="tone">Tone</label><select id="tone" name="ai_tone" class="input">@foreach (['friendly' => 'Friendly & concise', 'formal' => 'Polite & formal', 'casual' => 'Warm & casual'] as $k => $l)<option value="{{ $k }}" @selected($b->ai_tone === $k)>{{ $l }}</option>@endforeach</select></div>
        <div><label class="label" for="greet">Greeting</label><textarea id="greet" name="greeting" rows="2" maxlength="300" class="input" placeholder="Hi! I’m the AI assistant of …">{{ $b->greeting }}</textarea></div>
        <button class="btn btn-primary">Save</button>
        <div class="rounded-lg bg-tile p-3 text-xs text-ink-600">
            <p class="font-semibold text-ink-800">What the assistant can and can’t do</p>
            <p class="mt-1">✓ Answer from your published info, catalog, hours and live availability · prepare drafts the customer confirms · hand off to staff.</p>
            <p class="mt-1">✗ Book or order by itself · change prices, stock or policies · give discounts · verify payments · reveal other customers’ data.</p>
        </div>
    </form>
    <section class="card flex flex-col">
        <div class="flex items-center justify-between"><h2 class="h2">Test the assistant</h2>
            <form method="POST" action="{{ route('dashboard.settings.assistant.test') }}">@csrf<input type="hidden" name="message" value="reset"><button name="reset" value="1" class="link text-xs">Clear</button></form></div>
        <p class="hint">Uses your live catalog and settings in a private test chat. Try: “Magkano ang haircut?”, “Pwede bukas 3pm?”, “Can I get a discount?”</p>
        <div class="mt-3 max-h-96 flex-1 space-y-2 overflow-y-auto rounded-lg bg-tile p-3 text-sm">
            @forelse ($messages as $m)
                @continue($m->is_internal)
                <div @class(['flex', 'justify-end' => $m->sender_type === 'customer'])>
                    <div @class(['max-w-[85%] rounded-xl px-3 py-2 whitespace-pre-line', 'bg-ink-600 text-white' => $m->sender_type === 'customer', 'bg-white shadow-sm' => $m->sender_type === 'ai', 'bg-amber-50 text-xs text-amber-900' => $m->sender_type === 'system'])>
                        {{ $m->body }}
                        @if (! empty($m->meta['draft']))<span class="mt-1 block rounded bg-ink-100 px-2 py-1 text-xs text-ink-700">Draft: {{ $m->meta['draft']['title'] }} — {{ implode(' · ', $m->meta['draft']['lines']) }}</span>@endif
                        @if (! empty($m->meta['tools']))<span class="mt-1 block text-[10px] text-ink-400">tools: {{ implode(', ', $m->meta['tools']) }}</span>@endif
                    </div>
                </div>
            @empty
                <p class="text-ink-500">No test messages yet.</p>
            @endforelse
        </div>
        <form method="POST" action="{{ route('dashboard.settings.assistant.test') }}" class="mt-3 flex gap-2">@csrf
            <label for="tm" class="sr-only">Test message</label>
            <input id="tm" name="message" class="input" placeholder="Type as a customer…" required maxlength="1000" autofocus>
            <button class="btn btn-primary">Send</button>
        </form>
    </section>
</div>
@endsection
