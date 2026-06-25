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
     * Start a Stripe Checkout subscription flow for the shop.
     */
    public function startSubscriptionCheckout(
        Shop $shop,
        string $plan,
        string $successUrl,
        string $cancelUrl,
    ): string {
        $planConfig = $this->getPlanConfig($plan);

        if (! $planConfig || ! $planConfig['stripe_price_id']) {
            throw new \InvalidArgumentException("Invalid plan: {$plan}");
        }

        $builder = $shop->newSubscription('default', $planConfig['stripe_price_id']);
        $trialDays = $this->trialDaysForNewSubscriptionCheckout($shop);

        if ($trialDays !== null) {
            $builder->trialDays($trialDays);
        }

        return (string) $builder
            ->checkout([
                'success_url' => $successUrl,
                'cancel_url' => $cancelUrl,
            ])
            ->url;
    }

    /**
     * Create a Stripe Billing Portal session URL for the shop.
     */
    public function billingPortalUrl(Shop $shop, string $returnUrl): string
    {
        return $shop->billingPortalUrl($returnUrl);
    }

    /**
     * Determine how many trial days Stripe Checkout should attach to a new subscription.
     */
    public function trialDaysForNewSubscriptionCheckout(Shop $shop): ?int
    {
        $configuredTrialDays = (int) config('billing.trial_days', 14);

        if ($configuredTrialDays <= 0) {
            return null;
        }

        if ($shop->onGenericTrial()) {
            $secondsRemaining = now()->diffInSeconds($shop->trial_ends_at, false);

            if ($secondsRemaining <= 0) {
                return null;
            }

            return min($configuredTrialDays, max(1, (int) ceil($secondsRemaining / 86400)));
        }

        if ($this->hasConsumedGenericTrial($shop)) {
            return null;
        }

        return $configuredTrialDays;
    }

    /**
     * Cancel the shop's subscription at the end of the billing period.
     */
    public function cancelSubscription(Shop $shop): bool
    {
        $subscription = $shop->subscription('default');

        if (! $subscription || $subscription->canceled()) {
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
     * Check if the shop has access (active subscription, trial, or free plan).
     *
     * Free-plan shops have no Stripe subscription record but are still legitimate
     * users — they should not be blocked by the subscription gate. Only shops
     * whose subscription has fully expired/lapsed should be redirected to billing.
     */
    public function isSubscribed(Shop $shop): bool
    {
        // Active Stripe subscription or generic trial → always allow.
        if ($shop->subscribed('default') || $shop->onGenericTrial()) {
            return true;
        }

        // No subscription record at all = free plan. Free plan is a valid tier,
        // not an expired state. Allow access; plan limits are enforced separately
        // via canAccess() at the feature level.
        $subscription = $shop->subscription('default');

        return $subscription === null;
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
        if ($this->hasLapsedSubscription($shop)) {
            return false;
        }

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
                return in_array('Reports & Analytics', $planConfig['features'], true);

            case 'menu_engineering':
                return in_array('Menu Engineering', $planConfig['features'], true);

            case 'pricing_rules':
                return in_array('Pricing Rules', $planConfig['features'], true);

            default:
                return false;
        }
    }

    /**
     * Get the plan limits for the shop's current plan.
     *
     * @return array{staff_limit: ?int, product_limit: ?int}
     */
    public function getPlanLimits(Shop $shop): array
    {
        if ($this->hasLapsedSubscription($shop)) {
            $planConfig = $this->getPlanConfig('free');

            return [
                'staff_limit' => $planConfig['staff_limit'] ?? 1,
                'product_limit' => $planConfig['product_limit'] ?? 20,
            ];
        }

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

        if ($subscription->canceled() && $subscription->onGracePeriod()) {
            return 'cancelled';
        }

        if ($subscription->canceled()) {
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

    private function hasConsumedGenericTrial(Shop $shop): bool
    {
        $branding = is_array($shop->branding) ? $shop->branding : [];

        return $shop->trial_ends_at !== null
            || isset($branding['trial_started_at'])
            || isset($branding['trial_ends_at']);
    }

    private function hasLapsedSubscription(Shop $shop): bool
    {
        $subscription = $shop->subscription('default');

        return $subscription !== null
            && ! $subscription->valid()
            && ! $shop->onGenericTrial();
    }
}
