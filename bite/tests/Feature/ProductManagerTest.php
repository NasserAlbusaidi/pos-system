<?php

namespace Tests\Feature;

use App\Livewire\ProductManager;
use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_product()
    {
        $shop = Shop::factory()->create();
        $user = User::factory()->create(['shop_id' => $shop->id, 'role' => 'admin']);
        $category = Category::factory()->create(['shop_id' => $shop->id]);

        Livewire::actingAs($user)
            ->test(ProductManager::class)
            ->set('name_en', 'Latte')
            ->set('price', 4.50)
            ->set('category_id', $category->id)
            ->call('save')
            ->assertSet('name_en', null); // Resets after save

        $this->assertDatabaseHas('products', [
            'name_en' => 'Latte',
            'price' => 4.50,
            'shop_id' => $shop->id,
        ]);
    }

    public function test_lapsed_paid_subscription_cannot_hydrate_product_manager(): void
    {
        config(['billing.plans.pro.stripe_price_id' => 'price_pro_test']);

        $shop = Shop::factory()->create(['trial_ends_at' => null]);
        $category = Category::factory()->create(['shop_id' => $shop->id]);
        $user = User::factory()->create(['shop_id' => $shop->id, 'role' => 'admin']);

        $shop->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_lapsed_product_limit',
            'stripe_status' => 'canceled',
            'stripe_price' => 'price_pro_test',
            'quantity' => 1,
            'ends_at' => now()->subDay(),
        ]);

        Product::factory()
            ->count(20)
            ->create([
                'shop_id' => $shop->id,
                'category_id' => $category->id,
            ]);

        Livewire::actingAs($user)
            ->test(ProductManager::class)
            ->assertForbidden();

        $this->assertDatabaseMissing('products', [
            'shop_id' => $shop->id,
            'name_en' => 'Lapsed Plan Burger',
        ]);
        $this->assertSame(20, Product::where('shop_id', $shop->id)->count());
    }

    public function test_product_price_input_accepts_three_decimal_omr_values(): void
    {
        $shop = Shop::factory()->create([
            'currency_code' => 'OMR',
            'currency_symbol' => 'OMR',
            'currency_decimals' => 3,
        ]);
        $user = User::factory()->create(['shop_id' => $shop->id, 'role' => 'admin']);
        Category::factory()->create(['shop_id' => $shop->id]);

        Livewire::actingAs($user)
            ->test(ProductManager::class)
            ->assertSeeHtml('step="0.001" wire:model="price"');
    }
}
