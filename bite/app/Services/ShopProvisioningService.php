<?php

namespace App\Services;

use App\Helpers\SlugGenerator;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ShopProvisioningService
{
    /**
     * Provision a new shop and owner user in one transaction.
     */
    public function provisionOwner(string $name, string $email, string $password, ?string $shopName = null): User
    {
        $shopName = $shopName ?: "{$name}'s Restaurant";

        return DB::transaction(function () use ($name, $email, $password, $shopName) {
            $shop = Shop::create([
                'name' => $shopName,
                'slug' => SlugGenerator::fromName($shopName),
                'currency_code' => 'OMR',
                'currency_symbol' => "\u{0631}.\u{0639}.",
                'currency_decimals' => 3,
                'tax_rate' => 0,
                'branding' => [
                    'trial_started_at' => now()->toIso8601String(),
                    'trial_ends_at' => now()->addDays(14)->toIso8601String(),
                ],
            ]);
            $shop->status = 'trial';
            $shop->save();

            return User::create([
                'shop_id' => $shop->id,
                'name' => $name,
                'email' => $email,
                'password' => $password,
                'role' => 'admin',
            ]);
        });
    }
}
