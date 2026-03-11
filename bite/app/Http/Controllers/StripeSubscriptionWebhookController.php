<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use UnexpectedValueException;

class StripeSubscriptionWebhookController extends Controller
{
    /**
     * Handle Stripe subscription-related webhook events.
     *
     * This is separate from the payment webhook controller to keep
     * subscription billing concerns isolated from POS payment processing.
     */
    public function handle(Request $request): JsonResponse
    {
        $secret = (string) config('billing.stripe_webhook_secret', '');

        if ($secret === '') {
            Log::error('Stripe subscription webhook secret is not configured.');

            return response()->json(['error' => 'Webhook misconfigured'], 500);
        }

        if (! class_exists(Webhook::class)) {
            Log::error('Stripe SDK not installed.');

            return response()->json(['error' => 'Webhook processor unavailable'], 500);
        }

        $payload = $request->getContent();
        $signature = (string) $request->header('Stripe-Signature', '');

        try {
            $event = Webhook::constructEvent($payload, $signature, $secret);
        } catch (UnexpectedValueException|SignatureVerificationException $e) {
            Log::warning('Stripe subscription webhook signature verification failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Invalid signature'], 400);
        }

        $eventId = (string) ($event->id ?? '');
        if ($eventId === '') {
            return response()->json(['error' => 'Invalid event'], 400);
        }

        // Log the webhook event for idempotency and debugging.
        try {
            DB::table('webhook_events')->insert([
                'provider' => 'stripe_subscription',
                'event_id' => $eventId,
                'event_type' => (string) ($event->type ?? ''),
                'payload' => json_encode(json_decode($payload, true), JSON_UNESCAPED_SLASHES),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            // Duplicate event — already processed.
            if ($this->isDuplicate($e)) {
                return response()->json(['received' => true]);
            }

            throw $e;
        }

        $type = $event->type ?? '';
        $data = $event->data->object ?? null;

        if (! $data) {
            $this->markProcessed($eventId);

            return response()->json(['received' => true]);
        }

        try {
            DB::transaction(function () use ($type, $data, $eventId) {
                match ($type) {
                    'customer.subscription.created' => $this->handleSubscriptionCreated($data),
                    'customer.subscription.updated' => $this->handleSubscriptionUpdated($data),
                    'customer.subscription.deleted' => $this->handleSubscriptionDeleted($data),
                    'invoice.payment_succeeded' => $this->handleInvoicePaymentSucceeded($data),
                    'invoice.payment_failed' => $this->handleInvoicePaymentFailed($data),
                    default => null, // Ignore unhandled event types.
                };

                $this->markProcessed($eventId);
            });
        } catch (\Exception $e) {
            Log::error('Stripe subscription webhook handler error', [
                'event_type' => $type,
                'event_id' => $eventId,
                'error' => $e->getMessage(),
            ]);

            // Return 500 so Stripe retries the event instead of marking it processed.
            return response()->json(['error' => 'Handler failed'], 500);
        }

        return response()->json(['received' => true]);
    }

    /**
     * Handle customer.subscription.created event.
     */
    protected function handleSubscriptionCreated(object $subscription): void
    {
        $shop = $this->findShopByStripeCustomer($subscription->customer);

        if (! $shop) {
            Log::warning('Subscription created for unknown customer', [
                'customer' => $subscription->customer,
            ]);

            return;
        }

        Log::info('Subscription created via webhook', [
            'shop_id' => $shop->id,
            'subscription_id' => $subscription->id,
            'status' => $subscription->status,
        ]);
    }

    /**
     * Handle customer.subscription.updated event.
     */
    protected function handleSubscriptionUpdated(object $subscription): void
    {
        $shop = $this->findShopByStripeCustomer($subscription->customer);

        if (! $shop) {
            return;
        }

        // Update the local subscription record status.
        $localSub = DB::table('subscriptions')
            ->where('stripe_id', $subscription->id)
            ->first();

        if ($localSub) {
            DB::table('subscriptions')
                ->where('stripe_id', $subscription->id)
                ->update([
                    'stripe_status' => $subscription->status,
                    'ends_at' => $subscription->cancel_at
                        ? \Carbon\Carbon::createFromTimestamp($subscription->cancel_at)
                        : null,
                    'updated_at' => now(),
                ]);
        }

        Log::info('Subscription updated via webhook', [
            'shop_id' => $shop->id,
            'subscription_id' => $subscription->id,
            'status' => $subscription->status,
        ]);
    }

    /**
     * Handle customer.subscription.deleted event.
     */
    protected function handleSubscriptionDeleted(object $subscription): void
    {
        $shop = $this->findShopByStripeCustomer($subscription->customer);

        if (! $shop) {
            return;
        }

        // Mark the local subscription as cancelled/ended.
        DB::table('subscriptions')
            ->where('stripe_id', $subscription->id)
            ->update([
                'stripe_status' => $subscription->status,
                'ends_at' => now(),
                'updated_at' => now(),
            ]);

        Log::info('Subscription deleted via webhook', [
            'shop_id' => $shop->id,
            'subscription_id' => $subscription->id,
        ]);
    }

    /**
     * Handle invoice.payment_succeeded event.
     */
    protected function handleInvoicePaymentSucceeded(object $invoice): void
    {
        $shop = $this->findShopByStripeCustomer($invoice->customer);

        if (! $shop) {
            return;
        }

        // Update payment method info on the shop if provided.
        if (isset($invoice->payment_intent)) {
            try {
                $stripe = new \Stripe\StripeClient((string) config('cashier.secret'));
                $pi = $stripe->paymentIntents->retrieve($invoice->payment_intent);

                if ($pi->payment_method) {
                    $pm = $stripe->paymentMethods->retrieve($pi->payment_method);
                    $shop->update([
                        'pm_type' => $pm->card->brand ?? null,
                        'pm_last_four' => $pm->card->last4 ?? null,
                    ]);
                }
            } catch (\Exception $e) {
                // Non-critical — card info update failed but payment succeeded.
                Log::warning('Could not update payment method info', ['error' => $e->getMessage()]);
            }
        }

        Log::info('Invoice payment succeeded', [
            'shop_id' => $shop->id,
            'invoice_id' => $invoice->id,
            'amount' => $invoice->amount_paid,
        ]);
    }

    /**
     * Handle invoice.payment_failed event.
     */
    protected function handleInvoicePaymentFailed(object $invoice): void
    {
        $shop = $this->findShopByStripeCustomer($invoice->customer);

        if (! $shop) {
            return;
        }

        Log::warning('Invoice payment failed', [
            'shop_id' => $shop->id,
            'invoice_id' => $invoice->id,
            'amount' => $invoice->amount_due,
            'attempt_count' => $invoice->attempt_count ?? null,
        ]);

        // Update subscription status to reflect the payment failure.
        if (isset($invoice->subscription)) {
            DB::table('subscriptions')
                ->where('stripe_id', $invoice->subscription)
                ->update([
                    'stripe_status' => 'past_due',
                    'updated_at' => now(),
                ]);
        }
    }

    /**
     * Find a Shop by their Stripe customer ID.
     */
    protected function findShopByStripeCustomer(string $customerId): ?Shop
    {
        return Shop::where('stripe_id', $customerId)->first();
    }

    /**
     * Mark a webhook event as processed.
     */
    protected function markProcessed(string $eventId): void
    {
        DB::table('webhook_events')
            ->where('provider', 'stripe_subscription')
            ->where('event_id', $eventId)
            ->update([
                'processed_at' => now(),
                'updated_at' => now(),
            ]);
    }

    /**
     * Check if the query exception is due to a duplicate entry.
     */
    protected function isDuplicate(\Illuminate\Database\QueryException $e): bool
    {
        $message = strtolower($e->getMessage());

        return str_contains($message, 'webhook_events_provider_event_id_unique')
            || str_contains($message, 'duplicate entry')
            || str_contains($message, 'unique constraint failed');
    }
}
