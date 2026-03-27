<?php

namespace Tests\Feature;

use App\Http\Middleware\LogSlowRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class LogSlowRequestsTest extends TestCase
{
    private LogSlowRequests $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new LogSlowRequests();
    }

    public function test_logs_warning_when_request_exceeds_2000ms_threshold(): void
    {
        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function (string $message, array $context): bool {
                return $message === 'Slow request'
                    && $context['duration_ms'] >= 2000;
            });

        $request = Request::create('/test-slow', 'GET');

        $this->middleware->handle($request, function () {
            usleep(2100 * 1000); // 2.1 seconds

            return new Response('ok', 200);
        });
    }

    public function test_does_not_log_when_request_is_under_2000ms_threshold(): void
    {
        Log::shouldReceive('warning')->never();

        $request = Request::create('/test-fast', 'GET');

        $this->middleware->handle($request, function () {
            return new Response('ok', 200);
        });
    }

    public function test_log_context_contains_all_required_fields(): void
    {
        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function (string $message, array $context): bool {
                return $message === 'Slow request'
                    && isset($context['method'])
                    && isset($context['path'])
                    && isset($context['duration_ms'])
                    && isset($context['ip'])
                    && isset($context['status'])
                    && $context['method'] === 'GET'
                    && $context['path'] === 'test-fields'
                    && $context['status'] === 200;
            });

        $request = Request::create('/test-fields', 'GET');

        $this->middleware->handle($request, function () {
            usleep(2100 * 1000); // 2.1 seconds

            return new Response('ok', 200);
        });
    }

    public function test_middleware_returns_response_unchanged(): void
    {
        Log::shouldReceive('warning')->zeroOrMoreTimes();

        $request = Request::create('/test-passthrough', 'POST');

        $expectedResponse = new Response('body content', 201);

        $response = $this->middleware->handle($request, function () use ($expectedResponse) {
            return $expectedResponse;
        });

        $this->assertSame($expectedResponse, $response);
    }

    public function test_middleware_registered_globally_in_bootstrap(): void
    {
        // Verify LogSlowRequests is referenced in bootstrap/app.php
        $bootstrapContent = file_get_contents(base_path('bootstrap/app.php'));

        $this->assertStringContainsString(
            'LogSlowRequests',
            $bootstrapContent,
            'LogSlowRequests middleware must be registered globally in bootstrap/app.php',
        );
    }
}
