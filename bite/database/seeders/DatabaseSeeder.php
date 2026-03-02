<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Create the Demo Shop
        $shop = \App\Models\Shop::create([
            'name' => 'Bite Demo Coffee',
            'slug' => 'demo',
            'currency_code' => 'OMR',
            'currency_symbol' => 'ر.ع.',
            'currency_decimals' => 3,
            'tax_rate' => 0,
            'branding' => [
                'accent' => '#cc5500',
                'paper' => '#fdfcf8',
                'ink' => '#1a1918',
                'onboarding_completed' => true,
            ],
        ]);

        // 2. Create the Admin User
        \App\Models\User::create([
            'shop_id' => $shop->id,
            'name' => 'Nasser Admin',
            'email' => 'admin@bite.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        // 2.5 Create the Super Admin User
        $superAdmin = \App\Models\User::create([
            'shop_id' => $shop->id,
            'name' => 'Bite Platform Owner',
            'email' => 'super@bite.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);
        $superAdmin->is_super_admin = true;
        $superAdmin->save();

        // 3. Seed demo menu (categories, products, modifiers)
        (new DemoMenuSeeder)->seedForShop($shop);
    }
}
