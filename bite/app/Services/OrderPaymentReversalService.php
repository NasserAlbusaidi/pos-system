<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Throwable;

class OrderPaymentReversalService
{
    private const LOCAL_REVERSIBLE_METHODS = ['cash', 'card', 'voucher'];

    private const STRIPE_REVERSIBLE_METHOD = 'stripe';

    public function reverseLocalPaymentsAndCancel(Order $order, ?User $actor = null): array
    {
        $previousStatus = $order->status;
        $positivePayments = $order->trustedPayments()
            ->filter(fn (Payment $payment): bool => round((float) $payment->amount, 3) > 0)
            ->values();

        if ($positivePayments->isEmpty() || $order->paid_total <= 0) {
            return [
                'cancelled' => false,
                'reason' => 'no_payment_to_reverse',
                'order' => $order,
                'previous_status' => $previousStatus,
            ];
        }

        $unsupportedMethods = $positivePayments
            ->pluck('method')
            ->map(fn ($method) => trim((string) $method))
            ->filter(fn (string $method): bool => ! $this->isReversibleMethod($method))
            ->unique()
            ->values();

        if ($unsupportedMethods->isNotEmpty()) {
            return [
                'cancelled' => false,
                'reason' => 'unsupported_payment_method',
                'unsupported_methods' => $unsupportedMethods->all(),
                'order' => $order,
                'previous_status' => $previousStatus,
            ];
        }

        $stripePaymentsWithoutReference = $positivePayments
            ->filter(fn (Payment $payment): bool => $this->isStripeMethod((string) $payment->method)
                && trim((string) $payment->provider_reference) === '')
            ->values();

        if ($stripePaymentsWithoutReference->isNotEmpty()) {
            return [
                'cancelled' => false,
                'reason' => 'external_refund_required',
                'unsupported_methods' => ['stripe'],
                'order' => $order,
                'previous_status' => $previousStatus,
            ];
        }

        $reversalRows = [];

        foreach ($positivePayments as $payment) {
            $amount = round(-1 * abs((float) $payment->amount), 3);
            $providerReference = null;

            if ($this->isStripeMethod((string) $payment->method)) {
                try {
                    $providerReference = app(StripeRefundService::class)->refundPaymentIntent(
                        $order,
                        $payment,
                        $this->minorAmount($order, $payment)
                    );
                } catch (Throwable $e) {
                    return [
                        'cancelled' => false,
                        'reason' => 'stripe_refund_failed',
                        'error' => $e->getMessage(),
                        'order' => $order,
                        'previous_status' => $previousStatus,
                    ];
                }
            }

            $reversal = Payment::forceCreate([
                'shop_id' => $order->shop_id,
                'order_id' => $order->id,
                'amount' => $amount,
                'method' => $payment->method,
                'provider_reference' => $providerReference,
                'created_by' => $actor?->id,
                'reverses_payment_id' => $payment->id,
                'paid_at' => now(),
            ]);

            $reversalRows[] = [
                'payment_id' => $payment->id,
                'reversal_payment_id' => $reversal->id,
                'amount' => $amount,
                'method' => $payment->method,
                'provider_reference' => $providerReference,
            ];
        }

        $order->refresh();
        $order->update([
            'status' => 'cancelled',
            'payment_method' => 'refunded',
        ]);
        app(LoyaltyService::class)->reverseAwardForRefundedOrder($order->fresh());

        return [
            'cancelled' => true,
            'refunded' => true,
            'order' => $order->fresh(),
            'previous_status' => $previousStatus,
            'refund_total' => round(abs(collect($reversalRows)->sum('amount')), 3),
            'refund_rows' => $reversalRows,
        ];
    }

    private function isReversibleMethod(string $method): bool
    {
        return in_array($method, self::LOCAL_REVERSIBLE_METHODS, true)
            || $this->isStripeMethod($method);
    }

    private function isStripeMethod(string $method): bool
    {
        return $method === self::STRIPE_REVERSIBLE_METHOD;
    }

    private function minorAmount(Order $order, Payment $payment): int
    {
        $decimals = (int) ($order->shop?->currency_decimals ?? 3);

        return (int) round(abs((float) $payment->amount) * (10 ** $decimals));
    }
}
