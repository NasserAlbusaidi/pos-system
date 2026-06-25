<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Admin\AuditLogs;
use App\Livewire\Admin\MenuBuilder;
use App\Livewire\Admin\MenuEngineering;
use App\Livewire\Admin\PricingRules;
use App\Livewire\Admin\ReportsDashboard;
use App\Livewire\BillingSettings;
use App\Livewire\CashReconciliation;
use App\Livewire\KitchenDisplay;
use App\Livewire\ModifierManager;
use App\Livewire\OnboardingWizard;
use App\Livewire\PosDashboard;
use App\Livewire\ProductManager;
use App\Livewire\ShiftReport;
use App\Livewire\ShopDashboard;
use App\Livewire\ShopSettings;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The route-level role middleware only guards the GET render. The livewire/update
 * endpoint runs on bare `web` middleware, so without a component-level guard a
 * low-trust PIN account (server/kitchen) can hydrate any admin component and invoke
 * its actions. These tests assert each component self-guards on boot (mount + every
 * hydration). See issue #52.
 */
class AdminComponentRbacTest extends TestCase
{
    use RefreshDatabase;

    /** Components requiring manager or admin. */
    private const MANAGER_ADMIN = [
        ProductManager::class,
        ModifierManager::class,
        ShopSettings::class,
        ShiftReport::class,
        CashReconciliation::class,
        MenuBuilder::class,
        ReportsDashboard::class,
        AuditLogs::class,
        MenuEngineering::class,
        PricingRules::class,
    ];

    private const PRO_ONLY = [
        ReportsDashboard::class,
        MenuEngineering::class,
        PricingRules::class,
    ];

    protected Shop $shop;

    protected function setUp(): void
    {
        parent::setUp();

        $this->shop = Shop::create(['name' => 'Bite', 'slug' => 'bite']);
        $this->shop->trial_ends_at = now()->addDays(14);
        $this->shop->save();
    }

    public function test_server_is_forbidden_from_manager_admin_components(): void
    {
        $server = $this->makeUser('server');

        foreach (self::MANAGER_ADMIN as $component) {
            $this->assertForbidden($component, $server);
        }
    }

    public function test_kitchen_is_forbidden_from_manager_admin_components(): void
    {
        $kitchen = $this->makeUser('kitchen');

        foreach (self::MANAGER_ADMIN as $component) {
            $this->assertForbidden($component, $kitchen);
        }
    }

    public function test_kitchen_is_forbidden_from_pos_and_server_from_kds(): void
    {
        $this->assertForbidden(PosDashboard::class, $this->makeUser('kitchen'));
        $this->assertForbidden(ShopDashboard::class, $this->makeUser('kitchen'));
        $this->assertForbidden(KitchenDisplay::class, $this->makeUser('server'));
    }

    public function test_only_admin_can_reach_admin_only_components(): void
    {
        foreach ([BillingSettings::class, OnboardingWizard::class] as $component) {
            $this->assertForbidden($component, $this->makeUser('server'));
            $this->assertForbidden($component, $this->makeUser('kitchen'));
            $this->assertForbidden($component, $this->makeUser('manager'));

            $this->assertAllowed($component, $this->makeUser('admin'));
        }
    }

    public function test_manager_can_reach_operational_components_pos_and_kds(): void
    {
        $manager = $this->makeUser('manager');

        foreach (self::MANAGER_ADMIN as $component) {
            $this->assertAllowed($component, $manager);
        }

        $this->assertAllowed(PosDashboard::class, $manager);
        $this->assertAllowed(KitchenDisplay::class, $manager);
    }

    public function test_admin_can_reach_every_guarded_component(): void
    {
        $admin = $this->makeUser('admin');

        foreach ([...self::MANAGER_ADMIN, PosDashboard::class, KitchenDisplay::class, BillingSettings::class, OnboardingWizard::class] as $component) {
            $this->assertAllowed($component, $admin);
        }
    }

    public function test_server_can_reach_shop_dashboard_component(): void
    {
        $this->assertAllowed(ShopDashboard::class, $this->makeUser('server'));
    }

    public function test_suspended_shop_cannot_hydrate_operational_component(): void
    {
        $this->shop->forceFill(['status' => 'suspended'])->save();

        $this->actingAs($this->makeUser('admin'));

        Livewire::test(PosDashboard::class)->assertForbidden();
    }

    public function test_lapsed_subscription_cannot_hydrate_operational_component(): void
    {
        $this->shop->forceFill(['trial_ends_at' => null])->save();

        $this->shop->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_lapsed_livewire',
            'stripe_status' => 'canceled',
            'stripe_price' => 'price_test',
            'quantity' => 1,
            'ends_at' => now()->subDay(),
        ]);

        $this->actingAs($this->makeUser('admin'));

        Livewire::test(PosDashboard::class)->assertForbidden();
    }

    public function test_lapsed_subscription_admin_can_still_hydrate_billing_component(): void
    {
        $this->shop->forceFill(['trial_ends_at' => null])->save();

        $this->shop->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_lapsed_billing_livewire',
            'stripe_status' => 'canceled',
            'stripe_price' => 'price_test',
            'quantity' => 1,
            'ends_at' => now()->subDay(),
        ]);

        $this->assertAllowed(BillingSettings::class, $this->makeUser('admin'));
    }

    public function test_free_plan_cannot_hydrate_pro_only_components(): void
    {
        $this->shop->forceFill(['trial_ends_at' => null])->save();
        $admin = $this->makeUser('admin');

        foreach (self::PRO_ONLY as $component) {
            $this->assertForbidden($component, $admin);
        }
    }

    public function test_free_plan_can_still_hydrate_non_pro_manager_components(): void
    {
        $this->shop->forceFill(['trial_ends_at' => null])->save();
        $admin = $this->makeUser('admin');

        foreach ([ProductManager::class, ModifierManager::class, ShopSettings::class, ShiftReport::class, CashReconciliation::class, MenuBuilder::class, AuditLogs::class] as $component) {
            $this->assertAllowed($component, $admin);
        }
    }

    protected function makeUser(string $role): User
    {
        return User::factory()->create([
            'shop_id' => $this->shop->id,
            'role' => $role,
        ]);
    }

    protected function assertForbidden(string $component, User $user): void
    {
        $this->actingAs($user);

        Livewire::test($component)->assertForbidden();
    }

    protected function assertAllowed(string $component, User $user): void
    {
        $this->actingAs($user);

        Livewire::test($component)->assertOk();
    }
}
