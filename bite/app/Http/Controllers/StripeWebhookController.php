<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Order;
use App\Models\Payment;
use App\Models\ShiftClosure;
use App\Services\LoyaltyService;
use App\Services\PrintNodeService;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use Throwable;
use UnexpectedValueException;

class StripeWebhookController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        if ($this->customerPaymentProvider() !== 'stripe') {
            return response()->json(['error' => 'Stripe payments are disabled'], 404);
        }

        $secret = (string) config('payments.stripe_webhook_secret', '');
        if ($secret === '') {
            $this->logMissingWebhookSecretOnce();

            return response()->json(['error' => 'Webhook misconfigured'], 503);
        }

        if (! class_exists(Webhook::class)) {
            Log::error('Stripe SDK not installed.');

            return response()->json(['error' => 'Webhook processor unavailable'], 500);
        }

        $payload = $request->getContent();
        $signature = (string) $request->header('Stripe-Signature', '');
        $decodedPayload = json_decode($payload, true);
        if (! is_array($decodedPayload)) {
            return response()->json(['error' => 'Invalid payload'], 400);
        }

        try {
            $event = Webhook::constructEvent($payload, $signature, $secret);
        } catch (UnexpectedValueException|SignatureVerificationException $e) {
            Log::warning('Stripe webhook signature verification failed', ['error' => $e->getMessage()]);

            return response()->json(['error' => 'Invalid signature'], 400);
        }

        $eventId = (string) ($event->id ?? '');
        if ($eventId === '') {
            return response()->json(['error' => 'Invalid event'], 400);
        }

        try {
            DB::transaction(function () use ($event, $eventId, $decodedPayload) {
                DB::table('webhook_events')->insert([
                    'provider' => 'stripe',
                    'event_id' => $eventId,
                    'event_type' => (string) ($event->type ?? ''),
                    'payload' => json_encode($decodedPayload, JSON_UNESCAPED_SLASHES),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                if (($event->type ?? '') !== 'payment_intent.succeeded') {
                    $this->markWebhookProcessed($eventId);

                    return;
                }

                $data = $event->data->object ?? null;
                if (! $data) {
                    $this->markWebhookProcessed($eventId);

                    return;
                }

                $metadata = $data->metadata ?? null;
                $orderId = $metadata ? ($metadata->order_id ?? null) : null;
                if (! $orderId) {
                    $this->markWebhookProcessed($eventId);

                    return;
                }

                $order = Order::query()->with('shop')->whereKey((int) $orderId)->lockForUpdate()->first();
                if (! $order || $order->status !== 'unpaid') {
                    $this->markWebhookProcessed($eventId);

                    return;
                }

                $currency = strtolower((string) ($data->currency ?? 'omr'));
                $expectedCurrency = strtolower((string) ($order->shop?->currency_code ?: 'OMR'));
                if ($currency === '' || $currency !== $expectedCurrency) {
                    Log::warning('Stripe payment currency did not match order shop currency.', [
                        'order_id' => $order->id,
                        'shop_id' => $order->shop_id,
                        'stripe_currency' => $currency,
                        'expected_currency' => $expectedCurrency,
                    ]);
                    $this->markWebhookProcessed($eventId);

                    return;
                }

                $currencyDecimals = (int) ($order->shop?->currency_decimals ?? 3);
                $divisor = 10 ** $currencyDecimals;
                $amount = round(((float) ($data->amount_received ?? 0)) / $divisor, 3);
                if ($amount <= 0) {
                    $this->markWebhookProcessed($eventId);

                    return;
                }

                $balance = round($order->balance_due, 3);
                $tolerance = 0.0005;
                if (abs($amount - $balance) > $tolerance) {
                    $this->recordStripeAmountMismatch($order, $eventId, $data, $amount, $balance);
                    $this->markWebhookProcessed($eventId);

                    return;
                }

                if ($order->shop && ShiftClosure::isClosedFor($order->shop)) {
                    $this->recordStripePaymentAfterShiftClose($order, $eventId, $data, $amount);
                    $this->markWebhookProcessed($eventId);

                    return;
                }

                Payment::forceCreate([
                    'shop_id' => $order->shop_id,
                    'order_id' => $order->id,
                    'amount' => $amount,
                    'method' => 'stripe',
                    'provider_reference' => (string) ($data->id ?? ''),
                    'created_by' => null,
                    'paid_at' => now(),
                ]);

                $order->refresh();
                if ($order->balance_due <= 0) {
                    $order->update([
                        'status' => 'paid',
                        'payment_method' => $order->paymentSummaryMethod() ?? 'stripe',
                        'paid_at' => now(),
                    ]);

                    AuditLog::record('order.paid', $order, ['payment_method' => 'stripe']);
                    app(LoyaltyService::class)->award($order);
                    $this->printOrderSafely($order, 'kitchen');
                }

                $this->markWebhookProcessed($eventId);
            });
        } catch (QueryException $e) {
            if ($this->isDuplicateWebhookEvent($e)) {
                return response()->json(['received' => true]);
            }

            throw $e;
        }

        return response()->json(['received' => true]);
    }

    protected function recordStripeAmountMismatch(Order $order, string $eventId, mixed $data, float $amount, float $balance): void
    {
        $meta = [
            'event_id' => $eventId,
            'payment_intent_id' => (string) ($data->id ?? ''),
            'stripe_amount' => $amount,
            'expected_balance' => $balance,
            'currency' => (string) ($data->currency ?? ''),
        ];

        Log::warning('Stripe payment amount did not match remaining order balance.', [
            'order_id' => $order->id,
            'shop_id' => $order->shop_id,
        ] + $meta);

        AuditLog::record('stripe.payment_amount_mismatch', $order, $meta);
    }

    protected function recordStripePaymentAfterShiftClose(Order $order, string $eventId, mixed $data, float $amount): void
    {
        $meta = [
            'event_id' => $eventId,
            'payment_intent_id' => (string) ($data->id ?? ''),
            'stripe_amount' => $amount,
            'currency' => (string) ($data->currency ?? ''),
            'business_date' => $order->shop ? ShiftClosure::businessDateFor($order->shop) : null,
            'resolution' => 'manual_refund_or_reopen_required',
        ];

        Log::warning('Stripe payment succeeded after local shift was closed.', [
            'order_id' => $order->id,
            'shop_id' => $order->shop_id,
        ] + $meta);

        AuditLog::record('stripe.payment_after_shift_close', $order, $meta);
    }

    protected function printOrderSafely(Order $order, string $type): void
    {
        try {
            app(PrintNodeService::class)->printOrder($order, $type);
        } catch (Throwable $e) {
            Log::warning('Order print failed after Stripe payment processing.', [
                'order_id' => $order->id,
                'shop_id' => $order->shop_id,
                'type' => $type,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function markWebhookProcessed(string $eventId): void
    {
        DB::table('webhook_events')
            ->where('provider', 'stripe')
            ->where('event_id', $eventId)
            ->update([
                'processed_at' => now(),
                'updated_at' => now(),
            ]);
    }

    protected function isDuplicateWebhookEvent(QueryException $e): bool
    {
        $message = strtolower($e->getMessage());

        return str_contains($message, 'webhook_events_provider_event_id_unique')
            || str_contains($message, 'duplicate entry')
            || str_contains($message, 'unique constraint failed');
    }

    private function logMissingWebhookSecretOnce(): void
    {
        RateLimiter::attempt(
            'stripe-webhook-missing-secret-log',
            1,
            fn () => Log::error('Stripe webhook secret is not configured.'),
            3600,
        );
    }

    private function customerPaymentProvider(): string
    {
        return strtolower(trim((string) config('payments.provider', 'counter')));
    }
}
