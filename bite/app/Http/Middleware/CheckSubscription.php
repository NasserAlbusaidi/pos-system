<?php

namespace App\Http\Middleware;

use App\Services\BillingService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscription
{
    public function __construct(protected BillingService $billing)
    {
        //
    }

    /**
     * Verify the shop has an active subscription or is on trial.
     * Redirect to the billing page if the subscription has expired.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->shop) {
            return $next($request);
        }

        // Super admins bypass subscription checks.
        if ($user->is_super_admin) {
            return $next($request);
        }

        $shop = $user->shop;

        // Allow access if the shop is subscribed or on trial.
        if ($this->billing->isSubscribed($shop)) {
            return $next($request);
        }

        // If they are already on the billing page, let them through.
        if ($request->routeIs('billing')) {
            return $next($request);
        }

        // Redirect to billing page with a notice.
        session()->flash('billing_notice', 'Your subscription has expired. Please update your plan to continue.');

        return redirect()->route('billing');
    }
}
