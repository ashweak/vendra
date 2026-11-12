@extends('layouts.base')
@section('title', 'From inquiry to opportunity')
@section('body')
@php $firstDemo = $demos->first(); @endphp

{{-- Floating pill navigation --}}
<div class="sticky top-0 z-30 px-3 pt-3 sm:px-5">
    <nav class="mx-auto flex max-w-6xl items-center gap-2 rounded-full border border-line bg-white/85 py-2 pr-2 pl-5 shadow-[0_8px_30px_-18px_rgba(23,22,18,.35)] backdrop-blur" aria-label="Main">
        <a href="{{ route('landing') }}" class="mr-auto md:mr-0"><x-logo /></a>
        <div class="mx-auto hidden items-center gap-1 text-sm md:flex">
            @foreach (['#why' => 'Why Vendra', '#how' => 'How it works', '#demos' => 'Demos'] as $href => $label)
                <a href="{{ $href }}" class="rounded-full px-4 py-2 text-ink-600 hover:bg-tile hover:text-ink-900">{{ $label }}</a>
            @endforeach
        </div>
        @auth
            <a href="{{ route('dashboard.overview') }}" class="btn btn-primary btn-sm">Open dashboard</a>
        @else
            <a href="{{ route('login') }}" class="btn btn-sm text-ink-700 hover:bg-tile">Sign in</a>
            <a href="{{ route('register') }}" class="btn btn-primary btn-sm">Start free</a>
        @endauth
    </nav>
</div>

<main>
    {{-- Hero --}}
    <section class="mx-auto grid max-w-6xl items-center gap-12 px-5 pt-14 pb-16 sm:px-8 lg:grid-cols-[1.05fr_1fr] lg:pt-20">
        <div>
            <span class="eyebrow">For small businesses in the Philippines</span>
            <h1 class="display mt-6">From inquiry to <span class="mark">opportunity</span>.</h1>
            <p class="lead mt-6 max-w-lg">Vendra replies to your customers in English, Tagalog and Taglish — and turns each chat into a booking, order, quotation or service request that your team approves.</p>
            <div class="mt-8 flex flex-wrap items-center gap-3">
                <a href="{{ route('register') }}" class="btn btn-sun px-6">Start free <x-icon name="arrow" class="h-4 w-4" /></a>
                @if ($firstDemo)
                    <a href="{{ route('public.home', $firstDemo) }}" class="btn-play"><span class="btn-play-icon"><x-icon name="play" class="h-3.5 w-3.5" /></span> Try a live shop</a>
                @endif
            </div>
            <div class="mt-10 flex flex-wrap items-center gap-x-6 gap-y-3 text-sm text-ink-500">
                <span class="flex items-center gap-2"><x-icon name="check" class="h-4 w-4 text-leaf" /> No account needed for customers</span>
                <span class="flex items-center gap-2"><x-icon name="check" class="h-4 w-4 text-leaf" /> Staff stay in control</span>
            </div>
        </div>
        <div class="dots relative rounded-[32px] bg-tile px-6 pt-12 pb-10 sm:px-10">
            <x-sticker class="absolute -top-8 left-4 hidden sm:inline-flex" uid="hero" />
            <x-phone-chat />
            <div class="absolute -bottom-6 -left-3 hidden w-52 rounded-2xl bg-white p-3.5 shadow-[0_20px_40px_-20px_rgba(23,22,18,.4)] sm:block lg:-left-8">
                <p class="text-[11px] font-semibold tracking-wider text-ink-400 uppercase">New booking</p>
                <p class="mt-1 font-display text-lg font-bold">APT-7KQ2LM</p>
                <span class="badge badge-green mt-1">Confirmed</span>
            </div>
            <div class="absolute top-16 -right-3 hidden rounded-2xl bg-ink-900 p-3.5 text-white shadow-lg sm:block lg:-right-6">
                <p class="text-[11px] text-white/60">Discount request</p>
                <p class="mt-0.5 text-sm font-semibold">→ Sent to owner</p>
            </div>
        </div>
    </section>

    {{-- Category chips --}}
    <section class="mx-auto max-w-6xl px-5 py-10 text-center sm:px-8" aria-label="Who it’s for">
        <p class="text-sm text-ink-500">Made for the shops on your street</p>
        <div class="mt-5 flex flex-wrap justify-center gap-2.5">
            @foreach ([['Salons', 'spark'], ['Barbershops', 'spark'], ['Nail studios', 'spark'], ['Tutorial centers', 'doc'], ['Beauty shops', 'bag'], ['Clothing stores', 'bag'], ['Cleaning services', 'tool'], ['Aircon services', 'tool'], ['Repair shops', 'tool'], ['Bakeries', 'bag']] as [$c, $i])
                <span class="chip"><x-icon :name="$i" class="h-4 w-4 text-ink-500" /> {{ $c }}</span>
            @endforeach
        </div>
    </section>

    {{-- Bento --}}
    <section id="why" class="mx-auto max-w-6xl scroll-mt-24 px-5 pt-16 sm:px-8">
        <div class="max-w-2xl">
            <span class="eyebrow">Why Vendra</span>
            <h2 class="section-title mt-4">Quick for customers. Clean for your team.</h2>
        </div>
        <div class="mt-10 grid gap-4 md:grid-cols-6">
            <div class="rounded-[28px] bg-ink-900 p-7 text-white md:col-span-4">
                <span class="eyebrow text-white/70">Grounded answers</span>
                <h3 class="mt-4 max-w-md text-3xl font-bold">It only answers from your real catalog.</h3>
                <p class="mt-3 max-w-md text-sm text-white/60">Prices, hours and open times come straight from your dashboard. No guessing, no made-up promos.</p>
                <div class="mt-7 grid gap-2 sm:grid-cols-3">
                    @foreach ([['Haircut', '₱250', '45 min'], ['Hair Spa', '₱600', '60 min'], ['Manicure', '₱150', '30 min']] as [$n, $p, $d])
                        <div class="rounded-2xl bg-white/[.07] p-4"><p class="text-sm text-white/60">{{ $n }}</p><p class="mt-1 font-display text-2xl font-bold">{{ $p }}</p><p class="text-xs text-white/40">{{ $d }}</p></div>
                    @endforeach
                </div>
            </div>
            <div class="flex flex-col justify-between rounded-[28px] bg-sun p-7 md:col-span-2">
                <x-icon name="globe" class="h-7 w-7" />
                <div>
                    <p class="font-display text-6xl font-bold">3</p>
                    <p class="mt-1 font-medium">languages: English, Tagalog and natural Taglish.</p>
                </div>
            </div>
            @foreach ([
                ['calendar', 'No double bookings', 'Staff schedules and locked availability checks before every save.'],
                ['bag', 'Stock that adds up', 'Reserved when ordered, deducted once at handover, released on cancel.'],
                ['doc', 'Quotes you approve', 'Versioned quotations. Customers accept one exact version.'],
            ] as [$icon, $t, $b])
                <div class="card p-7 md:col-span-2">
                    <span class="icon-chip"><x-icon :name="$icon" class="h-5 w-5" /></span>
                    <h3 class="mt-8 text-xl font-bold">{{ $t }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-ink-500">{{ $b }}</p>
                </div>
            @endforeach
        </div>

        {{-- Stats band --}}
        <dl class="mt-4 grid divide-y divide-line rounded-[28px] border border-line bg-white sm:grid-cols-2 sm:divide-y-0 lg:grid-cols-4 lg:divide-x">
            @foreach ([['24/7', 'answers about prices, hours and open times'], ['4', 'workflows: bookings, orders, quotes, requests'], ['100%', 'of submissions confirmed by the customer'], ['0', 'prices the AI can change on its own']] as [$v, $l])
                <div class="p-7"><dt class="sr-only">{{ $l }}</dt><dd class="font-display text-4xl font-bold">{{ $v }}</dd><p class="mt-1 text-sm text-ink-500">{{ $l }}</p></div>
            @endforeach
        </dl>
    </section>

    {{-- How it works: timeline --}}
    <section id="how" class="mx-auto max-w-6xl scroll-mt-24 px-5 pt-28 sm:px-8">
        <div class="flex flex-wrap items-end justify-between gap-6">
            <div class="max-w-xl">
                <span class="eyebrow">How it works</span>
                <h2 class="section-title mt-4">The AI prepares. People confirm.</h2>
            </div>
            <a href="{{ route('register') }}" class="btn btn-primary">Set up your shop</a>
        </div>
        <ol class="relative mt-14 grid gap-10 md:grid-cols-3 md:gap-8">
            <span class="absolute top-6 right-[16%] left-[16%] hidden border-t-2 border-dashed border-ink-200 md:block" aria-hidden="true"></span>
            @foreach ([
                ['chat', 'Customer asks', 'In Taglish, any time — through chat or a simple form. No account needed.'],
                ['spark', 'Vendra prepares', 'Checks live prices and open times, then prepares a draft. Discounts and complaints go to staff.'],
                ['check', 'You stay in charge', 'The customer confirms, your team approves, and everything stays on record.'],
            ] as $i => [$icon, $t, $b])
                <li class="relative text-center">
                    <span class="relative mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-sun ring-8 ring-paper"><x-icon :name="$icon" class="h-5 w-5" /></span>
                    <p class="mt-5 text-xs font-semibold tracking-widest text-ink-400">STEP {{ $i + 1 }}</p>
                    <h3 class="mt-2 text-xl font-bold">{{ $t }}</h3>
                    <p class="mx-auto mt-2 max-w-xs text-sm leading-relaxed text-ink-500">{{ $b }}</p>
                </li>
            @endforeach
        </ol>
    </section>

    {{-- Demos --}}
    @if ($demos->isNotEmpty())
    <section id="demos" class="mx-auto max-w-6xl scroll-mt-24 px-5 pt-28 pb-20 sm:px-8">
        <span class="eyebrow">Live demos</span>
        <h2 class="section-title mt-4">Walk into a demo shop</h2>
        <p class="lead mt-3 max-w-lg">Fictional shops with fictional data. Open the chat and ask anything.</p>
        <div class="mt-10 grid gap-4 sm:grid-cols-3">
            @foreach ($demos as $d)
                <a href="{{ route('public.home', $d) }}" class="group card flex flex-col p-6 transition hover:-translate-y-1 hover:shadow-[0_20px_40px_-25px_rgba(23,22,18,.4)]">
                    <div class="dots flex h-28 items-center justify-center rounded-2xl bg-tile">
                        <span class="flex h-14 w-14 items-center justify-center rounded-2xl font-display text-2xl font-bold text-white" style="background: {{ $d->accent_color }}">{{ mb_substr($d->name, 0, 1) }}</span>
                    </div>
                    <p class="mt-5 font-display text-xl font-bold">{{ $d->name }}</p>
                    <p class="text-sm text-ink-500">{{ config('vendra.categories.'.$d->category.'.label') }}</p>
                    <span class="mt-5 inline-flex items-center gap-1.5 text-sm font-semibold">Visit shop <x-icon name="arrow" class="h-4 w-4 transition group-hover:translate-x-1" /></span>
                </a>
            @endforeach
        </div>
    </section>
    @endif
</main>

<footer class="px-3 pb-3 sm:px-5 sm:pb-5">
    <div class="relative mx-auto max-w-6xl overflow-hidden rounded-[32px] bg-sun px-7 py-14 sm:px-12 sm:py-16">
        <div class="relative z-10 max-w-xl">
            <h2 class="font-display text-4xl leading-[1.05] font-bold sm:text-5xl">Ready when your customers are.</h2>
            <div class="mt-8 flex flex-wrap gap-3">
                <a href="{{ route('register') }}" class="btn btn-primary px-6">Start free</a>
                <a href="{{ route('login') }}" class="btn border border-ink-900/20 text-ink-900 hover:bg-white/40">Sign in</a>
            </div>
        </div>
        <x-sticker class="absolute top-1/2 right-12 hidden -translate-y-1/2 scale-125 md:inline-flex" tone="white" uid="footer" text="FROM INQUIRY TO OPPORTUNITY · VENDRA · " />
    </div>
    <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-3 px-2 py-5 text-xs text-ink-500">
        <x-logo />
        <span>Working name · local build with fictional data. Increased income is not guaranteed.</span>
    </div>
</footer>
@endsection
