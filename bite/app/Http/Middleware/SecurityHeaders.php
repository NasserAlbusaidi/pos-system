<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        $response->headers->set('Content-Security-Policy', implode(' ', [
            "default-src 'self';",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://js.stripe.com;",
            "connect-src 'self' https://api.stripe.com;",
            'frame-src https://js.stripe.com https://hooks.stripe.com;',
            "img-src 'self' data: blob: https:;",
            "style-src 'self' 'unsafe-inline';",
            "font-src 'self';",
        ]));

        if (app()->environment('production')) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
