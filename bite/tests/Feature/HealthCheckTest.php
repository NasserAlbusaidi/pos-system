<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_health_endpoint_returns_200_with_correct_json_shape_when_healthy(): void
    {
        $response = $this->get('/health');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'db',
            'database_tables',
            'storage',
            'public_storage_link',
            'gd_webp',
            'queue',
            'latency_ms',
        ]);
        $response->assertJson(['status' => 'healthy']);
        $response->assertJson(['db' => 'ok']);
        $response->assertJson(['queue' => 'ok']);

        $data = $response->json();
        $this->assertContains($data['database_tables'], ['ok', 'skipped']);
    }

    public function test_health_endpoint_returns_503_when_db_fails(): void
    {
        DB::shouldReceive('select')
            ->andThrow(new \RuntimeException('DB connection failed'));

        $response = $this->get('/health');

        $response->assertStatus(503);
        $response->assertJson(['status' => 'degraded']);
        $response->assertJson(['db' => 'error']);
        $response->assertJson(['database_tables' => 'skipped']);
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

    public function test_health_endpoint_reports_database_backing_tables_when_configured(): void
    {
        config([
            'session.driver' => 'database',
            'cache.default' => 'database',
        ]);

        $response = $this->get('/health');

        $response->assertStatus(200);
        $response->assertJson(['database_tables' => 'ok']);
        $this->assertArrayNotHasKey('database_tables_missing', $response->json());
    }

    public function test_health_endpoint_degrades_when_database_cache_table_is_missing(): void
    {
        config([
            'session.driver' => 'array',
            'cache.default' => 'database',
        ]);
        Schema::dropIfExists('cache');

        $response = $this->get('/health');

        $response->assertStatus(503);
        $response->assertJson([
            'status' => 'degraded',
            'database_tables' => 'error',
            'database_tables_missing' => ['cache'],
        ]);
    }

    public function test_health_endpoint_degrades_when_database_session_table_is_missing(): void
    {
        config([
            'session.driver' => 'database',
            'cache.default' => 'array',
        ]);
        Schema::dropIfExists('sessions');

        $response = $this->get('/health');

        $response->assertStatus(503);
        $response->assertJson([
            'status' => 'degraded',
            'database_tables' => 'error',
            'database_tables_missing' => ['sessions'],
        ]);
    }

    public function test_health_endpoint_reports_public_storage_link_status_for_public_disk(): void
    {
        config(['filesystems.default' => 'public']);
        $this->usePublicStorageFixture(linked: true);

        $response = $this->get('/health');

        $response->assertStatus(200);
        $response->assertJson(['public_storage_link' => 'ok']);
    }

    public function test_health_endpoint_degrades_when_public_storage_link_is_missing_for_public_disk(): void
    {
        config(['filesystems.default' => 'public']);
        $this->usePublicStorageFixture(linked: false);

        $response = $this->get('/health');

        $response->assertStatus(503);
        $response->assertJson([
            'status' => 'degraded',
            'public_storage_link' => 'error',
        ]);
    }

    public function test_health_endpoint_skips_public_storage_link_for_non_public_disk(): void
    {
        config(['filesystems.default' => 'local']);
        $this->usePublicStorageFixture(linked: false);

        $response = $this->get('/health');

        $response->assertStatus(200);
        $response->assertJson(['public_storage_link' => 'skipped']);
    }

    public function test_health_endpoint_reports_gd_webp_status(): void
    {
        $response = $this->get('/health');

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertArrayHasKey('gd_webp', $data);
        $this->assertContains($data['gd_webp'], ['ok', 'error']);
    }

    public function test_health_endpoint_does_not_require_jobs_table_for_sync_queue(): void
    {
        config(['queue.default' => 'sync']);

        DB::shouldReceive('select')
            ->once()
            ->with('SELECT 1')
            ->andReturn([]);
        DB::shouldNotReceive('table');

        $response = $this->get('/health');

        $response->assertStatus(200);
        $response->assertJson(['queue' => 'ok']);
    }

    private function usePublicStorageFixture(bool $linked): void
    {
        if ($this->originalPublicPath === null) {
            $this->originalPublicPath = public_path();
        }

        $publicPath = storage_path('framework/testing/public-health-'.Str::uuid());
        File::ensureDirectoryExists($publicPath);
        File::ensureDirectoryExists(storage_path('app/public'));

        if ($linked) {
            symlink(storage_path('app/public'), $publicPath.'/storage');
        }

        $this->temporaryPublicPaths[] = $publicPath;
        $this->app->usePublicPath($publicPath);
    }
}
