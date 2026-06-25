<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;

class PinCodePolicy
{
    public function isUniqueForShop(int $shopId, string $pin, ?int $exceptUserId = null): bool
    {
        return $this->matchingUsers($shopId, $pin, $exceptUserId)->isEmpty();
    }

    /**
     * @return Collection<int, User>
     */
    public function matchingUsers(int $shopId, string $pin, ?int $exceptUserId = null): Collection
    {
        $pin = trim($pin);
        if ($pin === '') {
            return collect();
        }

        return User::where('shop_id', $shopId)
            ->whereNotNull('pin_code')
            ->when($exceptUserId, fn ($query) => $query->whereKeyNot($exceptUserId))
            ->orderBy('id')
            ->get()
            ->filter(fn (User $user) => Hash::check($pin, $user->pin_code))
            ->values();
    }
}
