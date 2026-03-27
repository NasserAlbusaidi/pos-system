<?php

namespace App\Providers;

use App\Models\Shop;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // HARD-02: Fail fast if required env vars are missing in production.
        // Uses config() not env() — after config:cache, env() returns null.
        // Guarded on production environment so tests (APP_ENV=testing) are never affected.
        if ($this->app->environment('production')) {
            $required = [
                'APP_KEY' => config('app.key'),
                'DB_HOST' => config('database.connections.mysql.host'),
                'DB_DATABASE' => config('database.connections.mysql.database'),
            ];

            // GCS vars only required when filesystem is gcs
            if (config('filesystems.default') === 'gcs') {
                $required['GCS_BUCKET'] = config('filesystems.disks.gcs.bucket');
                $required['GOOGLE_CLOUD_PROJECT_ID'] = config('filesystems.disks.gcs.project_id');
            }

            // Sentry DSN required in production
            $required['SENTRY_LARAVEL_DSN'] = config('sentry.dsn');

            $missing = array_keys(array_filter($required, fn ($value) => empty($value)));

            if (! empty($missing)) {
                throw new \RuntimeException(
                    'Missing required environment variables: ' . implode(', ', $missing)
                );
            }
        }

        // HARD-03: Register named rate limiter for Stripe webhook endpoints.
        // Returns 429 with Retry-After header after 60 requests per minute per IP.
        RateLimiter::for('stripe-webhooks', function (Request $request) {
            return Limit::perMinute(60)
                ->by($request->ip())
                ->response(fn () => response()->json(
                    ['message' => 'Too many requests.'],
                    429,
                    ['Retry-After' => '60']
                ));
        });

        if ($this->app->environment('production') || env('FORCE_HTTPS')) {
            URL::forceScheme('https');
        }

        // Use Shop (not User) as the billable model for Laravel Cashier.
        // Shops are the subscription entity — each shop has its own plan.
        if (class_exists(\Laravel\Cashier\Cashier::class)) {
            \Laravel\Cashier\Cashier::useCustomerModel(Shop::class);
        }
    }
}
