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
        ]);

        // 2. Create the Admin User
        \App\Models\User::create([
            'shop_id' => $shop->id,
            'name' => 'Nasser Admin',
            'email' => 'admin@bite.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'is_super_admin' => false,
        ]);

        // 2.5 Create the Super Admin User
        \App\Models\User::create([
            'shop_id' => $shop->id,
            'name' => 'Bite Platform Owner',
            'email' => 'super@bite.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'is_super_admin' => true,
        ]);

        // 3. Create Categories
        $coffee = \App\Models\Category::create([
            'shop_id' => $shop->id,
            'name' => 'Coffee',
            'sort_order' => 1,
        ]);

        $pastries = \App\Models\Category::create([
            'shop_id' => $shop->id,
            'name' => 'Pastries',
            'sort_order' => 2,
        ]);

        // 4. Create Products
        \App\Models\Product::create([
            'shop_id' => $shop->id,
            'category_id' => $coffee->id,
            'name' => 'Latte',
            'description' => 'Espresso with steamed milk',
            'price' => 4.50,
        ]);

        \App\Models\Product::create([
            'shop_id' => $shop->id,
            'category_id' => $coffee->id,
            'name' => 'Americano',
            'description' => 'Espresso with hot water',
            'price' => 3.00,
        ]);

        \App\Models\Product::create([
            'shop_id' => $shop->id,
            'category_id' => $pastries->id,
            'name' => 'Croissant',
            'description' => 'Buttery flaky pastry',
            'price' => 3.50,
        ]);
    }
}
