<?php

namespace Tests\Feature\Livewire;

use App\Livewire\GuestMenu;
use App\Models\Category;
use App\Models\ModifierGroup;
use App\Models\ModifierOption;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Shop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class GuestMenuProductSheetTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: Shop, 1: Product}
     */
    private function createModifierlessMenu(): array
    {
        $shop = Shop::create([
            'name' => 'Sourdough',
            'slug' => 'sourdough-'.Str::random(6),
        ]);
        $category = Category::create([
            'shop_id' => $shop->id,
            'name_en' => 'Bakery',
        ]);
        $product = Product::forceCreate([
            'shop_id' => $shop->id,
            'category_id' => $category->id,
            'name_en' => 'Country Loaf',
            'price' => 2.500,
            'is_available' => true,
            'is_visible' => true,
        ]);

        return [$shop, $product];
    }

    /**
     * @return array{0: Shop, 1: Product, 2: ModifierGroup, 3: ModifierOption, 4: ModifierGroup, 5: ModifierOption}
     */
    private function createModifierMenu(): array
    {
        [$shop, $product] = $this->createModifierlessMenu();

        $product->forceFill([
            'name_en' => 'Dubai Chocolate Latte',
            'description_en' => 'Hopresso crafted signature',
        ])->save();

        $sizeGroup = ModifierGroup::create([
            'shop_id' => $shop->id,
            'name_en' => 'Size',
            'min_selection' => 1,
            'max_selection' => 1,
        ]);

        $regular = ModifierOption::create([
            'modifier_group_id' => $sizeGroup->id,
            'name_en' => 'Regular',
            'price_adjustment' => 0,
        ]);

        ModifierOption::create([
            'modifier_group_id' => $sizeGroup->id,
            'name_en' => 'Large',
            'price_adjustment' => 0.400,
        ]);

        $milkGroup = ModifierGroup::create([
            'shop_id' => $shop->id,
            'name_en' => 'Milk & add-ons',
            'min_selection' => 0,
            'max_selection' => 3,
        ]);

        $standardMilk = ModifierOption::create([
            'modifier_group_id' => $milkGroup->id,
            'name_en' => 'Standard milk',
            'price_adjustment' => 0,
        ]);

        ModifierOption::create([
            'modifier_group_id' => $milkGroup->id,
            'name_en' => 'Oat milk',
            'price_adjustment' => 0,
        ]);

        $product->modifierGroups()->attach([$sizeGroup->id, $milkGroup->id]);

        return [$shop, $product, $sizeGroup, $regular, $milkGroup, $standardMilk];
    }

    public function test_open_product_sheet_opens_for_a_modifierless_product(): void
    {
        [$shop, $product] = $this->createModifierlessMenu();

        $component = Livewire::test(GuestMenu::class, ['shop' => $shop])
            ->call('openProductSheet', $product->id)
            ->assertSet('showModifierModal', true);

        $this->assertSame($product->id, $component->get('customizingProduct')->id);
    }

    public function test_open_product_uses_a_full_product_screen_instead_of_a_sheet(): void
    {
        [$shop, $product] = $this->createModifierlessMenu();

        Livewire::test(GuestMenu::class, ['shop' => $shop])
            ->call('openProductSheet', $product->id)
            ->assertSet('screen', 'product')
            ->assertSet('showModifierModal', true)
            ->assertSeeHtml('data-route-name="product"')
            ->assertSeeHtml('bite-product-screen')
            ->assertSeeHtml('bite-product-main')
            ->assertDontSeeHtml('bite-sheet-backdrop')
            ->assertSee($product->name_en)
            ->assertSee('Add to Cart');
    }

    public function test_product_page_matches_the_image_first_checkout_style(): void
    {
        [$shop, $product, $sizeGroup, $regular, $milkGroup, $standardMilk] = $this->createModifierMenu();

        $component = Livewire::test(GuestMenu::class, ['shop' => $shop])
            ->call('openProductSheet', $product->id);

        $selectedModifiers = $component->get('selectedModifiers');
        $this->assertSame((string) $regular->id, $selectedModifiers[$sizeGroup->id] ?? null);
        $this->assertSame((string) $standardMilk->id, $selectedModifiers[$milkGroup->id] ?? null);

        $component
            ->assertSeeHtml('bite-product-page__hero')
            ->assertSeeHtml('bite-product-title-row')
            ->assertSeeHtml('bite-product-meta')
            ->assertSeeHtml('bite-product-choice-group')
            ->assertSeeHtml('bite-product-option is-selected')
            ->assertSeeHtml('bite-product-note')
            ->assertSee('4.9 Rating')
            ->assertSee('300 kcal')
            ->assertSee('15 min')
            ->assertSee('Add to Cart')
            ->assertSee('Item note')
            ->assertSee('Less ice, no sugar, allergy note...')
            ->assertSee('Regular')
            ->assertSee('Large')
            ->assertSee('Oat milk');
    }

    public function test_open_product_sheet_resets_prior_customization_state(): void
    {
        [$shop, $product] = $this->createModifierlessMenu();

        Livewire::test(GuestMenu::class, ['shop' => $shop])
            ->set('itemNote', 'stale note')
            ->set('selectedModifiers', [99 => '1'])
            ->set('modifierError', 'stale error')
            ->call('openProductSheet', $product->id)
            ->assertSet('itemNote', '')
            ->assertSet('selectedModifiers', [])
            ->assertSet('modifierError', null);
    }

    public function test_open_product_sheet_is_shop_scoped(): void
    {
        [$shop] = $this->createModifierlessMenu();

        // A product belonging to a DIFFERENT shop must never load into the sheet.
        $otherShop = Shop::create([
            'name' => 'Other',
            'slug' => 'other-'.Str::random(6),
        ]);
        $otherCategory = Category::create([
            'shop_id' => $otherShop->id,
            'name_en' => 'Bakery',
        ]);
        $otherProduct = Product::forceCreate([
            'shop_id' => $otherShop->id,
            'category_id' => $otherCategory->id,
            'name_en' => 'Foreign Loaf',
            'price' => 9.000,
            'is_available' => true,
            'is_visible' => true,
        ]);

        Livewire::test(GuestMenu::class, ['shop' => $shop])
            ->call('openProductSheet', $otherProduct->id)
            ->assertSet('showModifierModal', false)
            ->assertSet('customizingProduct', null);
    }

    public function test_adding_from_sheet_persists_item_note_for_modifierless_product(): void
    {
        [$shop, $product] = $this->createModifierlessMenu();

        Livewire::test(GuestMenu::class, ['shop' => $shop])
            ->call('openProductSheet', $product->id)   // sheet opens, no modifiers
            ->assertSet('showModifierModal', true)
            ->set('itemNote', '  No nuts — severe allergy  ')
            ->call('addToCart', $product->id)           // commits from the sheet
            ->assertSet('showModifierModal', false)
            ->assertSet('itemNote', '')
            ->set('customerName', 'Layla')
            ->set('loyaltyPhone', '95123456')
            ->call('submitOrder');

        $item = OrderItem::firstOrFail();
        $this->assertSame('No nuts — severe allergy', $item->note);
    }
}
