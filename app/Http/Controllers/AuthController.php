<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AuditLogger;
use App\Support\Totp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate(['email' => 'required|email', 'password' => 'required']);
        $key = 'login:'.strtolower($data['email']).'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()->withErrors(['email' => 'Too many attempts. Try again in '.RateLimiter::availableIn($key).' seconds.'])->onlyInput('email');
        }
        if (! Auth::attempt($data, $request->boolean('remember'))) {
            RateLimiter::hit($key, 60);

            return back()->withErrors(['email' => 'These credentials do not match our records.'])->onlyInput('email');
        }
        RateLimiter::clear($key);
        $request->session()->regenerate();
        if (Auth::user()->isDisabled()) {
            Auth::logout();

            return back()->withErrors(['email' => 'This account has been disabled.']);
        }

        return redirect()->intended(Auth::user()->is_platform_admin && ! Auth::user()->memberships()->exists() ? route('admin.index') : route('dashboard.overview'));
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:190|unique:users,email',
            'password' => ['required', 'confirmed', Password::min(10)],
        ]);
        $user = User::create($data);
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('onboarding.business');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function showMfaSetup(Request $request)
    {
        $user = $request->user();
        if ($user->hasMfa()) {
            return redirect()->route('mfa.challenge');
        }
        $secret = $request->session()->get('mfa_pending_secret') ?? Totp::generateSecret();
        $request->session()->put('mfa_pending_secret', $secret);

        return view('auth.mfa-setup', ['secret' => $secret, 'uri' => Totp::uri($secret, $user->email)]);
    }

    public function confirmMfaSetup(Request $request)
    {
        $request->validate(['code' => 'required|string']);
        $secret = $request->session()->get('mfa_pending_secret');
        if (! $secret || ! Totp::verify($secret, $request->input('code'))) {
            return back()->withErrors(['code' => 'That code is not valid. Check your authenticator app’s time and try again.']);
        }
        $user = $request->user();
        $user->mfa_secret = $secret;
        $user->mfa_enabled_at = now();
        $user->save();
        $request->session()->forget('mfa_pending_secret');
        $request->session()->put('mfa_passed', true);
        AuditLogger::log(null, 'user.mfa_enabled', $user);

        return redirect()->route('dashboard.overview')->with('status', 'Two-factor authentication is on.');
    }

    public function showMfaChallenge(Request $request)
    {
        if (! $request->user()->hasMfa()) {
            return redirect()->route('mfa.setup');
        }

        return view('auth.mfa-challenge');
    }

    public function verifyMfa(Request $request)
    {
        $request->validate(['code' => 'required|string']);
        $key = 'mfa:'.$request->user()->id;
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()->withErrors(['code' => 'Too many attempts. Wait a minute and try again.']);
        }
        if (! Totp::verify($request->user()->mfa_secret, $request->input('code'))) {
            RateLimiter::hit($key, 60);

            return back()->withErrors(['code' => 'That code is not valid.']);
        }
        RateLimiter::clear($key);
        $request->session()->put('mfa_passed', true);

        return redirect()->intended(route('dashboard.overview'));
    }
}
