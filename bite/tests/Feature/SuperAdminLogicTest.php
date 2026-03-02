<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SuperAdminLogicTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Define a test route protected by our future middleware
        Route::middleware(['web', 'auth', 'super_admin'])->get('/test-super-admin', function () {
            return 'Success';
        });
    }

    public function test_user_has_super_admin_flag(): void
    {
        $user = User::factory()->superAdmin()->create();
        $this->assertTrue($user->is_super_admin);
    }

    public function test_non_super_admin_is_blocked_from_admin_routes(): void
    {
        $user = User::factory()->create(['is_super_admin' => false]);

        $this->actingAs($user)
            ->get('/test-super-admin')
            ->assertStatus(403);
    }

    public function test_super_admin_is_allowed_into_admin_routes(): void
    {
        $user = User::factory()->superAdmin()->create();

        $this->actingAs($user)
            ->get('/test-super-admin')
            ->assertStatus(200)
            ->assertSee('Success');
    }
}
