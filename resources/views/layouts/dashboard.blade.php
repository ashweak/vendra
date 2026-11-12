@extends('layouts.base')
@section('body')
@php
    $b = $currentBusiness; $m = $currentMembership;
    $nav = [
        ['Overview', 'dashboard.overview', 'home', true],
        ['Conversations', 'dashboard.conversations.index', 'chat', true],
        ['Customers', 'dashboard.customers.index', 'users', $m->can('customers.all')],
        ['Catalog', 'dashboard.catalog.index', 'tag', true],
        ['Appointments', 'dashboard.appointments.index', 'calendar', $b->moduleEnabled('appointments')],
        ['Orders', 'dashboard.orders.index', 'bag', $b->moduleEnabled('orders')],
        ['Quotations', 'dashboard.quotations.index', 'doc', $b->moduleEnabled('quotations')],
        ['Service requests', 'dashboard.service-requests.index', 'tool', $b->moduleEnabled('service_requests')],
        ['Reports', 'dashboard.reports', 'chart', $m->can('reports.view')],
        ['Activity log', 'dashboard.logs', 'log', $m->can('logs.view')],
        ['Team', 'dashboard.team.index', 'users', $m->can('hours.manage')],
        ['Settings', 'dashboard.settings.profile', 'cog', $m->can('settings.manage')],
    ];
@endphp
<input type="checkbox" id="nav-toggle" class="peer sr-only" aria-label="Toggle navigation">
<div class="min-h-full lg:flex">
    <aside class="fixed inset-y-0 left-0 z-40 flex w-72 -translate-x-full flex-col overflow-y-auto border-r border-line bg-white transition-transform lg:sticky lg:top-0 lg:h-screen lg:translate-x-0 [#nav-toggle:checked~div_&]:translate-x-0">
        <div class="flex h-16 items-center justify-between border-b border-line px-5">
            <a href="{{ route('dashboard.overview') }}"><x-logo /></a>
            <label for="nav-toggle" class="cursor-pointer rounded p-2 text-ink-500 lg:hidden" aria-label="Close menu">✕</label>
        </div>
        <div class="border-b border-line p-4">
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
                <div class="flex items-center gap-3 rounded-xl bg-tile p-3">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-sm font-semibold text-white" style="background: {{ $b->accent_color }}">{{ mb_substr($b->name, 0, 1) }}</span>
                    <span class="min-w-0"><span class="block truncate text-sm font-semibold">{{ $b->name }}</span><span class="block text-xs text-ink-500">{{ ucfirst($m->role) }}</span></span>
                </div>
            @endif
            @if ($allMemberships->count() > 1)<p class="mt-1 text-xs text-ink-500">Your role: <span class="font-medium text-ink-700">{{ ucfirst($m->role) }}</span></p>@endif
        </div>
        <nav class="flex-1 space-y-0.5 p-3" aria-label="Main">
            @foreach ($nav as [$label, $route, $icon, $show])
                @continue(! $show)
                @php $active = request()->routeIs(str_replace(['.index', '.profile'], '', $route).'*'); @endphp
                <a href="{{ route($route) }}" @class(['flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm', 'bg-ink-900 font-medium text-white' => $active, 'text-ink-600 hover:bg-tile hover:text-ink-900' => ! $active]) @if($active) aria-current="page" @endif>
                    <x-icon :name="$icon" class="h-[18px] w-[18px] shrink-0" />
                    {{ $label }}
                </a>
            @endforeach
        </nav>
        <div class="border-t border-line p-4 text-sm">
            <a href="{{ route('public.home', $b) }}" target="_blank" class="flex items-center justify-between rounded-lg px-3 py-2 text-ink-700 hover:bg-tile">View customer page <x-icon name="arrow" class="h-4 w-4 -rotate-45" /></a>
            @if (auth()->user()->is_platform_admin)
                <a href="{{ route('admin.index') }}" class="link mt-2 block">Platform admin</a>
            @endif
        </div>
    </aside>
    <label for="nav-toggle" class="fixed inset-0 z-30 hidden bg-ink-900/40 [#nav-toggle:checked~div_&]:block lg:hidden" aria-hidden="true"></label>

    <div class="flex min-w-0 flex-1 flex-col">
        <header class="sticky top-0 z-20 flex h-16 items-center gap-3 border-b border-line bg-white/90 px-4 backdrop-blur sm:px-8">
            <label for="nav-toggle" class="cursor-pointer rounded-md border border-line px-3 py-2 text-sm lg:hidden" aria-label="Open menu">☰ Menu</label>
            <div class="min-w-0 flex-1 truncate text-[15px] font-semibold tracking-tight">@yield('heading')</div>
            <a href="{{ route('dashboard.overview') }}#notifications" class="relative flex h-10 w-10 items-center justify-center rounded-full bg-tile text-ink-700 hover:bg-ink-100" aria-label="Notifications">
                <x-icon name="bell" class="h-[18px] w-[18px]" /> @if ($unreadNotifications)<span class="absolute -top-0.5 -right-0.5 rounded-full bg-ink-900 px-1.5 text-[10px] font-semibold text-white ring-2 ring-white">{{ $unreadNotifications }}</span>@endif
            </a>
            <span class="hidden items-center gap-2.5 border-l border-line pl-4 text-sm sm:flex"><span class="flex h-9 w-9 items-center justify-center rounded-full bg-ink-900 text-xs font-semibold text-white">{{ collect(explode(' ', auth()->user()->name))->map(fn ($p) => mb_substr($p, 0, 1))->take(2)->implode('') }}</span><span class="font-medium">{{ auth()->user()->name }}</span></span>
            <form method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-secondary btn-sm">Sign out</button></form>
        </header>
        <main class="mx-auto w-full max-w-6xl flex-1 p-4 sm:p-8">
            @if ($b->status === 'closing')
                <div class="mb-4 rounded-lg border border-amber-300 bg-amber-50 p-3 text-sm text-amber-900">This business is closing. New customer submissions are stopped. Export your data before {{ $b->closing_requested_at->addDays(30)->toFormattedDateString() }}.</div>
            @elseif (! $b->activated_at)
                <div class="mb-5 flex flex-wrap items-center gap-2 rounded-xl bg-tile p-4 text-sm"><span class="badge badge-amber">Setup</span> Setup not finished — your customer page isn’t accepting requests yet. <a class="link" href="{{ route('dashboard.onboarding') }}">Continue setup</a></div>
            @endif
            <x-flash />
            @yield('content')
        </main>
        <footer class="px-8 pb-6 text-xs text-ink-400">Vendra local build · fictional data only · not production-ready</footer>
    </div>
</div>
@endsection
