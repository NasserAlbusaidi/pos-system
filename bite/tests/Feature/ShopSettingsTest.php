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
        $user = User::factory()->create(['shop_id' => $shop->id, 'role' => 'admin']);

        Livewire::actingAs($user)
            ->test(ShopSettings::class)
            ->set('name', 'Updated Shop Name')
            ->set('paper', '#ffffff')
            ->set('ink', '#000000')
            ->set('accent', '#ff0000')
            ->set('currency_code', 'OMR')
            ->set('currency_symbol', 'ر.ع.')
            ->set('currency_decimals', 3)
            ->call('save')
            ->assertDispatched('toast', message: 'Shop settings saved.', variant: 'success');

        $shop->refresh();
        $this->assertEquals('Updated Shop Name', $shop->name);
        $this->assertEquals('#ffffff', $shop->branding['paper']);
        $this->assertEquals('#ff0000', $shop->branding['accent']);
    }

    public function test_save_persists_profile_and_business_hours_to_branding(): void
    {
        $shop = Shop::factory()->create();
        $user = User::factory()->create(['shop_id' => $shop->id, 'role' => 'admin']);

        Livewire::actingAs($user)
            ->test(ShopSettings::class)
            ->set('name', 'Olive & Thyme')
            ->set('phone', '+968 9123 4567')
            ->set('address', 'Al Mouj Marina, Muscat')
            ->set('about', 'All-day cafe by the marina.')
            ->set('timezone', 'Asia/Muscat')
            ->set('businessHours.friday.closed', true)
            ->set('businessHours.sunday.open', '07:00')
            ->set('businessHours.sunday.close', '23:00')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('toast', message: 'Shop settings saved.', variant: 'success');

        $shop->refresh();
        $this->assertSame('+968 9123 4567', $shop->branding['phone']);
        $this->assertSame('Al Mouj Marina, Muscat', $shop->branding['address']);
        $this->assertSame('Asia/Muscat', $shop->branding['timezone']);
        $this->assertTrue($shop->branding['business_hours']['friday']['closed']);
        $this->assertSame('07:00', $shop->branding['business_hours']['sunday']['open']);
        $this->assertSame('23:00', $shop->branding['business_hours']['sunday']['close']);
    }

    public function test_mount_loads_profile_and_backfills_seven_days(): void
    {
        $shop = Shop::factory()->create([
            'branding' => [
                'phone' => '+968 5000 0000',
                'about' => 'Saved about text.',
                'timezone' => 'Asia/Dubai',
                'business_hours' => [
                    'monday' => ['open' => '06:30', 'close' => '20:00', 'closed' => false],
                ],
            ],
        ]);
        $user = User::factory()->create(['shop_id' => $shop->id, 'role' => 'admin']);

        Livewire::actingAs($user)
            ->test(ShopSettings::class)
            ->assertSet('phone', '+968 5000 0000')
            ->assertSet('about', 'Saved about text.')
            ->assertSet('timezone', 'Asia/Dubai')
            ->assertSet('businessHours.monday.open', '06:30')
            // unsaved days are backfilled with defaults so the form always has 7 rows
            ->assertSet('businessHours.saturday.open', '09:00')
            ->assertSet('businessHours.saturday.closed', false);
    }

    public function test_save_rejects_invalid_timezone(): void
    {
        $shop = Shop::factory()->create();
        $user = User::factory()->create(['shop_id' => $shop->id, 'role' => 'admin']);

        Livewire::actingAs($user)
            ->test(ShopSettings::class)
            ->set('timezone', 'Mars/Olympus')
            ->call('save')
            ->assertHasErrors(['timezone']);

        $shop->refresh();
        $this->assertArrayNotHasKey('timezone', $shop->branding ?? []);
    }
}
