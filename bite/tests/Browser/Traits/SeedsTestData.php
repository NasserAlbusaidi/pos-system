<?php

namespace Tests\Browser\Traits;

use App\Models\Category;
use App\Models\ModifierGroup;
use App\Models\ModifierOption;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

trait SeedsTestData
{
    protected function createShopWithAdmin(array $shopOverrides = []): array
    {
        $shop = Shop::factory()->create(array_merge([
            'slug' => 'test-shop-'.uniqid(),
            'tax_rate' => 0,
            'branding' => [
                'accent' => '#cc5500',
                'paper' => '#fdfcf8',
                'ink' => '#1a1918',
                'onboarding_completed' => true,
            ],
        ], $shopOverrides));

        $admin = User::factory()->create([
            'shop_id' => $shop->id,
            'role' => 'admin',
            'email' => 'admin-'.uniqid().'@test.com',
            'password' => Hash::make('password'),
            'pin_code' => Hash::make('1234'),
        ]);

        return [$shop, $admin];
    }

    protected function createStaffUser(Shop $shop, string $role = 'server', string $pin = '5678'): User
    {
        return User::factory()->create([
            'shop_id' => $shop->id,
            'role' => $role,
            'email' => $role.'-'.uniqid().'@test.com',
            'password' => Hash::make('password'),
            'pin_code' => Hash::make($pin),
        ]);
    }

    protected function createSuperAdmin(): User
    {
        $shop = Shop::factory()->create(['slug' => 'super-shop-'.uniqid()]);

        return User::factory()->superAdmin()->create([
            'shop_id' => $shop->id,
            'role' => 'admin',
            'email' => 'super-'.uniqid().'@test.com',
            'password' => Hash::make('password'),
        ]);
    }

    protected function createProductWithCategory(Shop $shop, array $productOverrides = []): array
    {
        $category = Category::factory()->create([
            'shop_id' => $shop->id,
            'name_en' => 'Test Category',
            'name_ar' => 'فئة اختبار',
            'is_active' => true,
        ]);

        $product = Product::factory()->create(array_merge([
            'shop_id' => $shop->id,
            'category_id' => $category->id,
            'name_en' => 'Test Coffee',
            'name_ar' => 'قهوة اختبار',
            'price' => 2.500,
            'is_available' => true,
            'is_visible' => true,
        ], $productOverrides));

        return [$category, $product];
    }

    protected function createModifierGroup(Shop $shop, Product $product, bool $required = false): array
    {
        $group = ModifierGroup::create([
            'shop_id' => $shop->id,
            'name_en' => 'Size',
            'name_ar' => 'الحجم',
            'min_selection' => $required ? 1 : 0,
            'max_selection' => 1,
        ]);

        $option = ModifierOption::create([
            'modifier_group_id' => $group->id,
            'name_en' => 'Large',
            'name_ar' => 'كبير',
            'price_adjustment' => 1.000,
        ]);

        $product->modifierGroups()->attach($group->id);

        return [$group, $option];
    }

    protected function createPaidOrder(Shop $shop, Product $product, int $quantity = 1): Order
    {
        $total = $product->price * $quantity;

        $order = Order::forceCreate([
            'shop_id' => $shop->id,
            'status' => 'paid',
            'paid_at' => now(),
            'total_amount' => $total,
            'subtotal_amount' => $total,
            'tax_amount' => 0,
            'payment_method' => 'cash',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name_snapshot_en' => $product->name_en,
            'product_name_snapshot_ar' => $product->name_ar ?? $product->name_en,
            'quantity' => $quantity,
            'price_snapshot' => $product->price,
        ]);

        return $order;
    }

    protected function createUnpaidOrder(Shop $shop, Product $product, int $quantity = 1): Order
    {
        $total = $product->price * $quantity;

        $order = Order::forceCreate([
            'shop_id' => $shop->id,
            'status' => 'unpaid',
            'total_amount' => $total,
            'subtotal_amount' => $total,
            'tax_amount' => 0,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name_snapshot_en' => $product->name_en,
            'product_name_snapshot_ar' => $product->name_ar ?? $product->name_en,
            'quantity' => $quantity,
            'price_snapshot' => $product->price,
        ]);

        return $order;
    }
}
