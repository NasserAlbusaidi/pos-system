<?php

namespace Tests\Feature\Livewire;

use App\Livewire\ModifierManager;
use App\Models\ModifierGroup;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * SEC-01 regression tests — ModifierManager tenant isolation.
 *
 * These tests verify that a user authenticated for shopA cannot update
 * or delete modifier groups belonging to shopB via the ModifierManager.
 * Any failure here indicates a tenant isolation gap that must be fixed
 * before deployment (D-14 pre-deploy gate).
 */
class ModifierManagerTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_cannot_delete_another_shops_modifier_group(): void
    {
        [$shopA, $shopB] = $this->makeShops();

        $groupB = ModifierGroup::create([
            'shop_id' => $shopB->id,
            'name_en' => 'Size',
            'name_ar' => 'الحجم',
            'min_selection' => 0,
            'max_selection' => 1,
        ]);

        $adminA = User::factory()->create(['shop_id' => $shopA->id, 'role' => 'admin']);

        $this->expectException(ModelNotFoundException::class);

        Livewire::actingAs($adminA)
            ->test(ModifierManager::class)
            ->call('deleteGroup', $groupB->id);
    }

    public function test_cannot_add_option_to_another_shops_modifier_group(): void
    {
        [$shopA, $shopB] = $this->makeShops();

        $groupB = ModifierGroup::create([
            'shop_id' => $shopB->id,
            'name_en' => 'Extras',
            'name_ar' => 'إضافات',
            'min_selection' => 0,
            'max_selection' => 3,
        ]);

        $adminA = User::factory()->create(['shop_id' => $shopA->id, 'role' => 'admin']);

        $this->expectException(ModelNotFoundException::class);

        Livewire::actingAs($adminA)
            ->test(ModifierManager::class)
            ->set('selectedGroupId', $groupB->id)
            ->set('optionNameEn', 'Extra Cheese')
            ->set('optionNameAr', 'جبنة إضافية')
            ->set('optionPrice', 0.500)
            ->call('addOption');
    }

    protected function makeShops(): array
    {
        $shopA = Shop::create(['name' => 'Shop A', 'slug' => 'shop-a']);
        $shopB = Shop::create(['name' => 'Shop B', 'slug' => 'shop-b']);

        return [$shopA, $shopB];
    }
}
