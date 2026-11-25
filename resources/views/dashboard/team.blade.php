@extends('layouts.dashboard')
@section('title', 'Team')
@section('heading', 'Team')
@section('content')
@php $days = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat']; @endphp
<div class="grid gap-4 lg:grid-cols-[1fr_20rem]">
    <div class="space-y-3">
        @foreach ($members as $m)
            <div class="card">
                <div class="flex flex-wrap items-center gap-3">
                    <x-avatar :user="$m->user" class="h-10 w-10 text-sm" />
                    <span class="min-w-0 flex-1"><span class="block font-medium">{{ $m->user->name }}</span><span class="block text-xs text-ink-500">{{ $m->user->email }}</span></span>
                    <span class="badge badge-indigo">{{ ucfirst($m->role) }}</span>
                    @unless ($m->is_active)<span class="badge badge-gray">Disabled</span>@endunless
                    <span @class(['badge', 'badge-green' => $m->user->hasMfa(), 'badge-gray' => ! $m->user->hasMfa()])>Two-step {{ $m->user->hasMfa() ? 'on' : 'off' }}</span>
                </div>
                @if ($isOwner && $m->role !== 'owner' && $m->user->hasMfa())
                    <form method="POST" action="{{ route('dashboard.team.reset_mfa', $m->id) }}" class="mt-2" onsubmit="return confirm({{ \Illuminate\Support\Js::from('Reset two-step sign-in for '.$m->user->name.'? Use this when they lost their phone.') }})">@csrf
                        <button class="link text-xs">Reset two-step sign-in (lost phone)</button>
                    </form>
                @endif
                @if ($canManageTeam && $m->role !== 'owner')
                    <form method="POST" action="{{ route('dashboard.team.update', $m->id) }}" class="mt-3 flex flex-wrap items-center gap-2 text-sm">@csrf @method('PUT')
                        <select name="role" class="input w-36" aria-label="Role"><option value="manager" @selected($m->role === 'manager')>Manager</option><option value="staff" @selected($m->role === 'staff')>Staff</option></select>
                        <select name="is_active" class="input w-36" aria-label="Access"><option value="1" @selected($m->is_active)>Active</option><option value="0" @selected(! $m->is_active)>Disabled</option></select>
                        <button class="btn btn-secondary btn-sm">Save</button>
                    </form>
                @endif
                <details class="mt-3"><summary class="cursor-pointer text-sm font-medium text-ink-700">Weekly schedule</summary>
                    @php $sched = ($schedules[$m->user_id] ?? collect())->keyBy('weekday'); @endphp
                    <form method="POST" action="{{ route('dashboard.team.schedule', $m->user_id) }}" class="mt-3 space-y-2">@csrf
                        @foreach ($days as $d => $label)
                            <div class="grid grid-cols-[3rem_1fr_1fr] items-center gap-2 text-sm"><span>{{ $label }}</span>
                                <input type="time" name="days[{{ $d }}][start]" class="input" value="{{ isset($sched[$d]) ? substr($sched[$d]->starts_at, 0, 5) : '' }}" aria-label="{{ $label }} start">
                                <input type="time" name="days[{{ $d }}][end]" class="input" value="{{ isset($sched[$d]) ? substr($sched[$d]->ends_at, 0, 5) : '' }}" aria-label="{{ $label }} end"></div>
                        @endforeach
                        <button class="btn btn-secondary btn-sm">Save schedule</button>
                    </form>
                </details>
            </div>
        @endforeach
    </div>
    @if ($canManageTeam)
    <div class="space-y-4">
    @if ($isOwner)
        <form method="POST" action="{{ route('dashboard.team.security') }}" class="card h-fit space-y-2">@csrf
            <h2 class="h2">Sign-in security</h2>
            <label class="flex items-start gap-2 text-sm"><input type="hidden" name="require_staff_mfa" value="0"><input type="checkbox" name="require_staff_mfa" value="1" class="mt-1" @checked($requireMfa)> <span>Require two-step sign-in (authenticator app code) for managers and staff too. Owners always use it.</span></label>
            <button class="btn btn-secondary btn-sm">Save</button>
        </form>
    @endif
    <form method="POST" action="{{ route('dashboard.team.store') }}" class="card h-fit space-y-3">@csrf
        <h2 class="h2">Add team member</h2>
        <p class="hint">Plan allows {{ $staffLimit }} besides the owner. Managers can approve bookings and orders; staff only see work assigned to them.</p>
        <input name="name" class="input" placeholder="Name" required>
        <input type="email" name="email" class="input" placeholder="Email" required>
        <select name="role" class="input" aria-label="Role"><option value="staff">Staff</option><option value="manager">Manager</option></select>
        <button class="btn btn-primary w-full">Add</button>
    </form>
    </div>
    @endif
</div>
@endsection
