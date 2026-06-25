<?php

namespace Tests\Feature;

use App\Livewire\OnboardingWizard;
use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OnboardingAccessAndLimitsTest extends TestCase
{
    use RefreshDatabase;

    public function test_suspended_shop_admin_cannot_access_onboarding(): void
    {
        $shop = Shop::factory()->create(['status' => 'suspended']);
        $admin = User::factory()->create(['shop_id' => $shop->id, 'role' => 'admin']);

        $this->actingAs($admin)
            ->get('/onboarding')
            ->assertForbidden()
            ->assertSee('This account has been suspended.');
    }

    public function test_lapsed_subscription_admin_is_redirected_from_onboarding_to_billing(): void
    {
        $shop = Shop::factory()->create(['trial_ends_at' => null]);
        $shop->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_onboarding_lapsed',
            'stripe_status' => 'canceled',
            'stripe_price' => 'price_test',
            'quantity' => 1,
            'ends_at' => now()->subDay(),
        ]);
        $admin = User::factory()->create(['shop_id' => $shop->id, 'role' => 'admin']);

        $this->actingAs($admin)
            ->get('/onboarding')
            ->assertRedirect(route('billing'));
    }

    public function test_free_plan_onboarding_cannot_add_staff_over_plan_limit(): void
    {
        $shop = $this->makeFreeOnboardingShop();
        $admin = User::factory()->create(['shop_id' => $shop->id, 'role' => 'admin']);

        Livewire::actingAs($admin)
            ->test(OnboardingWizard::class)
            ->set('staffName', 'Extra Server')
            ->set('staffEmail', 'extra-server@example.test')
            ->set('staffPin', '2468')
            ->call('addStaff');

        $this->assertDatabaseMissing('users', ['email' => 'extra-server@example.test']);
        $this->assertSame(1, User::where('shop_id', $shop->id)->count());
    }

    public function test_free_plan_onboarding_cannot_add_manual_menu_items_over_plan_limit(): void
    {
        $shop = $this->makeFreeOnboardingShop();
        $admin = User::factory()->create(['shop_id' => $shop->id, 'role' => 'admin']);
        $this->seedProducts($shop, 20);

        Livewire::actingAs($admin)
            ->test(OnboardingWizard::class)
            ->set('menuItems', [['name' => 'Over Limit', 'price' => '1.000']])
            ->call('saveMenuItems');

        $this->assertSame(20, Product::where('shop_id', $shop->id)->count());
        $this->assertDatabaseMissing('products', [
            'shop_id' => $shop->id,
            'name_en' => 'Over Limit',
        ]);
    }

    public function test_free_plan_onboarding_cannot_save_extracted_menu_over_plan_limit(): void
    {
        $shop = $this->makeFreeOnboardingShop();
        $admin = User::factory()->create(['shop_id' => $shop->id, 'role' => 'admin']);
        $items = collect(range(1, 21))
            ->map(fn (int $index) => [
                'category_en' => 'Menu',
                'category_ar' => 'القائمة',
                'name_en' => "Extracted {$index}",
                'name_ar' => '',
                'description_en' => '',
                'description_ar' => '',
                'price' => 1.000,
            ])
            ->all();

        Livewire::actingAs($admin)
            ->test(OnboardingWizard::class)
            ->set('menuMode', 'review')
            ->set('extractedItems', $items)
            ->call('saveExtractedMenu');

        $this->assertSame(0, Product::where('shop_id', $shop->id)->count());
    }

    private function makeFreeOnboardingShop(): Shop
    {
        return Shop::factory()->create([
            'trial_ends_at' => null,
            'branding' => [],
        ]);
    }

    private function seedProducts(Shop $shop, int $count): void
    {
        $category = Category::create(['shop_id' => $shop->id, 'name_en' => 'Menu']);

        foreach (range(1, $count) as $index) {
            Product::forceCreate([
                'shop_id' => $shop->id,
                'category_id' => $category->id,
                'name_en' => "Existing {$index}",
                'price' => 1,
            ]);
        }
    }
}
