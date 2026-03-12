<?php

namespace Tests\Feature\Livewire;

use App\Livewire\ModifierManager;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ModifierManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_modifier_group_via_livewire(): void
    {
        $shop = Shop::create(['name' => 'Bite', 'slug' => 'bite']);
        $user = User::factory()->create(['shop_id' => $shop->id, 'role' => 'admin']);

        Livewire::actingAs($user)
            ->test(ModifierManager::class)
            ->set('name_en', 'Sugar Level')
            ->set('min_selection', 0)
            ->set('max_selection', 1)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('modifier_groups', [
            'shop_id' => $shop->id,
            'name_en' => 'Sugar Level',
        ]);
    }

    public function test_admin_can_add_options_to_group(): void
    {
        $shop = Shop::create(['name' => 'Bite', 'slug' => 'bite']);
        $user = User::factory()->create(['shop_id' => $shop->id, 'role' => 'admin']);
        $group = \App\Models\ModifierGroup::create(['shop_id' => $shop->id, 'name_en' => 'Milk']);

        Livewire::actingAs($user)
            ->test(ModifierManager::class)
            ->set('selectedGroupId', $group->id)
            ->set('optionNameEn', 'Almond Milk')
            ->set('optionPrice', 1.50)
            ->call('addOption')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('modifier_options', [
            'modifier_group_id' => $group->id,
            'name_en' => 'Almond Milk',
            'price_adjustment' => 1.50,
        ]);
    }
}
