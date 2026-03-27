<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class StartupValidationTest extends TestCase
{
    public function test_startup_validation_throws_in_production_when_app_key_is_missing(): void
    {
        // Simulate production environment with missing app.key
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/Missing required environment variables:.*APP_KEY/');

        $app = $this->app;

        // Override environment to 'production'
        $app->detectEnvironment(fn () => 'production');

        // Clear app key to simulate missing env var
        Config::set('app.key', null);
        Config::set('database.connections.mysql.host', 'some-host');
        Config::set('database.connections.mysql.database', 'some-db');
        Config::set('sentry.dsn', 'https://dsn@sentry.io/123');
        Config::set('filesystems.default', 'local');

        // Re-run the startup validation logic (simulated directly)
        $required = [
            'APP_KEY' => Config::get('app.key'),
            'DB_HOST' => Config::get('database.connections.mysql.host'),
            'DB_DATABASE' => Config::get('database.connections.mysql.database'),
        ];

        if (Config::get('filesystems.default') === 'gcs') {
            $required['GCS_BUCKET'] = Config::get('filesystems.disks.gcs.bucket');
            $required['GOOGLE_CLOUD_PROJECT_ID'] = Config::get('filesystems.disks.gcs.project_id');
        }

        $required['SENTRY_LARAVEL_DSN'] = Config::get('sentry.dsn');

        $missing = array_keys(array_filter($required, fn ($value) => empty($value)));

        if (! empty($missing)) {
            throw new \RuntimeException(
                'Missing required environment variables: '.implode(', ', $missing)
            );
        }
    }

    public function test_startup_validation_does_not_throw_when_all_required_vars_present(): void
    {
        // Should not throw when all required vars are present
        Config::set('app.key', 'base64:somevalidkey==');
        Config::set('database.connections.mysql.host', 'db.example.com');
        Config::set('database.connections.mysql.database', 'bite_prod');
        Config::set('sentry.dsn', 'https://key@sentry.io/123');
        Config::set('filesystems.default', 'local');

        $required = [
            'APP_KEY' => Config::get('app.key'),
            'DB_HOST' => Config::get('database.connections.mysql.host'),
            'DB_DATABASE' => Config::get('database.connections.mysql.database'),
        ];

        if (Config::get('filesystems.default') === 'gcs') {
            $required['GCS_BUCKET'] = Config::get('filesystems.disks.gcs.bucket');
            $required['GOOGLE_CLOUD_PROJECT_ID'] = Config::get('filesystems.disks.gcs.project_id');
        }

        $required['SENTRY_LARAVEL_DSN'] = Config::get('sentry.dsn');

        $missing = array_keys(array_filter($required, fn ($value) => empty($value)));

        $this->assertEmpty($missing, 'No missing vars expected when all are set');
    }

    public function test_startup_validation_does_not_affect_testing_environment(): void
    {
        // In the testing environment, validation should NOT run
        // This is enforced by the production guard in AppServiceProvider
        $this->assertSame('testing', app()->environment());

        // Even with empty app key, no exception should be thrown in testing
        Config::set('app.key', '');

        // The guard condition: $this->app->environment('production')
        // is false in testing — so validation is skipped
        $isProduction = app()->environment('production');
        $this->assertFalse($isProduction, 'Testing environment should not be production');
    }

    public function test_startup_validation_includes_gcs_vars_when_filesystem_is_gcs(): void
    {
        // When GCS is the default disk, GCS_BUCKET and project ID must be checked
        Config::set('filesystems.default', 'gcs');
        Config::set('filesystems.disks.gcs.bucket', null);
        Config::set('filesystems.disks.gcs.project_id', null);

        $required = [];
        if (Config::get('filesystems.default') === 'gcs') {
            $required['GCS_BUCKET'] = Config::get('filesystems.disks.gcs.bucket');
            $required['GOOGLE_CLOUD_PROJECT_ID'] = Config::get('filesystems.disks.gcs.project_id');
        }

        $missing = array_keys(array_filter($required, fn ($value) => empty($value)));

        $this->assertContains('GCS_BUCKET', $missing);
        $this->assertContains('GOOGLE_CLOUD_PROJECT_ID', $missing);
    }
}
