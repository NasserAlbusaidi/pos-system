<?php

namespace Tests\Feature\Livewire;

use App\Livewire\GuestMenu;
use App\Models\Category;
use App\Models\ModifierGroup;
use App\Models\ModifierOption;
use App\Models\Product;
use App\Models\Shop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class GuestMenuModifierTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_submit_order_with_modifiers(): void
    {
        $shop = Shop::create(['name' => 'Bite', 'slug' => 'bite']);
        $category = Category::create(['shop_id' => $shop->id, 'name' => 'Coffee']);
        $product = Product::create([
            'shop_id' => $shop->id,
            'category_id' => $category->id,
            'name' => 'Latte',
            'price' => 10.00,
        ]);

        $group = ModifierGroup::create(['shop_id' => $shop->id, 'name' => 'Milk']);
        $option = ModifierOption::create([
            'modifier_group_id' => $group->id,
            'name' => 'Oat Milk',
            'price_adjustment' => 2.00,
        ]);

        $product->modifierGroups()->attach($group->id);

        // Simulate cart with modifier
        Livewire::test(GuestMenu::class, ['shop' => $shop])
            ->set('cart', [
                'some-unique-key' => [
                    'id' => $product->id,
                    'name' => 'Latte',
                    'price' => 10.00,
                    'quantity' => 1,
                    'selectedModifiers' => [$option->id],
                ],
            ])
            ->call('submitOrder');

        // Order total should be 12.00 (10 + 2)
        $this->assertDatabaseHas('orders', [
            'total_amount' => 12.00,
        ]);

        // Modifier snapshot should exist
        $this->assertDatabaseHas('order_item_modifiers', [
            'modifier_option_name_snapshot' => 'Oat Milk',
            'price_adjustment_snapshot' => 2.00,
        ]);
    }
}
