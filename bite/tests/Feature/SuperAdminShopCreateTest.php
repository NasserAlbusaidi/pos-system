<?php

namespace Tests\Feature;

use App\Livewire\SuperAdmin\Shops\Manage;
use App\Models\Shop;
use App\Models\User;
use App\Services\BillingService;
use App\Services\ShopProvisioningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use InvalidArgumentException;
use Livewire\Livewire;
use Livewire\Volt\Volt;
use Tests\TestCase;

class SuperAdminShopCreateTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_shop_page_loads()
    {
        $admin = User::factory()->superAdmin()->create();

        $response = $this->actingAs($admin)->get(route('super-admin.shops.create'));

        $response->assertStatus(200);
        $response->assertSee('Onboard New Shop');
    }

    public function test_can_create_new_shop()
    {
        $admin = User::factory()->superAdmin()->create();

        Livewire::actingAs($admin)
            ->test(Manage::class)
            ->set('name', 'New Coffee Shop')
            ->set('status', 'active')
            ->set('ownerName', 'John Doe')
            ->set('ownerEmail', 'john@example.com')
            ->set('ownerPassword', 'launch-password')
            ->call('save')
            ->assertRedirect(route('super-admin.shops.index'));

        $this->assertDatabaseHas('shops', [
            'name' => 'New Coffee Shop',
            'slug' => 'new-coffee-shop', // auto-generated
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'role' => 'admin',
        ]);

        $owner = User::where('email', 'john@example.com')->firstOrFail();
        auth()->logout();

        Volt::test('pages.auth.login')
            ->set('form.email', 'john@example.com')
            ->set('form.password', 'launch-password')
            ->call('login')
            ->assertHasNoErrors()
            ->assertRedirect('/onboarding');

        $this->assertAuthenticatedAs($owner);
    }

    public function test_create_shop_defaults_to_trial_handoff_access(): void
    {
        $admin = User::factory()->superAdmin()->create();

        Livewire::actingAs($admin)
            ->test(Manage::class)
            ->assertSet('status', 'trial')
            ->set('name', 'Default Trial Kitchen')
            ->set('ownerName', 'Trial Ready Owner')
            ->set('ownerEmail', 'trial-ready@example.com')
            ->set('ownerPassword', 'trial-ready-password')
            ->call('save')
            ->assertRedirect(route('super-admin.shops.index'));

        $shop = Shop::where('slug', 'default-trial-kitchen')->firstOrFail();

        $this->assertSame('trial', $shop->status);
        $this->assertNotNull($shop->trial_ends_at);
        $this->assertTrue($shop->trial_ends_at->isFuture());
        $this->assertTrue(app(BillingService::class)->isSubscribed($shop));
        $this->assertTrue(app(BillingService::class)->canAccess($shop, 'reports'));
        $this->assertSame('pro', app(BillingService::class)->getCurrentPlan($shop));
    }

    public function test_trial_shop_created_by_super_admin_gets_real_trial_access(): void
    {
        $admin = User::factory()->superAdmin()->create();

        Livewire::actingAs($admin)
            ->test(Manage::class)
            ->set('name', 'Trial Coffee Shop')
            ->set('status', 'trial')
            ->set('ownerName', 'Trial Owner')
            ->set('ownerEmail', 'trial-owner@example.com')
            ->set('ownerPassword', 'trial-password')
            ->call('save')
            ->assertRedirect(route('super-admin.shops.index'));

        $shop = Shop::where('slug', 'trial-coffee-shop')->firstOrFail();

        $this->assertSame('trial', $shop->status);
        $this->assertNotNull($shop->trial_ends_at);
        $this->assertTrue($shop->trial_ends_at->isFuture());
        $this->assertTrue(app(BillingService::class)->isOnTrial($shop));
        $this->assertSame('pro', app(BillingService::class)->getCurrentPlan($shop));
    }

    public function test_new_shop_owner_password_must_be_handoff_strength(): void
    {
        $admin = User::factory()->superAdmin()->create();

        Livewire::actingAs($admin)
            ->test(Manage::class)
            ->set('name', 'Weak Password Cafe')
            ->set('status', 'active')
            ->set('ownerName', 'Weak Owner')
            ->set('ownerEmail', 'weak-owner@example.com')
            ->set('ownerPassword', 'password123')
            ->call('save')
            ->assertHasErrors(['ownerPassword']);

        $this->assertDatabaseMissing('users', ['email' => 'weak-owner@example.com']);
        $this->assertDatabaseMissing('shops', ['slug' => 'weak-password-cafe']);
    }

    public function test_shop_provisioning_service_rejects_weak_raw_owner_password(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Owner handoff password');

        app(ShopProvisioningService::class)->provisionOwner(
            name: 'Weak Owner',
            email: 'weak-owner@example.com',
            password: 'password123',
            shopName: 'Weak Password Cafe',
            slug: 'weak-password-cafe',
            status: 'trial',
        );
    }

    public function test_shop_provisioning_service_rejects_prehashed_owner_password(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('raw owner handoff password');

        app(ShopProvisioningService::class)->provisionOwner(
            name: 'Hashed Owner',
            email: 'hashed-owner@example.com',
            password: Hash::make('password123456'),
            shopName: 'Hashed Password Cafe',
            slug: 'hashed-password-cafe',
            status: 'trial',
        );
    }

    public function test_shop_provisioning_service_trims_owner_handoff_password_before_hashing(): void
    {
        $owner = app(ShopProvisioningService::class)->provisionOwner(
            name: 'Trimmed Owner',
            email: 'trimmed-owner@example.com',
            password: '  launch-password  ',
            shopName: 'Trimmed Password Cafe',
            slug: 'trimmed-password-cafe',
            status: 'trial',
        );

        $this->assertTrue(Hash::check('launch-password', $owner->password));
        $this->assertFalse(Hash::check('  launch-password  ', $owner->password));
    }

    public function test_shop_provisioning_service_normalizes_handoff_identity_and_slug(): void
    {
        $owner = app(ShopProvisioningService::class)->provisionOwner(
            name: '  Atlas Owner  ',
            email: '  Owner@Atlas-Night.TEST  ',
            password: 'launch-password',
            shopName: '  Atlas Night Kitchen  ',
            slug: '  Atlas Night Kitchen!!!  ',
            status: 'trial',
        );

        $shop = $owner->shop()->firstOrFail();

        $this->assertSame('Atlas Owner', $owner->name);
        $this->assertSame('owner@atlas-night.test', $owner->email);
        $this->assertSame('Atlas Night Kitchen', $shop->name);
        $this->assertSame('atlas-night-kitchen', $shop->slug);

        auth()->logout();

        Volt::test('pages.auth.login')
            ->set('form.email', 'owner@atlas-night.test')
            ->set('form.password', 'launch-password')
            ->call('login')
            ->assertHasNoErrors();
    }

    public function test_shop_provisioning_service_makes_explicit_slug_unique(): void
    {
        $firstOwner = app(ShopProvisioningService::class)->provisionOwner(
            name: 'First Owner',
            email: 'first-owner@example.com',
            password: 'launch-password',
            shopName: 'Atlas Night Kitchen',
            slug: 'Atlas Night Kitchen',
            status: 'trial',
        );

        $secondOwner = app(ShopProvisioningService::class)->provisionOwner(
            name: 'Second Owner',
            email: 'second-owner@example.com',
            password: 'launch-password',
            shopName: 'Atlas Night Kitchen',
            slug: 'Atlas Night Kitchen',
            status: 'trial',
        );

        $firstShop = $firstOwner->shop()->firstOrFail();
        $secondShop = $secondOwner->shop()->firstOrFail();

        $this->assertSame('atlas-night-kitchen', $firstShop->slug);
        $this->assertStringStartsWith('atlas-night-kitchen-', $secondShop->slug);
        $this->assertNotSame($firstShop->slug, $secondShop->slug);
        $this->assertSame(2, Shop::where('name', 'Atlas Night Kitchen')->count());
    }

    public function test_can_edit_existing_shop()
    {
        $admin = User::factory()->superAdmin()->create();
        $shop = Shop::factory()->create(['name' => 'Old Name']);

        Livewire::actingAs($admin)
            ->test(Manage::class, ['shop' => $shop])
            ->set('name', 'New Name')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('super-admin.shops.index'));

        $this->assertDatabaseHas('shops', [
            'id' => $shop->id,
            'name' => 'New Name',
        ]);
    }

    public function test_editing_existing_shop_to_trial_starts_real_trial_access(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $shop = Shop::factory()->create([
            'status' => 'active',
            'trial_ends_at' => null,
        ]);

        Livewire::actingAs($admin)
            ->test(Manage::class, ['shop' => $shop])
            ->set('status', 'trial')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('super-admin.shops.index'));

        $shop->refresh();

        $this->assertSame('trial', $shop->status);
        $this->assertNotNull($shop->trial_ends_at);
        $this->assertTrue($shop->trial_ends_at->isFuture());
        $this->assertTrue(app(BillingService::class)->isOnTrial($shop));
        $this->assertSame('pro', app(BillingService::class)->getCurrentPlan($shop));
    }

    public function test_editing_existing_shop_out_of_trial_clears_generic_trial_access(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $shop = Shop::factory()->create([
            'status' => 'trial',
            'trial_ends_at' => now()->addDays(14),
            'branding' => [
                'trial_started_at' => now()->subDay()->toIso8601String(),
                'trial_ends_at' => now()->addDays(14)->toIso8601String(),
            ],
        ]);

        Livewire::actingAs($admin)
            ->test(Manage::class, ['shop' => $shop])
            ->set('status', 'active')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('super-admin.shops.index'));

        $shop->refresh();

        $this->assertSame('active', $shop->status);
        $this->assertNull($shop->trial_ends_at);
        $this->assertFalse(app(BillingService::class)->isOnTrial($shop));
        $this->assertSame('free', app(BillingService::class)->getCurrentPlan($shop));
        $this->assertArrayNotHasKey('trial_started_at', $shop->branding ?? []);
        $this->assertArrayNotHasKey('trial_ends_at', $shop->branding ?? []);
    }
}
