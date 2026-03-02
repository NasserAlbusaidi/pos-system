<?php

namespace Tests\Feature\Livewire;

use App\Livewire\PinLogin;
use App\Livewire\PosDashboard;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Tests\TestCase;

class RateLimitProtectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_pin_login_is_rate_limited_after_repeated_failures(): void
    {
        $shop = Shop::create(['name' => 'Bite', 'slug' => 'bite']);

        User::factory()->create([
            'shop_id' => $shop->id,
            'role' => 'server',
            'pin_code' => Hash::make('2468'),
        ]);

        $component = Livewire::test(PinLogin::class, ['shop' => $shop]);

        for ($i = 0; $i < 5; $i++) {
            $component
                ->set('pin', '0000')
                ->call('login')
                ->assertSet('error', 'Authentication failed.');
        }

        $component->set('pin', '0000')->call('login');

        $this->assertStringContainsString('Too many attempts', (string) $component->get('error'));
    }

    public function test_successful_pin_login_clears_limiter(): void
    {
        $shop = Shop::create(['name' => 'Bite', 'slug' => 'bite']);

        User::factory()->create([
            'shop_id' => $shop->id,
            'role' => 'server',
            'pin_code' => Hash::make('2468'),
        ]);

        $throttleKey = 'pin-login:'.$shop->id.'|127.0.0.1';

        $component = Livewire::test(PinLogin::class, ['shop' => $shop]);

        $component->set('pin', '0000')->call('login');
        $this->assertSame(1, RateLimiter::attempts($throttleKey));

        $component
            ->set('pin', '2468')
            ->call('login')
            ->assertRedirect('/pos');

        $this->assertSame(0, RateLimiter::attempts($throttleKey));
    }

    public function test_manager_override_is_rate_limited_after_repeated_failures(): void
    {
        $shop = Shop::create(['name' => 'Bite', 'slug' => 'bite']);

        $server = User::factory()->create([
            'shop_id' => $shop->id,
            'role' => 'server',
        ]);

        User::factory()->create([
            'shop_id' => $shop->id,
            'role' => 'manager',
            'pin_code' => Hash::make('4321'),
        ]);

        $component = Livewire::actingAs($server)
            ->test(PosDashboard::class)
            ->call('requestManagerOverride', 'systemReset');

        for ($i = 0; $i < 5; $i++) {
            $component
                ->set('managerPin', '0000')
                ->call('confirmManagerOverride')
                ->assertSet('managerError', 'Manager approval failed.');
        }

        $component->set('managerPin', '0000')->call('confirmManagerOverride');

        $this->assertStringContainsString('Too many attempts', (string) $component->get('managerError'));
    }

    public function test_successful_manager_override_clears_limiter(): void
    {
        $shop = Shop::create(['name' => 'Bite', 'slug' => 'bite']);

        $server = User::factory()->create([
            'shop_id' => $shop->id,
            'role' => 'server',
        ]);

        User::factory()->create([
            'shop_id' => $shop->id,
            'role' => 'manager',
            'pin_code' => Hash::make('4321'),
        ]);

        $throttleKey = 'manager-override:'.$shop->id.'|127.0.0.1';

        $component = Livewire::actingAs($server)
            ->test(PosDashboard::class)
            ->call('requestManagerOverride', 'systemReset');

        $component->set('managerPin', '0000')->call('confirmManagerOverride');
        $this->assertSame(1, RateLimiter::attempts($throttleKey));

        $component->set('managerPin', '4321')->call('confirmManagerOverride');

        $this->assertSame(0, RateLimiter::attempts($throttleKey));
    }
}
