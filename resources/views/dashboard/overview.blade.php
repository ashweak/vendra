@extends('layouts.dashboard')
@section('title', 'Overview')
@section('heading', 'Overview')
@section('content')
@php $tz = $currentBusiness->timezone; @endphp
<span class="eyebrow"><x-icon name="calendar" class="h-3.5 w-3.5" /> {{ now($tz)->format('l, F j, Y') }}</span>
<h1 class="mt-4 text-3xl font-semibold tracking-tight sm:text-4xl">Magandang araw, {{ explode(' ', auth()->user()->name)[0] }}!</h1>
<p class="lead mt-2">Here’s what needs you today at {{ $currentBusiness->name }}.</p>

<div class="mt-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
    @php
        $tiles = [
            ['Customers waiting for staff', $counts['waiting'], route('dashboard.conversations.index', ['state' => 'waiting_for_staff']), true, 'chat'],
            ['Bookings to approve', $counts['pending_bookings'], route('dashboard.appointments.index', ['status' => 'pending_approval']), $currentBusiness->moduleEnabled('appointments'), 'calendar'],
            ['Orders to approve', $counts['pending_orders'], route('dashboard.orders.index', ['status' => 'pending_approval']), $currentBusiness->moduleEnabled('orders'), 'bag'],
            ['Open quotations', $counts['open_quotes'], route('dashboard.quotations.index'), $currentBusiness->moduleEnabled('quotations'), 'doc'],
            ['New service requests', $counts['open_requests'], route('dashboard.service-requests.index'), $currentBusiness->moduleEnabled('service_requests'), 'tool'],
        ];
    @endphp
    @foreach ($tiles as [$label, $n, $href, $show, $icon])
        @continue(! $show)
        <a href="{{ $href }}" class="stat group flex items-start gap-4 transition hover:bg-ink-100">
            <x-icon :name="$icon" class="stat-icon" />
            <span><span class="stat-value block">{{ $n }}</span><span class="stat-label block">{{ $label }}</span></span>
        </a>
    @endforeach
</div>

<div class="mt-8 grid gap-6 lg:grid-cols-[1fr_22rem]">
    @if ($currentBusiness->moduleEnabled('appointments'))
    <section class="card">
        <div class="flex items-center justify-between"><h2 class="h2">Today’s appointments</h2><a href="{{ route('dashboard.appointments.create') }}" class="btn btn-secondary btn-sm">+ Walk-in / phone</a></div>
        @forelse ($appointmentsToday as $a)
            <a href="{{ route('dashboard.appointments.show', $a->id) }}" class="mt-3 flex items-center gap-3 rounded-lg border border-line p-3 hover:bg-tile">
                <span class="w-16 shrink-0 font-mono text-sm font-semibold">{{ local_time($a->starts_at, $tz, 'g:i A') }}</span>
                <span class="min-w-0 flex-1"><span class="block truncate font-medium">{{ $a->service_name }}</span><span class="block truncate text-xs text-ink-500">{{ $a->customer?->name }} · {{ $a->staff?->name ?? 'Unassigned' }}</span></span>
                <x-status :value="$a->status" :label="$a->statusLabel()" />
            </a>
        @empty
            <p class="mt-3 text-sm text-ink-500">No appointments today.</p>
        @endforelse
    </section>
    @else
    <section class="card"><h2 class="h2">Quick links</h2><div class="mt-3 flex flex-wrap gap-2"><a class="btn btn-secondary" href="{{ route('dashboard.conversations.index') }}">Inbox</a><a class="btn btn-secondary" href="{{ route('dashboard.catalog.index') }}">Catalog</a></div></section>
    @endif

    <div class="space-y-6">
        <section class="card" id="notifications">
            <div class="flex items-center justify-between"><h2 class="h2">Notifications</h2>
                @if ($notifications->isNotEmpty())<form method="POST" action="{{ route('dashboard.notifications.read') }}">@csrf<button class="link text-xs">Mark all read</button></form>@endif
            </div>
            @forelse ($notifications as $n)
                <a href="{{ $n->data['url'] ?? '#' }}" class="mt-3 block rounded-xl bg-tile p-3.5 text-sm hover:bg-ink-100">
                    <p class="font-medium">{{ $n->data['title'] }}</p>
                    <p class="text-xs text-ink-600">{{ $n->data['body'] }}</p>
                    <p class="mt-1 text-[11px] text-ink-400">{{ $n->created_at->diffForHumans() }}</p>
                </a>
            @empty
                <p class="mt-3 text-sm text-ink-500">You’re all caught up.</p>
            @endforelse
        </section>
        @if ($currentMembership->can('reports.view'))
        <section class="card">
            <h2 class="h2">AI usage this month</h2>
            @php $used = $usage->requests ?? 0; $pct = $aiLimit ? min(100, round($used / $aiLimit * 100)) : 0; @endphp
            <p class="mt-2 text-sm">{{ $used }} of {{ $aiLimit }} replies</p>
            <div class="mt-3 h-2 rounded-full bg-tile" role="progressbar" aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100"> <div class="h-2 rounded-full {{ $pct >= 80 ? 'bg-amber-500' : 'bg-ink-900' }}" style="width: {{ $pct }}%"></div></div>
            <p class="mt-2 text-xs text-ink-500">At the limit, customers use forms and staff replies. No overage charges.</p>
        </section>
        @endif
    </div>
</div>
@endsection
