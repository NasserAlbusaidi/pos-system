<?php

namespace App\Support;

use App\Models\User;

class UserHomeRoute
{
    public static function url(User $user, bool $absolute = true): string
    {
        if ($user->is_super_admin) {
            return route('super-admin.dashboard', absolute: $absolute);
        }

        if ($user->shouldRedirectToOnboarding()) {
            return route('onboarding', absolute: $absolute);
        }

        if ($user->role === 'kitchen') {
            return route('kds.view', absolute: $absolute);
        }

        return route('dashboard', absolute: $absolute);
    }
}
