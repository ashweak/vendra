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
<section class="card mt-8 max-w-3xl text-sm" aria-labelledby="law-h">
    <h2 id="law-h" class="h2">Before real customers: legal checklist</h2>
    <ul class="mt-3 list-disc space-y-1.5 pl-5 text-ink-700">
        <li><strong>Data Privacy Act (RA 10173):</strong> your page has a privacy notice and customers can download their data or ask for corrections and deletion. Answer those requests from the customer’s page.</li>
        <li><strong>NPC registration:</strong> register your data processing system and a Data Protection Officer with the National Privacy Commission if you have 250 or more employees, process sensitive personal information of 1,000 or more people, or your processing is likely to pose a risk to people’s rights.</li>
        <li><strong>Data breach:</strong> if a breach that requires notification happens, notify the NPC and affected customers within 72 hours.</li>
        <li><strong>Internet Transactions Act (RA 11967):</strong> show your business name, physical address and contact details, and accurate prices and descriptions.</li>
        <li><strong>BIR:</strong> Vendra summaries aren’t invoices. Issue invoices from your registered system or booklet.</li>
    </ul>
    <p class="mt-3 text-xs text-ink-600">General information, not legal advice. Check with the NPC, DTI, BIR or a lawyer for your situation.</p>
</section>
@endsection
