<?php

namespace Tests\Feature;

use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression tests for free-plan access gating.
 *
 * Free-plan shops have no Stripe subscription and no trial. They must still
 * be able to access KDS, Catalog (products, menu-builder, modifiers), and
 * Settings. Only POS is accessible to all plans too. The `subscribed`
 * middleware must NOT redirect free-plan shops to billing.
 */
class FreePlanAccessTest extends TestCase
{
    use RefreshDatabase;

    protected Shop $shop;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a shop with NO trial and NO Stripe subscription — pure free plan.
        $this->shop = Shop::create([
            'name'          => 'Free Shop',
            'slug'          => 'free-shop',
            'trial_ends_at' => null,
        ]);
    }

    public function test_free_plan_admin_can_access_kds(): void
    {
        $admin = $this->makeUser('admin');

        $this->actingAs($admin)->get('/kds')->assertOk();
    }

    public function test_free_plan_admin_can_access_products(): void
    {
        $admin = $this->makeUser('admin');

        $this->actingAs($admin)->get('/products')->assertOk();
    }

    public function test_free_plan_admin_can_access_menu_builder(): void
    {
        $admin = $this->makeUser('admin');

        $this->actingAs($admin)->get('/menu-builder')->assertOk();
    }

    public function test_free_plan_admin_can_access_settings(): void
    {
        $admin = $this->makeUser('admin');

        $this->actingAs($admin)->get('/settings')->assertOk();
    }

    public function test_free_plan_admin_can_access_pos(): void
    {
        $admin = $this->makeUser('admin');

        $this->actingAs($admin)->get('/pos')->assertOk();
    }

    public function test_free_plan_manager_can_access_kds_and_catalog(): void
    {
        $manager = $this->makeUser('manager');

        $this->actingAs($manager)->get('/kds')->assertOk();
        $this->actingAs($manager)->get('/products')->assertOk();
        $this->actingAs($manager)->get('/settings')->assertOk();
    }

    public function test_free_plan_kitchen_can_access_kds(): void
    {
        $kitchen = $this->makeUser('kitchen');

        $this->actingAs($kitchen)->get('/kds')->assertOk();
    }

    /**
     * A shop with a cancelled+expired Stripe subscription (not on grace period)
     * should still be redirected — that is a genuinely lapsed subscription,
     * not a free plan.
     *
     * We can simulate this by creating a subscription record whose stripe_status
     * is 'canceled' and that is not in a grace period (ends_at is in the past).
     */
    public function test_expired_subscription_shop_is_redirected_to_billing(): void
    {
        $admin = $this->makeUser('admin');

        // Create a cancelled, fully-lapsed subscription for this shop.
        // Column is `type` (not `name`) per Cashier v15+ schema.
        $this->shop->subscriptions()->create([
            'type'            => 'default',
            'stripe_id'       => 'sub_test_expired',
            'stripe_status'   => 'canceled',
            'stripe_price'    => 'price_test',
            'quantity'        => 1,
            'ends_at'         => now()->subDay(), // grace period over
        ]);

        $this->actingAs($admin)
            ->get('/kds')
            ->assertRedirect(route('billing'));
    }

    protected function makeUser(string $role): User
    {
        return User::factory()->create([
            'shop_id' => $this->shop->id,
            'role'    => $role,
        ]);
    }
}
