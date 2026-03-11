<?php

namespace App\Livewire;

use App\Services\BillingService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Layout;
use Livewire\Component;

class BillingSettings extends Component
{
    public bool $showCancelModal = false;

    protected BillingService $billing;

    public function boot(BillingService $billing): void
    {
        $this->billing = $billing;
    }

    /**
     * Redirect to Stripe Checkout for subscribing to a plan.
     */
    public function subscribe(string $plan)
    {
        // Validate plan key against known plans to prevent config key probing.
        $allowedPlans = array_keys(config('billing.plans', []));
        if (! in_array($plan, $allowedPlans, true)) {
            $this->dispatch('toast', message: 'Invalid plan selected.', variant: 'error');

            return;
        }

        $shop = Auth::user()->shop;
        $planConfig = config("billing.plans.{$plan}");

        if (! $planConfig || ! $planConfig['stripe_price_id']) {
            $this->dispatch('toast', message: 'Invalid plan selected.', variant: 'error');

            return;
        }

        // Already on this plan.
        if ($this->billing->getCurrentPlan($shop) === $plan && $this->billing->isSubscribed($shop)) {
            $this->dispatch('toast', message: 'You are already on this plan.', variant: 'error');

            return;
        }

        // If the shop already has a subscription, swap instead of creating a new one.
        $subscription = $shop->subscription('default');
        if ($subscription && $subscription->valid()) {
            try {
                $this->billing->swapPlan($shop, $plan);
                $this->dispatch('toast', message: 'Plan updated successfully.', variant: 'success');

                return;
            } catch (\Exception $e) {
                Log::error('Plan swap failed', ['error' => $e->getMessage(), 'shop_id' => $shop->id]);
                $this->dispatch('toast', message: 'Failed to update plan. Please try again.', variant: 'error');

                return;
            }
        }

        // Create a new Stripe Checkout session for subscription.
        try {
            return $shop->newSubscription('default', $planConfig['stripe_price_id'])
                ->trialDays(config('billing.trial_days', 14))
                ->checkout([
                    'success_url' => route('billing').'?checkout=success',
                    'cancel_url' => route('billing').'?checkout=cancelled',
                ])
                ->redirect();
        } catch (\Exception $e) {
            Log::error('Checkout session creation failed', ['error' => $e->getMessage(), 'shop_id' => $shop->id]);
            $this->dispatch('toast', message: 'Could not start checkout. Please try again.', variant: 'error');
        }
    }

    /**
     * Cancel the current subscription.
     */
    public function cancelSubscription()
    {
        $shop = Auth::user()->shop;

        if ($this->billing->cancelSubscription($shop)) {
            $this->showCancelModal = false;
            $this->dispatch('toast', message: 'Subscription cancelled. You will have access until the end of your billing period.', variant: 'success');
        } else {
            $this->dispatch('toast', message: 'Unable to cancel subscription.', variant: 'error');
        }
    }

    /**
     * Resume a cancelled subscription within the grace period.
     */
    public function resumeSubscription()
    {
        $shop = Auth::user()->shop;

        if ($this->billing->resumeSubscription($shop)) {
            $this->dispatch('toast', message: 'Subscription resumed successfully.', variant: 'success');
        } else {
            $this->dispatch('toast', message: 'Unable to resume subscription.', variant: 'error');
        }
    }

    /**
     * Redirect to the Stripe Customer Portal for managing payment methods and invoices.
     */
    public function redirectToPortal()
    {
        $shop = Auth::user()->shop;

        try {
            return $shop->redirectToBillingPortal(route('billing'));
        } catch (\Exception $e) {
            Log::error('Billing portal redirect failed', ['error' => $e->getMessage(), 'shop_id' => $shop->id]);
            $this->dispatch('toast', message: 'Could not open billing portal. Please try again.', variant: 'error');
        }
    }

    #[Layout('layouts.admin')]
    public function render()
    {
        $shop = Auth::user()->shop;
        $subscription = $shop->subscription('default');

        $currentPlan = $this->billing->getCurrentPlan($shop);
        $statusLabel = $this->billing->getStatusLabel($shop);
        $isOnTrial = $this->billing->isOnTrial($shop);
        $trialDaysRemaining = $this->billing->trialDaysRemaining($shop);
        $isSubscribed = $this->billing->isSubscribed($shop);
        $plans = config('billing.plans', []);

        // Renewal / end date
        $renewalDate = null;
        if ($subscription) {
            if ($subscription->cancelled() && $subscription->onGracePeriod()) {
                $renewalDate = $subscription->ends_at;
            } elseif ($subscription->onTrial()) {
                $renewalDate = $subscription->trial_ends_at;
            }
        } elseif ($shop->onGenericTrial()) {
            $renewalDate = $shop->trial_ends_at;
        }

        // Payment method info
        $pmBrand = $shop->pm_type;
        $pmLastFour = $shop->pm_last_four;

        // Invoices (only if Stripe customer exists)
        $invoices = [];
        if ($shop->hasStripeId()) {
            try {
                $invoices = $shop->invoices();
            } catch (\Exception $e) {
                Log::warning('Could not fetch invoices', ['error' => $e->getMessage()]);
                $invoices = [];
            }
        }

        // Check for checkout callback query param
        $checkoutStatus = request()->query('checkout');

        return view('livewire.billing-settings', [
            'shop' => $shop,
            'subscription' => $subscription,
            'currentPlan' => $currentPlan,
            'statusLabel' => $statusLabel,
            'isOnTrial' => $isOnTrial,
            'trialDaysRemaining' => $trialDaysRemaining,
            'isSubscribed' => $isSubscribed,
            'plans' => $plans,
            'renewalDate' => $renewalDate,
            'pmBrand' => $pmBrand,
            'pmLastFour' => $pmLastFour,
            'invoices' => $invoices,
            'checkoutStatus' => $checkoutStatus,
        ]);
    }
}
