<?php

namespace Tests\Feature;

use App\Models\Shop;
use App\Services\BillingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class StripeSubscriptionWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_missing_subscription_webhook_secret_fails_without_log_flood(): void
    {
        config(['billing.stripe_webhook_secret' => '']);
        RateLimiter::clear('stripe-subscription-webhook-missing-secret-log');
        Log::spy();

        $payload = $this->subscriptionPayload('evt_sub_missing_secret', 'customer.subscription.created', [
            'id' => 'sub_missing_secret',
            'customer' => 'cus_missing_secret',
            'status' => 'active',
            'items' => ['data' => []],
        ]);

        for ($i = 0; $i < 2; $i++) {
            $this->call(
                'POST',
                route('webhooks.stripe.subscription'),
                [],
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                $payload
            )->assertStatus(503)
                ->assertJson(['error' => 'Webhook misconfigured']);
        }

        Log::shouldHaveReceived('error')
            ->once()
            ->with('Stripe subscription webhook secret is not configured.');
        $this->assertDatabaseCount('webhook_events', 0);
    }

    public function test_subscription_created_syncs_local_pro_subscription(): void
    {
        $secret = 'whsec_subscription_test';
        config([
            'billing.stripe_webhook_secret' => $secret,
            'billing.plans.pro.stripe_price_id' => 'price_pro_test',
        ]);

        $shop = Shop::create(['name' => 'Bite', 'slug' => 'bite']);
        $shop->forceFill([
            'stripe_id' => 'cus_subscription_test',
            'trial_ends_at' => now()->addDays(14),
        ])->save();

        $payload = $this->subscriptionPayload('evt_sub_created', 'customer.subscription.created', [
            'id' => 'sub_pro_test',
            'customer' => 'cus_subscription_test',
            'status' => 'active',
            'items' => [
                'data' => [[
                    'id' => 'si_pro_test',
                    'quantity' => 1,
                    'price' => [
                        'id' => 'price_pro_test',
                        'product' => 'prod_pro_test',
                    ],
                ]],
            ],
        ]);

        $this->postSignedSubscriptionWebhook($payload, $secret)
            ->assertOk()
            ->assertJson(['received' => true]);

        $this->assertDatabaseHas('subscriptions', [
            'shop_id' => $shop->id,
            'type' => 'default',
            'stripe_id' => 'sub_pro_test',
            'stripe_status' => 'active',
            'stripe_price' => 'price_pro_test',
        ]);
        $this->assertDatabaseHas('subscription_items', [
            'stripe_id' => 'si_pro_test',
            'stripe_product' => 'prod_pro_test',
            'stripe_price' => 'price_pro_test',
            'quantity' => 1,
        ]);
        $this->assertNull($shop->fresh()->trial_ends_at);
        $this->assertSame('pro', app(BillingService::class)->getCurrentPlan($shop->fresh()));
    }

    public function test_unprocessed_duplicate_subscription_event_is_retried(): void
    {
        $secret = 'whsec_subscription_test';
        config([
            'billing.stripe_webhook_secret' => $secret,
            'billing.plans.pro.stripe_price_id' => 'price_pro_test',
        ]);

        $shop = Shop::create(['name' => 'Bite', 'slug' => 'bite']);
        $shop->forceFill(['stripe_id' => 'cus_subscription_retry'])->save();

        DB::table('webhook_events')->insert([
            'provider' => 'stripe_subscription',
            'event_id' => 'evt_sub_retry',
            'event_type' => 'customer.subscription.created',
            'payload' => '{}',
            'processed_at' => null,
            'created_at' => now()->subMinute(),
            'updated_at' => now()->subMinute(),
        ]);

        $payload = $this->subscriptionPayload('evt_sub_retry', 'customer.subscription.created', [
            'id' => 'sub_retry_test',
            'customer' => 'cus_subscription_retry',
            'status' => 'active',
            'items' => [
                'data' => [[
                    'id' => 'si_retry_test',
                    'quantity' => 1,
                    'price' => [
                        'id' => 'price_pro_test',
                        'product' => 'prod_pro_test',
                    ],
                ]],
            ],
        ]);

        $this->postSignedSubscriptionWebhook($payload, $secret)
            ->assertOk()
            ->assertJson(['received' => true]);

        $this->assertDatabaseHas('subscriptions', [
            'shop_id' => $shop->id,
            'stripe_id' => 'sub_retry_test',
            'stripe_price' => 'price_pro_test',
        ]);
        $this->assertNotNull(DB::table('webhook_events')
            ->where('provider', 'stripe_subscription')
            ->where('event_id', 'evt_sub_retry')
            ->value('processed_at'));
    }

    public function test_subscription_deleted_expires_local_subscription_and_blocks_operational_access(): void
    {
        $secret = 'whsec_subscription_test';
        config([
            'billing.stripe_webhook_secret' => $secret,
            'billing.plans.pro.stripe_price_id' => 'price_pro_test',
        ]);

        $shop = Shop::create(['name' => 'Bite', 'slug' => 'bite']);
        $shop->forceFill(['stripe_id' => 'cus_subscription_deleted'])->save();
        $user = \App\Models\User::factory()->create([
            'shop_id' => $shop->id,
            'role' => 'admin',
        ]);

        $shop->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_deleted_test',
            'stripe_status' => 'active',
            'stripe_price' => 'price_pro_test',
            'quantity' => 1,
        ]);

        $payload = $this->subscriptionPayload('evt_sub_deleted', 'customer.subscription.deleted', [
            'id' => 'sub_deleted_test',
            'customer' => 'cus_subscription_deleted',
            'status' => 'canceled',
            'items' => [
                'data' => [[
                    'id' => 'si_deleted_test',
                    'quantity' => 1,
                    'price' => [
                        'id' => 'price_pro_test',
                        'product' => 'prod_pro_test',
                    ],
                ]],
            ],
        ]);

        $this->postSignedSubscriptionWebhook($payload, $secret)
            ->assertOk()
            ->assertJson(['received' => true]);

        $this->assertDatabaseHas('subscriptions', [
            'shop_id' => $shop->id,
            'stripe_id' => 'sub_deleted_test',
            'stripe_status' => 'canceled',
        ]);
        $this->assertNotNull(DB::table('subscriptions')
            ->where('stripe_id', 'sub_deleted_test')
            ->value('ends_at'));
        $this->assertFalse(app(BillingService::class)->isSubscribed($shop->fresh()));
        $this->actingAs($user)
            ->get(route('pos.dashboard'))
            ->assertRedirect(route('billing'));
    }

    public function test_subscription_update_replaces_stale_subscription_items(): void
    {
        $secret = 'whsec_subscription_test';
        config([
            'billing.stripe_webhook_secret' => $secret,
            'billing.plans.pro.stripe_price_id' => 'price_pro_new',
        ]);

        $shop = Shop::create(['name' => 'Bite', 'slug' => 'bite']);
        $shop->forceFill(['stripe_id' => 'cus_subscription_items'])->save();

        $subscriptionId = DB::table('subscriptions')->insertGetId([
            'shop_id' => $shop->id,
            'type' => 'default',
            'stripe_id' => 'sub_items_test',
            'stripe_status' => 'active',
            'stripe_price' => 'price_pro_old',
            'quantity' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('subscription_items')->insert([
            'subscription_id' => $subscriptionId,
            'stripe_id' => 'si_old_item',
            'stripe_product' => 'prod_old',
            'stripe_price' => 'price_pro_old',
            'quantity' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $payload = $this->subscriptionPayload('evt_sub_items_updated', 'customer.subscription.updated', [
            'id' => 'sub_items_test',
            'customer' => 'cus_subscription_items',
            'status' => 'active',
            'items' => [
                'data' => [[
                    'id' => 'si_new_item',
                    'quantity' => 2,
                    'price' => [
                        'id' => 'price_pro_new',
                        'product' => 'prod_new',
                    ],
                ]],
            ],
        ]);

        $this->postSignedSubscriptionWebhook($payload, $secret)
            ->assertOk()
            ->assertJson(['received' => true]);

        $this->assertDatabaseHas('subscriptions', [
            'id' => $subscriptionId,
            'stripe_price' => 'price_pro_new',
            'quantity' => 2,
        ]);
        $this->assertDatabaseMissing('subscription_items', [
            'subscription_id' => $subscriptionId,
            'stripe_id' => 'si_old_item',
        ]);
        $this->assertDatabaseHas('subscription_items', [
            'subscription_id' => $subscriptionId,
            'stripe_id' => 'si_new_item',
            'stripe_product' => 'prod_new',
            'stripe_price' => 'price_pro_new',
            'quantity' => 2,
        ]);
        $this->assertSame('pro', app(BillingService::class)->getCurrentPlan($shop->fresh()));
    }

    protected function subscriptionPayload(string $eventId, string $eventType, array $subscription): string
    {
        return json_encode([
            'id' => $eventId,
            'type' => $eventType,
            'data' => [
                'object' => $subscription,
            ],
        ], JSON_THROW_ON_ERROR);
    }

    protected function postSignedSubscriptionWebhook(string $payload, string $secret)
    {
        return $this->call(
            'POST',
            route('webhooks.stripe.subscription'),
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_STRIPE_SIGNATURE' => $this->signature($payload, $secret),
            ],
            $payload
        );
    }

    protected function signature(string $payload, string $secret): string
    {
        $timestamp = time();
        $signedPayload = $timestamp.'.'.$payload;
        $signature = hash_hmac('sha256', $signedPayload, $secret);

        return "t={$timestamp},v1={$signature}";
    }
}
