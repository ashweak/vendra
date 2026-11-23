<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Spam protection for public forms, at no cost:
 *  - a honeypot field people never see (bots that fill every field are refused);
 *  - optional Cloudflare Turnstile (free) when TURNSTILE_SITE_KEY and TURNSTILE_SECRET_KEY are set.
 * A passed check is remembered for 30 minutes in the visitor's session, so the
 * review → confirm steps of one request only ask once.
 */
class HumanCheck
{
    public const HONEYPOT = 'company_website';

    private const SESSION_KEY = 'human_verified_until';

    public static function turnstileEnabled(): bool
    {
        return filled(config('services.turnstile.site_key')) && filled(config('services.turnstile.secret_key'));
    }

    /** Whether the Turnstile widget should be shown on the next form. */
    public static function needsChallenge(): bool
    {
        return self::turnstileEnabled() && ! self::recentlyVerified();
    }

    public static function recentlyVerified(): bool
    {
        $request = request();

        return $request->hasSession() && (int) $request->session()->get(self::SESSION_KEY, 0) > now()->timestamp;
    }

    public function honeypotFilled(Request $request): bool
    {
        return filled($request->input(self::HONEYPOT));
    }

    public function passes(Request $request): bool
    {
        if (! self::turnstileEnabled() || self::recentlyVerified()) {
            return true;
        }
        $token = $request->input('cf-turnstile-response');
        if (! is_string($token) || $token === '' || strlen($token) > 2048) {
            return false;
        }
        try {
            $response = Http::asForm()->timeout(5)->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                'secret' => config('services.turnstile.secret_key'),
                'response' => $token,
                'remoteip' => $request->ip(),
            ]);
        } catch (\Throwable $e) {
            // Cloudflare unreachable: don't turn real customers away; the honeypot still applies.
            Log::warning('Turnstile check skipped: '.$e->getMessage());

            return true;
        }
        if (! $response->json('success')) {
            return false;
        }
        $request->session()->put(self::SESSION_KEY, now()->addMinutes(30)->timestamp);

        return true;
    }
}
