@extends('layouts.base')
@section('body')
<header class="border-b border-gray-200 bg-white">
    <div class="mx-auto flex max-w-6xl flex-wrap items-center gap-4 px-4 py-3">
        <x-logo /><span class="badge badge-amber">Platform admin</span>
        <nav class="flex flex-1 gap-1 text-sm"><a class="rounded-lg px-3 py-2 hover:bg-gray-100" href="{{ route('admin.index') }}">Accounts</a><a class="rounded-lg px-3 py-2 hover:bg-gray-100" href="{{ route('admin.logs') }}">Admin log</a></nav>
        <form method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-secondary btn-sm">Sign out</button></form>
    </div>
</header>
<main class="mx-auto max-w-6xl p-4 sm:p-6"><x-flash />@yield('content')</main>
@endsection
