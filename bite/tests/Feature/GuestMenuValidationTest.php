<?php

namespace Tests\Feature;

use App\Livewire\GuestMenu;
use App\Models\Category;
use App\Models\ModifierGroup;
use App\Models\ModifierOption;
use App\Models\Product;
use App\Models\Shop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class GuestMenuValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_cannot_add_hidden_product_to_cart(): void
    {
        [$shop, $category] = $this->createMenu();
        $product = $this->createProduct($shop, $category, [
            'name_en' => 'Staff Meal',
            'is_available' => true,
            'is_visible' => false,
        ]);

        Livewire::test(GuestMenu::class, ['shop' => $shop])
            ->call('addToCart', $product->id)
            ->assertSet('cart', [])
            ->assertSet('modifierError', null)
            ->assertSet('orderError', null);
    }

    public function test_cannot_add_unavailable_product_to_cart(): void
    {
        [$shop, $category] = $this->createMenu();
        $product = $this->createProduct($shop, $category, [
            'name_en' => 'Sold Out Roll',
            'is_available' => false,
            'is_visible' => true,
        ]);

        Livewire::test(GuestMenu::class, ['shop' => $shop])
            ->call('addToCart', $product->id)
            ->assertSet('cart', []);
    }

    public function test_visible_unavailable_product_still_appears_in_menu_render(): void
    {
        [$shop, $category] = $this->createMenu();
        $this->createProduct($shop, $category, [
            'name_en' => 'Sold Out Roll',
            'is_available' => false,
            'is_visible' => true,
        ]);

        Livewire::test(GuestMenu::class, ['shop' => $shop])
            ->assertSee('Sold Out Roll')
            ->assertSee(__('guest.sold_out'));
    }

    public function test_max_selection_enforced(): void
    {
        $fixture = $this->createProductWithModifierGroup([
            'min_selection' => 0,
            'max_selection' => 1,
        ]);

        $component = Livewire::test(GuestMenu::class, ['shop' => $fixture['shop']])
            ->call('addToCart', $fixture['product']->id)
            ->set('selectedModifiers', [
                $fixture['group']->id => [
                    (string) $fixture['optionA']->id,
                    (string) $fixture['optionB']->id,
                ],
            ])
            ->call('addToCart', $fixture['product']->id)
            ->assertSet('cart', [])
            ->assertSet('showModifierModal', true);

        $this->assertSame(
            __('guest.select_at_most', ['count' => 1, 'group' => 'Size']),
            $component->get('modifierError')
        );
    }

    public function test_invalid_modifier_option_id_rejected(): void
    {
        $fixture = $this->createProductWithModifierGroup();

        $component = Livewire::test(GuestMenu::class, ['shop' => $fixture['shop']])
            ->call('addToCart', $fixture['product']->id)
            ->set('selectedModifiers', [
                $fixture['group']->id => ['999999'],
            ])
            ->call('addToCart', $fixture['product']->id)
            ->assertSet('cart', [])
            ->assertSet('showModifierModal', true);

        $this->assertSame(__('guest.invalid_modifier_selection'), $component->get('modifierError'));
    }

    public function test_modifier_option_from_other_group_rejected(): void
    {
        $fixture = $this->createProductWithModifierGroup();
        $otherGroup = ModifierGroup::create([
            'shop_id' => $fixture['shop']->id,
            'name_en' => 'Sauce',
            'min_selection' => 0,
            'max_selection' => 1,
        ]);
        $otherOption = ModifierOption::create([
            'modifier_group_id' => $otherGroup->id,
            'name_en' => 'Garlic',
            'price_adjustment' => 0.100,
        ]);

        $component = Livewire::test(GuestMenu::class, ['shop' => $fixture['shop']])
            ->call('addToCart', $fixture['product']->id)
            ->set('selectedModifiers', [
                $fixture['group']->id => [(string) $otherOption->id],
            ])
            ->call('addToCart', $fixture['product']->id)
            ->assertSet('cart', [])
            ->assertSet('showModifierModal', true);

        $this->assertSame(__('guest.invalid_modifier_selection'), $component->get('modifierError'));
    }

    public function test_submit_order_revalidates_against_orderable_scope(): void
    {
        [$shop, $category] = $this->createMenu();
        $product = $this->createProduct($shop, $category, [
            'name_en' => 'Karak',
            'is_available' => true,
            'is_visible' => true,
        ]);

        $component = Livewire::test(GuestMenu::class, ['shop' => $shop])
            ->call('addToCart', $product->id);

        $product->update(['is_visible' => false]);

        $component->call('submitOrder')
            ->assertSet('showReviewModal', true)
            ->assertNotSet('orderError', null);

        $this->assertDatabaseCount('orders', 0);
    }

    private function createMenu(): array
    {
        $shop = Shop::create([
            'name' => 'Bite',
            'slug' => 'bite',
        ]);
        $category = Category::create([
            'shop_id' => $shop->id,
            'name_en' => 'Cafe',
        ]);

        return [$shop, $category];
    }

    private function createProduct(Shop $shop, Category $category, array $attributes = []): Product
    {
        return Product::forceCreate(array_merge([
            'shop_id' => $shop->id,
            'category_id' => $category->id,
            'name_en' => 'Karak',
            'price' => 1.000,
            'is_available' => true,
            'is_visible' => true,
        ], $attributes));
    }

    private function createProductWithModifierGroup(array $groupAttributes = []): array
    {
        [$shop, $category] = $this->createMenu();
        $product = $this->createProduct($shop, $category);

        $group = ModifierGroup::create(array_merge([
            'shop_id' => $shop->id,
            'name_en' => 'Size',
            'min_selection' => 0,
            'max_selection' => 2,
        ], $groupAttributes));
        $optionA = ModifierOption::create([
            'modifier_group_id' => $group->id,
            'name_en' => 'Small',
            'price_adjustment' => 0,
        ]);
        $optionB = ModifierOption::create([
            'modifier_group_id' => $group->id,
            'name_en' => 'Large',
            'price_adjustment' => 0.200,
        ]);

        $product->modifierGroups()->attach($group->id);

        return compact('shop', 'category', 'product', 'group', 'optionA', 'optionB');
    }
}
