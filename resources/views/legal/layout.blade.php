@extends('layouts.base')
@section('body')
<div class="min-h-dvh bg-paper">
    <header class="mx-auto flex max-w-3xl items-center justify-between px-5 py-6">
        <a href="{{ route('landing') }}"><x-logo /></a>
        <nav class="flex gap-4 text-sm text-ink-600"><a href="{{ route('legal.privacy') }}" class="hover:text-ink-900">Privacy</a><a href="{{ route('legal.terms') }}" class="hover:text-ink-900">Terms</a></nav>
    </header>
    <main class="mx-auto max-w-3xl px-5 pb-20">
        @yield('content')
    </main>
</div>
@endsection
