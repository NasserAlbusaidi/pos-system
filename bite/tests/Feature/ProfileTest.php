<?php

namespace Tests\Feature;

use App\Livewire\Profile\UpdatePinForm;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Livewire\Volt\Volt;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile');

        $response
            ->assertOk()
            ->assertSeeVolt('profile.update-profile-information-form')
            ->assertSeeVolt('profile.update-password-form')
            ->assertSeeVolt('profile.delete-user-form');
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $component = Volt::test('profile.update-profile-information-form')
            ->set('name', 'Test User')
            ->set('email', 'test@example.com')
            ->call('updateProfileInformation');

        $component
            ->assertHasNoErrors()
            ->assertNoRedirect();

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $component = Volt::test('profile.update-profile-information-form')
            ->set('name', 'Test User')
            ->set('email', $user->email)
            ->call('updateProfileInformation');

        $component
            ->assertHasNoErrors()
            ->assertNoRedirect();

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $component = Volt::test('profile.delete-user-form')
            ->set('password', 'password')
            ->call('deleteUser');

        $component
            ->assertHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($user->fresh());
    }

    public function test_last_shop_admin_cannot_delete_their_account(): void
    {
        $shop = Shop::factory()->create();
        $admin = User::factory()->create(['shop_id' => $shop->id, 'role' => 'admin']);

        $this->actingAs($admin);

        $component = Volt::test('profile.delete-user-form')
            ->set('password', 'password')
            ->call('deleteUser');

        $component
            ->assertHasErrors('password')
            ->assertNoRedirect();

        $this->assertAuthenticatedAs($admin);
        $this->assertNotNull($admin->fresh());
        $this->assertSame(1, User::where('shop_id', $shop->id)->where('role', 'admin')->count());
    }

    public function test_shop_admin_can_delete_their_account_when_another_admin_exists(): void
    {
        $shop = Shop::factory()->create();
        $admin = User::factory()->create(['shop_id' => $shop->id, 'role' => 'admin']);
        User::factory()->create(['shop_id' => $shop->id, 'role' => 'admin']);

        $this->actingAs($admin);

        $component = Volt::test('profile.delete-user-form')
            ->set('password', 'password')
            ->call('deleteUser');

        $component
            ->assertHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($admin->fresh());
        $this->assertSame(1, User::where('shop_id', $shop->id)->where('role', 'admin')->count());
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $component = Volt::test('profile.delete-user-form')
            ->set('password', 'wrong-password')
            ->call('deleteUser');

        $component
            ->assertHasErrors('password')
            ->assertNoRedirect();

        $this->assertNotNull($user->fresh());
    }

    public function test_profile_pin_update_rejects_duplicate_pin_in_same_shop(): void
    {
        $shop = Shop::factory()->create();
        $user = User::factory()->create(['shop_id' => $shop->id]);
        User::factory()->create([
            'shop_id' => $shop->id,
            'pin_code' => Hash::make('2468'),
        ]);

        Livewire::actingAs($user)
            ->test(UpdatePinForm::class)
            ->set('pin', '2468')
            ->set('pin_confirmation', '2468')
            ->call('updatePin')
            ->assertHasErrors(['pin']);

        $this->assertNull($user->fresh()->pin_code);
    }
}
