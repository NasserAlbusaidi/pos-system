<?php

namespace Tests\Feature;

use App\Livewire\ShopSettings;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ShopSettingsThemeTest extends TestCase
{
    use RefreshDatabase;

    private function createAdminUser(): array
    {
        $shop = Shop::factory()->create();
        $user = User::factory()->create(['shop_id' => $shop->id, 'role' => 'admin']);

        return [$shop, $user];
    }

    public function test_mount_loads_theme_from_branding(): void
    {
        [$shop, $user] = $this->createAdminUser();
        $shop->update(['branding' => ['theme' => 'dark']]);

        Livewire::actingAs($user)
            ->test(ShopSettings::class)
            ->assertSet('theme', 'dark');
    }

    public function test_mount_defaults_to_warm_when_no_theme(): void
    {
        [$shop, $user] = $this->createAdminUser();
        $shop->update(['branding' => []]);

        Livewire::actingAs($user)
            ->test(ShopSettings::class)
            ->assertSet('theme', 'warm');
    }

    public function test_save_persists_theme_to_branding(): void
    {
        [$shop, $user] = $this->createAdminUser();

        Livewire::actingAs($user)
            ->test(ShopSettings::class)
            ->set('theme', 'modern')
            ->call('save')
            ->assertDispatched('toast');

        $shop->refresh();
        $this->assertEquals('modern', $shop->branding['theme']);
    }

    public function test_save_does_not_alter_brand_colors(): void
    {
        [$shop, $user] = $this->createAdminUser();
        $shop->update(['branding' => [
            'paper' => '#F5F0E8',
            'ink' => '#2C2520',
            'accent' => '#C4975A',
            'theme' => 'warm',
        ]]);

        Livewire::actingAs($user)
            ->test(ShopSettings::class)
            ->set('theme', 'dark')
            ->call('save')
            ->assertDispatched('toast');

        $shop->refresh();
        $this->assertEquals('dark', $shop->branding['theme']);
        $this->assertEquals('#f5f0e8', $shop->branding['paper']);
        $this->assertEquals('#2c2520', $shop->branding['ink']);
        $this->assertEquals('#c4975a', $shop->branding['accent']);
    }

    public function test_rejects_invalid_theme_value(): void
    {
        [$shop, $user] = $this->createAdminUser();

        Livewire::actingAs($user)
            ->test(ShopSettings::class)
            ->set('theme', 'hacked')
            ->call('save')
            ->assertHasErrors(['theme']);
    }
}
