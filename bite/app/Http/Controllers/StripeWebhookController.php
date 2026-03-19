<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Order;
use App\Models\Payment;
use App\Services\LoyaltyService;
use App\Services\PrintNodeService;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use UnexpectedValueException;

class StripeWebhookController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        $secret = (string) config('payments.stripe_webhook_secret', '');
        if ($secret === '') {
            Log::error('Stripe webhook secret is not configured.');

            return response()->json(['error' => 'Webhook misconfigured'], 500);
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

                $order = Order::query()->whereKey((int) $orderId)->lockForUpdate()->first();
                if (! $order || $order->status !== 'unpaid') {
                    $this->markWebhookProcessed($eventId);

                    return;
                }

                $amount = round(((float) ($data->amount_received ?? 0)) / 100, 3);
                if ($amount <= 0) {
                    $this->markWebhookProcessed($eventId);

                    return;
                }

                Payment::forceCreate([
                    'shop_id' => $order->shop_id,
                    'order_id' => $order->id,
                    'amount' => $amount,
                    'method' => 'stripe',
                    'created_by' => null,
                    'paid_at' => now(),
                ]);

                $order->refresh();
                if ($order->balance_due <= 0) {
                    $order->update([
                        'status' => 'paid',
                        'payment_method' => 'stripe',
                        'paid_at' => now(),
                    ]);

                    AuditLog::record('order.paid', $order, ['payment_method' => 'stripe']);
                    app(LoyaltyService::class)->award($order);
                    app(PrintNodeService::class)->printOrder($order, 'kitchen');
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
}
