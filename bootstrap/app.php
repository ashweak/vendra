<?php

use App\Exceptions\RuleViolation;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'mfa' => \App\Http\Middleware\EnsureMfa::class,
            'business' => \App\Http\Middleware\SetCurrentBusiness::class,
            'platform' => \App\Http\Middleware\PlatformAdmin::class,
        ]);
        $middleware->redirectGuestsTo(fn () => route('login'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
        // Business-rule failures return the user to the form with a safe message.
        $exceptions->render(function (RuleViolation $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage(), 'code' => $e->code_], 422);
            }

            return back()->withInput()->withErrors(['rule' => $e->getMessage()]);
        });
    })->create();
