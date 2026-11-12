@extends('layouts.base')
@section('body')
<div class="flex min-h-full flex-col items-center justify-center px-4 py-10">
    <a href="{{ route('landing') }}" class="mb-6 text-xl"><x-logo /></a>
    <div class="w-full max-w-md">
        <x-flash />
        <div class="card p-6">@yield('content')</div>
    </div>
    <p class="mt-6 text-xs text-gray-400">Local development build · fictional data only</p>
</div>
@endsection
