<?php

namespace Tests\Feature;

use App\Livewire\KitchenDisplay;
use App\Models\Category;
use App\Models\ModifierGroup;
use App\Models\ModifierOption;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemModifier;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class KitchenDisplayModifiersTest extends TestCase
{
    use RefreshDatabase;

    public function test_modifiers_appear_in_render(): void
    {
        [$shop, $user] = $this->makeKitchenUser();
        $item = $this->createPaidOrderItem($shop, [
            'product_name_snapshot_en' => 'Falafel Wrap',
            'quantity' => 2,
        ]);

        $this->createModifier($item, 'No Onions');
        $this->createModifier($item, 'Extra Cheese');

        Livewire::actingAs($user)
            ->test(KitchenDisplay::class)
            ->assertSee('Falafel Wrap')
            ->assertSee('2x')
            ->assertSee('No Onions')
            ->assertSee('Extra Cheese');
    }

    public function test_no_modifiers_does_not_break_render(): void
    {
        [$shop, $user] = $this->makeKitchenUser();

        $this->createPaidOrderItem($shop, [
            'product_name_snapshot_en' => 'Plain Burger',
        ]);

        Livewire::actingAs($user)
            ->test(KitchenDisplay::class)
            ->assertSee('Plain Burger')
            ->assertDontSee('No Onions')
            ->assertDontSeeHtml('space-y-0.5 pl-1');
    }

    public function test_uses_snapshot_not_live_data(): void
    {
        [$shop, $user] = $this->makeKitchenUser();
        [$product, $option] = $this->createProductWithModifierOption($shop, 'Oat Milk');

        $item = $this->createPaidOrderItem($shop, [
            'product_id' => $product->id,
            'product_name_snapshot_en' => 'Latte',
        ]);
        $this->createModifier($item, 'Oat Milk');

        $option->update(['name_en' => 'Soy Milk']);

        Livewire::actingAs($user)
            ->test(KitchenDisplay::class)
            ->assertSee('Latte')
            ->assertSee('Oat Milk')
            ->assertDontSee('Soy Milk');
    }

    public function test_no_n_plus_one_with_many_orders(): void
    {
        [$shop, $user] = $this->makeKitchenUser();

        for ($i = 0; $i < 25; $i++) {
            $item = $this->createPaidOrderItem($shop, [
                'product_name_snapshot_en' => 'Ticket Item '.$i,
            ]);

            $this->createModifier($item, 'Modifier '.$i);
        }

        DB::flushQueryLog();
        DB::enableQueryLog();

        Livewire::actingAs($user)
            ->test(KitchenDisplay::class)
            ->assertSee('Ticket Item 0')
            ->assertSee('Modifier 24');

        $queries = collect(DB::getQueryLog())->pluck('query');
        DB::disableQueryLog();

        $orderItemQueries = $queries
            ->filter(fn (string $query): bool => str_contains($query, 'order_items'))
            ->values();
        $modifierQueries = $queries
            ->filter(fn (string $query): bool => str_contains($query, 'order_item_modifiers'))
            ->values();

        $this->assertCount(1, $orderItemQueries, $orderItemQueries->implode(PHP_EOL));
        $this->assertCount(1, $modifierQueries, $modifierQueries->implode(PHP_EOL));
    }

    private function makeKitchenUser(): array
    {
        $shop = Shop::factory()->create();
        $user = User::factory()->create([
            'shop_id' => $shop->id,
            'role' => 'kitchen',
        ]);

        return [$shop, $user];
    }

    private function createPaidOrderItem(Shop $shop, array $attributes = []): OrderItem
    {
        $order = Order::forceCreate([
            'shop_id' => $shop->id,
            'status' => 'paid',
            'total_amount' => 15.00,
            'paid_at' => now(),
        ]);

        return OrderItem::create(array_merge([
            'order_id' => $order->id,
            'product_id' => null,
            'product_name_snapshot_en' => 'Kitchen Item',
            'product_name_snapshot_ar' => null,
            'price_snapshot' => 5.00,
            'quantity' => 1,
        ], $attributes));
    }

    private function createModifier(OrderItem $item, string $name): OrderItemModifier
    {
        return OrderItemModifier::create([
            'order_item_id' => $item->id,
            'modifier_option_name_snapshot_en' => $name,
            'modifier_option_name_snapshot_ar' => null,
            'price_adjustment_snapshot' => 0.50,
        ]);
    }

    private function createProductWithModifierOption(Shop $shop, string $optionName): array
    {
        $category = Category::create([
            'shop_id' => $shop->id,
            'name_en' => 'Drinks',
            'name_ar' => null,
        ]);

        $product = Product::forceCreate([
            'shop_id' => $shop->id,
            'category_id' => $category->id,
            'name_en' => 'Latte',
            'name_ar' => null,
            'description_en' => null,
            'description_ar' => null,
            'price' => 3.00,
            'is_available' => true,
        ]);

        $group = ModifierGroup::create([
            'shop_id' => $shop->id,
            'name_en' => 'Milk',
            'name_ar' => null,
            'min_selection' => 0,
            'max_selection' => 1,
        ]);

        $option = ModifierOption::create([
            'modifier_group_id' => $group->id,
            'name_en' => $optionName,
            'name_ar' => null,
            'price_adjustment' => 0.50,
        ]);

        $product->modifierGroups()->attach($group->id);

        return [$product, $option];
    }
}
