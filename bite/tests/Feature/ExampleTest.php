<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_landing_page_renders_new_design_with_working_ctas(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        // Key copy from the reskinned landing
        $response->assertSee('Snap-to-Menu');
        $response->assertSee('Your floor, your kitchen, your menu', false);
        $response->assertSee('Start free trial');
        // CTAs point at the real auth routes, not placeholder anchors
        $response->assertSee(route('register'), false);
        $response->assertSee(route('login'), false);
        // Pricing is driven by config, not hardcoded
        $response->assertSee('OMR '.config('billing.plans.pro.price'), false);
    }
}
