<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_verification_notice_route_is_disabled(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/verify-email');

        $response->assertNotFound();
    }

    public function test_signed_email_verification_route_is_disabled(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/verify-email/1/hash');

        $response->assertNotFound();
    }
}
