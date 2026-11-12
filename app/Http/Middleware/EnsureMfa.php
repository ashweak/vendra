<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureMfa
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if ($user && $user->isDisabled()) {
            Auth::logout();
            $request->session()->invalidate();

            return redirect()->route('login')->withErrors(['email' => 'This account has been disabled.']);
        }
        if ($user && config('vendra.enforce_mfa') && $user->requiresMfa()) {
            if (! $user->hasMfa()) {
                return redirect()->route('mfa.setup');
            }
            if (! $request->session()->get('mfa_passed')) {
                return redirect()->route('mfa.challenge');
            }
        }

        return $next($request);
    }
}
