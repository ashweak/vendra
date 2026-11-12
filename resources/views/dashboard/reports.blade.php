@extends('layouts.dashboard')
@section('title', 'Reports')
@section('heading', 'Reports')
@section('content')
<form method="GET" class="flex flex-wrap items-end gap-2">
    <div><label class="label" for="from">From</label><input id="from" type="date" name="from" class="input" value="{{ $from->toDateString() }}"></div>
    <div><label class="label" for="to">To</label><input id="to" type="date" name="to" class="input" value="{{ $to->toDateString() }}"></div>
    <button class="btn btn-secondary">Update</button>
</form>
<p class="mt-3 max-w-3xl text-xs text-gray-500">Submitted, confirmed and completed figures are shown separately. Pending requests are not sales, and recorded sales are not proof of extra income caused by Vendra.</p>

<div class="mt-4 grid gap-4 lg:grid-cols-2">
    @if ($currentBusiness->moduleEnabled('appointments'))
    <section class="card"><h2 class="h2">Appointments</h2>
        <table class="table mt-2"><thead><tr><th>Status</th><th class="text-right">Count</th><th class="text-right">Value</th></tr></thead><tbody>
            @foreach (\App\Models\Appointment::STATUSES as $k => $l)
                @php $r = $appt[$k] ?? null; @endphp
                <tr><td><x-status :value="$k" :label="$l" /></td><td class="text-right">{{ $r->n ?? 0 }}</td><td class="text-right">{{ peso((int) ($r->value ?? 0)) }}</td></tr>
            @endforeach
        </tbody></table>
        @if ($byStaff->isNotEmpty())<p class="mt-3 text-sm font-medium">Completed by staff</p><ul class="text-sm text-gray-600">@foreach ($byStaff as $s)<li>{{ $s->name }}: {{ $s->n }}</li>@endforeach</ul>@endif
    </section>
    @endif
    @if ($currentBusiness->moduleEnabled('orders'))
    <section class="card"><h2 class="h2">Orders</h2>
        <table class="table mt-2"><thead><tr><th>Status</th><th class="text-right">Count</th><th class="text-right">Value</th></tr></thead><tbody>
            @foreach (\App\Models\Order::STATUSES as $k => $l)
                @php $r = $orders[$k] ?? null; @endphp
                <tr><td><x-status :value="$k" :label="$l" /></td><td class="text-right">{{ $r->n ?? 0 }}</td><td class="text-right">{{ peso((int) ($r->value ?? 0)) }}</td></tr>
            @endforeach
        </tbody></table>
    </section>
    @endif
    @if ($currentBusiness->moduleEnabled('quotations') || $currentBusiness->moduleEnabled('service_requests'))
    <section class="card"><h2 class="h2">Quotations & requests</h2>
        <div class="mt-2 grid grid-cols-2 gap-4 text-sm">
            <div><p class="font-medium">Quotations</p>@foreach (\App\Models\Quotation::STATUSES as $k => $l)<p class="flex justify-between text-gray-600"><span>{{ $l }}</span><span>{{ $quotes[$k] ?? 0 }}</span></p>@endforeach</div>
            <div><p class="font-medium">Service requests</p>@foreach (\App\Models\ServiceRequest::STATUSES as $k => $l)<p class="flex justify-between text-gray-600"><span>{{ $l }}</span><span>{{ $requests[$k] ?? 0 }}</span></p>@endforeach</div>
        </div>
    </section>
    @endif
    <section class="card"><h2 class="h2">Conversations & AI</h2>
        <p class="mt-2 text-sm">{{ $conversationCount }} conversations · {{ $handoffs }} handed to staff</p>
        <table class="table mt-3"><thead><tr><th>Month</th><th class="text-right">AI replies</th><th class="text-right">Failures</th><th class="text-right">Tokens</th></tr></thead><tbody>
            @forelse ($usage as $u)<tr><td>{{ $u->period }}</td><td class="text-right">{{ $u->requests }} / {{ $aiLimit }}</td><td class="text-right">{{ $u->failures }}</td><td class="text-right">{{ number_format($u->tokens) }}</td></tr>
            @empty<tr><td colspan="4" class="text-gray-500">No AI usage yet.</td></tr>@endforelse
        </tbody></table>
    </section>
</div>
@endsection
