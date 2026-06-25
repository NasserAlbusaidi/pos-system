<?php

namespace Tests\Feature\Livewire;

use App\Livewire\ModifierManager;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\ModifierGroup;
use App\Models\ModifierOption;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ModifierManagerAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_modifier_group_and_priced_option_creation_are_audited(): void
    {
        [$user, $shop] = $this->makeAdmin();

        Livewire::actingAs($user)
            ->test(ModifierManager::class)
            ->set('name_en', 'Milk Options')
            ->set('name_ar', 'خيارات الحليب')
            ->set('min_selection', 0)
            ->set('max_selection', 2)
            ->call('save')
            ->assertHasNoErrors();

        $group = ModifierGroup::where('shop_id', $shop->id)
            ->where('name_en', 'Milk Options')
            ->firstOrFail();

        $groupCreated = AuditLog::where('action', 'modifier.group.created')->firstOrFail();
        $this->assertSame($shop->id, $groupCreated->shop_id);
        $this->assertSame($user->id, $groupCreated->user_id);
        $this->assertSame(ModifierGroup::class, $groupCreated->auditable_type);
        $this->assertSame($group->id, $groupCreated->auditable_id);
        $this->assertSame('Milk Options', $groupCreated->meta['group_name']);
        $this->assertSame(0, $groupCreated->meta['min_selection']);
        $this->assertSame(2, $groupCreated->meta['max_selection']);

        Livewire::actingAs($user)
            ->test(ModifierManager::class)
            ->set('selectedGroupId', $group->id)
            ->set('optionNameEn', 'Oat Milk')
            ->set('optionNameAr', 'حليب الشوفان')
            ->set('optionPrice', 0.450)
            ->call('addOption')
            ->assertHasNoErrors();

        $option = ModifierOption::where('modifier_group_id', $group->id)
            ->where('name_en', 'Oat Milk')
            ->firstOrFail();

        $optionCreated = AuditLog::where('action', 'modifier.option.created')->firstOrFail();
        $this->assertSame(ModifierOption::class, $optionCreated->auditable_type);
        $this->assertSame($option->id, $optionCreated->auditable_id);
        $this->assertSame($group->id, $optionCreated->meta['modifier_group_id']);
        $this->assertSame('Milk Options', $optionCreated->meta['group_name']);
        $this->assertSame('Oat Milk', $optionCreated->meta['option_name']);
        $this->assertSame(0.450, $optionCreated->meta['price_adjustment']);
    }

    public function test_modifier_option_and_group_deletion_are_audited_with_price_snapshot(): void
    {
        [$user, $shop] = $this->makeAdmin();
        $group = ModifierGroup::create([
            'shop_id' => $shop->id,
            'name_en' => 'Size',
            'name_ar' => 'الحجم',
            'min_selection' => 1,
            'max_selection' => 1,
        ]);
        $option = ModifierOption::create([
            'modifier_group_id' => $group->id,
            'name_en' => 'Large',
            'name_ar' => 'كبير',
            'price_adjustment' => 0.750,
        ]);

        Livewire::actingAs($user)
            ->test(ModifierManager::class)
            ->call('deleteOption', $option->id);

        $optionDeleted = AuditLog::where('action', 'modifier.option.deleted')->firstOrFail();
        $this->assertSame($option->id, $optionDeleted->auditable_id);
        $this->assertSame('Large', $optionDeleted->meta['option_name']);
        $this->assertSame(0.750, $optionDeleted->meta['price_adjustment']);
        $this->assertDatabaseMissing('modifier_options', ['id' => $option->id]);

        $category = Category::factory()->create(['shop_id' => $shop->id]);
        $product = Product::factory()->create(['shop_id' => $shop->id, 'category_id' => $category->id]);
        $group->products()->attach($product->id);
        ModifierOption::create([
            'modifier_group_id' => $group->id,
            'name_en' => 'Small',
            'price_adjustment' => -0.250,
        ]);

        Livewire::actingAs($user)
            ->test(ModifierManager::class)
            ->call('deleteGroup', $group->id);

        $groupDeleted = AuditLog::where('action', 'modifier.group.deleted')->firstOrFail();
        $this->assertSame($group->id, $groupDeleted->auditable_id);
        $this->assertSame('Size', $groupDeleted->meta['group_name']);
        $this->assertSame(1, $groupDeleted->meta['attached_product_count']);
        $this->assertSame(1, $groupDeleted->meta['option_count']);
        $this->assertSame('Small', $groupDeleted->meta['options'][0]['option_name']);
        $this->assertSame(-0.250, $groupDeleted->meta['options'][0]['price_adjustment']);
        $this->assertDatabaseMissing('modifier_groups', ['id' => $group->id]);
    }

    /**
     * @return array{0: User, 1: Shop}
     */
    private function makeAdmin(): array
    {
        $shop = Shop::factory()->create();
        $user = User::factory()->create([
            'shop_id' => $shop->id,
            'role' => 'admin',
        ]);

        return [$user, $shop];
    }
}
