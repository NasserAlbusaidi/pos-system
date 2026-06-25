<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Admin\PricingRules;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\PricingRule;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PricingRulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_pricing_rule_create_update_and_delete_are_audited(): void
    {
        [$shop] = $this->makeShops();
        $manager = User::factory()->create(['shop_id' => $shop->id, 'role' => 'manager']);
        $category = Category::factory()->create(['shop_id' => $shop->id, 'name_en' => 'Pastry']);
        $product = Product::factory()->create([
            'shop_id' => $shop->id,
            'category_id' => $category->id,
            'name_en' => 'Almond Croissant',
            'price' => 2.500,
        ]);

        Livewire::actingAs($manager)
            ->test(PricingRules::class)
            ->set('name', 'Morning Pastry')
            ->set('discount_type', 'percentage')
            ->set('discount_value', '15')
            ->set('start_time', '08:00')
            ->set('end_time', '11:00')
            ->set('days_of_week', [1, 2, 3, 4, 5])
            ->set('category_id', $category->id)
            ->call('save')
            ->assertHasNoErrors();

        $rule = PricingRule::where('shop_id', $shop->id)
            ->where('name', 'Morning Pastry')
            ->firstOrFail();

        $created = AuditLog::where('action', 'pricing_rule.created')->firstOrFail();
        $this->assertSame($shop->id, $created->shop_id);
        $this->assertSame($manager->id, $created->user_id);
        $this->assertSame(PricingRule::class, $created->auditable_type);
        $this->assertSame($rule->id, $created->auditable_id);
        $this->assertSame('Morning Pastry', $created->meta['rule_name']);
        $this->assertSame('category', $created->meta['target_type']);
        $this->assertSame($category->id, $created->meta['category_id']);
        $this->assertEquals(15.0, $created->meta['discount_value']);

        Livewire::actingAs($manager)
            ->test(PricingRules::class)
            ->call('edit', $rule->id)
            ->set('name', 'Croissant Happy Hour')
            ->set('discount_type', 'fixed')
            ->set('discount_value', '0.350')
            ->set('start_time', '14:00')
            ->set('end_time', '16:00')
            ->set('days_of_week', [])
            ->set('category_id', '')
            ->set('product_id', $product->id)
            ->call('save')
            ->assertHasNoErrors();

        $updated = AuditLog::where('action', 'pricing_rule.updated')->firstOrFail();
        $this->assertSame($rule->id, $updated->auditable_id);
        $this->assertSame('Croissant Happy Hour', $updated->meta['rule_name']);
        $this->assertSame('product', $updated->meta['target_type']);
        $this->assertSame($product->id, $updated->meta['product_id']);
        $this->assertSame(0.350, $updated->meta['discount_value']);
        $this->assertSame('Morning Pastry', $updated->meta['previous']['rule_name']);
        $this->assertSame('category', $updated->meta['previous']['target_type']);
        $this->assertSame($category->id, $updated->meta['previous']['category_id']);

        Livewire::actingAs($manager)
            ->test(PricingRules::class)
            ->call('delete', $rule->id);

        $deleted = AuditLog::where('action', 'pricing_rule.deleted')->firstOrFail();
        $this->assertSame($rule->id, $deleted->auditable_id);
        $this->assertSame('Croissant Happy Hour', $deleted->meta['rule_name']);
        $this->assertSame($product->id, $deleted->meta['product_id']);
        $this->assertDatabaseMissing('pricing_rules', ['id' => $rule->id]);
    }

    public function test_pricing_rule_active_toggle_is_audited_and_visible_in_product_filter(): void
    {
        [$shop] = $this->makeShops();
        $manager = User::factory()->create(['shop_id' => $shop->id, 'role' => 'manager']);
        $rule = PricingRule::create([
            'shop_id' => $shop->id,
            'name' => 'Quiet Hour',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'start_time' => '15:00:00',
            'end_time' => '17:00:00',
            'days_of_week' => null,
            'is_active' => true,
        ]);

        Livewire::actingAs($manager)
            ->test(PricingRules::class)
            ->call('toggleActive', $rule->id);

        $deactivated = AuditLog::where('action', 'pricing_rule.deactivated')->firstOrFail();
        $this->assertSame($rule->id, $deactivated->auditable_id);
        $this->assertFalse($deactivated->meta['is_active']);

        Livewire::actingAs($manager)
            ->test(PricingRules::class)
            ->call('toggleActive', $rule->id);

        $activated = AuditLog::where('action', 'pricing_rule.activated')->firstOrFail();
        $this->assertSame($rule->id, $activated->auditable_id);
        $this->assertTrue($activated->meta['is_active']);

        Livewire::actingAs($manager)
            ->test(\App\Livewire\Admin\AuditLogs::class)
            ->set('logFilter', 'products')
            ->assertSee('pricing_rule.activated');
    }

    public function test_percentage_discount_cannot_exceed_one_hundred_percent(): void
    {
        $shop = Shop::factory()->create(['trial_ends_at' => now()->addDays(14)]);
        $manager = User::factory()->create(['shop_id' => $shop->id, 'role' => 'manager']);

        Livewire::actingAs($manager)
            ->test(PricingRules::class)
            ->set('name', 'Accidental Free Menu')
            ->set('discount_type', 'percentage')
            ->set('discount_value', '125')
            ->set('start_time', '09:00')
            ->set('end_time', '17:00')
            ->call('save')
            ->assertHasErrors(['discount_value']);

        $this->assertSame(0, PricingRule::where('shop_id', $shop->id)->count());
    }

    public function test_pricing_rule_cannot_target_another_shops_category(): void
    {
        [$shopA, $shopB] = $this->makeShops();
        $otherCategory = Category::create(['shop_id' => $shopB->id, 'name_en' => 'Other Bakery']);
        $managerA = User::factory()->create(['shop_id' => $shopA->id, 'role' => 'manager']);

        Livewire::actingAs($managerA)
            ->test(PricingRules::class)
            ->set('name', 'Cross Shop Happy Hour')
            ->set('discount_type', 'percentage')
            ->set('discount_value', '10')
            ->set('start_time', '09:00')
            ->set('end_time', '17:00')
            ->set('category_id', $otherCategory->id)
            ->call('save')
            ->assertHasErrors(['category_id']);

        $this->assertSame(0, PricingRule::where('shop_id', $shopA->id)->count());
    }

    public function test_pricing_rule_cannot_target_another_shops_product(): void
    {
        [$shopA, $shopB] = $this->makeShops();
        $otherCategory = Category::create(['shop_id' => $shopB->id, 'name_en' => 'Other Bakery']);
        $otherProduct = Product::forceCreate([
            'shop_id' => $shopB->id,
            'category_id' => $otherCategory->id,
            'name_en' => 'Other Croissant',
            'price' => 2.500,
        ]);
        $managerA = User::factory()->create(['shop_id' => $shopA->id, 'role' => 'manager']);

        Livewire::actingAs($managerA)
            ->test(PricingRules::class)
            ->set('name', 'Cross Shop Product Deal')
            ->set('discount_type', 'fixed')
            ->set('discount_value', '0.500')
            ->set('start_time', '09:00')
            ->set('end_time', '17:00')
            ->set('product_id', $otherProduct->id)
            ->call('save')
            ->assertHasErrors(['product_id']);

        $this->assertSame(0, PricingRule::where('shop_id', $shopA->id)->count());
    }

    /**
     * @return array{0: Shop, 1: Shop}
     */
    private function makeShops(): array
    {
        return [
            Shop::factory()->create(['name' => 'Bite A', 'slug' => 'bite-a', 'trial_ends_at' => now()->addDays(14)]),
            Shop::factory()->create(['name' => 'Bite B', 'slug' => 'bite-b', 'trial_ends_at' => now()->addDays(14)]),
        ];
    }
}
