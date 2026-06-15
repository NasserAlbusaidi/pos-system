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
        // Trust proxies so request()->ip() returns the real client IP (used by
        // rate limiting, loyalty, and abuse controls). Defaulting to "*" lets any
        // client spoof X-Forwarded-For, so when TRUSTED_PROXIES is unset we fall
        // back to loopback + RFC1918 private ranges: a same-host nginx reverse
        // proxy is honored while public clients cannot spoof their IP. Set
        // TRUSTED_PROXIES to the real proxy IP/CIDR (or "*" for a managed front
        // end whose app port is unreachable from the public internet).
        // Read from the environment directly: this middleware closure runs during
        // bootstrap, before the config repository is loaded, so config() is not yet
        // available here (it throws "Target [config] is not instantiable"). This is
        // the one sanctioned env() call outside a config file; trustProxies is the
        // only consumer, so there is no config/ mirror to drift out of sync with.
        $configured = env('TRUSTED_PROXIES');

        if ($configured === '*') {
            $proxies = '*';
        } elseif (filled($configured)) {
            $proxies = array_values(array_filter(array_map('trim', explode(',', $configured))));
        } else {
            $proxies = ['127.0.0.1', '::1', '10.0.0.0/8', '172.16.0.0/12', '192.168.0.0/16'];
        }

        $middleware->trustProxies(
            at: $proxies,
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO,
        );

        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);
        $middleware->append(\App\Http\Middleware\LogSlowRequests::class);

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
            'shop.active' => \App\Http\Middleware\EnsureShopActive::class,
            'plan' => \App\Http\Middleware\EnsurePlanFeature::class,
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
