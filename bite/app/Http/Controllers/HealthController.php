<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class HealthController
{
    public function __invoke(): JsonResponse
    {
        $checks = [];
        $overall = 'healthy';
        $start = microtime(true);

        // DB connectivity check
        try {
            DB::select('SELECT 1');
            $checks['db'] = 'ok';
        } catch (\Throwable) {
            $checks['db'] = 'error';
            $overall = 'degraded';
        }

        // Storage access check — disk selection is intentionally env-driven via
        // config('filesystems.default'). In test/dev this resolves to 'public' (local),
        // in production it resolves to 'gcs'. The test suite verifies the health
        // check logic path works; actual GCS connectivity is verified only in a
        // deployed environment (Cloud Run).
        try {
            $disk = config('filesystems.default', 'local');
            Storage::disk($disk)->put('.health-probe', 'ok');
            Storage::disk($disk)->delete('.health-probe');
            $checks['storage'] = 'ok';
        } catch (\Throwable) {
            $checks['storage'] = 'error';
            $overall = 'degraded';
        }

        // GD extension with WebP support check
        $checks['gd_webp'] = (
            extension_loaded('gd')
            && function_exists('imagecreatefromwebp')
            && (imagetypes() & IMG_WEBP)
        ) ? 'ok' : 'error';

        if ($checks['gd_webp'] === 'error') {
            $overall = 'degraded';
        }

        // Queue check (database driver — verify jobs table accessible)
        try {
            DB::table('jobs')->limit(1)->count();
            $checks['queue'] = 'ok';
        } catch (\Throwable) {
            $checks['queue'] = 'error';
            $overall = 'degraded';
        }

        $latencyMs = (int) ((microtime(true) - $start) * 1000);

        return response()->json(
            array_merge(['status' => $overall], $checks, ['latency_ms' => $latencyMs]),
            $overall === 'healthy' ? 200 : 503
        );
    }
}
