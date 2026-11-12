@extends('layouts.base')
@section('body')
@php
    $b = $currentBusiness; $m = $currentMembership;
    $nav = [
        ['Overview', 'dashboard.overview', 'M3 12l9-9 9 9M5 10v10h14V10', true],
        ['Conversations', 'dashboard.conversations.index', 'M4 5h16v11H8l-4 4z', true],
        ['Customers', 'dashboard.customers.index', 'M16 14a4 4 0 10-8 0M12 10a3 3 0 100-6 3 3 0 000 6zM4 20h16', $m->can('customers.all')],
        ['Catalog', 'dashboard.catalog.index', 'M4 6h16M4 12h16M4 18h10', true],
        ['Appointments', 'dashboard.appointments.index', 'M7 3v3M17 3v3M4 8h16v12H4z', $b->moduleEnabled('appointments')],
        ['Orders', 'dashboard.orders.index', 'M5 7h14l-1 13H6zM9 7a3 3 0 016 0', $b->moduleEnabled('orders')],
        ['Quotations', 'dashboard.quotations.index', 'M6 3h9l4 4v14H6zM9 12h7M9 16h5', $b->moduleEnabled('quotations')],
        ['Service requests', 'dashboard.service-requests.index', 'M14 7l3-3 3 3-3 3M4 20l9-9', $b->moduleEnabled('service_requests')],
        ['Reports', 'dashboard.reports', 'M4 20V10M10 20V4M16 20v-7M22 20H2', $m->can('reports.view')],
        ['Activity log', 'dashboard.logs', 'M12 8v4l3 2M12 21a9 9 0 100-18 9 9 0 000 18z', $m->can('logs.view')],
        ['Team', 'dashboard.team.index', 'M17 20v-2a4 4 0 00-8 0v2M13 8a4 4 0 11-8 0 4 4 0 018 0', $m->can('hours.manage')],
        ['Settings', 'dashboard.settings.profile', 'M12 15a3 3 0 100-6 3 3 0 000 6zM19 12l2-1-2-4-2 1-2-2V4h-4v2L9 8 7 7 5 11l2 1v2l-2 1 2 4 2-1 2 2v2h4v-2l2-2 2 1 2-4-2-1z', $m->can('settings.manage')],
    ];
@endphp
<input type="checkbox" id="nav-toggle" class="peer sr-only" aria-label="Toggle navigation">
<div class="min-h-full lg:flex">
    <aside class="fixed inset-y-0 left-0 z-40 w-72 -translate-x-full overflow-y-auto border-r border-gray-200 bg-white transition-transform lg:static lg:translate-x-0 [#nav-toggle:checked~div_&]:translate-x-0">
        <div class="flex h-16 items-center justify-between border-b border-gray-100 px-5">
            <a href="{{ route('dashboard.overview') }}"><x-logo /></a>
            <label for="nav-toggle" class="cursor-pointer rounded p-2 text-gray-500 lg:hidden" aria-label="Close menu">✕</label>
        </div>
        <div class="border-b border-gray-100 p-4">
            @if ($allMemberships->count() > 1)
                <form method="POST" action="{{ route('dashboard.switch') }}">
                    @csrf
                    <label class="label" for="biz">Active business</label>
                    <select id="biz" name="business_id" class="input" onchange="this.form.submit()">
                        @foreach ($allMemberships as $mm)
                            <option value="{{ $mm->business_id }}" @selected((int) $mm->business_id === (int) $b->id)>{{ $mm->business->name }}</option>
                        @endforeach
                    </select>
                    <noscript><button class="btn btn-secondary btn-sm mt-2">Switch</button></noscript>
                </form>
            @else
                <p class="text-xs uppercase tracking-wide text-gray-500">Business</p>
                <p class="font-semibold">{{ $b->name }}</p>
            @endif
            <p class="mt-1 text-xs text-gray-500">Your role: <span class="font-medium text-gray-700">{{ ucfirst($m->role) }}</span></p>
        </div>
        <nav class="space-y-0.5 p-3" aria-label="Main">
            @foreach ($nav as [$label, $route, $icon, $show])
                @continue(! $show)
                @php $active = request()->routeIs(str_replace(['.index', '.profile'], '', $route).'*'); @endphp
                <a href="{{ route($route) }}" @class(['flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium', 'bg-indigo-50 text-indigo-700' => $active, 'text-gray-700 hover:bg-gray-50' => ! $active]) @if($active) aria-current="page" @endif>
                    <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="{{ $icon }}"/></svg>
                    {{ $label }}
                </a>
            @endforeach
        </nav>
        <div class="border-t border-gray-100 p-4 text-sm">
            <a href="{{ route('public.home', $b) }}" target="_blank" class="link">View customer page ↗</a>
            @if (auth()->user()->is_platform_admin)
                <a href="{{ route('admin.index') }}" class="link mt-2 block">Platform admin</a>
            @endif
        </div>
    </aside>
    <label for="nav-toggle" class="fixed inset-0 z-30 hidden bg-gray-900/40 [#nav-toggle:checked~div_&]:block lg:hidden" aria-hidden="true"></label>

    <div class="flex min-w-0 flex-1 flex-col">
        <header class="sticky top-0 z-20 flex h-16 items-center gap-3 border-b border-gray-200 bg-white/95 px-4 backdrop-blur sm:px-6">
            <label for="nav-toggle" class="cursor-pointer rounded-lg border border-gray-200 px-3 py-2 text-sm lg:hidden" aria-label="Open menu">☰ Menu</label>
            <div class="min-w-0 flex-1 truncate font-semibold text-gray-800">@yield('heading')</div>
            <a href="{{ route('dashboard.overview') }}#notifications" class="relative rounded-lg px-2 py-2 text-sm text-gray-600 hover:bg-gray-50" aria-label="Notifications">
                🔔 @if ($unreadNotifications)<span class="absolute -top-0.5 -right-0.5 rounded-full bg-red-600 px-1.5 text-[10px] font-bold text-white">{{ $unreadNotifications }}</span>@endif
            </a>
            <span class="hidden text-sm text-gray-600 sm:inline">{{ auth()->user()->name }}</span>
            <form method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-secondary btn-sm">Sign out</button></form>
        </header>
        <main class="mx-auto w-full max-w-6xl flex-1 p-4 sm:p-6">
            @if ($b->status === 'closing')
                <div class="mb-4 rounded-lg border border-amber-300 bg-amber-50 p-3 text-sm text-amber-900">This business is closing. New customer submissions are stopped. Export your data before {{ $b->closing_requested_at->addDays(30)->toFormattedDateString() }}.</div>
            @elseif (! $b->activated_at)
                <div class="mb-4 rounded-lg border border-indigo-200 bg-indigo-50 p-3 text-sm text-indigo-900">Setup not finished — your customer page isn’t accepting requests yet. <a class="link" href="{{ route('dashboard.onboarding') }}">Continue setup</a></div>
            @endif
            <x-flash />
            @yield('content')
        </main>
        <footer class="px-6 pb-6 text-xs text-gray-400">Vendra local build · fictional data only · not production-ready</footer>
    </div>
</div>
@endsection
