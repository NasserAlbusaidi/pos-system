<?php

namespace Tests\Feature;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class WebhookEventPruningTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_prunes_only_processed_events_older_than_retention_window(): void
    {
        $oldProcessed = $this->insertWebhookEvent('stripe', 'evt_old_processed', now()->subDays(45), now()->subDays(45));
        $recentProcessed = $this->insertWebhookEvent('stripe', 'evt_recent_processed', now()->subDays(45), now()->subDays(7));
        $oldUnprocessed = $this->insertWebhookEvent('stripe_subscription', 'evt_old_unprocessed', now()->subDays(45), null);

        $this->artisan('webhook-events:prune')
            ->expectsOutput('Pruned 1 processed webhook event(s) older than 30 day(s).')
            ->assertExitCode(0);

        $this->assertDatabaseMissing('webhook_events', ['id' => $oldProcessed]);
        $this->assertDatabaseHas('webhook_events', ['id' => $recentProcessed]);
        $this->assertDatabaseHas('webhook_events', ['id' => $oldUnprocessed]);
    }

    public function test_command_supports_custom_retention_window(): void
    {
        $oldEnoughForCustomWindow = $this->insertWebhookEvent('stripe', 'evt_custom_old', now()->subDays(10), now()->subDays(8));
        $tooRecentForCustomWindow = $this->insertWebhookEvent('stripe', 'evt_custom_recent', now()->subDays(10), now()->subDays(6));

        $this->artisan('webhook-events:prune --days=7')
            ->expectsOutput('Pruned 1 processed webhook event(s) older than 7 day(s).')
            ->assertExitCode(0);

        $this->assertDatabaseMissing('webhook_events', ['id' => $oldEnoughForCustomWindow]);
        $this->assertDatabaseHas('webhook_events', ['id' => $tooRecentForCustomWindow]);
    }

    public function test_command_rejects_invalid_retention_window(): void
    {
        $this->artisan('webhook-events:prune --days=0')
            ->expectsOutput('The --days option must be at least 1.')
            ->assertExitCode(1);
    }

    public function test_processed_webhook_pruning_is_scheduled_daily(): void
    {
        $events = collect(app(Schedule::class)->events());

        $this->assertTrue(
            $events->contains(fn ($event) => $event->description === 'webhook-events.prune-processed'
                && str_contains((string) $event->command, 'webhook-events:prune --days=30')),
            'Expected processed webhook pruning to be scheduled with a 30 day retention window.'
        );
    }

    private function insertWebhookEvent(string $provider, string $eventId, mixed $createdAt, mixed $processedAt): int
    {
        return (int) DB::table('webhook_events')->insertGetId([
            'provider' => $provider,
            'event_id' => $eventId,
            'event_type' => 'test.event',
            'payload' => '{}',
            'processed_at' => $processedAt,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }
}
