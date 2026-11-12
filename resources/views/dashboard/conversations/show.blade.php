@extends('layouts.dashboard')
@section('title', 'Conversation')
@section('heading', 'Conversation')
@section('content')
@php $tz = $currentBusiness->timezone; $c = $conversation; @endphp
<div class="grid gap-4 lg:grid-cols-[16rem_1fr_16rem]">
    <aside class="hidden lg:block">
        <a href="{{ route('dashboard.conversations.index') }}" class="link text-sm">← All conversations</a>
        <div class="mt-3 space-y-1">
            @foreach ($conversations as $o)
                <a href="{{ route('dashboard.conversations.show', $o->id) }}" @class(['block rounded-lg px-3 py-2 text-sm', 'bg-ink-50 font-medium' => (int) $o->id === (int) $c->id, 'hover:bg-ink-100' => (int) $o->id !== (int) $c->id])>
                    {{ $o->customer?->name ?? 'Visitor #'.$o->id }} <span class="block text-[11px] text-ink-500">{{ \App\Models\Conversation::STATES[$o->state] }}</span>
                </a>
            @endforeach
        </div>
    </aside>

    <section class="card flex min-h-[32rem] flex-col p-0">
        <header class="flex flex-wrap items-center gap-2 border-b border-line p-4">
            <h1 class="flex-1 font-semibold">{{ $c->customer?->name ?? 'Website visitor #'.$c->id }}</h1>
            <x-status :value="$c->state" :label="\App\Models\Conversation::STATES[$c->state]" />
        </header>
        @if ($c->state === 'ai_active')
            <div class="border-b border-ink-100 bg-ink-50 px-4 py-2 text-xs text-ink-900">The AI is replying. Take over to pause it, or just send a reply (that also pauses it).</div>
        @elseif ($c->state !== 'closed')
            <div class="border-b border-amber-100 bg-amber-50 px-4 py-2 text-xs text-amber-900">AI paused. It resumes only when someone clicks “Resume AI”.</div>
        @endif
        <div class="flex-1 space-y-3 overflow-y-auto bg-tile p-4 text-sm">
            @foreach ($c->messages as $msg)
                @if ($msg->is_internal)
                    <div class="mx-auto max-w-[90%] rounded-lg border border-dashed border-amber-300 bg-amber-50 px-3 py-2 text-xs text-amber-900">
                        <span class="font-semibold">🔒 Internal{{ $msg->user ? ' · '.$msg->user->name : '' }}:</span> {{ $msg->body }}
                        <span class="block text-[10px] text-amber-700">{{ local_time($msg->created_at, $tz, 'M j g:i A') }} · never shown to customer</span>
                    </div>
                @else
                    <div @class(['flex', 'justify-end' => $msg->sender_type !== 'customer'])>
                        <div @class(['max-w-[80%] rounded-2xl px-3 py-2',
                            'bg-white shadow-sm' => $msg->sender_type === 'customer',
                            'bg-ink-600 text-white' => $msg->sender_type === 'staff',
                            'bg-ink-100 text-ink-950' => $msg->sender_type === 'ai',
                            'bg-ink-200 text-ink-700 text-xs' => $msg->sender_type === 'system'])>
                            <span class="mb-0.5 block text-[11px] font-semibold opacity-75">{{ ['customer' => 'Customer', 'ai' => 'AI assistant', 'staff' => $msg->user?->name ?? 'Staff', 'system' => 'System'][$msg->sender_type] }}</span>
                            <span class="whitespace-pre-line">{{ $msg->body }}</span>
                            @if (! empty($msg->meta['draft']))<span class="mt-1 block rounded bg-white/70 px-2 py-1 text-xs text-ink-700">Draft offered: {{ $msg->meta['draft']['title'] }}</span>@endif
                            <span class="mt-1 block text-[10px] opacity-60">{{ local_time($msg->created_at, $tz, 'M j g:i A') }}</span>
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
        <form method="POST" action="{{ route('dashboard.conversations.reply', $c->id) }}" class="border-t border-line p-3">
            @csrf
            <label for="body" class="sr-only">Reply</label>
            <textarea id="body" name="body" rows="2" class="input" required maxlength="2000" placeholder="Write a reply…"></textarea>
            <div class="mt-2 flex flex-wrap items-center justify-between gap-2">
                <label class="flex items-center gap-2 text-xs text-ink-600"><input type="checkbox" name="internal" value="1"> Internal note (not sent to customer)</label>
                <button class="btn btn-primary btn-sm">Send</button>
            </div>
        </form>
    </section>

    <aside class="space-y-4">
        <div class="card text-sm">
            <h2 class="font-semibold">Controls</h2>
            <div class="mt-3 flex flex-col gap-2">
                @if ($c->state !== 'staff_active')
                    <form method="POST" action="{{ route('dashboard.conversations.takeover', $c->id) }}">@csrf<button class="btn btn-secondary btn-sm w-full">Take over (pause AI)</button></form>
                @endif
                @if ($c->state !== 'ai_active' && $currentBusiness->ai_enabled)
                    <form method="POST" action="{{ route('dashboard.conversations.resume', $c->id) }}">@csrf<button class="btn btn-secondary btn-sm w-full">Resume AI</button></form>
                @endif
                @if ($c->state !== 'closed')
                    <form method="POST" action="{{ route('dashboard.conversations.close', $c->id) }}">@csrf<button class="btn btn-secondary btn-sm w-full">Close conversation</button></form>
                @endif
            </div>
            @if ($currentMembership->can('conversations.assign'))
                <form method="POST" action="{{ route('dashboard.conversations.assign', $c->id) }}" class="mt-4">
                    @csrf
                    <label class="label" for="assign">Assigned to</label>
                    <select id="assign" name="user_id" class="input" onchange="this.form.submit()">
                        <option value="">Unassigned</option>
                        @foreach ($team as $t)<option value="{{ $t->id }}" @selected((int) $c->assigned_to === (int) $t->id)>{{ $t->name }} ({{ $t->role }})</option>@endforeach
                    </select>
                </form>
            @endif
        </div>
        @if ($c->handoff_summary)
            <div class="card text-sm"><h2 class="font-semibold">Handoff summary</h2><p class="mt-2 text-ink-700">{{ $c->handoff_summary }}</p></div>
        @endif
        @if ($c->customer)
            <div class="card text-sm"><h2 class="font-semibold">Customer</h2><p class="mt-2">{{ $c->customer->name }}</p><p class="text-ink-600">{{ $c->customer->email }}</p>
                @if ($currentMembership->can('customers.all'))<a class="link mt-2 inline-block" href="{{ route('dashboard.customers.show', $c->customer->id) }}">View history</a>@endif
            </div>
        @endif
    </aside>
</div>
@endsection
