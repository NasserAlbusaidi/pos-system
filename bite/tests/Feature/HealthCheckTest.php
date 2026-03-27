<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_endpoint_returns_200_with_correct_json_shape_when_healthy(): void
    {
        $response = $this->get('/health');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'db',
            'storage',
            'gd_webp',
            'queue',
            'latency_ms',
        ]);
        $response->assertJson(['status' => 'healthy']);
        $response->assertJson(['db' => 'ok']);
        $response->assertJson(['queue' => 'ok']);
    }

    public function test_health_endpoint_returns_503_when_db_fails(): void
    {
        DB::shouldReceive('select')
            ->andThrow(new \RuntimeException('DB connection failed'));

        // DB::table('jobs') also needs to be intercepted
        DB::shouldReceive('table')
            ->andThrow(new \RuntimeException('DB connection failed'));

        $response = $this->get('/health');

        $response->assertStatus(503);
        $response->assertJson(['status' => 'degraded']);
        $response->assertJson(['db' => 'error']);
    }

    public function test_health_endpoint_returns_json_content_type(): void
    {
        $response = $this->get('/health');

        $response->assertHeader('Content-Type', 'application/json');
    }

    public function test_health_endpoint_latency_ms_is_non_negative_integer(): void
    {
        $response = $this->get('/health');

        $response->assertStatus(200);

        $data = $response->json();
        $this->assertArrayHasKey('latency_ms', $data);
        $this->assertIsInt($data['latency_ms']);
        $this->assertGreaterThanOrEqual(0, $data['latency_ms']);
    }

    public function test_health_endpoint_reports_storage_status(): void
    {
        $response = $this->get('/health');

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertArrayHasKey('storage', $data);
        $this->assertContains($data['storage'], ['ok', 'error']);
    }

    public function test_health_endpoint_reports_gd_webp_status(): void
    {
        $response = $this->get('/health');

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertArrayHasKey('gd_webp', $data);
        $this->assertContains($data['gd_webp'], ['ok', 'error']);
    }
}
