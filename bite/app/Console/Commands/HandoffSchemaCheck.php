<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class HandoffSchemaCheck extends Command
{
    protected $signature = 'bite:schema-check
        {--json : Output machine-readable JSON}';

    protected $description = 'Validate post-migration schema required for restaurant handoff flows.';

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

        $this->info('Restaurant handoff schema readiness');
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
            $this->error(count($failed).' schema readiness check(s) failed.');

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('Schema readiness checks passed.');

        return self::SUCCESS;
    }

    /**
     * @return list<array{name: string, ok: bool, detail: string}>
     */
    private function checks(): array
    {
        return [
            $this->columnsCheck('orders', [
                'source',
                'order_note',
                'idempotency_key',
                'idempotency_fingerprint',
                'subtotal_amount',
                'tax_amount',
                'paid_at',
            ]),
            $this->columnsCheck('order_items', [
                'note',
                'product_name_snapshot_en',
                'price_snapshot',
            ]),
            $this->columnsCheck('payments', [
                'shop_id',
                'amount',
                'method',
                'provider_reference',
                'reverses_payment_id',
                'paid_at',
            ]),
            $this->columnsCheck('shift_closures', [
                'shop_id',
                'business_date',
                'expected_cash',
                'actual_cash',
                'difference',
                'shift_summary',
                'closed_by',
                'closed_at',
            ]),
            $this->columnsCheck('webhook_events', [
                'provider',
                'event_id',
                'payload',
                'processed_at',
            ]),
        ];
    }

    /**
     * @param  list<string>  $columns
     * @return array{name: string, ok: bool, detail: string}
     */
    private function columnsCheck(string $table, array $columns): array
    {
        if (! Schema::hasTable($table)) {
            return $this->check("{$table} table exists", false, 'table is missing');
        }

        $missing = array_values(array_filter(
            $columns,
            fn (string $column) => ! Schema::hasColumn($table, $column),
        ));

        return $this->check(
            "{$table} handoff columns exist",
            empty($missing),
            empty($missing) ? implode(', ', $columns) : 'missing: '.implode(', ', $missing),
        );
    }

    /**
     * @return array{name: string, ok: bool, detail: string}
     */
    private function check(string $name, bool $ok, string $detail = ''): array
    {
        return compact('name', 'ok', 'detail');
    }
}
