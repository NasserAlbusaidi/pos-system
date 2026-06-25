<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use RuntimeException;
use Stripe\StripeClient;

class StripeRefundService
{
    public function refundPaymentIntent(Order $order, Payment $payment, int $amountMinor): string
    {
        $paymentIntentId = trim((string) $payment->provider_reference);

        if ($paymentIntentId === '') {
            throw new RuntimeException('Stripe payment intent reference is missing.');
        }

        if ($amountMinor <= 0) {
            throw new RuntimeException('Stripe refund amount must be positive.');
        }

        $secret = trim((string) config('cashier.secret'));
        if ($secret === '') {
            throw new RuntimeException('Stripe secret key is not configured.');
        }

        $refund = (new StripeClient($secret))->refunds->create([
            'payment_intent' => $paymentIntentId,
            'amount' => $amountMinor,
            'metadata' => [
                'shop_id' => (string) $order->shop_id,
                'order_id' => (string) $order->id,
                'payment_id' => (string) $payment->id,
            ],
        ], [
            'idempotency_key' => "order-{$order->id}-payment-{$payment->id}-refund",
        ]);

        return (string) $refund->id;
    }
}
