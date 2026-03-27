<?php

namespace Tests\Feature\Livewire;

use App\Livewire\ModifierManager;
use App\Livewire\OnboardingWizard;
use App\Livewire\ProductManager;
use App\Models\Category;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * SEC-03 / D-16 — Input validation sweep across all Livewire components.
 *
 * This test verifies that all Livewire components accepting user input
 * have validation rules applied and reject invalid data before mutations.
 * It focuses on components known to accept admin or guest input.
 *
 * Components confirmed to have validation (checked during audit):
 *   - OnboardingWizard: saveShopProfile, saveMenuItems, addStaff, extractMenu
 *   - ProductManager: save() via rules() method (name_en, price, category_id, image)
 *   - ModifierManager: save(), addOption() — name, selection bounds, price, group scoping
 *   - ShiftReport, CashReconciliation, ShopSettings, PinLogin: all have validate() calls
 *   - OrderTracker: submitFeedback() — now has validate() after SEC-03 fix
 *
 * Components without user-writable state (excluded from sweep):
 *   - ShopDashboard: setDailyGoal() clamps internally — no free-text input
 *   - KitchenDisplay: updateStatus() validates against enum allowlist inline
 *   - ReportsDashboard: rangeDays clamped in updatedRangeDays()
 *   - BillingSettings: subscribe() validates plan key against config allowlist
 *   - GuestMenu: submitOrder() validates phone format and cart integrity
 */
class InputValidationSweepTest extends TestCase
{
    use RefreshDatabase;

    // ── OnboardingWizard ──────────────────────────────────────────────────

    public function test_onboarding_wizard_save_shop_profile_rejects_invalid_tax_rate(): void
    {
        $shop = Shop::create(['name' => 'My Shop', 'slug' => 'my-shop']);
        // Factory defaults: role=server, but onboarding only loads for admin.
        // branding without 'onboarding_completed' key means onboarding is not done.
        $admin = User::factory()->create([
            'shop_id' => $shop->id,
            'role' => 'admin',
        ]);

        Livewire::actingAs($admin)
            ->test(OnboardingWizard::class)
            ->set('tax_rate', 200) // max is 100
            ->set('accent', '#CC5500')
            ->set('paper', '#FDFCF8')
            ->set('ink', '#1A1918')
            ->call('saveShopProfile')
            ->assertHasErrors(['tax_rate']);
    }

    public function test_onboarding_wizard_normalizes_hex_colors_before_validation(): void
    {
        $shop = Shop::create(['name' => 'My Shop', 'slug' => 'my-shop']);
        $admin = User::factory()->create([
            'shop_id' => $shop->id,
            'role' => 'admin',
        ]);

        // When an invalid hex color is set, the updatedAccent() hook normalizes it
        // to the fallback before saveShopProfile() runs. This is defense-in-depth:
        // invalid inputs are sanitized, not stored. Setting the property directly
        // bypasses the updatedXxx hooks, so we verify the component normalizes
        // by testing the hook response via set().
        $component = Livewire::actingAs($admin)
            ->test(OnboardingWizard::class)
            ->set('accent', 'not-a-color'); // triggers updatedAccent() hook

        // The normalizeHex hook replaces invalid value with the fallback color.
        // The accent should be a valid hex (the fallback) after the hook runs.
        $accent = $component->get('accent');
        $this->assertMatchesRegularExpression('/^#[A-Fa-f0-9]{6}$/', $accent, 'normalizeHex must produce a valid 6-digit hex color');
    }

    // ── ProductManager ────────────────────────────────────────────────────

    public function test_product_manager_rejects_empty_product_name(): void
    {
        $shop = Shop::create(['name' => 'My Shop', 'slug' => 'my-shop']);
        $category = Category::create(['shop_id' => $shop->id, 'name_en' => 'Coffee']);
        $admin = User::factory()->create(['shop_id' => $shop->id, 'role' => 'admin']);

        Livewire::actingAs($admin)
            ->test(ProductManager::class)
            ->set('name_en', '')  // empty — required|min:3
            ->set('price', 5.00)
            ->set('category_id', $category->id)
            ->call('save')
            ->assertHasErrors(['name_en']);
    }

    public function test_product_manager_rejects_negative_price(): void
    {
        $shop = Shop::create(['name' => 'My Shop', 'slug' => 'my-shop']);
        $category = Category::create(['shop_id' => $shop->id, 'name_en' => 'Coffee']);
        $admin = User::factory()->create(['shop_id' => $shop->id, 'role' => 'admin']);

        Livewire::actingAs($admin)
            ->test(ProductManager::class)
            ->set('name_en', 'Espresso')
            ->set('price', -1.00)   // min:0
            ->set('category_id', $category->id)
            ->call('save')
            ->assertHasErrors(['price']);
    }

    // ── ModifierManager ───────────────────────────────────────────────────

    public function test_modifier_manager_rejects_empty_group_name(): void
    {
        $shop = Shop::create(['name' => 'My Shop', 'slug' => 'my-shop']);
        $admin = User::factory()->create(['shop_id' => $shop->id, 'role' => 'admin']);

        Livewire::actingAs($admin)
            ->test(ModifierManager::class)
            ->set('name_en', '')  // required|min:2
            ->set('min_selection', 0)
            ->set('max_selection', 1)
            ->call('save')
            ->assertHasErrors(['name_en']);
    }

    public function test_modifier_manager_rejects_zero_max_selection(): void
    {
        $shop = Shop::create(['name' => 'My Shop', 'slug' => 'my-shop']);
        $admin = User::factory()->create(['shop_id' => $shop->id, 'role' => 'admin']);

        Livewire::actingAs($admin)
            ->test(ModifierManager::class)
            ->set('name_en', 'Sizes')
            ->set('min_selection', 0)
            ->set('max_selection', 0) // min:1
            ->call('save')
            ->assertHasErrors(['max_selection']);
    }

    // ── KitchenDisplay — enum allowlist ────────────────────────────────────

    public function test_kitchen_display_rejects_arbitrary_status_string(): void
    {
        $shop = Shop::create(['name' => 'My Shop', 'slug' => 'my-shop']);
        $kitchen = User::factory()->create(['shop_id' => $shop->id, 'role' => 'kitchen']);

        // Calling updateStatus with an invalid status must abort(422) — not mutate any data.
        // Livewire wraps the abort as a 422 HTTP response; assertStatus confirms rejection.
        Livewire::actingAs($kitchen)
            ->test(\App\Livewire\KitchenDisplay::class)
            ->call('updateStatus', 0, 'hacked_status')
            ->assertStatus(422);
    }

    // ── BillingSettings — plan allowlist ──────────────────────────────────

    public function test_billing_settings_rejects_unknown_plan_key(): void
    {
        $shop = Shop::create(['name' => 'My Shop', 'slug' => 'my-shop']);
        $admin = User::factory()->create(['shop_id' => $shop->id, 'role' => 'admin']);

        // subscribe() with an unknown plan key must dispatch a toast error and not throw.
        // assertDispatched verifies the toast event was dispatched with error variant.
        Livewire::actingAs($admin)
            ->test(\App\Livewire\BillingSettings::class)
            ->call('subscribe', 'malicious_plan_key')
            ->assertDispatched('toast', message: 'Invalid plan selected.', variant: 'error');
    }
}
