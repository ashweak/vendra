<?php

namespace App\Http\Middleware;

use App\Services\HumanCheck;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

/** Refuses public form posts that fail the honeypot or the optional Turnstile check (see HumanCheck). */
class SpamGuard
{
    public function __construct(private HumanCheck $check) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->check->honeypotFilled($request)) {
            Log::info('Spam guard: honeypot filled', ['route' => $request->route()?->getName(), 'ip' => $request->ip()]);

            throw ValidationException::withMessages(['human' => 'We couldn’t send that. Please try again.']);
        }
        if (! $this->check->passes($request)) {
            throw ValidationException::withMessages(['human' => 'Please complete the “I’m human” check, then send again.']);
        }

        return $next($request);
    }
}
