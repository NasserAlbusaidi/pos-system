<?php

namespace Tests\Feature;

use App\Livewire\ModifierManager;
use App\Models\ModifierGroup;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ModifierManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_modifier_group()
    {
        $shop = Shop::factory()->create();
        $user = User::factory()->create(['shop_id' => $shop->id]);

        Livewire::actingAs($user)
            ->test(ModifierManager::class)
            ->set('name_en', 'Milk Options')
            ->set('min_selection', 1)
            ->set('max_selection', 1)
            ->call('save')
            ->assertSet('name_en', null);

        $this->assertDatabaseHas('modifier_groups', [
            'name_en' => 'Milk Options',
            'shop_id' => $shop->id,
        ]);
    }

    public function test_can_add_option_to_group()
    {
        $shop = Shop::factory()->create();
        $user = User::factory()->create(['shop_id' => $shop->id]);
        $group = ModifierGroup::create([
            'shop_id' => $shop->id,
            'name_en' => 'Sizes',
            'min_selection' => 1,
            'max_selection' => 1,
        ]);

        Livewire::actingAs($user)
            ->test(ModifierManager::class)
            ->set('selectedGroupId', $group->id)
            ->set('optionNameEn', 'Large')
            ->set('optionPrice', 0.50)
            ->call('addOption');

        $this->assertDatabaseHas('modifier_options', [
            'modifier_group_id' => $group->id,
            'name_en' => 'Large',
            'price_adjustment' => 0.50,
        ]);
    }
}
