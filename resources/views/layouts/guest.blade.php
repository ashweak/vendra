@extends('layouts.base')
@section('body')
<div class="dots flex min-h-dvh flex-col bg-tile px-6 py-8 sm:px-12">
    <a href="{{ route('landing') }}" class="self-start"><x-logo /></a>
    <div class="mx-auto flex w-full max-w-5xl flex-1 items-center justify-center gap-16 py-12">
        <div class="w-full max-w-md">
            <x-flash />
            @yield('content')
            <div class="mt-8 border-t border-line pt-5">
                <p class="flex flex-wrap gap-x-4 gap-y-1 text-xs text-ink-500"><span>✓ Answers from your own catalog</span><span>✓ English, Tagalog &amp; Taglish</span><span>✓ Staff stay in control</span></p>
            </div>
        </div>
        <div class="relative hidden shrink-0 flex-col items-center lg:flex" aria-hidden="true">
            <x-sticker class="absolute -top-10 -left-12 hidden ring-8 ring-tile lg:inline-flex" uid="guest" />
            <x-phone-chat />
            <p class="mt-7 max-w-sm text-center font-display text-2xl font-bold leading-tight">The AI prepares. <span class="mark">You confirm.</span></p>
        </div>
    </div>
    <p class="text-center text-xs text-ink-400">{{ config('vendra.public_demo') ? 'Public demo · fictional data only' : 'Local development build · fictional data only' }}</p>
</div>
@endsection
