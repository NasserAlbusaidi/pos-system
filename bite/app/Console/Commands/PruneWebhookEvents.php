<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PruneWebhookEvents extends Command
{
    protected $signature = 'webhook-events:prune
        {--days=30 : Delete processed webhook events older than this many days}';

    protected $description = 'Prune processed webhook idempotency records after the retry and debugging window.';

    public function handle(): int
    {
        $days = (int) $this->option('days');

        if ($days < 1) {
            $this->error('The --days option must be at least 1.');

            return self::FAILURE;
        }

        $deleted = DB::table('webhook_events')
            ->whereNotNull('processed_at')
            ->where('processed_at', '<', now()->subDays($days))
            ->delete();

        $this->info("Pruned {$deleted} processed webhook event(s) older than {$days} day(s).");

        return self::SUCCESS;
    }
}
