<?php

namespace Tests\Feature\Livewire;

use App\Livewire\PinLogin;
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
}
