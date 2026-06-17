<?php

namespace App\Livewire\Concerns;

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
        abort_unless(
            in_array(Auth::user()?->role, $this->allowedRoles(), true),
            403,
            'Unauthorized role.'
        );
    }
}
