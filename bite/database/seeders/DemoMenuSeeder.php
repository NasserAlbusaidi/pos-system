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
            'name_en' => 'Hot Drinks',
            'name_ar' => 'مشروبات ساخنة',
            'sort_order' => 1,
        ]);

        $coldDrinks = Category::create([
            'shop_id' => $shop->id,
            'name_en' => 'Cold Drinks',
            'name_ar' => 'مشروبات باردة',
            'sort_order' => 2,
        ]);

        $food = Category::create([
            'shop_id' => $shop->id,
            'name_en' => 'Food',
            'name_ar' => 'طعام',
            'sort_order' => 3,
        ]);

        $desserts = Category::create([
            'shop_id' => $shop->id,
            'name_en' => 'Desserts',
            'name_ar' => 'حلويات',
            'sort_order' => 4,
        ]);

        // ── Products ────────────────────────────────────────────
        $hotDrinkProducts = [
            ['name_en' => 'Karak Tea', 'name_ar' => 'شاي كرك', 'description_en' => 'Traditional spiced milk tea', 'description_ar' => 'شاي حليب بالتوابل التقليدية', 'price' => 0.500],
            ['name_en' => 'Turkish Coffee', 'name_ar' => 'قهوة تركية', 'description_en' => 'Strong finely ground coffee', 'description_ar' => 'قهوة مطحونة ناعمة وقوية', 'price' => 0.800],
            ['name_en' => 'Cappuccino', 'name_ar' => 'كابتشينو', 'description_en' => 'Espresso with steamed milk foam', 'description_ar' => 'إسبريسو مع رغوة الحليب', 'price' => 1.200],
            ['name_en' => 'Latte', 'name_ar' => 'لاتيه', 'description_en' => 'Espresso with smooth steamed milk', 'description_ar' => 'إسبريسو مع حليب مبخر ناعم', 'price' => 1.500],
            ['name_en' => 'Hot Chocolate', 'name_ar' => 'شوكولاتة ساخنة', 'description_en' => 'Rich creamy hot chocolate', 'description_ar' => 'شوكولاتة ساخنة غنية وكريمية', 'price' => 1.200],
        ];

        $coldDrinkProducts = [
            ['name_en' => 'Iced Latte', 'name_ar' => 'لاتيه مثلج', 'description_en' => 'Chilled espresso with cold milk', 'description_ar' => 'إسبريسو مبرد مع حليب بارد', 'price' => 1.800],
            ['name_en' => 'Fresh Juice', 'name_ar' => 'عصير طازج', 'description_en' => 'Freshly squeezed seasonal fruit', 'description_ar' => 'فواكه موسمية طازجة معصورة', 'price' => 1.500],
            ['name_en' => 'Smoothie', 'name_ar' => 'سموذي', 'description_en' => 'Blended fruit and yogurt', 'description_ar' => 'فواكه مخلوطة مع الزبادي', 'price' => 2.000],
            ['name_en' => 'Iced Tea', 'name_ar' => 'شاي مثلج', 'description_en' => 'Refreshing cold brewed tea', 'description_ar' => 'شاي بارد منعش', 'price' => 1.000],
        ];

        $foodProducts = [
            ['name_en' => 'Club Sandwich', 'name_ar' => 'كلوب ساندويتش', 'description_en' => 'Triple-decker with chicken and veggies', 'description_ar' => 'ساندويتش ثلاثي مع الدجاج والخضروات', 'price' => 2.500],
            ['name_en' => 'Chicken Shawarma', 'name_ar' => 'شاورما دجاج', 'description_en' => 'Grilled marinated chicken in flatbread', 'description_ar' => 'دجاج مشوي متبل في خبز مسطح', 'price' => 1.500],
            ['name_en' => 'Beef Burger', 'name_ar' => 'برجر لحم', 'description_en' => 'Juicy beef patty with fresh toppings', 'description_ar' => 'قطعة لحم بقري طازجة مع إضافات', 'price' => 3.000],
            ['name_en' => 'Caesar Salad', 'name_ar' => 'سلطة سيزر', 'description_en' => 'Crisp romaine with caesar dressing', 'description_ar' => 'خس روماني مع صلصة سيزر', 'price' => 2.200],
            ['name_en' => 'French Fries', 'name_ar' => 'بطاطس مقلية', 'description_en' => 'Golden crispy fries', 'description_ar' => 'بطاطس مقلية ذهبية ومقرمشة', 'price' => 1.000],
        ];

        $dessertProducts = [
            ['name_en' => 'Kunafa', 'name_ar' => 'كنافة', 'description_en' => 'Sweet cheese pastry with syrup', 'description_ar' => 'معجنات جبن حلوة مع شراب', 'price' => 1.800],
            ['name_en' => 'Cheesecake', 'name_ar' => 'تشيز كيك', 'description_en' => 'Classic New York style cheesecake', 'description_ar' => 'تشيز كيك كلاسيكي على طريقة نيويورك', 'price' => 2.000],
            ['name_en' => 'Luqaimat', 'name_ar' => 'لقيمات', 'description_en' => 'Sweet fried dumplings with date syrup', 'description_ar' => 'عجين مقلي حلو مع دبس التمر', 'price' => 1.200],
            ['name_en' => 'Ice Cream', 'name_ar' => 'آيس كريم', 'description_en' => 'Two scoops of premium ice cream', 'description_ar' => 'كرتين من الآيس كريم الفاخر', 'price' => 1.500],
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
            'name_en' => 'Size',
            'name_ar' => 'الحجم',
            'min_selection' => 1,
            'max_selection' => 1,
        ]);

        ModifierOption::create([
            'modifier_group_id' => $sizeGroup->id,
            'name_en' => 'Small',
            'name_ar' => 'صغير',
            'price_adjustment' => -0.300,
        ]);
        ModifierOption::create([
            'modifier_group_id' => $sizeGroup->id,
            'name_en' => 'Regular',
            'name_ar' => 'عادي',
            'price_adjustment' => 0,
        ]);
        ModifierOption::create([
            'modifier_group_id' => $sizeGroup->id,
            'name_en' => 'Large',
            'name_ar' => 'كبير',
            'price_adjustment' => 0.300,
        ]);

        // Milk Type (for coffee)
        $milkGroup = ModifierGroup::create([
            'shop_id' => $shop->id,
            'name_en' => 'Milk Type',
            'name_ar' => 'نوع الحليب',
            'min_selection' => 0,
            'max_selection' => 1,
        ]);

        ModifierOption::create([
            'modifier_group_id' => $milkGroup->id,
            'name_en' => 'Regular Milk',
            'name_ar' => 'حليب عادي',
            'price_adjustment' => 0,
        ]);
        ModifierOption::create([
            'modifier_group_id' => $milkGroup->id,
            'name_en' => 'Oat Milk',
            'name_ar' => 'حليب الشوفان',
            'price_adjustment' => 0.200,
        ]);
        ModifierOption::create([
            'modifier_group_id' => $milkGroup->id,
            'name_en' => 'Almond Milk',
            'name_ar' => 'حليب اللوز',
            'price_adjustment' => 0.200,
        ]);

        // Extras (for food)
        $extrasGroup = ModifierGroup::create([
            'shop_id' => $shop->id,
            'name_en' => 'Extras',
            'name_ar' => 'إضافات',
            'min_selection' => 0,
            'max_selection' => 2,
        ]);

        ModifierOption::create([
            'modifier_group_id' => $extrasGroup->id,
            'name_en' => 'Extra Cheese',
            'name_ar' => 'جبن إضافي',
            'price_adjustment' => 0.300,
        ]);
        ModifierOption::create([
            'modifier_group_id' => $extrasGroup->id,
            'name_en' => 'Extra Sauce',
            'name_ar' => 'صلصة إضافية',
            'price_adjustment' => 0,
        ]);

        // ── Attach modifiers to products ────────────────────────
        // All drinks get Size modifier
        $drinkNames = collect($hotDrinkProducts)->pluck('name_en')
            ->merge(collect($coldDrinkProducts)->pluck('name_en'));

        $drinkProducts = $createdProducts->filter(fn ($p) => $drinkNames->contains($p->name_en));
        foreach ($drinkProducts as $product) {
            $product->modifierGroups()->attach($sizeGroup->id);
        }

        // Coffee-based drinks get Milk Type modifier
        $coffeeNames = ['Cappuccino', 'Latte', 'Iced Latte', 'Hot Chocolate'];
        $coffeeProducts = $createdProducts->filter(fn ($p) => in_array($p->name_en, $coffeeNames));
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
