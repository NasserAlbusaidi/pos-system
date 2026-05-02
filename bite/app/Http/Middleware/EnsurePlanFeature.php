<?php

namespace App\Http\Middleware;

use App\Services\BillingService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePlanFeature
{
    public function __construct(protected BillingService $billing)
    {
        //
    }

    /**
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        if ($user->is_super_admin) {
            return $next($request);
        }

        if (! $user->shop) {
            return $next($request);
        }

        if ($this->billing->canAccess($user->shop, $feature)) {
            return $next($request);
        }

        session()->flash('billing_notice', 'This feature requires Pro plan.');

        return redirect()->route('billing');
    }
}
