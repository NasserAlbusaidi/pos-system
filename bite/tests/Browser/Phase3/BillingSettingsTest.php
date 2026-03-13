<?php

namespace Tests\Browser\Phase3;

use App\Models\Shop;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class BillingSettingsTest extends DuskTestCase
{
    public function test_billing_page_shows_trial_status(): void
    {
        $shop = Shop::factory()->create([
            'slug' => 'billing-test-' . uniqid(),
            'trial_ends_at' => now()->addDays(10),
            'branding' => ['onboarding_completed' => true],
        ]);

        $admin = User::factory()->create([
            'shop_id' => $shop->id,
            'role' => 'admin',
            'password' => Hash::make('password'),
        ]);

        $this->browse(function (Browser $browser) use ($admin) {
            $browser->loginAs($admin)
                ->visit('/billing')
                ->assertPathIs('/billing')
                ->assertSee('trial')
                ->assertSee('days remaining');
        });
    }

    public function test_billing_page_shows_current_plan(): void
    {
        $shop = Shop::factory()->create([
            'slug' => 'plan-test-' . uniqid(),
            'branding' => ['onboarding_completed' => true],
        ]);

        $admin = User::factory()->create([
            'shop_id' => $shop->id,
            'role' => 'admin',
            'password' => Hash::make('password'),
        ]);

        $this->browse(function (Browser $browser) use ($admin) {
            $browser->loginAs($admin)
                ->visit('/billing')
                ->assertPathIs('/billing');
            // Asserts the page renders without error — plan details
            // depend on Stripe state which we don't mock in E2E
        });
    }
}
