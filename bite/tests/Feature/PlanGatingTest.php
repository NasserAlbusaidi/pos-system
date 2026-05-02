<?php

namespace Tests\Feature;

use App\Models\Shop;
use App\Models\User;
use App\Services\BillingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanGatingTest extends TestCase
{
    use RefreshDatabase;

    private const UPGRADE_MESSAGE = 'This feature requires Pro plan.';

    protected function setUp(): void
    {
        parent::setUp();

        config(['billing.plans.pro.stripe_price_id' => 'price_pro_test']);
    }

    public function test_free_plan_blocked_from_reports(): void
    {
        $user = $this->makeUserForPlan('free');

        $this->assertFreeUserBlocked($user, '/reports');
    }

    public function test_pro_plan_allowed_to_reports(): void
    {
        $user = $this->makeUserForPlan('pro');

        $this->actingAs($user)
            ->get('/reports')
            ->assertOk();
    }

    public function test_trial_user_treated_as_pro(): void
    {
        $user = $this->makeUserForPlan('trial');

        $this->actingAs($user)
            ->get('/reports')
            ->assertOk();
    }

    public function test_super_admin_bypasses_plan_gating(): void
    {
        $shop = $this->makeShop('free');
        $superAdmin = User::factory()->superAdmin()->create([
            'shop_id' => $shop->id,
            'role' => 'admin',
        ]);

        $this->actingAs($superAdmin)
            ->get('/reports')
            ->assertOk();
    }

    public function test_billing_route_remains_accessible_to_free_users(): void
    {
        $user = $this->makeUserForPlan('free');

        $this->actingAs($user)
            ->get('/billing')
            ->assertOk()
            ->assertSessionMissing('billing_notice');
    }

    public function test_free_plan_blocked_from_reports_export(): void
    {
        $user = $this->makeUserForPlan('free');

        $this->assertFreeUserBlocked($user, '/reports/export');
    }

    public function test_pro_plan_allowed_to_reports_export(): void
    {
        $user = $this->makeUserForPlan('pro');

        $this->actingAs($user)
            ->get('/reports/export')
            ->assertOk();
    }

    public function test_free_plan_blocked_from_menu_engineering(): void
    {
        $user = $this->makeUserForPlan('free');

        $this->assertFreeUserBlocked($user, '/menu-engineering');
    }

    public function test_pro_plan_allowed_to_menu_engineering(): void
    {
        $user = $this->makeUserForPlan('pro');

        $this->actingAs($user)
            ->get('/menu-engineering')
            ->assertOk();
    }

    public function test_trial_user_allowed_to_menu_engineering(): void
    {
        $user = $this->makeUserForPlan('trial');

        $this->actingAs($user)
            ->get('/menu-engineering')
            ->assertOk();
    }

    public function test_free_plan_blocked_from_pricing_rules(): void
    {
        $user = $this->makeUserForPlan('free');

        $this->assertFreeUserBlocked($user, '/pricing-rules');
    }

    public function test_pro_plan_allowed_to_pricing_rules(): void
    {
        $user = $this->makeUserForPlan('pro');

        $this->actingAs($user)
            ->get('/pricing-rules')
            ->assertOk();
    }

    public function test_trial_user_allowed_to_pricing_rules(): void
    {
        $user = $this->makeUserForPlan('trial');

        $this->actingAs($user)
            ->get('/pricing-rules')
            ->assertOk();
    }

    public function test_billing_service_handles_every_gated_feature(): void
    {
        $billing = app(BillingService::class);
        $freeShop = $this->makeShop('free');
        $proShop = $this->makeShop('pro');

        foreach (['reports', 'menu_engineering', 'pricing_rules'] as $feature) {
            $this->assertFalse($billing->canAccess($freeShop, $feature), "{$feature} should be blocked on Free.");
            $this->assertTrue($billing->canAccess($proShop, $feature), "{$feature} should be allowed on Pro.");
        }

        $this->assertFalse($billing->canAccess($proShop, 'unknown_feature'));
    }

    private function assertFreeUserBlocked(User $user, string $path): void
    {
        $this->actingAs($user)
            ->get($path)
            ->assertRedirect(route('billing'))
            ->assertSessionHas('billing_notice', self::UPGRADE_MESSAGE);
    }

    private function makeUserForPlan(string $plan): User
    {
        $shop = $this->makeShop($plan);

        return User::factory()->create([
            'shop_id' => $shop->id,
            'role' => 'admin',
        ]);
    }

    private function makeShop(string $plan): Shop
    {
        $shop = Shop::factory()->create();
        $shop->trial_ends_at = null;
        $shop->save();

        if ($plan === 'trial') {
            $shop->trial_ends_at = now()->addDays(14);
            $shop->save();
        }

        if ($plan === 'pro') {
            $shop->subscriptions()->create([
                'type' => 'default',
                'stripe_id' => 'sub_test_'.$shop->id,
                'stripe_status' => 'active',
                'stripe_price' => config('billing.plans.pro.stripe_price_id'),
                'quantity' => 1,
            ]);
        }

        return $shop;
    }
}
