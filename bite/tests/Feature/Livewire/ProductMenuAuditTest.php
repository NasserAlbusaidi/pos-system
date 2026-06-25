<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Admin\MenuBuilder;
use App\Livewire\ProductManager;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductMenuAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_create_and_price_update_are_audited(): void
    {
        [$user, $shop, $category] = $this->makeCatalogActor();

        Livewire::actingAs($user)
            ->test(ProductManager::class)
            ->set('name_en', 'Shakshuka Roll')
            ->set('price', 4.875)
            ->set('tax_rate', 5)
            ->set('category_id', $category->id)
            ->call('save')
            ->assertHasNoErrors();

        $product = Product::where('shop_id', $shop->id)
            ->where('name_en', 'Shakshuka Roll')
            ->firstOrFail();

        $created = AuditLog::where('action', 'product.created')->firstOrFail();
        $this->assertSame($shop->id, $created->shop_id);
        $this->assertSame($user->id, $created->user_id);
        $this->assertSame(Product::class, $created->auditable_type);
        $this->assertSame($product->id, $created->auditable_id);
        $this->assertSame('Shakshuka Roll', $created->meta['product_name']);
        $this->assertSame(4.875, $created->meta['price']);
        $this->assertSame($category->id, $created->meta['category_id']);

        Livewire::actingAs($user)
            ->test(ProductManager::class)
            ->call('editProduct', $product->id)
            ->set('name_en', 'Shakshuka Roll Large')
            ->set('price', 5.125)
            ->set('tax_rate', 7.5)
            ->set('category_id', $category->id)
            ->call('save')
            ->assertHasNoErrors();

        $updated = AuditLog::where('action', 'product.updated')->firstOrFail();
        $this->assertSame(Product::class, $updated->auditable_type);
        $this->assertSame($product->id, $updated->auditable_id);
        $this->assertSame('Shakshuka Roll Large', $updated->meta['product_name']);
        $this->assertSame(5.125, $updated->meta['price']);
        $this->assertSame('Shakshuka Roll', $updated->meta['previous']['product_name']);
        $this->assertSame(4.875, $updated->meta['previous']['price']);
        $this->assertEquals(5.0, $updated->meta['previous']['tax_rate']);
    }

    public function test_menu_visibility_and_delete_actions_are_audited_with_product_snapshot(): void
    {
        [$user, $shop, $category] = $this->makeCatalogActor();

        $product = Product::factory()->create([
            'shop_id' => $shop->id,
            'category_id' => $category->id,
            'name_en' => 'Audit Mezze',
            'price' => 3.250,
            'is_visible' => true,
            'is_available' => true,
        ]);

        Livewire::actingAs($user)
            ->test(MenuBuilder::class)
            ->call('toggleVisibility', $product->id);

        $hidden = AuditLog::where('action', 'product.hidden')->firstOrFail();
        $this->assertSame($product->id, $hidden->auditable_id);
        $this->assertSame('Audit Mezze', $hidden->meta['product_name']);
        $this->assertFalse($hidden->meta['is_visible']);

        Livewire::actingAs($user)
            ->test(MenuBuilder::class)
            ->call('toggleVisibility', $product->id);

        $visible = AuditLog::where('action', 'product.visible')->firstOrFail();
        $this->assertSame($product->id, $visible->auditable_id);
        $this->assertTrue($visible->meta['is_visible']);

        Livewire::actingAs($user)
            ->test(MenuBuilder::class)
            ->call('deleteProduct', $product->id);

        $deleted = AuditLog::where('action', 'product.deleted')->firstOrFail();
        $this->assertSame($product->id, $deleted->auditable_id);
        $this->assertSame('Audit Mezze', $deleted->meta['product_name']);
        $this->assertSame(3.250, $deleted->meta['price']);
        $this->assertSame($category->id, $deleted->meta['category_id']);
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    /**
     * @return array{0: User, 1: Shop, 2: Category}
     */
    private function makeCatalogActor(): array
    {
        $shop = Shop::factory()->create();
        $user = User::factory()->create([
            'shop_id' => $shop->id,
            'role' => 'admin',
        ]);
        $category = Category::factory()->create(['shop_id' => $shop->id]);

        return [$user, $shop, $category];
    }
}
