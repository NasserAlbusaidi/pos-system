<?php

namespace Tests\Feature;

use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuspendedShopTest extends TestCase
{
    use RefreshDatabase;

    public function test_suspended_shop_user_blocked_from_pos(): void
    {
        $user = $this->makeUser('admin', 'suspended');

        $this->actingAs($user)
            ->get('/pos')
            ->assertForbidden()
            ->assertSee('This account has been suspended.')
            ->assertSee('Contact support.');
    }

    public function test_suspended_shop_user_blocked_from_kds(): void
    {
        $user = $this->makeUser('admin', 'suspended');

        $this->actingAs($user)
            ->get('/kds')
            ->assertForbidden()
            ->assertSee('This account has been suspended.');
    }

    public function test_suspended_shop_user_blocked_from_products_and_reports(): void
    {
        $user = $this->makeUser('admin', 'suspended');

        foreach (['/products', '/reports'] as $path) {
            $this->actingAs($user)
                ->get($path)
                ->assertForbidden()
                ->assertSee('This account has been suspended.');
        }
    }

    public function test_suspended_shop_user_can_still_access_billing(): void
    {
        $user = $this->makeUser('admin', 'suspended');

        $this->actingAs($user)
            ->get('/billing')
            ->assertOk()
            ->assertDontSee('This account has been suspended.');
    }

    public function test_active_shop_user_can_access_pos(): void
    {
        $user = $this->makeUser('admin', 'active');

        $this->actingAs($user)
            ->get('/pos')
            ->assertOk()
            ->assertDontSee('This account has been suspended.');
    }

    public function test_super_admin_unaffected_by_suspension(): void
    {
        $user = $this->makeUser('admin', 'suspended', isSuperAdmin: true);

        $this->actingAs($user)
            ->get('/pos')
            ->assertOk()
            ->assertDontSee('This account has been suspended.');
    }

    private function makeUser(string $role, string $shopStatus, bool $isSuperAdmin = false): User
    {
        $shop = Shop::factory()->create();
        $shop->status = $shopStatus;
        $shop->save();

        return User::factory()->create([
            'shop_id' => $shop->id,
            'role' => $role,
            'is_super_admin' => $isSuperAdmin,
        ]);
    }
}
