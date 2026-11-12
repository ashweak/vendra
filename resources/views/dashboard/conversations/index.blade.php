@extends('layouts.dashboard')
@section('title', 'Conversations')
@section('heading', 'Conversations')
@section('content')
<div class="flex flex-wrap gap-2">
    @foreach (['' => 'All'] + \App\Models\Conversation::STATES as $k => $label)
        <a href="{{ route('dashboard.conversations.index', array_filter(['state' => $k])) }}" @class(['btn btn-sm', 'btn-primary' => (string) $state === (string) $k, 'btn-secondary' => (string) $state !== (string) $k])>{{ $label }}</a>
    @endforeach
</div>
<div class="mt-4 space-y-2">
    @forelse ($conversations as $c)
        <a href="{{ route('dashboard.conversations.show', $c->id) }}" class="card flex flex-wrap items-center gap-3 p-4 hover:border-indigo-300">
            <span class="min-w-0 flex-1">
                <span class="block font-medium">{{ $c->customer?->name ?? 'Website visitor #'.$c->id }}</span>
                <span class="block truncate text-xs text-gray-500">{{ $c->handoff_summary ?? optional($c->messages()->where('is_internal', false)->latest('id')->first())->body }}</span>
            </span>
            <span class="text-xs text-gray-500">{{ $c->assignee?->name ?? 'Unassigned' }}</span>
            <x-status :value="$c->state" :label="\App\Models\Conversation::STATES[$c->state]" />
            <span class="text-xs text-gray-400">{{ $c->last_activity_at?->diffForHumans() }}</span>
        </a>
    @empty
        <x-empty title="No conversations">When customers chat on your page, they appear here.</x-empty>
    @endforelse
</div>
<div class="mt-4">{{ $conversations->links() }}</div>
@endsection
