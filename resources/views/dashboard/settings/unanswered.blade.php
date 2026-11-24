@extends('layouts.dashboard')
@section('title', 'Unanswered questions')
@section('heading', 'Settings')
@section('content')
@include('dashboard.settings.nav')
<div class="max-w-3xl">
    <p class="text-sm text-ink-600">Questions the auto-assistant couldn’t answer, most asked first. Write an answer once and it becomes a published FAQ that the assistant uses from now on. Emails, phone numbers and references are hidden before anything is saved, and questions not asked again for {{ config('vendra.retention.unanswered_questions_days', 90) }} days are removed.</p>
    <nav class="mt-4 flex gap-1 text-sm" aria-label="Filter">
        @foreach (['open' => 'To answer', 'answered' => 'Answered', 'dismissed' => 'Dismissed'] as $k => $label)
            <a href="{{ route('dashboard.settings.unanswered', ['show' => $k]) }}" @class(['rounded-full px-3 py-1.5', 'bg-ink-900 text-white' => $status === $k, 'bg-ink-50 text-ink-700 hover:bg-ink-100' => $status !== $k]) @if ($status === $k) aria-current="page" @endif>{{ $label }}</a>
        @endforeach
    </nav>

    @forelse ($gaps as $gap)
        <article class="card mt-4" aria-labelledby="gap-{{ $gap->id }}">
            <div class="flex flex-wrap items-start gap-3">
                <h2 id="gap-{{ $gap->id }}" class="min-w-0 flex-1 font-medium">“{{ $gap->question }}”</h2>
                <span class="badge badge-gray">{{ $gap->times_asked }}× · last {{ $gap->last_asked_at->diffForHumans() }}</span>
            </div>
            @if ($gap->conversation_id)
                <a href="{{ route('dashboard.conversations.show', $gap->conversation_id) }}" class="link mt-1 inline-block text-sm">Open the conversation</a>
            @endif

            @if ($status === 'answered' && $gap->knowledgeEntry)
                <p class="mt-3 rounded-xl bg-green-50 p-3 text-sm text-green-900"><strong>{{ $gap->knowledgeEntry->title }}</strong> — {{ \Illuminate\Support\Str::limit($gap->knowledgeEntry->body, 200) }}</p>
            @elseif ($status !== 'answered')
                @php $staff = $gap->staffReply(); @endphp
                <form method="POST" action="{{ route('dashboard.settings.unanswered.answer', $gap->id) }}" class="mt-4 space-y-3">
                    @csrf
                    <div>
                        <label class="label" for="t-{{ $gap->id }}">FAQ question</label>
                        <input id="t-{{ $gap->id }}" name="title" class="input" maxlength="160" required value="{{ \Illuminate\Support\Str::limit(\Illuminate\Support\Str::ucfirst($gap->question), 155, '') }}">
                    </div>
                    <div>
                        <label class="label" for="b-{{ $gap->id }}">Answer</label>
                        <textarea id="b-{{ $gap->id }}" name="body" rows="3" class="input" maxlength="3000" required placeholder="Write the answer customers should get">{{ $staff }}</textarea>
                        @if ($staff)<p class="hint mt-1">Filled in from your team’s reply in that conversation. Edit it before saving.</p>@endif
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button class="btn btn-primary btn-sm">Add to FAQ</button>
                        @if ($status === 'open')
                            <button form="dismiss-{{ $gap->id }}" class="btn btn-secondary btn-sm">Dismiss</button>
                        @endif
                    </div>
                </form>
                <form id="dismiss-{{ $gap->id }}" method="POST" action="{{ route('dashboard.settings.unanswered.dismiss', $gap->id) }}">@csrf</form>
            @endif
        </article>
    @empty
        <div class="mt-4"><x-empty :title="$status === 'open' ? 'Nothing to answer right now' : 'Nothing here yet'">When the auto-assistant can’t answer a customer, the question shows up here.</x-empty></div>
    @endforelse
    <div class="mt-4">{{ $gaps->links() }}</div>
</div>
@endsection
