<?php

namespace Tests\Feature;

use App\Livewire\ShopSettings;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ShopSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_update_shop_branding()
    {
        $shop = Shop::factory()->create();
        $user = User::factory()->create(['shop_id' => $shop->id]);

        Livewire::actingAs($user)
            ->test(ShopSettings::class)
            ->set('name', 'Updated Shop Name')
            ->set('paper', '#ffffff')
            ->set('ink', '#000000')
            ->set('accent', '#ff0000')
            ->call('save')
            ->assertSee('Shop configuration updated successfully');

        $shop->refresh();
        $this->assertEquals('Updated Shop Name', $shop->name);
        $this->assertEquals('#ffffff', $shop->branding['paper']);
        $this->assertEquals('#ff0000', $shop->branding['accent']);
    }
}
