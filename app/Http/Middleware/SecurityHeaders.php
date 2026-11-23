<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Browser security headers for every web response.
 * Scripts are self-hosted and inline only; the policy blocks scripts, frames, forms and connections
 * to any other site, and plugins entirely.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $h = $response->headers;

        $h->set('X-Content-Type-Options', 'nosniff');
        $h->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $h->set('X-Frame-Options', 'SAMEORIGIN');
        $h->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');
        // Cloudflare Turnstile (optional spam check) loads a script and a small frame from Cloudflare.
        $turnstile = \App\Services\HumanCheck::turnstileEnabled() ? ' https://challenges.cloudflare.com' : '';
        $h->set('Content-Security-Policy', implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline'".$turnstile,
            "style-src 'self' 'unsafe-inline'",
            "img-src 'self' data:".self::mediaOrigin(),
            "font-src 'self'",
            "connect-src 'self'",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'self'",
            "frame-src 'self'".$turnstile,
            "worker-src 'self'",
            "manifest-src 'self'",
        ]));
        if ($request->secure()) {
            $h->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }

    /** Pictures stored on Cloudflare R2 load from its public URL. */
    private static function mediaOrigin(): string
    {
        $disk = (string) config('vendra.media_disk');
        $url = $disk === 'public' ? null : config("filesystems.disks.$disk.url");
        $parts = $url ? parse_url((string) $url) : null;

        return ($parts['scheme'] ?? null) === 'https' && ! empty($parts['host'])
            ? ' https://'.$parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '')
            : '';
    }
}
