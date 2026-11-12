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
        <div class="dots relative flex h-full min-h-[36rem] flex-col items-center justify-center rounded-[32px] bg-tile p-10">
            <x-sticker class="absolute top-10 left-10" uid="guest" />
            <x-phone-chat />
            <p class="mt-8 max-w-sm text-center font-display text-2xl font-bold">The AI prepares. <span class="mark">You confirm.</span></p>
        </div>
    </div>
</div>
@endsection
