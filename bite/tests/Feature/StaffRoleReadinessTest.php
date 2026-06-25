<?php

namespace Tests\Feature;

use App\Livewire\OnboardingWizard;
use App\Livewire\PinLogin;
use App\Livewire\ShopSettings;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class StaffRoleReadinessTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_default_staff_role_creates_pos_accessible_staff(): void
    {
        $shop = $this->makeShop(['branding' => ['onboarding_completed' => true]]);
        $admin = User::factory()->create(['shop_id' => $shop->id, 'role' => 'admin']);

        Livewire::actingAs($admin)
            ->test(ShopSettings::class)
            ->set('staffName', 'Counter Staff')
            ->set('staffEmail', 'counter@example.test')
            ->set('staffPin', '2468')
            ->call('addStaff')
            ->assertHasNoErrors();

        $staff = User::where('email', 'counter@example.test')->firstOrFail();

        $this->assertSame('server', $staff->role);
        $this->actingAs($staff)->get('/pos')->assertOk();
    }

    public function test_onboarding_default_staff_role_creates_pos_accessible_staff(): void
    {
        $shop = $this->makeShop();
        $admin = User::factory()->create(['shop_id' => $shop->id, 'role' => 'admin']);

        Livewire::actingAs($admin)
            ->test(OnboardingWizard::class)
            ->set('staffName', 'Launch Server')
            ->set('staffEmail', 'server@example.test')
            ->set('staffPin', '1357')
            ->call('addStaff')
            ->assertHasNoErrors();

        $staff = User::where('email', 'server@example.test')->firstOrFail();

        $this->assertSame('server', $staff->role);
        $this->assertTrue(Hash::check('1357', $staff->pin_code));
        $this->actingAs($staff)->get('/pos')->assertOk();
    }

    public function test_staff_forms_reject_unsupported_cashier_role(): void
    {
        $shop = $this->makeShop(['branding' => ['onboarding_completed' => true]]);
        $admin = User::factory()->create(['shop_id' => $shop->id, 'role' => 'admin']);

        Livewire::actingAs($admin)
            ->test(ShopSettings::class)
            ->set('staffName', 'Legacy Cashier')
            ->set('staffEmail', 'cashier@example.test')
            ->set('staffRole', 'cashier')
            ->set('staffPin', '9999')
            ->call('addStaff')
            ->assertHasErrors(['staffRole']);

        $this->assertDatabaseMissing('users', ['email' => 'cashier@example.test']);
    }

    public function test_settings_rejects_duplicate_pin_in_same_shop(): void
    {
        $shop = $this->makeShop(['branding' => ['onboarding_completed' => true]]);
        $admin = User::factory()->create(['shop_id' => $shop->id, 'role' => 'admin']);
        User::factory()->create([
            'shop_id' => $shop->id,
            'role' => 'server',
            'pin_code' => Hash::make('2468'),
        ]);

        Livewire::actingAs($admin)
            ->test(ShopSettings::class)
            ->set('staffName', 'Duplicate PIN')
            ->set('staffEmail', 'duplicate-pin@example.test')
            ->set('staffPin', '2468')
            ->call('addStaff')
            ->assertHasErrors(['staffPin']);

        $this->assertDatabaseMissing('users', ['email' => 'duplicate-pin@example.test']);
    }

    public function test_settings_created_kitchen_staff_can_use_pin_to_reach_kds(): void
    {
        $shop = $this->makeShop(['branding' => ['onboarding_completed' => true]]);
        $admin = User::factory()->create(['shop_id' => $shop->id, 'role' => 'admin']);

        Livewire::actingAs($admin)
            ->test(ShopSettings::class)
            ->set('staffName', 'Kitchen Lead')
            ->set('staffEmail', 'kitchen-lead@example.test')
            ->set('staffRole', 'kitchen')
            ->set('staffPin', '8642')
            ->call('addStaff')
            ->assertHasNoErrors();

        auth()->logout();

        $kitchen = User::where('email', 'kitchen-lead@example.test')->firstOrFail();

        Livewire::test(PinLogin::class, ['shop' => $shop])
            ->set('pin', '8642')
            ->call('login')
            ->assertRedirect(route('kds.view'));

        $this->assertAuthenticatedAs($kitchen);
    }

    public function test_settings_allows_same_pin_in_different_shop(): void
    {
        $shop = $this->makeShop(['branding' => ['onboarding_completed' => true]]);
        $otherShop = $this->makeShop(['slug' => 'other-shop']);
        $admin = User::factory()->create(['shop_id' => $shop->id, 'role' => 'admin']);
        User::factory()->create([
            'shop_id' => $otherShop->id,
            'role' => 'server',
            'pin_code' => Hash::make('2468'),
        ]);

        Livewire::actingAs($admin)
            ->test(ShopSettings::class)
            ->set('staffName', 'Same PIN Elsewhere')
            ->set('staffEmail', 'same-pin-elsewhere@example.test')
            ->set('staffPin', '2468')
            ->call('addStaff')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('users', [
            'shop_id' => $shop->id,
            'email' => 'same-pin-elsewhere@example.test',
        ]);
    }

    public function test_onboarding_rejects_duplicate_pin_in_same_shop(): void
    {
        $shop = $this->makeShop();
        $admin = User::factory()->create(['shop_id' => $shop->id, 'role' => 'admin']);
        User::factory()->create([
            'shop_id' => $shop->id,
            'role' => 'kitchen',
            'pin_code' => Hash::make('1357'),
        ]);

        Livewire::actingAs($admin)
            ->test(OnboardingWizard::class)
            ->set('staffName', 'Duplicate Launch PIN')
            ->set('staffEmail', 'duplicate-launch@example.test')
            ->set('staffPin', '1357')
            ->call('addStaff')
            ->assertHasErrors(['staffPin']);

        $this->assertDatabaseMissing('users', ['email' => 'duplicate-launch@example.test']);
    }

    private function makeShop(array $overrides = []): Shop
    {
        return Shop::factory()->create(array_merge([
            'trial_ends_at' => now()->addDays(14),
        ], $overrides));
    }
}
