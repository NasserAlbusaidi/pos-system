<?php

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
    ->withMiddleware(function (Middleware $middleware) {
        // Trust Cloud Run / load balancer proxies so request()->ip() returns the real client IP.
        $middleware->trustProxies(at: '*', headers: Request::HEADER_X_FORWARDED_FOR);

        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);

        $middleware->web(append: [
            \App\Http\Middleware\SetLocale::class,
        ]);

        $middleware->validateCsrfTokens([
            'webhooks/stripe',
            'webhooks/stripe/subscription',
        ]);

        $middleware->alias([
            'super_admin' => \App\Http\Middleware\EnsureUserIsSuperAdmin::class,
            'role' => \App\Http\Middleware\EnsureUserHasRole::class,
            'subscribed' => \App\Http\Middleware\CheckSubscription::class,
        ]);

        $middleware->redirectTo(
            guests: '/login',
            users: function ($request) {
                if ($request->user()?->is_super_admin) {
                    return route('super-admin.dashboard');
                }

                return route('dashboard');
            }
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
