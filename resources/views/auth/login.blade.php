@extends('layouts.guest')
@section('title', 'Sign in')
@section('content')
<h1 class="font-display text-3xl font-bold tracking-tight">Sign in</h1>
<form method="POST" action="{{ route('login') }}" class="mt-6 space-y-4">
    @csrf
    <div><label class="label" for="email">Email</label><input id="email" type="email" name="email" class="input" value="{{ old('email') }}" required autofocus autocomplete="username"></div>
    <div><label class="label" for="password">Password</label><input id="password" type="password" name="password" class="input" required autocomplete="current-password"></div>
    <div class="flex items-center justify-between gap-3 text-sm"><label class="flex items-center gap-2"><input type="checkbox" name="remember" value="1"> Keep me signed in</label><a href="{{ route('password.request') }}" class="font-medium underline decoration-sun decoration-2 underline-offset-4">Forgot password?</a></div>
    <button class="btn btn-primary w-full">Sign in</button>
</form>
@if (config('vendra.public_demo'))
    <div class="mt-6 rounded-2xl bg-sun-soft p-4 text-sm">
        <p class="font-semibold">Demo accounts <span class="font-normal text-ink-600">(password: <code>password</code>)</span></p>
        <div class="mt-3 grid gap-2">
            @foreach (['owner@lumina.test' => 'Salon owner', 'manager@lumina.test' => 'Salon manager', 'joy@lumina.test' => 'Salon staff', 'owner@ganda.test' => 'Shop owner', 'owner@kislap.test' => 'Cleaning owner', 'admin@vendra.test' => 'Platform admin'] as $email => $role)
                <button type="button" class="flex items-center justify-between rounded-xl bg-white px-3 py-2 text-left hover:ring-1 hover:ring-ink-300" data-demo="{{ $email }}">
                    <span>{{ $role }}</span><span class="text-xs text-ink-500">{{ $email }}</span>
                </button>
            @endforeach
        </div>
        <p class="mt-3 text-xs text-ink-600">Fictional data. Everything resets every 6 hours and whenever the server restarts. Changes that would affect other visitors are turned off.</p>
    </div>
    @push('scripts')
    <script>
    document.querySelectorAll('[data-demo]').forEach(b => b.addEventListener('click', () => {
        document.getElementById('email').value = b.dataset.demo;
        document.getElementById('password').value = 'password';
        document.getElementById('password').form.requestSubmit();
    }));
    </script>
    @endpush
@endif
<p class="mt-4 text-center text-sm text-ink-600">New business? <a class="link" href="{{ route('register') }}">Create an account</a></p>
@endsection
