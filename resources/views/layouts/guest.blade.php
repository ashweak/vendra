@extends('layouts.base')
@section('body')
<div class="grid min-h-full lg:grid-cols-2">
    <div class="flex flex-col px-6 py-8 sm:px-12">
        <a href="{{ route('landing') }}" class="self-start"><x-logo /></a>
        <div class="mx-auto flex w-full max-w-sm flex-1 flex-col justify-center py-12">
            <x-flash />
            @yield('content')
        </div>
        <p class="text-xs text-ink-400">Local development build · fictional data only</p>
    </div>
    <div class="hidden p-4 lg:block">
        <div class="notch art-warm grain relative h-full min-h-[36rem]" style="--nw: 42%; --nh: 64px">
            <span class="notch-slot"><span class="eyebrow">From inquiry to opportunity</span></span>
            <div class="absolute inset-x-8 bottom-8 z-[6] max-w-md rounded-2xl bg-white/90 p-6 backdrop-blur">
                <p class="text-2xl leading-snug font-semibold tracking-tight">“Magkano po ang haircut bukas ng 3pm?”</p>
                <p class="mt-3 text-sm text-ink-600">Vendra answers from your live prices and schedule, prepares the booking, and lets your customer confirm. You stay in control.</p>
            </div>
        </div>
    </div>
</div>
@endsection
