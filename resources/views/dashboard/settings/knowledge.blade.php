@extends('layouts.dashboard')
@section('title', 'Policies & FAQ')
@section('heading', 'Settings')
@section('content')
@include('dashboard.settings.nav')
<p class="max-w-2xl text-sm text-ink-600">The AI only uses <strong>published</strong> entries. Drafts are never shown to customers. Live prices and schedules always override what is written here.</p>
<form method="POST" action="{{ route('dashboard.settings.knowledge') }}" class="card mt-4 max-w-2xl space-y-3">
    @csrf
    <div class="grid gap-3 sm:grid-cols-[8rem_1fr]">
        <select name="kind" class="input" aria-label="Type"><option value="faq">FAQ</option><option value="policy">Policy</option></select>
        <input name="title" class="input" placeholder="Title, e.g. Payment methods" required>
    </div>
    <textarea name="body" rows="3" class="input" placeholder="Answer or policy text" required></textarea>
    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_published" value="1"> Publish now</label>
    <button class="btn btn-primary">Add entry</button>
</form>
<div class="mt-6 max-w-2xl space-y-3">
    @foreach ($entries as $e)
        <form method="POST" action="{{ route('dashboard.settings.knowledge.update', $e->id) }}" class="card space-y-2">
            @csrf @method('PUT')
            <div class="flex items-center gap-2"><span class="badge badge-gray">{{ strtoupper($e->kind) }}</span><input name="title" class="input" value="{{ $e->title }}" required aria-label="Title"></div>
            <textarea name="body" rows="3" class="input" required aria-label="Body">{{ $e->body }}</textarea>
            <div class="flex flex-wrap items-center gap-3">
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_published" value="1" @checked($e->is_published)> Published</label>
                <span class="flex-1"></span>
                <button class="btn btn-secondary btn-sm">Save</button>
                <button name="delete" value="1" class="btn btn-danger btn-sm" onclick="return confirm('Delete this entry?')">Delete</button>
            </div>
        </form>
    @endforeach
</div>
@endsection
