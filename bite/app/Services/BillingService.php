<?php

namespace App\Services;

use App\Models\Shop;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Exceptions\IncompletePayment;

class BillingService
{
    /**
     * Create a new subscription for the shop.
     *
     * @throws IncompletePayment
     */
    public function createSubscription(Shop $shop, string $plan, string $paymentMethod): void
    {
        $planConfig = $this->getPlanConfig($plan);

        if (! $planConfig || ! $planConfig['stripe_price_id']) {
            throw new \InvalidArgumentException("Invalid plan: {$plan}");
        }

        $shop->newSubscription('default', $planConfig['stripe_price_id'])
            ->create($paymentMethod);

        Log::info('Subscription created', ['shop_id' => $shop->id, 'plan' => $plan]);
    }

    /**
     * Cancel the shop's subscription at the end of the billing period.
     */
    public function cancelSubscription(Shop $shop): bool
    {
        $subscription = $shop->subscription('default');

        if (! $subscription || $subscription->cancelled()) {
            return false;
        }

        $subscription->cancel();

        Log::info('Subscription cancelled', ['shop_id' => $shop->id]);

        return true;
    }

    /**
     * Resume a cancelled subscription that is still within the grace period.
     */
    public function resumeSubscription(Shop $shop): bool
    {
        $subscription = $shop->subscription('default');

        if (! $subscription || ! $subscription->onGracePeriod()) {
            return false;
        }

        $subscription->resume();

        Log::info('Subscription resumed', ['shop_id' => $shop->id]);

        return true;
    }

    /**
     * Swap the shop's subscription to a different plan.
     */
    public function swapPlan(Shop $shop, string $newPlan): bool
    {
        $planConfig = $this->getPlanConfig($newPlan);

        if (! $planConfig || ! $planConfig['stripe_price_id']) {
            return false;
        }

        $subscription = $shop->subscription('default');

        if (! $subscription) {
            return false;
        }

        $subscription->swap($planConfig['stripe_price_id']);

        Log::info('Plan swapped', ['shop_id' => $shop->id, 'new_plan' => $newPlan]);

        return true;
    }

    /**
     * Check if the shop is currently on a trial.
     */
    public function isOnTrial(Shop $shop): bool
    {
        return $shop->onTrial('default') || $shop->onGenericTrial();
    }

    /**
     * Check if the shop has an active subscription (including trial).
     */
    public function isSubscribed(Shop $shop): bool
    {
        return $shop->subscribed('default') || $shop->onGenericTrial();
    }

    /**
     * Get the shop's current plan key ('free', 'pro', etc.).
     */
    public function getCurrentPlan(Shop $shop): ?string
    {
        // Shops on a generic trial get Pro features (trial = try the full product).
        if ($shop->onGenericTrial()) {
            return 'pro';
        }

        $subscription = $shop->subscription('default');

        if (! $subscription) {
            return 'free';
        }

        $plans = config('billing.plans', []);
        $currentPriceId = $subscription->stripe_price;

        foreach ($plans as $key => $plan) {
            if (($plan['stripe_price_id'] ?? null) === $currentPriceId) {
                return $key;
            }
        }

        return 'free';
    }

    /**
     * Check if the shop can access a specific feature based on plan limits.
     */
    public function canAccess(Shop $shop, string $feature): bool
    {
        $plan = $this->getCurrentPlan($shop);
        $planConfig = $this->getPlanConfig($plan);

        if (! $planConfig) {
            return false;
        }

        switch ($feature) {
            case 'add_staff':
                $limit = $planConfig['staff_limit'];
                if ($limit === null) {
                    return true; // unlimited
                }

                return $shop->users()->count() < $limit;

            case 'add_product':
                $limit = $planConfig['product_limit'];
                if ($limit === null) {
                    return true; // unlimited
                }

                return $shop->products()->count() < $limit;

            case 'reports':
                return in_array('Reports & Analytics', $planConfig['features']);

            default:
                return true;
        }
    }

    /**
     * Get the plan limits for the shop's current plan.
     *
     * @return array{staff_limit: ?int, product_limit: ?int}
     */
    public function getPlanLimits(Shop $shop): array
    {
        $plan = $this->getCurrentPlan($shop);
        $planConfig = $this->getPlanConfig($plan);

        return [
            'staff_limit' => $planConfig['staff_limit'] ?? 1,
            'product_limit' => $planConfig['product_limit'] ?? 20,
        ];
    }

    /**
     * Get remaining days on the shop's trial.
     */
    public function trialDaysRemaining(Shop $shop): int
    {
        if ($shop->onGenericTrial()) {
            return (int) now()->diffInDays($shop->trial_ends_at, false);
        }

        $subscription = $shop->subscription('default');

        if ($subscription && $subscription->onTrial()) {
            return (int) now()->diffInDays($subscription->trial_ends_at, false);
        }

        return 0;
    }

    /**
     * Get the subscription status label for display.
     */
    public function getStatusLabel(Shop $shop): string
    {
        $subscription = $shop->subscription('default');

        if (! $subscription) {
            if ($shop->onGenericTrial()) {
                return 'trialing';
            }

            return 'free';
        }

        if ($subscription->onTrial()) {
            return 'trialing';
        }

        if ($subscription->cancelled() && $subscription->onGracePeriod()) {
            return 'cancelled';
        }

        if ($subscription->cancelled()) {
            return 'expired';
        }

        $stripeStatus = $subscription->stripe_status;

        return match ($stripeStatus) {
            'active' => 'active',
            'past_due' => 'past_due',
            'incomplete' => 'incomplete',
            'incomplete_expired' => 'expired',
            'unpaid' => 'past_due',
            default => $stripeStatus,
        };
    }

    /**
     * Get the config array for a given plan key.
     */
    protected function getPlanConfig(string $plan): ?array
    {
        return config("billing.plans.{$plan}");
    }
}
