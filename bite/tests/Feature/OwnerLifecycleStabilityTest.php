<?php

namespace Tests\Feature;

use App\Livewire\ModifierManager;
use App\Livewire\ShopSettings;
use App\Models\ModifierGroup;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OwnerLifecycleStabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_shop_settings_accepts_valid_hex_colors_including_digit_nine(): void
    {
        $shop = Shop::create(['name' => 'Bite', 'slug' => 'bite']);
        $user = User::factory()->create(['shop_id' => $shop->id, 'role' => 'admin']);

        Livewire::actingAs($user)
            ->test(ShopSettings::class)
            ->set('name', 'Bite Updated')
            ->set('paper', '#999999') // Contains 9s
            ->set('ink', '#FFFFFF')
            ->set('accent', '#000000')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertEquals('Bite Updated', $shop->fresh()->name);
        $this->assertEquals('#999999', $shop->fresh()->branding['paper']);
    }

    public function test_modifier_options_cannot_be_added_to_other_shop_groups(): void
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $myShop = Shop::create(['name' => 'My Shop', 'slug' => 'my-shop']);
        $me = User::factory()->create(['shop_id' => $myShop->id]);

        $otherShop = Shop::create(['name' => 'Other', 'slug' => 'other']);
        $otherGroup = ModifierGroup::create(['shop_id' => $otherShop->id, 'name_en' => 'Hack Me']);

        Livewire::actingAs($me)
            ->test(ModifierManager::class)
            ->set('selectedGroupId', $otherGroup->id)
            ->set('optionNameEn', 'Malicious Option')
            ->set('optionPrice', 0)
            ->call('addOption');

        // Should not exist under the other shop's group if we block it
        $this->assertDatabaseMissing('modifier_options', [
            'modifier_group_id' => $otherGroup->id,
            'name_en' => 'Malicious Option',
        ]);
    }
}
