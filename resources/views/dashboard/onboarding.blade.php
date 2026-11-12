@extends('layouts.dashboard')
@section('title', 'Setup')
@section('heading', 'Setup checklist')
@section('content')
<h1 class="h1">Finish setting up {{ $currentBusiness->name }}</h1>
<p class="mt-1 text-sm text-ink-600">Progress is saved as you go. Required steps must be done before activation.</p>
<ol class="mt-6 space-y-2">
    @foreach ($steps as $i => $s)
        <li class="card flex items-center gap-4 p-4">
            <span @class(['flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-sm font-bold', 'bg-green-100 text-green-800' => $s['done'], 'bg-ink-100 text-ink-600' => ! $s['done']])>{{ $s['done'] ? '✓' : $i + 1 }}</span>
            <span class="flex-1"><span class="font-medium">{{ $s['label'] }}</span> @unless($s['required'])<span class="badge badge-gray ml-1">Optional</span>@endunless</span>
            <a href="{{ $s['url'] }}" class="btn btn-secondary btn-sm">{{ $s['done'] ? 'Review' : 'Set up' }}</a>
        </li>
    @endforeach
</ol>
<form method="POST" action="{{ route('dashboard.activate') }}" class="mt-6">
    @csrf
    @if ($currentBusiness->activated_at)
        <p class="text-sm text-green-800">Activated {{ $currentBusiness->activated_at->diffForHumans() }}. Customer page: <a class="link" href="{{ route('public.home', $currentBusiness) }}" target="_blank">{{ route('public.home', $currentBusiness) }}</a></p>
    @else
        <button class="btn btn-primary">Activate customer page</button>
        <p class="hint">Use fictional data only. Live customer use needs the production review in the plan (M13).</p>
    @endif
</form>
@endsection
