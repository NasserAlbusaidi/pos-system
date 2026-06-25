<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProductionCheckCommandTest extends TestCase
{
    private ?string $originalPublicPath = null;

    /** @var list<string> */
    private array $temporaryPublicPaths = [];

    protected function tearDown(): void
    {
        if ($this->originalPublicPath !== null) {
            $this->app->usePublicPath($this->originalPublicPath);
        }

        foreach ($this->temporaryPublicPaths as $path) {
            File::deleteDirectory($path);
        }

        parent::tearDown();
    }

    public function test_forge_pilot_configuration_passes_readiness_check(): void
    {
        $this->useForgePilotConfig();

        $this->artisan('bite:production-check --allow-non-production')
            ->assertSuccessful();
    }

    public function test_misconfigured_production_settings_fail_readiness_check(): void
    {
        $this->useForgePilotConfig([
            'app.debug' => true,
            'app.url' => 'http://getbite.om',
            'database.connections.mysql.password' => '<set-in-forge>',
            'queue.default' => 'database',
        ]);

        $this->artisan('bite:production-check --allow-non-production')
            ->assertFailed();
    }

    public function test_queue_failure_reports_current_connection(): void
    {
        $this->useForgePilotConfig([
            'queue.default' => 'database',
        ]);

        $exitCode = Artisan::call('bite:production-check', [
            '--allow-non-production' => true,
            '--json' => true,
        ]);
        $output = Artisan::output();

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('"name": "Queue connection is sync"', $output);
        $this->assertStringContainsString('"detail": "current: database; expected: sync"', $output);
    }

    public function test_missing_database_host_and_socket_fails_readiness_check(): void
    {
        $this->useForgePilotConfig([
            'database.connections.mysql.host' => '',
            'database.connections.mysql.unix_socket' => '',
        ]);

        $exitCode = Artisan::call('bite:production-check', [
            '--allow-non-production' => true,
            '--json' => true,
        ]);
        $output = Artisan::output();

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('"name": "Database host or socket is set"', $output);
        $this->assertStringContainsString('"detail": "DB_HOST or DB_SOCKET is required for the MySQL connection"', $output);
    }

    public function test_database_socket_can_satisfy_readiness_check_when_host_is_empty(): void
    {
        $this->useForgePilotConfig([
            'database.connections.mysql.host' => '',
            'database.connections.mysql.unix_socket' => '/var/run/mysqld/mysqld.sock',
        ]);

        $this->artisan('bite:production-check --allow-non-production')
            ->assertSuccessful();
    }

    public function test_missing_stripe_billing_configuration_fails_readiness_check(): void
    {
        $this->useForgePilotConfig([
            'cashier.key' => '',
            'cashier.secret' => '',
            'billing.plans.pro.stripe_price_id' => '',
            'billing.stripe_webhook_secret' => '',
        ]);

        $this->artisan('bite:production-check --allow-non-production')
            ->assertFailed();
    }

    public function test_stripe_customer_payment_provider_requires_payment_webhook_secret(): void
    {
        $this->useForgePilotConfig([
            'payments.provider' => 'stripe',
            'payments.stripe_webhook_secret' => '',
        ]);

        $exitCode = Artisan::call('bite:production-check', [
            '--allow-non-production' => true,
            '--json' => true,
        ]);
        $output = Artisan::output();

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('"name": "Stripe payment webhook secret matches customer payment provider"', $output);
        $this->assertStringContainsString('set STRIPE_WEBHOOK_SECRET when PAYMENT_PROVIDER=stripe', $output);
    }

    public function test_stripe_customer_payment_provider_passes_with_payment_webhook_secret(): void
    {
        $this->useForgePilotConfig([
            'payments.provider' => 'stripe',
            'payments.stripe_webhook_secret' => 'whsec_customer_payments_live',
        ]);

        $this->artisan('bite:production-check --allow-non-production')
            ->assertSuccessful();
    }

    public function test_unknown_customer_payment_provider_fails_readiness_check(): void
    {
        $this->useForgePilotConfig([
            'payments.provider' => 'thawani',
        ]);

        $exitCode = Artisan::call('bite:production-check', [
            '--allow-non-production' => true,
            '--json' => true,
        ]);
        $output = Artisan::output();

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('"name": "Customer payment provider is supported"', $output);
        $this->assertStringContainsString('PAYMENT_PROVIDER must be counter or stripe; current: thawani', $output);
    }

    public function test_missing_sourdough_handoff_password_fails_readiness_check(): void
    {
        $this->useForgePilotConfig([
            'services.sourdough.admin_password' => 'password',
        ]);

        $this->artisan('bite:production-check --allow-non-production')
            ->assertFailed();
    }

    public function test_missing_sentry_dsn_fails_readiness_check(): void
    {
        $this->useForgePilotConfig([
            'sentry.dsn' => '',
        ]);

        $this->artisan('bite:production-check --allow-non-production')
            ->assertFailed();
    }

    public function test_missing_public_storage_link_fails_readiness_check(): void
    {
        $this->useForgePilotConfig(linkPublicStorage: false);

        $this->artisan('bite:production-check --allow-non-production')
            ->assertFailed();
    }

    public function test_enabled_printnode_requires_key_printer_and_https_endpoint(): void
    {
        $this->useForgePilotConfig([
            'printnode.enabled' => true,
            'printnode.api_key' => '',
            'printnode.default_printer_id' => '',
            'printnode.endpoint' => 'https://api.printnode.com',
        ]);

        $exitCode = Artisan::call('bite:production-check', [
            '--allow-non-production' => true,
            '--json' => true,
        ]);
        $output = Artisan::output();

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('"name": "PrintNode is disabled or fully configured"', $output);
        $this->assertStringContainsString('when PRINTNODE_ENABLED=true', $output);
    }

    public function test_enabled_printnode_rejects_non_https_endpoint(): void
    {
        $this->useForgePilotConfig([
            'printnode.enabled' => true,
            'printnode.api_key' => 'printnode-live-key',
            'printnode.default_printer_id' => '123456',
            'printnode.endpoint' => 'http://api.printnode.com',
        ]);

        $this->artisan('bite:production-check --allow-non-production')
            ->assertFailed();
    }

    public function test_enabled_printnode_passes_when_fully_configured(): void
    {
        $this->useForgePilotConfig([
            'printnode.enabled' => true,
            'printnode.api_key' => 'printnode-live-key',
            'printnode.default_printer_id' => '123456',
            'printnode.endpoint' => 'https://api.printnode.com',
        ]);

        $this->artisan('bite:production-check --allow-non-production')
            ->assertSuccessful();
    }

    public function test_test_mode_stripe_keys_fail_readiness_check(): void
    {
        $this->useForgePilotConfig([
            'cashier.key' => 'pk_test_wrong_mode',
            'cashier.secret' => 'sk_test_wrong_mode',
        ]);

        $this->artisan('bite:production-check --allow-non-production')
            ->assertFailed();
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function useForgePilotConfig(array $overrides = [], bool $linkPublicStorage = true): void
    {
        $this->usePublicStorageFixture($linkPublicStorage);

        config(array_merge([
            'app.debug' => false,
            'app.key' => 'base64:'.base64_encode(str_repeat('x', 32)),
            'app.url' => 'https://getbite.om',
            'database.default' => 'mysql',
            'database.connections.mysql.database' => 'getbite',
            'database.connections.mysql.username' => 'forge',
            'database.connections.mysql.password' => 'strong-password',
            'session.driver' => 'database',
            'session.encrypt' => true,
            'session.secure' => true,
            'session.same_site' => 'lax',
            'cache.default' => 'database',
            'queue.default' => 'sync',
            'filesystems.default' => 'public',
            'printnode.enabled' => false,
            'printnode.api_key' => '',
            'printnode.default_printer_id' => '',
            'printnode.endpoint' => 'https://api.printnode.com',
            'payments.provider' => 'counter',
            'payments.stripe_webhook_secret' => '',
            'services.sourdough.admin_password' => 'strong-handoff-password',
            'cashier.key' => 'pk_live_test',
            'cashier.secret' => 'sk_live_test',
            'billing.plans.pro.stripe_price_id' => 'price_pro_live',
            'billing.stripe_webhook_secret' => 'whsec_subscription_live',
            'sentry.dsn' => 'https://key@sentry.io/123',
        ], $overrides));
    }

    private function usePublicStorageFixture(bool $linked): void
    {
        if ($this->originalPublicPath === null) {
            $this->originalPublicPath = public_path();
        }

        $publicPath = storage_path('framework/testing/public-'.Str::uuid());
        File::ensureDirectoryExists($publicPath);
        File::ensureDirectoryExists(storage_path('app/public'));

        if ($linked) {
            symlink(storage_path('app/public'), $publicPath.'/storage');
        }

        $this->temporaryPublicPaths[] = $publicPath;
        $this->app->usePublicPath($publicPath);
    }
}
