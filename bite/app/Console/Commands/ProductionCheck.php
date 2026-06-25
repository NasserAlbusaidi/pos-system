<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ProductionCheck extends Command
{
    protected $signature = 'bite:production-check
        {--allow-non-production : Do not fail when APP_ENV is not production, useful for local smoke tests}
        {--json : Output machine-readable JSON}';

    protected $description = 'Validate the Forge pilot production configuration before selling or demoing a restaurant.';

    public function handle(): int
    {
        $checks = $this->checks();
        $failed = array_values(array_filter($checks, fn (array $check) => ! $check['ok']));

        if ($this->option('json')) {
            $this->line(json_encode([
                'ok' => empty($failed),
                'checks' => $checks,
            ], JSON_PRETTY_PRINT));

            return empty($failed) ? self::SUCCESS : self::FAILURE;
        }

        $this->info('Forge pilot production readiness');
        $this->newLine();

        foreach ($checks as $check) {
            $this->line(sprintf(
                '%s %s%s',
                $check['ok'] ? '<fg=green>PASS</>' : '<fg=red>FAIL</>',
                $check['name'],
                $check['detail'] ? ' — '.$check['detail'] : '',
            ));
        }

        if (! empty($failed)) {
            $this->newLine();
            $this->error(count($failed).' production readiness check(s) failed.');

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('Production readiness checks passed.');

        return self::SUCCESS;
    }

    /**
     * @return list<array{name: string, ok: bool, detail: string}>
     */
    private function checks(): array
    {
        return [
            $this->check(
                'APP_ENV is production',
                $this->option('allow-non-production') || $this->laravel->environment('production'),
                'current: '.$this->laravel->environment(),
            ),
            $this->check(
                'APP_DEBUG is disabled',
                config('app.debug') === false,
                'debug must be false',
            ),
            $this->check(
                'APP_KEY is configured',
                $this->configured(config('app.key')),
                'generate once with php artisan key:generate --show',
            ),
            $this->check(
                'APP_URL uses HTTPS',
                Str::startsWith((string) config('app.url'), 'https://'),
                'current: '.config('app.url'),
            ),
            $this->check(
                'Sourdough handoff admin password is configured',
                $this->configuredHandoffPassword(config('services.sourdough.admin_password')),
                'SOURDOUGH_ADMIN_PASSWORD must be set before running the Sourdough production seed',
            ),
            $this->check(
                'Database driver is MySQL',
                config('database.default') === 'mysql',
                'current: '.config('database.default'),
            ),
            $this->check(
                'Database host or socket is set',
                $this->configured(config('database.connections.mysql.host'))
                    || $this->configured(config('database.connections.mysql.unix_socket')),
                'DB_HOST or DB_SOCKET is required for the MySQL connection',
            ),
            $this->check(
                'Database name, user, and password are set',
                $this->configured(config('database.connections.mysql.database'))
                    && $this->configured(config('database.connections.mysql.username'))
                    && $this->configured(config('database.connections.mysql.password')),
                'DB_DATABASE, DB_USERNAME, DB_PASSWORD are required',
            ),
            $this->check(
                'Session driver is database',
                config('session.driver') === 'database',
                'current: '.config('session.driver'),
            ),
            $this->check(
                'Sessions are encrypted',
                config('session.encrypt') === true,
                'SESSION_ENCRYPT=true',
            ),
            $this->check(
                'Session cookies are HTTPS-only',
                config('session.secure') === true,
                'SESSION_SECURE_COOKIE=true',
            ),
            $this->check(
                'Session SameSite is lax or strict',
                in_array(config('session.same_site'), ['lax', 'strict'], true),
                'current: '.var_export(config('session.same_site'), true),
            ),
            $this->check(
                'Cache store is database',
                config('cache.default') === 'database',
                'current: '.config('cache.default'),
            ),
            $this->check(
                'Queue connection is sync',
                config('queue.default') === 'sync',
                'current: '.config('queue.default').'; expected: sync',
            ),
            $this->check(
                'Filesystem disk is public',
                config('filesystems.default') === 'public',
                'Forge pilot serves product images from public storage',
            ),
            $this->check(
                'Public storage link points to storage/app/public',
                $this->publicStorageLinked(),
                'run php artisan storage:link before handoff',
            ),
            $this->check(
                'PrintNode is disabled or fully configured',
                ! $this->printNodeEnabled() || (
                    $this->configured(config('printnode.api_key'))
                    && $this->configuredPrintNodePrinter(config('printnode.default_printer_id'))
                    && $this->configuredHttpsUrl(config('printnode.endpoint'))
                ),
                'when PRINTNODE_ENABLED=true, set PRINTNODE_API_KEY, numeric PRINTNODE_PRINTER_ID, and HTTPS PRINTNODE_ENDPOINT',
            ),
            $this->check(
                'Customer payment provider is supported',
                in_array($this->customerPaymentProvider(), ['counter', 'stripe'], true),
                'PAYMENT_PROVIDER must be counter or stripe; current: '.$this->customerPaymentProvider(),
            ),
            $this->check(
                'Stripe payment webhook secret matches customer payment provider',
                $this->customerPaymentProvider() !== 'stripe' || $this->configured(config('payments.stripe_webhook_secret')),
                'set STRIPE_WEBHOOK_SECRET when PAYMENT_PROVIDER=stripe; use PAYMENT_PROVIDER=counter for the pay-at-counter pilot',
            ),
            $this->check(
                'Stripe live publishable key is configured',
                $this->configuredStripeKey(config('cashier.key'), 'pk_live_'),
                'STRIPE_KEY must be a live publishable key for subscription checkout',
            ),
            $this->check(
                'Stripe live secret key is configured',
                $this->configuredStripeKey(config('cashier.secret'), 'sk_live_'),
                'STRIPE_SECRET must be a live secret key for subscription checkout',
            ),
            $this->check(
                'Stripe Pro price ID is configured',
                $this->configured(config('billing.plans.pro.stripe_price_id')),
                'STRIPE_PRO_PRICE_ID must match the live monthly plan',
            ),
            $this->check(
                'Stripe subscription webhook secret is configured',
                $this->configured(config('billing.stripe_webhook_secret')),
                'STRIPE_SUBSCRIPTION_WEBHOOK_SECRET must be set from the Stripe endpoint',
            ),
            $this->check(
                'Sentry DSN is configured',
                $this->configured(config('sentry.dsn')),
                'SENTRY_LARAVEL_DSN is required by production startup validation',
            ),
        ];
    }

    private function check(string $name, bool $ok, string $detail = ''): array
    {
        return compact('name', 'ok', 'detail');
    }

    private function configured(mixed $value): bool
    {
        if (! is_string($value)) {
            return $value !== null;
        }

        $value = trim($value);

        return $value !== ''
            && ! str_contains($value, '<')
            && ! str_contains($value, 'PLACEHOLDER')
            && ! str_contains(strtolower($value), 'dummy');
    }

    private function configuredStripeKey(mixed $value, string $expectedPrefix): bool
    {
        return is_string($value)
            && $this->configured($value)
            && Str::startsWith(trim($value), $expectedPrefix);
    }

    private function configuredHandoffPassword(mixed $value): bool
    {
        if (! is_string($value) || ! $this->configured($value)) {
            return false;
        }

        $value = trim($value);

        return $value !== 'password' && strlen($value) >= 12;
    }

    private function printNodeEnabled(): bool
    {
        return config('printnode.enabled') === true;
    }

    private function configuredPrintNodePrinter(mixed $value): bool
    {
        return is_numeric($value) && (int) $value > 0;
    }

    private function configuredHttpsUrl(mixed $value): bool
    {
        return is_string($value)
            && $this->configured($value)
            && filter_var($value, FILTER_VALIDATE_URL) !== false
            && Str::startsWith(strtolower(trim($value)), 'https://');
    }

    private function customerPaymentProvider(): string
    {
        return strtolower(trim((string) config('payments.provider', 'counter')));
    }

    private function publicStorageLinked(): bool
    {
        $link = public_path('storage');
        $target = storage_path('app/public');

        return is_link($link)
            && realpath($link) !== false
            && realpath($link) === realpath($target);
    }
}
