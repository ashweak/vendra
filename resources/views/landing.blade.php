@extends('layouts.base')
@section('title', 'From inquiry to opportunity')
@section('body')
@php $firstDemo = $demos->first(); @endphp
<div class="mx-auto max-w-6xl px-3 pt-3 sm:px-5 sm:pt-5">
    <header class="flex flex-wrap items-stretch rounded-2xl border border-line bg-white">
        <a href="{{ route('landing') }}" class="flex items-center px-5 py-4 md:border-r md:border-line"><x-logo /></a>
        <nav class="order-3 flex w-full flex-wrap justify-center gap-x-1 border-t border-line px-3 py-2 text-sm md:order-none md:w-auto md:flex-1 md:border-t-0 md:py-0" aria-label="Main">
            <a href="#about" class="flex items-center px-3 py-2 text-ink-600 hover:text-ink-900 md:py-0">About</a>
            <a href="#features" class="flex items-center px-3 py-2 text-ink-600 hover:text-ink-900 md:py-0">Features</a>
            <a href="#how" class="flex items-center px-3 py-2 text-ink-600 hover:text-ink-900 md:py-0">How it works</a>
            <a href="#demos" class="flex items-center px-3 py-2 text-ink-600 hover:text-ink-900 md:py-0">Demos</a>
        </nav>
        <div class="ml-auto flex items-center gap-2 px-4 py-3 md:ml-0 md:border-l md:border-line">
            @auth
                <a href="{{ route('dashboard.overview') }}" class="btn btn-primary btn-sm">Open dashboard</a>
            @else
                <a href="{{ route('login') }}" class="btn btn-secondary btn-sm">Sign in</a>
                <a href="{{ route('register') }}" class="btn btn-primary btn-sm">Get started</a>
            @endauth
        </div>
    </header>
</div>

<main>
    {{-- Hero --}}
    <section class="mx-auto grid max-w-6xl items-center gap-12 px-5 pt-14 pb-20 sm:px-8 lg:grid-cols-[1fr_1.05fr] lg:pt-20">
        <div>
            <span class="eyebrow">AI assistant for Philippine small businesses</span>
            <h1 class="display mt-5">From inquiry to opportunity.</h1>
            <p class="lead mt-5 max-w-md">Vendra answers your customers in English, Tagalog and Taglish — then turns every chat into a booking, order, quotation or service request your team can trust.</p>
            <div class="mt-8 flex flex-wrap items-center gap-6">
                <a href="{{ route('register') }}" class="btn btn-primary">Get started</a>
                @if ($firstDemo)
                    <a href="{{ route('public.home', $firstDemo) }}" class="btn-play"><span class="btn-play-icon"><x-icon name="play" class="h-4 w-4" /></span> See a live demo</a>
                @endif
            </div>
        </div>
        <div class="notch art-warm grain aspect-[6/5] w-full" style="--nw: 40%; --nh: 56px">
            <span class="notch-slot"><span class="eyebrow ring-1 ring-line"><span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> Live in your shop</span></span>
            <div class="absolute bottom-6 left-6 z-[6] w-[min(22rem,calc(100%-3rem))] space-y-2.5 text-sm">
                <div class="ml-auto w-fit max-w-[85%] rounded-2xl rounded-tr-md bg-ink-900 px-4 py-2.5 text-white">Pa-book po ng haircut bukas 3pm</div>
                <div class="w-fit max-w-[92%] rounded-2xl rounded-tl-md bg-white px-4 py-3">
                    <p class="text-[11px] font-medium text-ink-400">AI assistant</p>
                    <p>Bakante po ang Haircut bukas, 3:00 PM. I-tap ang “Review & confirm” para matapos.</p>
                    <div class="mt-2.5 rounded-xl bg-tile p-3">
                        <p class="font-semibold">Haircut · ₱250.00</p>
                        <p class="text-xs text-ink-500">With Joy · 45 min</p>
                        <span class="btn btn-primary btn-sm mt-2 w-full">Review & confirm</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Strip --}}
    <section class="relative" aria-label="Who it’s for">
        <div class="absolute inset-x-0 top-0 z-10 flex -translate-y-1/2 justify-center"><span class="eyebrow border border-line bg-white">Built for small businesses across the Philippines</span></div>
        <div class="marquee">
            <div class="marquee-track">
                @foreach ([1, 2] as $_)
                    @foreach (['Salons', 'Barbershops', 'Nail studios', 'Tutorial centers', 'Beauty shops', 'Clothing stores', 'Cleaning services', 'Aircon services', 'Repair shops', 'Photo studios', 'Bakeries'] as $c)
                        <span class="text-ink-300" aria-hidden="true">•</span><span>{{ $c }}</span>
                    @endforeach
                @endforeach
            </div>
        </div>
    </section>

    {{-- About --}}
    <section id="about" class="mx-auto max-w-6xl scroll-mt-6 px-5 pt-24 sm:px-8">
        <div class="text-center">
            <span class="eyebrow"><x-icon name="spark" class="h-3.5 w-3.5" /> About Vendra</span>
            <h2 class="section-title mt-4">What we do. Learn how it works.</h2>
            <p class="lead mx-auto mt-3 max-w-xl">Fast answers for your customers, clean records for your team. The AI prepares — people confirm.</p>
        </div>
        <div class="mt-12 grid gap-6 lg:grid-cols-2">
            <div class="notch notch-bl art-cool grain min-h-[24rem] p-6 text-white sm:p-7" style="--nw: 44%; --nh: 64px">
                <p class="text-sm text-white/60">Today at Lumina Studio</p>
                <div class="mt-4 grid grid-cols-2 gap-3">
                    @foreach ([['2', 'Waiting for staff'], ['5', 'Bookings today'], ['1', 'Quote to approve'], ['37', 'AI replies']] as [$n, $l])
                        <div class="rounded-xl bg-white/10 p-4"><p class="text-2xl font-semibold">{{ $n }}</p><p class="text-xs text-white/60">{{ $l }}</p></div>
                    @endforeach
                </div>
                <div class="mt-3 space-y-2">
                    @foreach ([['10:00 AM', 'Haircut', 'Andrea · Joy', 'Confirmed'], ['2:00 PM', 'Hair Color', 'Carlo · Joy', 'Needs approval']] as [$t, $s, $w, $st])
                        <div class="flex items-center gap-3 rounded-xl bg-white/10 px-4 py-3 text-sm"><span class="w-16 font-medium">{{ $t }}</span><span class="flex-1">{{ $s }}<span class="block text-xs text-white/50">{{ $w }}</span></span><span class="rounded-full bg-white/15 px-2.5 py-0.5 text-xs">{{ $st }}</span></div>
                    @endforeach
                </div>
            </div>
            <div class="flex flex-col gap-4">
                <div class="grid flex-1 gap-4 sm:grid-cols-2">
                    <div class="stat"><div class="flex items-start gap-4"><x-icon name="clock" class="stat-icon" /><div><p class="stat-value">24/7</p><p class="stat-label">Answers about prices, hours and open times</p></div></div></div>
                    <div class="stat"><div class="flex items-start gap-4"><x-icon name="globe" class="stat-icon" /><div><p class="stat-value">3</p><p class="stat-label">Languages: English, Tagalog and Taglish</p></div></div></div>
                    <div class="stat"><div class="flex items-start gap-4"><x-icon name="briefcase" class="stat-icon" /><div><p class="stat-value">4</p><p class="stat-label">Workflows: bookings, orders, quotes, requests</p></div></div></div>
                    <div class="stat"><div class="flex items-start gap-4"><x-icon name="shield" class="stat-icon" /><div><p class="stat-value">100%</p><p class="stat-label">Of submissions confirmed by the customer</p></div></div></div>
                </div>
                <div class="flex flex-wrap items-center gap-5">
                    <a href="#features" class="btn btn-primary">More about Vendra</a>
                    <span class="flex items-center gap-3"><span class="flex h-10 w-10 items-center justify-center rounded-full bg-tile"><x-icon name="chat" class="h-4 w-4" /></span><span class="leading-tight"><span class="block text-[11px] text-ink-500">Try it now</span><span class="block text-sm font-semibold">Chat with a demo shop</span></span></span>
                </div>
            </div>
        </div>
    </section>

    {{-- Features --}}
    <section id="features" class="mx-auto max-w-6xl scroll-mt-6 px-5 pt-28 sm:px-8">
        <div class="flex flex-wrap items-end justify-between gap-6">
            <div>
                <span class="eyebrow"><x-icon name="folder" class="h-3.5 w-3.5" /> Features</span>
                <h2 class="section-title mt-4 max-w-xl">Everything between “Hi po” and “Confirmed”.</h2>
            </div>
            <p class="lead max-w-sm">One place for chats, bookings, orders and quotations — with rules your team can rely on.</p>
        </div>
        <div class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ([
                ['chat', 'AI that knows your shop', 'Replies only from your live catalog, schedules and published policies. No made-up prices.'],
                ['calendar', 'No double bookings', 'Staff schedules, approval deadlines and locked availability checks before every save.'],
                ['bag', 'Orders with real stock', 'Stock is reserved when a customer orders and deducted once at handover.'],
                ['doc', 'Versioned quotations', 'Draft, owner-approve and send. Customers accept a specific version.'],
                ['users', 'Your team, your rules', 'Owner, manager and staff roles. Staff see only the work assigned to them.'],
                ['shield', 'Safe by design', 'Two-factor login for owners, activity logs, and an AI that can’t change prices or verify payments.'],
            ] as [$icon, $title, $body])
                <div class="tile flex flex-col">
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-white"><x-icon :name="$icon" class="h-5 w-5" /></span>
                    <h3 class="mt-6 text-lg font-semibold">{{ $title }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-ink-600">{{ $body }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- How it works --}}
    <section id="how" class="mx-auto max-w-6xl scroll-mt-6 px-5 pt-28 sm:px-8">
        <div class="text-center">
            <span class="eyebrow"><x-icon name="list" class="h-3.5 w-3.5" /> How it works</span>
            <h2 class="section-title mt-4">Three steps. No surprises.</h2>
        </div>
        <ol class="mt-12 grid gap-px overflow-hidden rounded-2xl border border-line bg-line md:grid-cols-3">
            @foreach ([
                ['Customer asks', 'They chat in Taglish, browse your catalog or open a form — no account needed.'],
                ['AI prepares', 'Vendra checks live prices and availability and prepares a draft. Complaints and discounts go to staff.'],
                ['Customer confirms, you approve', 'The customer reviews and confirms. Your team approves, fulfills and keeps everything on record.'],
            ] as $i => [$title, $body])
                <li class="bg-white p-7">
                    <span class="text-sm font-medium text-ink-400">0{{ $i + 1 }}</span>
                    <h3 class="mt-8 text-xl font-semibold">{{ $title }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-ink-600">{{ $body }}</p>
                </li>
            @endforeach
        </ol>
    </section>

    {{-- Demos --}}
    @if ($demos->isNotEmpty())
    <section id="demos" class="mx-auto max-w-6xl scroll-mt-6 px-5 pt-28 pb-24 sm:px-8">
        <div class="flex flex-wrap items-end justify-between gap-6">
            <div>
                <span class="eyebrow"><x-icon name="play" class="h-3.5 w-3.5" /> Live demos</span>
                <h2 class="section-title mt-4">Try a demo business</h2>
            </div>
            <p class="lead max-w-sm">Fictional shops with fictional data. Open the chat and ask anything.</p>
        </div>
        <div class="mt-10 grid gap-4 sm:grid-cols-3">
            @foreach ($demos as $d)
                <a href="{{ route('public.home', $d) }}" class="group tile flex flex-col transition hover:bg-ink-100">
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl text-base font-semibold text-white" style="background: {{ $d->accent_color }}">{{ mb_substr($d->name, 0, 1) }}</span>
                    <p class="mt-6 text-lg font-semibold">{{ $d->name }}</p>
                    <p class="text-sm text-ink-500">{{ config('vendra.categories.'.$d->category.'.label') }}</p>
                    <span class="mt-6 inline-flex items-center gap-1.5 text-sm font-medium">Open demo <x-icon name="arrow" class="h-4 w-4 transition group-hover:translate-x-1" /></span>
                </a>
            @endforeach
        </div>
    </section>
    @endif
</main>

<footer class="mx-3 mb-3 rounded-2xl bg-ink-900 text-white sm:mx-5 sm:mb-5">
    <div class="mx-auto flex max-w-6xl flex-wrap items-end justify-between gap-8 px-6 py-12 sm:px-8">
        <div>
            <x-logo :dark="true" />
            <p class="mt-4 max-w-sm text-3xl font-semibold tracking-tight">From inquiry to opportunity.</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('register') }}" class="btn bg-white text-ink-900 hover:bg-ink-100">Get started</a>
            <a href="{{ route('login') }}" class="btn border border-white/20 text-white hover:bg-white/10">Sign in</a>
        </div>
    </div>
    <div class="mx-auto max-w-6xl border-t border-white/10 px-6 py-5 text-xs text-white/50 sm:px-8">Vendra · working name · local build for demonstration with fictional data. Increased income is not guaranteed.</div>
</footer>
@endsection
