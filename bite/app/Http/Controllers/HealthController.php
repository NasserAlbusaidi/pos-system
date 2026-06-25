<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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

        $checks['database_tables'] = 'skipped';
        $missingTables = [];

        if ($checks['db'] === 'ok') {
            try {
                $missingTables = $this->missingRequiredDatabaseTables();
                $checks['database_tables'] = empty($missingTables) ? 'ok' : 'error';

                if (! empty($missingTables)) {
                    $overall = 'degraded';
                }
            } catch (\Throwable) {
                $checks['database_tables'] = 'error';
                $overall = 'degraded';
            }
        }

        // Storage access check. The Forge pilot uses the public disk; the
        // paused Cloud Run path may use GCS if that deployment target returns.
        try {
            $disk = config('filesystems.default', 'local');
            Storage::disk($disk)->put('.health-probe', 'ok');
            Storage::disk($disk)->delete('.health-probe');
            $checks['storage'] = 'ok';
        } catch (\Throwable) {
            $checks['storage'] = 'error';
            $overall = 'degraded';
        }

        // Forge serves uploaded product photos from storage/app/public through
        // public/storage. The disk can be writable while the symlink is broken,
        // which would leave menus healthy-looking but image-broken.
        $checks['public_storage_link'] = 'skipped';
        if (config('filesystems.default') === 'public') {
            if ($this->publicStorageLinked()) {
                $checks['public_storage_link'] = 'ok';
            } else {
                $checks['public_storage_link'] = 'error';
                $overall = 'degraded';
            }
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

        // Queue check. The Forge pilot intentionally uses the sync driver, so
        // there is no worker or jobs table dependency to verify in that mode.
        try {
            if (config('queue.default') === 'database') {
                DB::table('jobs')->limit(1)->count();
            }

            $checks['queue'] = 'ok';
        } catch (\Throwable) {
            $checks['queue'] = 'error';
            $overall = 'degraded';
        }

        $latencyMs = (int) ((microtime(true) - $start) * 1000);

        $payload = array_merge(['status' => $overall], $checks, ['latency_ms' => $latencyMs]);

        if (! empty($missingTables)) {
            $payload['database_tables_missing'] = $missingTables;
        }

        return response()->json(
            $payload,
            $overall === 'healthy' ? 200 : 503
        );
    }

    private function publicStorageLinked(): bool
    {
        $link = public_path('storage');
        $target = storage_path('app/public');

        return is_link($link)
            && realpath($link) !== false
            && realpath($link) === realpath($target);
    }

    /**
     * @return list<string>
     */
    private function missingRequiredDatabaseTables(): array
    {
        $tables = [];

        if (config('session.driver') === 'database') {
            $tables[] = (string) config('session.table', 'sessions');
        }

        $cacheStore = (string) config('cache.default');
        if (config("cache.stores.{$cacheStore}.driver") === 'database') {
            $tables[] = (string) config("cache.stores.{$cacheStore}.table", 'cache');
        }

        if (config('queue.default') === 'database') {
            $tables[] = (string) config('queue.connections.database.table', 'jobs');
        }

        return array_values(array_filter(array_unique($tables), fn (string $table): bool => ! Schema::hasTable($table)));
    }
}
