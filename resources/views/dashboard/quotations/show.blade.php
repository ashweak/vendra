@extends('layouts.dashboard')
@section('title', $quote->reference)
@section('heading', 'Quotation')
@section('content')
@php $tz = $currentBusiness->timezone; $q = $quote; $canDraft = in_array($q->status, ['submitted', 'under_review', 'awaiting_owner_approval'], true); @endphp
<a href="{{ route('dashboard.quotations.index') }}" class="link text-sm">← Quotations</a>
<div class="mt-3 grid gap-4 lg:grid-cols-[1fr_20rem]">
    <div class="space-y-4">
        <div class="card">
            <div class="flex flex-wrap items-center gap-3"><h1 class="h1 flex-1 font-mono">{{ $q->reference }}</h1><x-status :value="$q->status" :label="$q->statusLabel()" /></div>
            <p class="text-sm text-gray-600">{{ $q->customer?->name }} · {{ $q->customer?->phone }} · {{ $q->customer?->email }}</p>
            <h2 class="mt-4 text-sm font-semibold">Customer requirements</h2>
            <p class="mt-1 text-sm whitespace-pre-line">{{ $q->requirements }}</p>
            @foreach ($q->attachments as $att)
                <a class="link mt-2 inline-block text-sm" href="{{ route('dashboard.quotations.file', [$q->id, $att->id]) }}" target="_blank">📎 {{ $att->original_name }}</a>
            @endforeach
            @if ($q->status === 'submitted')
                <form method="POST" action="{{ route('dashboard.quotations.status', $q->id) }}" class="mt-4">@csrf<button name="status" value="under_review" class="btn btn-secondary btn-sm">Start review</button></form>
            @endif
        </div>

        @foreach ($q->versions->sortByDesc('version') as $v)
            <div @class(['card', 'ring-2 ring-indigo-200' => (int) $v->id === (int) $q->current_version_id])>
                <div class="flex flex-wrap items-center gap-2">
                    <h2 class="h2 flex-1">Version {{ $v->version }}</h2>
                    @if ((int) $v->id === (int) $q->accepted_version_id)<span class="badge badge-green">Accepted by customer</span>@endif
                    @if ($v->sent_at)<span class="badge badge-indigo">Sent {{ local_time($v->sent_at, $tz, 'M j') }}</span>@elseif ((int) $v->id === (int) $q->current_version_id)<span class="badge badge-amber">Awaiting owner approval</span>@else<span class="badge badge-gray">Superseded</span>@endif
                </div>
                <table class="table mt-2"><tbody>
                    @foreach ($v->line_items as $l)<tr><td>{{ $l['quantity'] }} × {{ $l['description'] }}</td><td class="text-right">{{ peso($l['unit_price_cents']) }}</td><td class="text-right">{{ peso($l['line_total_cents']) }}</td></tr>@endforeach
                    <tr><td colspan="2" class="text-right font-semibold">Total</td><td class="text-right font-semibold">{{ peso($v->total_cents) }}</td></tr>
                </tbody></table>
                <p class="mt-2 text-xs text-gray-500">Valid until {{ $v->valid_until->toFormattedDateString() }} · drafted by {{ $v->drafter?->name }} @if($v->notes) · {{ $v->notes }} @endif</p>
            </div>
        @endforeach

        @if ($canDraft)
        <form method="POST" action="{{ route('dashboard.quotations.draft', $q->id) }}" class="card space-y-3">@csrf
            <h2 class="h2">Draft {{ $q->versions->isEmpty() ? 'the quotation' : 'a new version' }}</h2>
            <p class="text-xs text-gray-500">Each draft is a new version. Sent versions are never edited. The owner approves before sending.</p>
            @for ($i = 0; $i < 4; $i++)
                <div class="grid grid-cols-[1fr_5rem_8rem] gap-2">
                    <input name="lines[{{ $i }}][description]" class="input" placeholder="Line item" aria-label="Line {{ $i + 1 }} description" @if($i === 0) required @endif>
                    <input name="lines[{{ $i }}][quantity]" type="number" min="1" value="1" class="input" aria-label="Line {{ $i + 1 }} quantity">
                    <input name="lines[{{ $i }}][unit_price]" type="number" step="0.01" min="0" class="input" placeholder="₱ each" aria-label="Line {{ $i + 1 }} unit price">
                </div>
            @endfor
            <div class="grid gap-2 sm:grid-cols-2">
                <div><label class="label" for="vu">Valid until</label><input id="vu" type="date" name="valid_until" class="input" value="{{ $defaultValid }}" required></div>
                <div><label class="label" for="nt">Notes to customer</label><input id="nt" name="notes" class="input" placeholder="E.g. date is not reserved until confirmed"></div>
            </div>
            <button class="btn btn-primary">Save draft</button>
        </form>
        @endif
    </div>
    <aside class="space-y-4">
        <div class="card space-y-2 text-sm">
            <h2 class="font-semibold">Actions</h2>
            @if ($q->status === 'awaiting_owner_approval')
                @if ($canApprove)
                    <form method="POST" action="{{ route('dashboard.quotations.approve', $q->id) }}">@csrf<button class="btn btn-primary w-full" onclick="return confirm('Approve and send version {{ $q->currentVersion?->version }} to the customer?')">Approve & send v{{ $q->currentVersion?->version }}</button></form>
                @else
                    <p class="text-amber-800">Waiting for the owner to approve.</p>
                @endif
            @endif
            @if ($q->status === 'accepted' && ! $q->converted_id && $currentBusiness->moduleEnabled('service_requests') && $currentMembership->can('transactions.approve'))
                <form method="POST" action="{{ route('dashboard.quotations.convert', $q->id) }}">@csrf<button class="btn btn-primary w-full">Convert to service request</button></form>
            @endif
            @if ($q->converted_id)<p>Converted to {{ str_replace('_', ' ', $q->converted_type) }} <a class="link" href="{{ route('dashboard.service-requests.show', $q->converted_id) }}">#{{ $q->converted_id }}</a></p>@endif
            @if (in_array($q->status, ['submitted', 'under_review', 'awaiting_owner_approval', 'sent'], true) && $currentMembership->can('transactions.approve'))
                <form method="POST" action="{{ route('dashboard.quotations.status', $q->id) }}" class="space-y-2">@csrf
                    <input name="reason" class="input" placeholder="Reason">
                    <div class="flex gap-2">
                        @if (in_array($q->status, ['submitted', 'under_review'], true))<button name="status" value="declined" class="btn btn-danger btn-sm">Decline</button>@endif
                        <button name="status" value="withdrawn" class="btn btn-danger btn-sm">Withdraw</button>
                    </div>
                </form>
            @endif
        </div>
        @if ($canAssign)
            @include('dashboard.partials.assign', ['record' => $q, 'action' => route('dashboard.quotations.assign', $q->id)])
        @endif
    </aside>
</div>
@endsection
