<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class HandoffSchemaCheckCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_handoff_schema_check_passes_after_migrations(): void
    {
        $this->artisan('bite:schema-check')
            ->expectsOutputToContain('PASS orders handoff columns exist')
            ->expectsOutputToContain('PASS order_items handoff columns exist')
            ->expectsOutputToContain('PASS payments handoff columns exist')
            ->expectsOutputToContain('PASS shift_closures handoff columns exist')
            ->expectsOutputToContain('PASS webhook_events handoff columns exist')
            ->assertSuccessful();
    }

    public function test_handoff_schema_check_json_names_critical_columns(): void
    {
        $exitCode = Artisan::call('bite:schema-check', ['--json' => true]);
        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('"ok": true', $output);
        $this->assertStringContainsString('orders handoff columns exist', $output);
        $this->assertStringContainsString('idempotency_fingerprint', $output);
        $this->assertStringContainsString('order_items handoff columns exist', $output);
        $this->assertStringContainsString('payments handoff columns exist', $output);
        $this->assertStringContainsString('provider_reference', $output);
        $this->assertStringContainsString('shift_closures handoff columns exist', $output);
        $this->assertStringContainsString('webhook_events handoff columns exist', $output);
    }
}
