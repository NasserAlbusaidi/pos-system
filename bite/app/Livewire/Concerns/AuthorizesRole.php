<?php

namespace App\Livewire\Concerns;

use App\Services\BillingService;
use Illuminate\Support\Facades\Auth;

/**
 * Component-level role guard for Livewire components.
 *
 * Route role middleware (role:manager,admin, etc.) only protects the GET render.
 * The livewire/update endpoint runs on the bare `web` middleware group, so without
 * this a low-trust PIN account can hydrate any admin component and invoke its
 * actions. Livewire calls boot{TraitName}() on mount AND every hydration, so this
 * guard fires on every request that touches the component. See issue #52.
 *
 * Each component implements allowedRoles(); forgetting to is a fatal error, not a
 * silent hole.
 */
trait AuthorizesRole
{
    /**
     * Roles permitted to interact with this component.
     *
     * @return array<int, string>
     */
    abstract protected function allowedRoles(): array;

    public function bootAuthorizesRole(): void
    {
        $user = Auth::user();

        abort_unless(
            in_array($user?->role, $this->allowedRoles(), true),
            403,
            'Unauthorized role.'
        );

        if (! $user || $user->is_super_admin || ! $this->requiresActiveSubscribedShop()) {
            return;
        }

        abort_unless($user->shop, 403, 'Shop is required.');
        abort_if($user->shop->status === 'suspended', 403, 'Shop is suspended.');

        $billing = app(BillingService::class);
        abort_unless($billing->isSubscribed($user->shop), 403, 'Subscription is not active.');

        $feature = $this->requiredPlanFeature();
        if ($feature !== null) {
            abort_unless($billing->canAccess($user->shop, $feature), 403, 'Feature requires Pro plan.');
        }
    }

    protected function requiresActiveSubscribedShop(): bool
    {
        return true;
    }

    protected function requiredPlanFeature(): ?string
    {
        return null;
    }
}
