<?php

namespace Tests\Feature;

use App\Livewire\ProductManager;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductAvailabilityToggleTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_toggle_product_availability_from_list(): void
    {
        $shop = Shop::create(['name' => 'Test Shop', 'slug' => 'test-shop']);
        $category = Category::create(['shop_id' => $shop->id, 'name_en' => 'Drinks']);
        $user = User::factory()->create(['shop_id' => $shop->id, 'role' => 'admin']);

        $product = Product::forceCreate([
            'shop_id' => $shop->id,
            'category_id' => $category->id,
            'name_en' => 'Espresso',
            'price' => 1.500,
            'is_available' => true,
        ]);

        Livewire::actingAs($user)
            ->test(ProductManager::class)
            ->call('toggleAvailability', $product->id);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'is_available' => false,
        ]);

        Livewire::actingAs($user)
            ->test(ProductManager::class)
            ->call('toggleAvailability', $product->id);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'is_available' => true,
        ]);
    }

    public function test_toggle_creates_audit_log(): void
    {
        $shop = Shop::create(['name' => 'Test Shop', 'slug' => 'test-shop']);
        $category = Category::create(['shop_id' => $shop->id, 'name_en' => 'Drinks']);
        $user = User::factory()->create(['shop_id' => $shop->id, 'role' => 'admin']);

        $product = Product::forceCreate([
            'shop_id' => $shop->id,
            'category_id' => $category->id,
            'name_en' => 'Espresso',
            'price' => 1.500,
            'is_available' => true,
        ]);

        Livewire::actingAs($user)
            ->test(ProductManager::class)
            ->call('toggleAvailability', $product->id);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'product.86d',
            'auditable_type' => Product::class,
            'auditable_id' => $product->id,
        ]);

        $auditLog = AuditLog::where('action', 'product.86d')->first();
        $this->assertNotNull($auditLog);
        $this->assertEquals('Espresso', $auditLog->meta['product_name']);

        Livewire::actingAs($user)
            ->test(ProductManager::class)
            ->call('toggleAvailability', $product->id);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'product.restored',
            'auditable_type' => Product::class,
            'auditable_id' => $product->id,
        ]);
    }

    public function test_toggle_dispatches_toast(): void
    {
        $shop = Shop::create(['name' => 'Test Shop', 'slug' => 'test-shop']);
        $category = Category::create(['shop_id' => $shop->id, 'name_en' => 'Drinks']);
        $user = User::factory()->create(['shop_id' => $shop->id, 'role' => 'admin']);

        $product = Product::forceCreate([
            'shop_id' => $shop->id,
            'category_id' => $category->id,
            'name_en' => 'Espresso',
            'price' => 1.500,
            'is_available' => true,
        ]);

        Livewire::actingAs($user)
            ->test(ProductManager::class)
            ->call('toggleAvailability', $product->id)
            ->assertDispatched('toast', fn ($event, $params) => $params['variant'] === 'error');

        Livewire::actingAs($user)
            ->test(ProductManager::class)
            ->call('toggleAvailability', $product->id)
            ->assertDispatched('toast', fn ($event, $params) => $params['variant'] === 'success');
    }

    public function test_toggle_scoped_to_shop(): void
    {
        $shopA = Shop::create(['name' => 'Shop A', 'slug' => 'shop-a']);
        $shopB = Shop::create(['name' => 'Shop B', 'slug' => 'shop-b']);

        $categoryA = Category::create(['shop_id' => $shopA->id, 'name_en' => 'Drinks']);

        $userB = User::factory()->create(['shop_id' => $shopB->id, 'role' => 'admin']);

        $productA = Product::forceCreate([
            'shop_id' => $shopA->id,
            'category_id' => $categoryA->id,
            'name_en' => 'Espresso',
            'price' => 1.500,
            'is_available' => true,
        ]);

        // Livewire wraps exceptions — assert product availability was not changed
        try {
            Livewire::actingAs($userB)
                ->test(ProductManager::class)
                ->call('toggleAvailability', $productA->id);
        } catch (\Throwable $e) {
            // Expected — method threw an exception due to tenant isolation
        }

        $this->assertDatabaseHas('products', [
            'id' => $productA->id,
            'is_available' => true,
        ]);
    }
}
