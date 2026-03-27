<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LogSlowRequests
{
    private const THRESHOLD_MS = 2000;

    public function handle(Request $request, Closure $next): Response
    {
        $start = microtime(true);
        $response = $next($request);
        $durationMs = (int) ((microtime(true) - $start) * 1000);

        if ($durationMs >= self::THRESHOLD_MS) {
            Log::warning('Slow request', [
                'method' => $request->method(),
                'path' => $request->path(),
                'duration_ms' => $durationMs,
                'ip' => $request->ip(),
                'status' => $response->getStatusCode(),
            ]);
        }

        return $response;
    }
}
