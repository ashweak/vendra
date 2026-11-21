<?php

namespace App\Http\Controllers;

use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/** The signed-in user's own name, email and password, plus email verification. */
class AccountController extends Controller
{
    public function edit(Request $request)
    {
        return view('dashboard.account', ['user' => $request->user()]);
    }

    public function update(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'email' => ['required', 'email', 'max:190', Rule::unique('users', 'email')->ignore($user->id)],
            'current_password' => 'nullable|string',
        ]);

        $emailChanged = strtolower($data['email']) !== strtolower($user->email);
        if ($emailChanged && ! $request->filled('current_password')) {
            return back()->withInput()->withErrors(['current_password' => 'Enter your current password to change your email.']);
        }
        if ($emailChanged) {
            $request->validate(['current_password' => 'current_password']);
        }

        $user->name = $data['name'];
        if ($emailChanged) {
            $user->email = $data['email'];
            $user->email_verified_at = null;
        }
        $user->save();
        if ($emailChanged) {
            $user->sendEmailVerificationNotification();
        }
        AuditLogger::log(null, $emailChanged ? 'account.email_changed' : 'account.updated', $user);

        return back()->with('status', $emailChanged ? 'Saved. We sent a link to your new email — open it to verify the address.' : 'Saved.');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|current_password',
            'password' => ['required', 'confirmed', Password::min(10)],
        ]);
        $user = $request->user();
        $user->forceFill(['password' => $request->input('password'), 'remember_token' => Str::random(60)])->save();
        $request->session()->regenerate();
        AuditLogger::log(null, 'account.password_changed', $user);

        return back()->with('status', 'Password changed.');
    }

    public function verifyNotice(Request $request)
    {
        return $request->user()->hasVerifiedEmail()
            ? redirect()->route('dashboard.overview')
            : view('auth.verify-email');
    }

    public function verify(Request $request, string $id, string $hash)
    {
        $user = $request->user();
        abort_unless((string) $user->getKey() === $id && hash_equals(sha1($user->getEmailForVerification()), $hash), 403);
        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            AuditLogger::log(null, 'account.email_verified', $user);
        }

        return redirect()->route('dashboard.overview')->with('status', 'Email verified. Thank you!');
    }

    public function resendVerification(Request $request)
    {
        if (! $request->user()->hasVerifiedEmail()) {
            $request->user()->sendEmailVerificationNotification();
        }

        return back()->with('status', 'We sent a new verification link. It expires in 60 minutes.');
    }
}
