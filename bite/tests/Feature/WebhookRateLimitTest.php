<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class WebhookRateLimitTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Clear rate limiter state before each test
        RateLimiter::clear('stripe-webhooks|127.0.0.1');
    }

    protected function tearDown(): void
    {
        RateLimiter::clear('stripe-webhooks|127.0.0.1');
        parent::tearDown();
    }

    public function test_webhook_endpoint_returns_429_after_60_requests_per_minute(): void
    {
        // Make 60 requests (these may fail with 400/422/500 due to Stripe signature,
        // but should NOT be rate limited)
        for ($i = 0; $i < 60; $i++) {
            $response = $this->post('/webhooks/stripe', [], [
                'CONTENT_TYPE' => 'application/json',
            ]);
            $this->assertNotEquals(429, $response->status(), "Request $i should not be rate limited");
        }

        // 61st request should be rate limited
        $response = $this->post('/webhooks/stripe', [], [
            'CONTENT_TYPE' => 'application/json',
        ]);

        $response->assertStatus(429);
    }

    public function test_webhook_429_response_includes_retry_after_header(): void
    {
        // Exhaust the rate limit
        for ($i = 0; $i < 60; $i++) {
            $this->post('/webhooks/stripe');
        }

        $response = $this->post('/webhooks/stripe');

        $response->assertStatus(429);
        $response->assertHeader('Retry-After', '60');
    }

    public function test_webhook_subscription_endpoint_also_rate_limited(): void
    {
        // Make 60 requests
        for ($i = 0; $i < 60; $i++) {
            $response = $this->post('/webhooks/stripe/subscription');
            $this->assertNotEquals(429, $response->status(), "Request $i should not be rate limited");
        }

        // 61st should be rate limited
        $response = $this->post('/webhooks/stripe/subscription');
        $response->assertStatus(429);
    }
}
