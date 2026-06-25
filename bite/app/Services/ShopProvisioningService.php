<?php

namespace App\Services;

use App\Helpers\SlugGenerator;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use InvalidArgumentException;

class ShopProvisioningService
{
    /**
     * Provision a new shop and owner user in one transaction.
     */
    public function provisionOwner(
        string $name,
        string $email,
        string $password,
        ?string $shopName = null,
        ?string $slug = null,
        string $status = 'trial',
    ): User {
        if (! Hash::needsRehash($password)) {
            throw new InvalidArgumentException(
                'Shop provisioning requires a raw owner handoff password so it can be validated before hashing.'
            );
        }

        $password = trim($password);

        if (! $this->handoffPasswordIsAcceptable($password)) {
            throw new InvalidArgumentException(
                'Owner handoff password must be at least 12 characters and cannot be the default password.'
            );
        }

        $name = trim($name);
        $email = Str::lower(trim($email));
        $shopName = trim($shopName ?: "{$name}'s Restaurant");
        $slug = $this->normalizeSlug($slug, $shopName);
        $status = in_array($status, ['active', 'suspended', 'trial'], true) ? $status : 'trial';
        $trialEndsAt = $status === 'trial'
            ? now()->addDays(config('billing.trial_days', 14))
            : null;

        return DB::transaction(function () use ($name, $email, $password, $shopName, $slug, $status, $trialEndsAt) {
            $shop = Shop::create([
                'name' => $shopName,
                'slug' => $slug,
                'currency_code' => 'OMR',
                'currency_symbol' => "\u{0631}.\u{0639}.",
                'currency_decimals' => 3,
                'tax_rate' => 0,
                'branding' => $trialEndsAt
                    ? [
                        'trial_started_at' => now()->toIso8601String(),
                        'trial_ends_at' => $trialEndsAt->toIso8601String(),
                    ]
                    : null,
            ]);
            $shop->status = $status;
            $shop->trial_ends_at = $trialEndsAt;
            $shop->save();

            return User::forceCreate([
                'shop_id' => $shop->id,
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
                'role' => 'admin',
            ]);
        });
    }

    private function handoffPasswordIsAcceptable(string $password): bool
    {
        $password = trim($password);

        return strlen($password) >= 12 && $password !== 'password';
    }

    private function normalizeSlug(?string $slug, string $shopName): string
    {
        if ($slug === null || trim($slug) === '') {
            return SlugGenerator::fromName($shopName);
        }

        $normalized = Str::slug($slug);

        return $normalized !== ''
            ? SlugGenerator::fromName($normalized)
            : SlugGenerator::fromName($shopName);
    }
}
