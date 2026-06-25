<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\ShiftClosure;
use App\Models\Shop;
use App\Services\PrintNodeService;
use App\Support\ShopClock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class StripeWebhookSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['payments.provider' => 'stripe']);
    }

    public function test_payment_webhook_is_unavailable_when_customer_payments_are_counter_only(): void
    {
        config([
            'payments.provider' => 'counter',
            'payments.stripe_webhook_secret' => 'whsec_test_secret',
        ]);

        $payload = json_encode(['id' => 'evt_counter_only'], JSON_THROW_ON_ERROR);

        $this->call(
            'POST',
            route('webhooks.stripe'),
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_STRIPE_SIGNATURE' => $this->signature($payload, 'whsec_test_secret'),
            ],
            $payload
        )->assertNotFound()
            ->assertJson(['error' => 'Stripe payments are disabled']);

        $this->assertDatabaseCount('webhook_events', 0);
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_missing_payment_webhook_secret_fails_without_log_flood(): void
    {
        config(['payments.stripe_webhook_secret' => '']);
        RateLimiter::clear('stripe-webhook-missing-secret-log');
        Log::spy();

        $payload = json_encode(['id' => 'evt_missing_secret'], JSON_THROW_ON_ERROR);

        for ($i = 0; $i < 2; $i++) {
            $this->call(
                'POST',
                route('webhooks.stripe'),
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
            ->with('Stripe webhook secret is not configured.');
        $this->assertDatabaseCount('webhook_events', 0);
    }

    public function test_signed_stripe_event_is_accepted_and_applied_once(): void
    {
        $secret = 'whsec_test_secret';
        config(['payments.stripe_webhook_secret' => $secret]);

        $shop = Shop::create(['name' => 'Bite', 'slug' => 'bite']);
        $order = Order::forceCreate([
            'shop_id' => $shop->id,
            'status' => 'unpaid',
            'total_amount' => 15.00,
            'subtotal_amount' => 15.00,
            'tax_amount' => 0,
        ]);

        $payload = $this->paymentIntentSucceededPayload($order, 'evt_accepted_once', 15000);
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
            'provider_reference' => 'pi_evt_accepted_once',
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

    public function test_printer_failure_does_not_block_signed_stripe_payment(): void
    {
        $secret = 'whsec_test_secret';
        config(['payments.stripe_webhook_secret' => $secret]);

        $shop = Shop::create(['name' => 'Bite', 'slug' => 'bite']);
        $order = Order::forceCreate([
            'shop_id' => $shop->id,
            'status' => 'unpaid',
            'total_amount' => 15.00,
            'subtotal_amount' => 15.00,
            'tax_amount' => 0,
        ]);

        $this->app->instance(PrintNodeService::class, new class extends PrintNodeService
        {
            public function printOrder(Order $order, string $type = 'kitchen'): bool
            {
                throw new \RuntimeException('Printer offline');
            }
        });

        $payload = $this->paymentIntentSucceededPayload($order, 'evt_print_failure', 15000);
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
        )->assertOk()->assertJson(['received' => true]);

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
        $this->assertNotNull(DB::table('webhook_events')
            ->where('provider', 'stripe')
            ->where('event_id', 'evt_print_failure')
            ->value('processed_at'));
    }

    public function test_invalid_signature_is_rejected(): void
    {
        config(['payments.stripe_webhook_secret' => 'whsec_test_secret']);

        $shop = Shop::create(['name' => 'Bite', 'slug' => 'bite']);
        $order = Order::forceCreate([
            'shop_id' => $shop->id,
            'status' => 'unpaid',
            'total_amount' => 15.00,
            'subtotal_amount' => 15.00,
            'tax_amount' => 0,
        ]);

        $payload = $this->paymentIntentSucceededPayload($order, 'evt_invalid_sig', 15000);

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
        $order = Order::forceCreate([
            'shop_id' => $shop->id,
            'status' => 'unpaid',
            'total_amount' => 20.00,
            'subtotal_amount' => 20.00,
            'tax_amount' => 0,
        ]);

        $payload = $this->paymentIntentSucceededPayload($order, 'evt_duplicate', 20000);
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

    public function test_stripe_payment_over_remaining_balance_is_rejected_without_local_payment(): void
    {
        $secret = 'whsec_test_secret';
        config(['payments.stripe_webhook_secret' => $secret]);

        $shop = Shop::create(['name' => 'Bite', 'slug' => 'bite']);
        $order = Order::forceCreate([
            'shop_id' => $shop->id,
            'status' => 'unpaid',
            'total_amount' => 15.00,
            'subtotal_amount' => 15.00,
            'tax_amount' => 0,
        ]);

        $payload = $this->paymentIntentSucceededPayload($order, 'evt_over_balance', 20000);
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

        $this->assertDatabaseCount('payments', 0);
        $this->assertSame('unpaid', $order->fresh()->status);
        $this->assertSame(0.0, $order->fresh()->paid_total);
        $this->assertSame(15.0, $order->fresh()->balance_due);
        $this->assertDatabaseHas('audit_logs', [
            'shop_id' => $shop->id,
            'action' => 'stripe.payment_amount_mismatch',
            'auditable_type' => Order::class,
            'auditable_id' => $order->id,
        ]);
        $this->assertNotNull(DB::table('webhook_events')
            ->where('provider', 'stripe')
            ->where('event_id', 'evt_over_balance')
            ->value('processed_at'));
    }

    public function test_stripe_payment_under_remaining_balance_is_rejected_without_local_payment(): void
    {
        $secret = 'whsec_test_secret';
        config(['payments.stripe_webhook_secret' => $secret]);

        $shop = Shop::create(['name' => 'Bite', 'slug' => 'bite']);
        $order = Order::forceCreate([
            'shop_id' => $shop->id,
            'status' => 'unpaid',
            'total_amount' => 15.00,
            'subtotal_amount' => 15.00,
            'tax_amount' => 0,
        ]);

        $payload = $this->paymentIntentSucceededPayload($order, 'evt_under_balance', 10000);
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

        $this->assertDatabaseCount('payments', 0);
        $this->assertSame('unpaid', $order->fresh()->status);
        $this->assertSame(0.0, $order->fresh()->paid_total);
        $this->assertSame(15.0, $order->fresh()->balance_due);
        $this->assertDatabaseHas('audit_logs', [
            'shop_id' => $shop->id,
            'action' => 'stripe.payment_amount_mismatch',
            'auditable_type' => Order::class,
            'auditable_id' => $order->id,
        ]);
        $this->assertNotNull(DB::table('webhook_events')
            ->where('provider', 'stripe')
            ->where('event_id', 'evt_under_balance')
            ->value('processed_at'));
    }

    public function test_stripe_final_payment_after_existing_payment_marks_order_split(): void
    {
        $secret = 'whsec_test_secret';
        config(['payments.stripe_webhook_secret' => $secret]);

        $shop = Shop::create(['name' => 'Bite', 'slug' => 'bite']);
        $order = Order::forceCreate([
            'shop_id' => $shop->id,
            'status' => 'unpaid',
            'total_amount' => 15.00,
            'subtotal_amount' => 15.00,
            'tax_amount' => 0,
        ]);

        \App\Models\Payment::forceCreate([
            'shop_id' => $shop->id,
            'order_id' => $order->id,
            'amount' => 5.00,
            'method' => 'cash',
            'paid_at' => now(),
        ]);

        $payload = $this->paymentIntentSucceededPayload($order, 'evt_existing_payment_split', 10000);
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
        )->assertOk()->assertJson(['received' => true]);

        $paid = $order->fresh();
        $this->assertSame('paid', $paid->status);
        $this->assertSame('split', $paid->payment_method);
        $this->assertEqualsWithDelta(15.00, $paid->paid_total, 0.0001);
    }

    public function test_stripe_payment_currency_must_match_order_shop_currency(): void
    {
        $secret = 'whsec_test_secret';
        config(['payments.stripe_webhook_secret' => $secret]);

        $shop = Shop::create([
            'name' => 'Bite',
            'slug' => 'bite',
            'currency_code' => 'OMR',
            'currency_decimals' => 3,
        ]);
        $order = Order::forceCreate([
            'shop_id' => $shop->id,
            'status' => 'unpaid',
            'total_amount' => 15.00,
            'subtotal_amount' => 15.00,
            'tax_amount' => 0,
        ]);

        $payload = $this->paymentIntentSucceededPayload($order, 'evt_wrong_currency', 1500, 'usd');
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
        )->assertOk()->assertJson(['received' => true]);

        $this->assertDatabaseCount('payments', 0);
        $this->assertSame('unpaid', $order->fresh()->status);
        $this->assertNotNull(DB::table('webhook_events')
            ->where('provider', 'stripe')
            ->where('event_id', 'evt_wrong_currency')
            ->value('processed_at'));
    }

    public function test_stripe_success_after_shift_close_is_audited_without_local_payment(): void
    {
        $secret = 'whsec_test_secret';
        config(['payments.stripe_webhook_secret' => $secret]);

        $shop = Shop::create(['name' => 'Bite', 'slug' => 'bite']);
        $order = Order::forceCreate([
            'shop_id' => $shop->id,
            'status' => 'unpaid',
            'total_amount' => 15.00,
            'subtotal_amount' => 15.00,
            'tax_amount' => 0,
        ]);
        ShiftClosure::forceCreate([
            'shop_id' => $shop->id,
            'business_date' => ShopClock::localDate($shop),
            'closed_by' => null,
            'expected_cash' => 0.000,
            'actual_cash' => 0.000,
            'difference' => 0.000,
            'shift_summary' => [
                'total_orders' => 0,
                'total_revenue' => 0.000,
                'cash_total' => 0.000,
                'card_total' => 0.000,
                'voucher_total' => 0.000,
            ],
            'closed_at' => now(),
        ]);

        $payload = $this->paymentIntentSucceededPayload($order, 'evt_after_shift_close', 15000);
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
        )->assertOk()->assertJson(['received' => true]);

        $this->assertDatabaseCount('payments', 0);
        $this->assertSame('unpaid', $order->fresh()->status);
        $this->assertDatabaseHas('audit_logs', [
            'shop_id' => $shop->id,
            'action' => 'stripe.payment_after_shift_close',
            'auditable_type' => Order::class,
            'auditable_id' => $order->id,
        ]);
        $this->assertNotNull(DB::table('webhook_events')
            ->where('provider', 'stripe')
            ->where('event_id', 'evt_after_shift_close')
            ->value('processed_at'));
    }

    protected function paymentIntentSucceededPayload(Order $order, string $eventId, int $amountReceived, string $currency = 'omr'): string
    {
        return json_encode([
            'id' => $eventId,
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => 'pi_'.$eventId,
                    'amount_received' => $amountReceived,
                    'currency' => $currency,
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
