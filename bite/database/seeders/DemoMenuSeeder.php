<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\ModifierGroup;
use App\Models\ModifierOption;
use App\Models\Product;
use App\Models\Shop;
use Illuminate\Database\Seeder;

class DemoMenuSeeder extends Seeder
{
    /**
     * Seed a demo cafe menu for a specific shop.
     *
     * Usage: (new DemoMenuSeeder)->seedForShop($shop);
     */
    public function seedForShop(Shop $shop): void
    {
        // ── Categories ──────────────────────────────────────────
        $hotDrinks = Category::create([
            'shop_id' => $shop->id,
            'name' => 'Hot Drinks',
            'sort_order' => 1,
        ]);

        $coldDrinks = Category::create([
            'shop_id' => $shop->id,
            'name' => 'Cold Drinks',
            'sort_order' => 2,
        ]);

        $food = Category::create([
            'shop_id' => $shop->id,
            'name' => 'Food',
            'sort_order' => 3,
        ]);

        $desserts = Category::create([
            'shop_id' => $shop->id,
            'name' => 'Desserts',
            'sort_order' => 4,
        ]);

        // ── Products ────────────────────────────────────────────
        $hotDrinkProducts = [
            ['name' => 'Karak Tea', 'description' => 'Traditional spiced milk tea', 'price' => 0.500],
            ['name' => 'Turkish Coffee', 'description' => 'Strong finely ground coffee', 'price' => 0.800],
            ['name' => 'Cappuccino', 'description' => 'Espresso with steamed milk foam', 'price' => 1.200],
            ['name' => 'Latte', 'description' => 'Espresso with smooth steamed milk', 'price' => 1.500],
            ['name' => 'Hot Chocolate', 'description' => 'Rich creamy hot chocolate', 'price' => 1.200],
        ];

        $coldDrinkProducts = [
            ['name' => 'Iced Latte', 'description' => 'Chilled espresso with cold milk', 'price' => 1.800],
            ['name' => 'Fresh Juice', 'description' => 'Freshly squeezed seasonal fruit', 'price' => 1.500],
            ['name' => 'Smoothie', 'description' => 'Blended fruit and yogurt', 'price' => 2.000],
            ['name' => 'Iced Tea', 'description' => 'Refreshing cold brewed tea', 'price' => 1.000],
        ];

        $foodProducts = [
            ['name' => 'Club Sandwich', 'description' => 'Triple-decker with chicken and veggies', 'price' => 2.500],
            ['name' => 'Chicken Shawarma', 'description' => 'Grilled marinated chicken in flatbread', 'price' => 1.500],
            ['name' => 'Beef Burger', 'description' => 'Juicy beef patty with fresh toppings', 'price' => 3.000],
            ['name' => 'Caesar Salad', 'description' => 'Crisp romaine with caesar dressing', 'price' => 2.200],
            ['name' => 'French Fries', 'description' => 'Golden crispy fries', 'price' => 1.000],
        ];

        $dessertProducts = [
            ['name' => 'Kunafa', 'description' => 'Sweet cheese pastry with syrup', 'price' => 1.800],
            ['name' => 'Cheesecake', 'description' => 'Classic New York style cheesecake', 'price' => 2.000],
            ['name' => 'Luqaimat', 'description' => 'Sweet fried dumplings with date syrup', 'price' => 1.200],
            ['name' => 'Ice Cream', 'description' => 'Two scoops of premium ice cream', 'price' => 1.500],
        ];

        $createdProducts = collect();

        $order = 1;
        foreach ($hotDrinkProducts as $p) {
            $createdProducts->push(Product::create(array_merge($p, [
                'shop_id' => $shop->id,
                'category_id' => $hotDrinks->id,
                'sort_order' => $order++,
            ])));
        }

        $order = 1;
        foreach ($coldDrinkProducts as $p) {
            $createdProducts->push(Product::create(array_merge($p, [
                'shop_id' => $shop->id,
                'category_id' => $coldDrinks->id,
                'sort_order' => $order++,
            ])));
        }

        $order = 1;
        foreach ($foodProducts as $p) {
            $createdProducts->push(Product::create(array_merge($p, [
                'shop_id' => $shop->id,
                'category_id' => $food->id,
                'sort_order' => $order++,
            ])));
        }

        $order = 1;
        foreach ($dessertProducts as $p) {
            $createdProducts->push(Product::create(array_merge($p, [
                'shop_id' => $shop->id,
                'category_id' => $desserts->id,
                'sort_order' => $order++,
            ])));
        }

        // ── Modifier Groups ────────────────────────────────────

        // Size (for drinks)
        $sizeGroup = ModifierGroup::create([
            'shop_id' => $shop->id,
            'name' => 'Size',
            'min_selection' => 1,
            'max_selection' => 1,
        ]);

        ModifierOption::create([
            'modifier_group_id' => $sizeGroup->id,
            'name' => 'Small',
            'price_adjustment' => -0.300,
        ]);
        ModifierOption::create([
            'modifier_group_id' => $sizeGroup->id,
            'name' => 'Regular',
            'price_adjustment' => 0,
        ]);
        ModifierOption::create([
            'modifier_group_id' => $sizeGroup->id,
            'name' => 'Large',
            'price_adjustment' => 0.300,
        ]);

        // Milk Type (for coffee)
        $milkGroup = ModifierGroup::create([
            'shop_id' => $shop->id,
            'name' => 'Milk Type',
            'min_selection' => 0,
            'max_selection' => 1,
        ]);

        ModifierOption::create([
            'modifier_group_id' => $milkGroup->id,
            'name' => 'Regular Milk',
            'price_adjustment' => 0,
        ]);
        ModifierOption::create([
            'modifier_group_id' => $milkGroup->id,
            'name' => 'Oat Milk',
            'price_adjustment' => 0.200,
        ]);
        ModifierOption::create([
            'modifier_group_id' => $milkGroup->id,
            'name' => 'Almond Milk',
            'price_adjustment' => 0.200,
        ]);

        // Extras (for food)
        $extrasGroup = ModifierGroup::create([
            'shop_id' => $shop->id,
            'name' => 'Extras',
            'min_selection' => 0,
            'max_selection' => 2,
        ]);

        ModifierOption::create([
            'modifier_group_id' => $extrasGroup->id,
            'name' => 'Extra Cheese',
            'price_adjustment' => 0.300,
        ]);
        ModifierOption::create([
            'modifier_group_id' => $extrasGroup->id,
            'name' => 'Extra Sauce',
            'price_adjustment' => 0,
        ]);

        // ── Attach modifiers to products ────────────────────────
        // All drinks get Size modifier
        $drinkNames = collect($hotDrinkProducts)->pluck('name')
            ->merge(collect($coldDrinkProducts)->pluck('name'));

        $drinkProducts = $createdProducts->filter(fn ($p) => $drinkNames->contains($p->name));
        foreach ($drinkProducts as $product) {
            $product->modifierGroups()->attach($sizeGroup->id);
        }

        // Coffee-based drinks get Milk Type modifier
        $coffeeNames = ['Cappuccino', 'Latte', 'Iced Latte', 'Hot Chocolate'];
        $coffeeProducts = $createdProducts->filter(fn ($p) => in_array($p->name, $coffeeNames));
        foreach ($coffeeProducts as $product) {
            $product->modifierGroups()->attach($milkGroup->id);
        }

        // Food items get Extras modifier
        $foodItems = $createdProducts->filter(fn ($p) => $p->category_id === $food->id);
        foreach ($foodItems as $product) {
            $product->modifierGroups()->attach($extrasGroup->id);
        }
    }

    /**
     * Run the seeder (standard Artisan interface).
     * Seeds the first shop found, or creates a demo one.
     */
    public function run(): void
    {
        $shop = Shop::first();

        if (! $shop) {
            $shop = Shop::create([
                'name' => 'Bite Demo Coffee',
                'slug' => 'demo',
                'currency_code' => 'OMR',
                'currency_symbol' => "\xD8\xB1.\xD8\xB9.",
                'currency_decimals' => 3,
            ]);
        }

        $this->seedForShop($shop);
    }
}
