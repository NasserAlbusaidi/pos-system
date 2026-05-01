<?php

namespace Tests\Feature;

use App\Livewire\GuestMenu;
use App\Models\Category;
use App\Models\LoyaltyCustomer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Shop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use ReflectionClass;
use Tests\TestCase;

class GuestMenuPrivacyTest extends TestCase
{
    use RefreshDatabase;

    public function test_recognize_customer_does_not_expose_order_history(): void
    {
        [$shop] = $this->createMenu();
        $this->createCustomer($shop);
        $order = Order::forceCreate([
            'shop_id' => $shop->id,
            'loyalty_phone' => '99887766',
            'status' => 'completed',
            'subtotal_amount' => 7.500,
            'tax_amount' => 0,
            'total_amount' => 7.500,
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'product_name_snapshot_en' => 'Private Anniversary Cake',
            'price_snapshot' => 7.500,
            'quantity' => 1,
        ]);

        $component = Livewire::test(GuestMenu::class, ['shop' => $shop])
            ->set('loyaltyPhone', '99887766')
            ->call('recognizeCustomer');

        $component
            ->assertSet('recognizedCustomer', ['name' => 'Mohammed', 'points' => 120])
            ->assertDontSee('Private Anniversary Cake')
            ->assertDontSee(__('guest.recent_orders'));

        $this->assertFalse((new ReflectionClass(GuestMenu::class))->hasProperty('customerOrderHistory'));
    }

    public function test_recognize_customer_does_not_expose_favorites(): void
    {
        [$shop, $product] = $this->createMenu();
        $this->createCustomer($shop, [
            [
                'id' => $product->id,
                'name' => 'Secret Wedding Order',
                'quantity' => 2,
                'selectedModifiers' => [],
            ],
        ]);

        $component = Livewire::test(GuestMenu::class, ['shop' => $shop])
            ->set('loyaltyPhone', '99887766')
            ->call('recognizeCustomer');

        $recognizedCustomer = $component->get('recognizedCustomer');

        $this->assertSame(['name' => 'Mohammed', 'points' => 120], $recognizedCustomer);
        $this->assertArrayNotHasKey('favorites', $recognizedCustomer);
        $this->assertStringNotContainsString('Secret Wedding Order', json_encode($recognizedCustomer));
    }

    public function test_recognize_customer_returns_only_first_name_token(): void
    {
        [$shop] = $this->createMenu();
        $this->createCustomer($shop);

        $recognizedCustomer = Livewire::test(GuestMenu::class, ['shop' => $shop])
            ->set('loyaltyPhone', '99887766')
            ->call('recognizeCustomer')
            ->get('recognizedCustomer');

        $this->assertSame('Mohammed', $recognizedCustomer['name']);
        $this->assertSame(120, $recognizedCustomer['points']);
        $this->assertArrayNotHasKey('visit_count', $recognizedCustomer);
    }

    public function test_order_usual_still_loads_favorites_into_cart_without_exposing_them(): void
    {
        [$shop, $product] = $this->createMenu();
        $this->createCustomer($shop, [
            [
                'id' => $product->id,
                'name' => 'Secret Wedding Order',
                'quantity' => 2,
                'selectedModifiers' => [],
            ],
        ]);

        Livewire::test(GuestMenu::class, ['shop' => $shop])
            ->set('loyaltyPhone', '99887766')
            ->call('recognizeCustomer')
            ->assertSet('recognizedCustomer', ['name' => 'Mohammed', 'points' => 120])
            ->call('orderUsual')
            ->assertSet('recognizedCustomer', ['name' => 'Mohammed', 'points' => 120])
            ->assertSet('cart', [
                $product->id.'-plain' => [
                    'id' => $product->id,
                    'name' => 'Latte',
                    'price' => 4.5,
                    'quantity' => 2,
                    'selectedModifiers' => [],
                    'modifierNames' => [],
                ],
            ]);
    }

    /**
     * @return array{0: Shop, 1: Product}
     */
    private function createMenu(): array
    {
        $shop = Shop::create(['name' => 'Bite', 'slug' => 'bite']);
        $category = Category::create(['shop_id' => $shop->id, 'name_en' => 'Coffee']);
        $product = Product::forceCreate([
            'shop_id' => $shop->id,
            'category_id' => $category->id,
            'name_en' => 'Latte',
            'price' => 4.500,
            'is_available' => true,
            'is_visible' => true,
        ]);

        return [$shop, $product];
    }

    private function createCustomer(Shop $shop, array $favorites = []): LoyaltyCustomer
    {
        return LoyaltyCustomer::create([
            'shop_id' => $shop->id,
            'phone' => '99887766',
            'name' => 'Mohammed Al-Rawahi',
            'points' => 120,
            'visit_count' => 9,
            'favorites' => $favorites,
        ]);
    }
}
