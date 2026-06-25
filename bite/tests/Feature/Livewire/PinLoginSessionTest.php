<?php

namespace Tests\Feature\Livewire;

use App\Livewire\PinLogin;
use App\Models\AuditLog;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * On a shared POS terminal a fixed pre-login session must not survive into the
 * authenticated session. Auth::login() already migrates the session id; PinLogin
 * additionally regenerate()s to rotate the CSRF token. See issue #54.
 */
class PinLoginSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_pin_login_rotates_the_session_and_authenticates(): void
    {
        $shop = Shop::create(['name' => 'Bite', 'slug' => 'bite']);

        $user = User::factory()->create([
            'shop_id' => $shop->id,
            'role' => 'server',
            'pin_code' => Hash::make('2468'),
        ]);

        $component = Livewire::test(PinLogin::class, ['shop' => $shop]);

        // A failed attempt settles the session to a stable, pre-login id.
        $component->set('pin', '0000')->call('login');
        $sessionIdBeforeLogin = session()->getId();

        $component->set('pin', '2468')->call('login')->assertRedirect('/pos');

        $this->assertAuthenticatedAs($user);
        $this->assertNotSame(
            $sessionIdBeforeLogin,
            session()->getId(),
            'A fixed pre-login session id must not survive PIN login.'
        );
    }

    public function test_duplicate_pin_does_not_authenticate_ambiguous_user(): void
    {
        $shop = Shop::create(['name' => 'Bite', 'slug' => 'bite']);

        User::factory()->create([
            'shop_id' => $shop->id,
            'role' => 'admin',
            'pin_code' => Hash::make('2468'),
        ]);
        User::factory()->create([
            'shop_id' => $shop->id,
            'role' => 'server',
            'pin_code' => Hash::make('2468'),
        ]);

        Livewire::test(PinLogin::class, ['shop' => $shop])
            ->set('pin', '2468')
            ->call('login')
            ->assertSet('error', 'Authentication failed.');

        $this->assertGuest();
    }

    public function test_pin_login_rejects_non_four_digit_pin_even_if_user_record_matches(): void
    {
        $shop = Shop::create(['name' => 'Bite', 'slug' => 'bite']);

        User::factory()->create([
            'shop_id' => $shop->id,
            'role' => 'server',
            'pin_code' => Hash::make('12345'),
        ]);

        Livewire::test(PinLogin::class, ['shop' => $shop])
            ->set('pin', '12345')
            ->call('login')
            ->assertSet('error', 'Authentication failed.');

        $this->assertGuest();
    }

    public function test_kitchen_staff_pin_login_redirects_to_kitchen_display(): void
    {
        $shop = Shop::create(['name' => 'Bite', 'slug' => 'bite']);

        $kitchen = User::factory()->create([
            'shop_id' => $shop->id,
            'role' => 'kitchen',
            'pin_code' => Hash::make('8642'),
        ]);

        Livewire::test(PinLogin::class, ['shop' => $shop])
            ->set('pin', '8642')
            ->call('login')
            ->assertRedirect(route('kds.view'));

        $this->assertAuthenticatedAs($kitchen);
    }

    public function test_successful_pin_login_is_audited(): void
    {
        $shop = Shop::create(['name' => 'Bite', 'slug' => 'bite']);

        $server = User::factory()->create([
            'shop_id' => $shop->id,
            'role' => 'server',
            'pin_code' => Hash::make('2468'),
        ]);

        Livewire::test(PinLogin::class, ['shop' => $shop])
            ->set('pin', '2468')
            ->call('login')
            ->assertRedirect(route('pos.dashboard'));

        $audit = AuditLog::where('shop_id', $shop->id)
            ->where('user_id', $server->id)
            ->where('action', 'pin.login')
            ->firstOrFail();

        $this->assertSame(User::class, $audit->auditable_type);
        $this->assertSame($server->id, $audit->auditable_id);
        $this->assertSame('server', $audit->meta['role']);
    }
}
