<?php

namespace Tests\Browser\Phase2;

use App\Models\Shop;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\SeedsTestData;
use Tests\DuskTestCase;

class OnboardingWizardTest extends DuskTestCase
{
    use SeedsTestData;

    public function test_new_admin_can_complete_onboarding(): void
    {
        $slug = 'onboard-test-'.uniqid();

        $shop = Shop::factory()->create([
            'slug' => $slug,
            'name' => 'Onboard Cafe',
            'tax_rate' => 0,
            'branding' => [
                'accent' => '#cc5500',
                'paper' => '#fdfcf8',
                'ink' => '#1a1918',
                // Note: no 'onboarding_completed' key — triggers onboarding flow
            ],
        ]);

        $admin = User::factory()->create([
            'shop_id' => $shop->id,
            'role' => 'admin',
            'email' => 'onboard-'.uniqid().'@test.com',
            'password' => Hash::make('password'),
            'is_super_admin' => false,
        ]);

        $this->browse(function (Browser $browser) use ($admin) {
            // Step 1: Welcome
            $browser->loginAs($admin)
                ->visit('/onboarding')
                ->assertPathIs('/onboarding')
                ->waitForText('Welcome to Bite')
                ->assertSee('Onboard Cafe')
                ->click('button[wire\\:click="nextStep"]')

                // Step 2: Shop Profile — skip it
                ->waitForText('Shop Profile')
                ->assertSee('CURRENCY')
                ->click('button[wire\\:click="nextStep"]')

                // Step 3: First Menu Items — skip it
                ->waitForText('First Menu Items')
                ->assertSee('ADD A FEW PRODUCTS')
                ->click('button[wire\\:click="nextStep"]')

                // Step 4: Staff PINs — skip it
                ->waitForText('Create Staff PINs')
                ->assertSee('ADD TEAM MEMBERS')
                ->click('button[wire\\:click="nextStep"]')

                // Step 5: Done
                ->waitForText("You're All Set")
                ->assertSee('Onboard Cafe')

                // Complete onboarding — click "Go to Dashboard"
                ->click('button[wire\\:click="completeOnboarding"]')
                ->waitForLocation('/dashboard')
                ->assertPathIs('/dashboard');
        });

        // Verify onboarding_completed flag was persisted
        $shop->refresh();
        $this->assertTrue(
            ! empty($shop->branding['onboarding_completed']),
            'Onboarding completed flag should be set in shop branding'
        );
    }
}
