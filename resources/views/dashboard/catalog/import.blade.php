@extends('layouts.dashboard')
@section('title', 'Import catalog')
@section('heading', 'Catalog')
@section('content')
<div class="max-w-2xl">
    <a href="{{ route('dashboard.catalog.index') }}" class="link text-sm">← Back to catalog</a>
    <h2 class="h2 mt-3">Import from a spreadsheet</h2>
    <p class="mt-1 text-sm text-ink-600">Add many services and products at once. In Excel or Google Sheets, use <strong>File → Save as / Download → CSV</strong>. Every row is checked first: if any row has a problem, nothing is imported and you’ll see which rows to fix. Items already in your catalog (same name) are skipped, never changed.</p>

    <div class="card mt-5">
        <h3 class="font-semibold">Columns</h3>
        <dl class="mt-3 grid gap-x-4 gap-y-2 text-sm sm:grid-cols-[9rem_1fr]">
            <dt class="font-mono text-xs font-semibold">type</dt><dd class="text-ink-600"><code>service</code> or <code>product</code> (required)</dd>
            <dt class="font-mono text-xs font-semibold">name</dt><dd class="text-ink-600">Required, up to 120 characters</dd>
            <dt class="font-mono text-xs font-semibold">price</dt><dd class="text-ink-600">Required, in pesos, e.g. <code>250</code> or <code>₱1,250.50</code></dd>
            <dt class="font-mono text-xs font-semibold">category</dt><dd class="text-ink-600">Optional, e.g. Hair, Skincare</dd>
            <dt class="font-mono text-xs font-semibold">duration_minutes</dt><dd class="text-ink-600">Services only, 15–600 (60 if empty)</dd>
            <dt class="font-mono text-xs font-semibold">stock</dt><dd class="text-ink-600">Products only; leave empty if you don’t track stock</dd>
            <dt class="font-mono text-xs font-semibold">description</dt><dd class="text-ink-600">Optional</dd>
            <dt class="font-mono text-xs font-semibold">active</dt><dd class="text-ink-600"><code>yes</code> or <code>no</code> (shown to customers; yes if empty)</dd>
        </dl>
        <a href="{{ route('dashboard.catalog.import.template') }}" class="btn btn-secondary btn-sm mt-4">Download the template (CSV)</a>
    </div>

    @if (session('import'))
        <div class="mt-5 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-900" role="alert">
            <p class="font-semibold">Rows to fix</p>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                @foreach (session('import')['errors'] as $e)<li>{{ $e }}</li>@endforeach
            </ul>
            @if (session('import')['skipped'])
                <p class="mt-3 font-semibold">Will be skipped (already in your catalog)</p>
                <ul class="mt-1 list-disc space-y-1 pl-5">@foreach (session('import')['skipped'] as $sk)<li>{{ $sk }}</li>@endforeach</ul>
            @endif
        </div>
    @endif

    <form method="POST" action="{{ route('dashboard.catalog.import.store') }}" enctype="multipart/form-data" class="card mt-5 space-y-3">
        @csrf
        <div>
            <label class="label" for="file">CSV file (up to 500 rows, 1 MB)</label>
            <input id="file" type="file" name="file" accept=".csv,text/csv" required class="text-sm">
        </div>
        <button class="btn btn-primary">Import</button>
    </form>
</div>
@endsection
