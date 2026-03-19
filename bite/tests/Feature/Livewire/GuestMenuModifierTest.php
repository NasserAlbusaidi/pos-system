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

    private function createShopWithModifierProduct(): array
    {
        $shop = Shop::create(['name' => 'Bite', 'slug' => 'bite']);
        $category = Category::create(['shop_id' => $shop->id, 'name_en' => 'Drinks']);

        $product = Product::forceCreate([
            'shop_id' => $shop->id,
            'category_id' => $category->id,
            'name_en' => 'Karak',
            'price' => 1.000,
            'is_available' => true,
            'is_visible' => true,
        ]);

        // Size group: radio (single-select, required)
        $sizeGroup = ModifierGroup::create([
            'shop_id' => $shop->id,
            'name_en' => 'Size',
            'min_selection' => 1,
            'max_selection' => 1,
        ]);
        $small = ModifierOption::create([
            'modifier_group_id' => $sizeGroup->id,
            'name_en' => 'Small',
            'price_adjustment' => 0,
        ]);
        $medium = ModifierOption::create([
            'modifier_group_id' => $sizeGroup->id,
            'name_en' => 'Medium',
            'price_adjustment' => 0.200,
        ]);

        // Extras group: checkbox (multi-select, optional)
        $extrasGroup = ModifierGroup::create([
            'shop_id' => $shop->id,
            'name_en' => 'Extras',
            'min_selection' => 0,
            'max_selection' => 3,
        ]);
        $extraSpice = ModifierOption::create([
            'modifier_group_id' => $extrasGroup->id,
            'name_en' => 'Extra Spice',
            'price_adjustment' => 0.100,
        ]);

        $product->modifierGroups()->attach([$sizeGroup->id, $extrasGroup->id]);

        return compact('shop', 'product', 'sizeGroup', 'extrasGroup', 'small', 'medium', 'extraSpice');
    }

    public function test_guest_can_submit_order_with_modifiers(): void
    {
        $shop = Shop::create(['name' => 'Bite', 'slug' => 'bite']);
        $category = Category::create(['shop_id' => $shop->id, 'name_en' => 'Coffee']);
        $product = Product::forceCreate([
            'shop_id' => $shop->id,
            'category_id' => $category->id,
            'name_en' => 'Latte',
            'price' => 10.00,
        ]);

        $group = ModifierGroup::create(['shop_id' => $shop->id, 'name_en' => 'Milk']);
        $option = ModifierOption::create([
            'modifier_group_id' => $group->id,
            'name_en' => 'Oat Milk',
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
            'modifier_option_name_snapshot_en' => 'Oat Milk',
            'price_adjustment_snapshot' => 2.00,
        ]);
    }

    public function test_selecting_radio_modifier_preserves_selection_when_checkbox_is_added(): void
    {
        $data = $this->createShopWithModifierProduct();

        $component = Livewire::test(GuestMenu::class, ['shop' => $data['shop']])
            ->call('addToCart', $data['product']->id); // Opens modifier modal

        // Verify modal opened
        $component->assertSet('showModifierModal', true);

        // Select Medium size (radio - single-select group)
        $component->call('selectModifier', $data['sizeGroup']->id, $data['medium']->id, false);

        // Verify Medium is selected
        $selectedModifiers = $component->get('selectedModifiers');
        $this->assertEquals(
            (string) $data['medium']->id,
            $selectedModifiers[$data['sizeGroup']->id] ?? null,
            'Medium size should be selected'
        );

        // Now select Extra Spice (checkbox - multi-select group)
        $component->call('selectModifier', $data['extrasGroup']->id, $data['extraSpice']->id, true);

        // Verify BOTH selections are preserved
        $selectedModifiers = $component->get('selectedModifiers');
        $this->assertEquals(
            (string) $data['medium']->id,
            $selectedModifiers[$data['sizeGroup']->id] ?? null,
            'Medium size should STILL be selected after adding extra'
        );
        $this->assertContains(
            (string) $data['extraSpice']->id,
            (array) ($selectedModifiers[$data['extrasGroup']->id] ?? []),
            'Extra Spice should be selected'
        );

        // Submit (second addToCart call)
        $component->call('addToCart', $data['product']->id);

        // Verify cart item has correct price: base (1.000) + medium (0.200) + extra spice (0.100) = 1.300
        $cart = $component->get('cart');
        $this->assertNotEmpty($cart, 'Cart should not be empty after adding item');

        $cartItem = collect($cart)->first();
        $expectedPrice = 1.000 + 0.200 + 0.100;
        $this->assertEquals($expectedPrice, $cartItem['price'], 'Cart item price should include both modifier adjustments');
        $this->assertCount(2, $cartItem['selectedModifiers'], 'Cart item should have 2 modifiers (Medium + Extra Spice)');
    }

    public function test_selecting_radio_modifier_replaces_previous_selection_in_same_group(): void
    {
        $data = $this->createShopWithModifierProduct();

        $component = Livewire::test(GuestMenu::class, ['shop' => $data['shop']])
            ->call('addToCart', $data['product']->id); // Opens modal

        // Select Small first
        $component->call('selectModifier', $data['sizeGroup']->id, $data['small']->id, false);
        $selectedModifiers = $component->get('selectedModifiers');
        $this->assertEquals(
            (string) $data['small']->id,
            $selectedModifiers[$data['sizeGroup']->id]
        );

        // Now select Medium - should replace Small
        $component->call('selectModifier', $data['sizeGroup']->id, $data['medium']->id, false);
        $selectedModifiers = $component->get('selectedModifiers');
        $this->assertEquals(
            (string) $data['medium']->id,
            $selectedModifiers[$data['sizeGroup']->id],
            'Medium should replace Small in single-select group'
        );

        // Submit
        $component->call('addToCart', $data['product']->id);

        $cart = $component->get('cart');
        $cartItem = collect($cart)->first();
        $expectedPrice = 1.000 + 0.200; // base + medium
        $this->assertEquals($expectedPrice, $cartItem['price']);
        $this->assertCount(1, $cartItem['selectedModifiers'], 'Should only have Medium modifier, not Small');
    }

    public function test_checkbox_modifier_toggles_on_and_off(): void
    {
        $data = $this->createShopWithModifierProduct();

        $component = Livewire::test(GuestMenu::class, ['shop' => $data['shop']])
            ->call('addToCart', $data['product']->id); // Opens modal

        // Select Small (required)
        $component->call('selectModifier', $data['sizeGroup']->id, $data['small']->id, false);

        // Add Extra Spice
        $component->call('selectModifier', $data['extrasGroup']->id, $data['extraSpice']->id, true);
        $selectedModifiers = $component->get('selectedModifiers');
        $this->assertContains(
            (string) $data['extraSpice']->id,
            (array) ($selectedModifiers[$data['extrasGroup']->id] ?? [])
        );

        // Remove Extra Spice (toggle off)
        $component->call('selectModifier', $data['extrasGroup']->id, $data['extraSpice']->id, true);
        $selectedModifiers = $component->get('selectedModifiers');
        $this->assertNotContains(
            (string) $data['extraSpice']->id,
            (array) ($selectedModifiers[$data['extrasGroup']->id] ?? []),
            'Extra Spice should be removed after toggling off'
        );

        // Submit
        $component->call('addToCart', $data['product']->id);

        $cart = $component->get('cart');
        $cartItem = collect($cart)->first();
        $expectedPrice = 1.000; // base + small (0)
        $this->assertEquals($expectedPrice, $cartItem['price']);
        $this->assertCount(1, $cartItem['selectedModifiers'], 'Should only have Small modifier');
    }

    public function test_modifier_price_computed_property_reflects_selections(): void
    {
        $data = $this->createShopWithModifierProduct();

        $component = Livewire::test(GuestMenu::class, ['shop' => $data['shop']])
            ->call('addToCart', $data['product']->id); // Opens modal

        // Initially: base price only
        $this->assertEquals(1.000, $component->get('customizingProductPrice'));

        // Select Medium
        $component->call('selectModifier', $data['sizeGroup']->id, $data['medium']->id, false);
        $this->assertEquals(1.200, $component->get('customizingProductPrice'));

        // Add Extra Spice
        $component->call('selectModifier', $data['extrasGroup']->id, $data['extraSpice']->id, true);
        $this->assertEquals(1.300, $component->get('customizingProductPrice'));
    }

    public function test_order_total_correct_with_multi_group_modifiers(): void
    {
        $data = $this->createShopWithModifierProduct();

        $component = Livewire::test(GuestMenu::class, ['shop' => $data['shop']])
            ->call('addToCart', $data['product']->id) // Opens modal
            ->call('selectModifier', $data['sizeGroup']->id, $data['medium']->id, false)
            ->call('selectModifier', $data['extrasGroup']->id, $data['extraSpice']->id, true)
            ->call('addToCart', $data['product']->id); // Submit to cart

        // Now submit the order
        $component->call('submitOrder');

        // Order total: 1.000 + 0.200 + 0.100 = 1.300
        $this->assertDatabaseHas('orders', [
            'shop_id' => $data['shop']->id,
            'total_amount' => 1.30,
        ]);

        // Both modifiers should be snapshotted
        $this->assertDatabaseHas('order_item_modifiers', [
            'modifier_option_name_snapshot_en' => 'Medium',
            'price_adjustment_snapshot' => 0.200,
        ]);
        $this->assertDatabaseHas('order_item_modifiers', [
            'modifier_option_name_snapshot_en' => 'Extra Spice',
            'price_adjustment_snapshot' => 0.100,
        ]);
    }
}
