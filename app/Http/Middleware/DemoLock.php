<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * In the public demo, anyone can sign in with the shared accounts. Changes that would affect every
 * other visitor (business details, team, passwords, platform status) are blocked there.
 */
class DemoLock
{
    public const MESSAGE = 'This change is turned off in the public demo so every visitor sees the same data.';

    public function handle(Request $request, Closure $next): Response
    {
        if (config('vendra.public_demo') && ! $request->isMethodSafe()) {
            return back()->withErrors(['rule' => self::MESSAGE]);
        }

        return $next($request);
    }
}
