@extends('layouts.base')
@section('title', 'From inquiry to opportunity')
@section('body')
<div class="bg-white">
    <header class="mx-auto flex max-w-6xl items-center justify-between px-4 py-5">
        <x-logo />
        <nav class="flex gap-2 text-sm">
            @auth<a href="{{ route('dashboard.overview') }}" class="btn btn-primary">Open dashboard</a>
            @else<a href="{{ route('login') }}" class="btn btn-secondary">Sign in</a><a href="{{ route('register') }}" class="btn btn-primary">Get started</a>@endauth
        </nav>
    </header>
    <section class="mx-auto max-w-6xl px-4 pt-10 pb-16">
        <p class="text-sm font-semibold text-indigo-700">For small businesses in the Philippines</p>
        <h1 class="mt-2 max-w-3xl text-4xl font-extrabold tracking-tight sm:text-5xl">From inquiry to opportunity.</h1>
        <p class="mt-4 max-w-2xl text-lg text-gray-600">Vendra is an AI business assistant that answers customer questions in English, Tagalog and Taglish — and turns them into bookings, orders, quotations and service requests your team can trust.</p>
        <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ([
                ['Accurate answers', 'Replies come only from your live catalog, schedules and published policies.'],
                ['Customer confirms', 'The AI prepares drafts. Customers review and confirm every booking or order.'],
                ['Staff stay in control', 'Approvals, handoffs, discounts and payments stay with your team.'],
                ['Works without AI', 'Forms and staff workflows keep running if the AI is off or at its limit.'],
            ] as [$t, $d])
                <div class="card"><p class="font-semibold">{{ $t }}</p><p class="mt-1 text-sm text-gray-600">{{ $d }}</p></div>
            @endforeach
        </div>
    </section>
    @if ($demos->isNotEmpty())
    <section class="border-t border-gray-100 bg-gray-50">
        <div class="mx-auto max-w-6xl px-4 py-12">
            <h2 class="h2">Try a demo business</h2>
            <p class="mt-1 text-sm text-gray-600">Fictional businesses with fictional data.</p>
            <div class="mt-4 grid gap-3 sm:grid-cols-3">
                @foreach ($demos as $d)
                    <a href="{{ route('public.home', $d) }}" class="card block hover:border-indigo-300">
                        <span class="flex h-9 w-9 items-center justify-center rounded-lg font-bold text-white" style="background: {{ $d->accent_color }}">{{ mb_substr($d->name, 0, 1) }}</span>
                        <p class="mt-2 font-semibold">{{ $d->name }}</p>
                        <p class="text-sm text-gray-600">{{ config('vendra.categories.'.$d->category.'.label') }}</p>
                    </a>
                @endforeach
            </div>
        </div>
    </section>
    @endif
    <footer class="mx-auto max-w-6xl px-4 py-8 text-xs text-gray-500">Vendra · working name · local build for demonstration with fictional data. Increased income is not guaranteed.</footer>
</div>
@endsection
