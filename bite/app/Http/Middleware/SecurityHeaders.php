<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
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

        [$viteHttpOrigin, $viteWsOrigin] = $this->viteHotOrigins();

        $response->headers->set('Content-Security-Policy', implode(' ', [
            "default-src 'self';",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://js.stripe.com{$viteHttpOrigin};",
            "connect-src 'self' https://api.stripe.com{$viteHttpOrigin}{$viteWsOrigin};",
            'frame-src https://js.stripe.com https://hooks.stripe.com;',
            "img-src 'self' data: blob: https:;",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com{$viteHttpOrigin};",
            "font-src 'self' https://fonts.gstatic.com{$viteHttpOrigin};",
        ]));

        if (app()->environment('production')) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }

    /**
     * Allow Laravel's configured Vite hot server while keeping production CSP tight.
     *
     * @return array{0: string, 1: string}
     */
    private function viteHotOrigins(): array
    {
        if (! Vite::isRunningHot()) {
            return ['', ''];
        }

        $hotUrl = trim((string) file_get_contents(Vite::hotFile()));
        $parts = parse_url($hotUrl);

        if (! is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
            return ['', ''];
        }

        $port = isset($parts['port']) ? ':'.$parts['port'] : '';
        $httpOrigin = ' '.$parts['scheme'].'://'.$parts['host'].$port;
        $wsScheme = $parts['scheme'] === 'https' ? 'wss' : 'ws';

        return [$httpOrigin, ' '.$wsScheme.'://'.$parts['host'].$port];
    }
}
