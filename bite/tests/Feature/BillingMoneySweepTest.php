<?php

namespace Tests\Feature;

use App\Livewire\BillingSettings;
use App\Models\Shop;
use App\Models\User;
use App\Services\BillingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Mockery;
use Tests\TestCase;

class BillingMoneySweepTest extends TestCase
{
    use RefreshDatabase;

    public function test_switching_to_free_cancels_current_paid_subscription(): void
    {
        $shop = Shop::create(['name' => 'Bite', 'slug' => 'bite']);
        $admin = User::factory()->create(['shop_id' => $shop->id, 'role' => 'admin']);

        $billing = Mockery::mock(BillingService::class);
        $billing->shouldReceive('getCurrentPlan')->andReturn('pro');
        $billing->shouldReceive('getStatusLabel')->andReturn('active');
        $billing->shouldReceive('isOnTrial')->andReturn(false);
        $billing->shouldReceive('trialDaysRemaining')->andReturn(0);
        $billing->shouldReceive('isSubscribed')->andReturn(true);
        $billing->shouldReceive('cancelSubscription')
            ->once()
            ->with(Mockery::on(fn (Shop $target) => $target->is($shop)))
            ->andReturn(true);

        $this->app->instance(BillingService::class, $billing);

        Livewire::actingAs($admin)
            ->test(BillingSettings::class)
            ->call('subscribe', 'free')
            ->assertDispatched(
                'toast',
                message: 'Subscription cancelled. You will have access until the end of your billing period.',
                variant: 'success',
            );
    }

    public function test_consumed_generic_trial_does_not_get_fresh_stripe_checkout_trial(): void
    {
        config(['billing.trial_days' => 14]);

        $shop = Shop::factory()->create([
            'trial_ends_at' => now()->subDay(),
            'branding' => [
                'trial_started_at' => now()->subDays(15)->toIso8601String(),
                'trial_ends_at' => now()->subDay()->toIso8601String(),
            ],
        ]);

        $this->assertNull(app(BillingService::class)->trialDaysForNewSubscriptionCheckout($shop));
    }

    public function test_pro_checkout_is_created_through_billing_service(): void
    {
        config(['billing.plans.pro.stripe_price_id' => 'price_pro_test']);

        $shop = Shop::factory()->create(['trial_ends_at' => now()->subDay()]);
        $admin = User::factory()->create(['shop_id' => $shop->id, 'role' => 'admin']);

        $billing = Mockery::mock(BillingService::class);
        $billing->shouldReceive('getCurrentPlan')->andReturn('free');
        $billing->shouldReceive('getStatusLabel')->andReturn('free');
        $billing->shouldReceive('isOnTrial')->andReturn(false);
        $billing->shouldReceive('trialDaysRemaining')->andReturn(0);
        $billing->shouldReceive('isSubscribed')->andReturn(true);
        $billing->shouldReceive('startSubscriptionCheckout')
            ->once()
            ->with(
                Mockery::on(fn (Shop $target) => $target->is($shop)),
                'pro',
                route('billing').'?checkout=success',
                route('billing').'?checkout=cancelled',
            )
            ->andReturn('https://checkout.stripe.test/session');

        $this->app->instance(BillingService::class, $billing);

        Livewire::actingAs($admin)
            ->test(BillingSettings::class)
            ->call('subscribe', 'pro')
            ->assertRedirect('https://checkout.stripe.test/session');
    }

    public function test_billing_portal_redirect_is_created_through_billing_service(): void
    {
        $shop = Shop::factory()->create();
        $admin = User::factory()->create(['shop_id' => $shop->id, 'role' => 'admin']);

        $billing = Mockery::mock(BillingService::class);
        $billing->shouldReceive('getCurrentPlan')->andReturn('free');
        $billing->shouldReceive('getStatusLabel')->andReturn('free');
        $billing->shouldReceive('isOnTrial')->andReturn(false);
        $billing->shouldReceive('trialDaysRemaining')->andReturn(0);
        $billing->shouldReceive('isSubscribed')->andReturn(true);
        $billing->shouldReceive('billingPortalUrl')
            ->once()
            ->with(
                Mockery::on(fn (Shop $target) => $target->is($shop)),
                route('billing'),
            )
            ->andReturn('https://billing.stripe.test/session');

        $this->app->instance(BillingService::class, $billing);

        Livewire::actingAs($admin)
            ->test(BillingSettings::class)
            ->call('redirectToPortal')
            ->assertRedirect('https://billing.stripe.test/session');
    }
}
