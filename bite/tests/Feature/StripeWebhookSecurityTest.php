<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Shop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StripeWebhookSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_signed_stripe_event_is_accepted_and_applied_once(): void
    {
        $secret = 'whsec_test_secret';
        config(['payments.stripe_webhook_secret' => $secret]);

        $shop = Shop::create(['name' => 'Bite', 'slug' => 'bite']);
        $order = Order::create([
            'shop_id' => $shop->id,
            'status' => 'unpaid',
            'total_amount' => 15.00,
            'subtotal_amount' => 15.00,
            'tax_amount' => 0,
        ]);

        $payload = $this->paymentIntentSucceededPayload($order, 'evt_accepted_once', 1500);
        $signature = $this->signature($payload, $secret);

        $response = $this->call(
            'POST',
            route('webhooks.stripe'),
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_STRIPE_SIGNATURE' => $signature,
            ],
            $payload
        );

        $response->assertOk()->assertJson(['received' => true]);

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'shop_id' => $shop->id,
            'amount' => 15.00,
            'method' => 'stripe',
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'paid',
            'payment_method' => 'stripe',
        ]);

        $this->assertDatabaseHas('webhook_events', [
            'provider' => 'stripe',
            'event_id' => 'evt_accepted_once',
            'event_type' => 'payment_intent.succeeded',
        ]);

        $processedAt = (string) DB::table('webhook_events')
            ->where('provider', 'stripe')
            ->where('event_id', 'evt_accepted_once')
            ->value('processed_at');

        $this->assertNotSame('', $processedAt);
    }

    public function test_invalid_signature_is_rejected(): void
    {
        config(['payments.stripe_webhook_secret' => 'whsec_test_secret']);

        $shop = Shop::create(['name' => 'Bite', 'slug' => 'bite']);
        $order = Order::create([
            'shop_id' => $shop->id,
            'status' => 'unpaid',
            'total_amount' => 15.00,
            'subtotal_amount' => 15.00,
            'tax_amount' => 0,
        ]);

        $payload = $this->paymentIntentSucceededPayload($order, 'evt_invalid_sig', 1500);

        $response = $this->call(
            'POST',
            route('webhooks.stripe'),
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_STRIPE_SIGNATURE' => 'invalid-signature',
            ],
            $payload
        );

        $response->assertStatus(400);

        $this->assertDatabaseCount('payments', 0);
        $this->assertDatabaseMissing('webhook_events', [
            'provider' => 'stripe',
            'event_id' => 'evt_invalid_sig',
        ]);
    }

    public function test_duplicate_event_id_is_idempotent(): void
    {
        $secret = 'whsec_test_secret';
        config(['payments.stripe_webhook_secret' => $secret]);

        $shop = Shop::create(['name' => 'Bite', 'slug' => 'bite']);
        $order = Order::create([
            'shop_id' => $shop->id,
            'status' => 'unpaid',
            'total_amount' => 20.00,
            'subtotal_amount' => 20.00,
            'tax_amount' => 0,
        ]);

        $payload = $this->paymentIntentSucceededPayload($order, 'evt_duplicate', 2000);
        $signature = $this->signature($payload, $secret);

        $this->call(
            'POST',
            route('webhooks.stripe'),
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_STRIPE_SIGNATURE' => $signature,
            ],
            $payload
        )->assertOk();

        $this->call(
            'POST',
            route('webhooks.stripe'),
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_STRIPE_SIGNATURE' => $signature,
            ],
            $payload
        )->assertOk();

        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseCount('webhook_events', 1);
    }

    protected function paymentIntentSucceededPayload(Order $order, string $eventId, int $amountReceived): string
    {
        return json_encode([
            'id' => $eventId,
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => 'pi_'.$eventId,
                    'amount_received' => $amountReceived,
                    'metadata' => [
                        'order_id' => (string) $order->id,
                    ],
                ],
            ],
        ], JSON_THROW_ON_ERROR);
    }

    protected function signature(string $payload, string $secret): string
    {
        $timestamp = time();
        $signedPayload = $timestamp.'.'.$payload;
        $signature = hash_hmac('sha256', $signedPayload, $secret);

        return "t={$timestamp},v1={$signature}";
    }
}
