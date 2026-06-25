<?php

namespace Tests\Feature;

use App\Livewire\GuestMenu;
use App\Models\Category;
use App\Models\ModifierGroup;
use App\Models\ModifierOption;
use App\Models\Product;
use App\Models\Shop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Design-contract guards for the guest re-skin (prototype screens 4-6).
 *
 * Phase 4 reskinned the menu screen; phases 5-6 reskinned the product detail
 * sheet and the cart/checkout sheet onto the prototype design system. These
 * assertions pin the prototype markup — choice-row chips, the honest metric
 * row, the web-cart-line thumbnail, the green qty-stepper, and the single
 * pay-at-counter select-card — so a later refactor that silently drops the
 * look fails CI. Behaviour is covered by the other Guest* tests; this file
 * only guards the presentation contract.
 */
class GuestMenuReskinContractTest extends TestCase
{
    use RefreshDatabase;

    private function seedShop(): Shop
    {
        $shop = Shop::create([
            'name' => 'Sourdough',
            'slug' => 'sourdough-'.Str::random(6),
            'branding' => [],
        ]);

        $coffee = Category::create([
            'shop_id' => $shop->id,
            'name_en' => 'Coffee',
            'name_ar' => 'قهوة',
            'sort_order' => 1,
        ]);

        $product = Product::forceCreate([
            'shop_id' => $shop->id,
            'category_id' => $coffee->id,
            'name_en' => 'Flat White',
            'name_ar' => 'فلات وايت',
            'price' => 1.500,
            'is_available' => true,
            'is_visible' => true,
            'sort_order' => 1,
        ]);

        $group = ModifierGroup::create([
            'shop_id' => $shop->id,
            'name_en' => 'Milk',
            'name_ar' => 'الحليب',
            'min_selection' => 1,
            'max_selection' => 1,
        ]);
        ModifierOption::create([
            'modifier_group_id' => $group->id,
            'name_en' => 'Oat',
            'name_ar' => 'شوفان',
            'price_adjustment' => 0.200,
        ]);
        $product->modifierGroups()->attach($group->id);

        return $shop;
    }

    public function test_detail_sheet_uses_prototype_choice_chips_and_metric_row(): void
    {
        $shop = $this->seedShop();
        $product = $shop->products()->first();
        $group = $product->modifierGroups()->first();
        $option = $group->options()->first();

        $component = Livewire::test(GuestMenu::class, ['shop' => $shop])
            ->call('openProductSheet', $product->id)
            ->assertSet('showModifierModal', true);

        // title-price + honest metric row (category chip — no fabricated stats).
        $component->assertSeeHtml('guest-detail__price')
            ->assertSeeHtml('guest-metric-row')
            ->assertSeeHtml('guest-metric')
            ->assertSee('Coffee');

        // Modifier options render as choice-row chips (toggle buttons), not radios.
        $component->assertSeeHtml('guest-choice-row')
            ->assertSeeHtml('guest-choice')
            ->assertSeeHtml('aria-pressed="false"');

        // Selecting the option flips the chip's active state (presentation only;
        // the same selectModifier() flow as before).
        $component->call('selectModifier', $group->id, $option->id, false)
            ->assertSeeHtml('guest-choice--on')
            ->assertSeeHtml('aria-pressed="true"');
    }

    public function test_on_sale_metric_chip_shows_only_for_discounted_products(): void
    {
        $shop = $this->seedShop();
        $product = $shop->products()->first();

        // Not on sale → no sale chip.
        Livewire::test(GuestMenu::class, ['shop' => $shop])
            ->call('openProductSheet', $product->id)
            ->assertDontSeeHtml('guest-metric--sale');

        $product->forceFill([
            'discount_value' => 0.300,
            'discount_type' => 'fixed',
            'is_on_sale' => true,
        ])->save();

        Livewire::test(GuestMenu::class, ['shop' => $shop])
            ->call('openProductSheet', $product->id)
            ->assertSeeHtml('guest-metric--sale')
            ->assertSee(__('guest.on_sale'));
    }

    public function test_cart_uses_prototype_web_cart_line_stepper_and_summary(): void
    {
        $shop = $this->seedShop();
        $product = $shop->products()->first();
        $group = $product->modifierGroups()->first();
        $option = $group->options()->first();

        $component = Livewire::test(GuestMenu::class, ['shop' => $shop])
            ->call('openProductSheet', $product->id)
            ->call('selectModifier', $group->id, $option->id, false)
            ->call('addToCart', $product->id)
            ->assertSet('showModifierModal', false)
            ->call('toggleReview')
            ->assertSet('showReviewModal', true);

        // web-cart-line: thumbnail (placeholder here — product has no image) +
        // the green qty-stepper.
        $component->assertSeeHtml('guest-cartline__img')
            ->assertSeeHtml('guest-ministep__btn');

        // web-summary + the single pay-at-counter select-card. Pay-at-counter
        // only: no payment dropdown, no voucher field (scope #29).
        $component->assertSeeHtml('guest-summary')
            ->assertSeeHtml('guest-paysel')
            ->assertSee(__('guest.pay_at_counter'));
    }

    public function test_checkout_confirmation_dispatches_guest_confirm_surface(): void
    {
        $shop = $this->seedShop();
        $product = $shop->products()->first();
        $group = $product->modifierGroups()->first();
        $option = $group->options()->first();

        Livewire::test(GuestMenu::class, ['shop' => $shop])
            ->call('openProductSheet', $product->id)
            ->call('selectModifier', $group->id, $option->id, false)
            ->call('addToCart', $product->id)
            ->call('toggleReview')
            ->assertSeeHtml("surface: 'guest'")
            ->assertSeeHtml('confirmLabel:')
            ->assertSeeHtml('cancelLabel:')
            ->assertDontSeeHtml('Please confirm this action');
    }

    public function test_group_share_modal_uses_guest_sheet_surface(): void
    {
        $shop = $this->seedShop();

        Livewire::test(GuestMenu::class, ['shop' => $shop])
            ->call('createGroup')
            ->assertSet('showGroupShareModal', true)
            ->assertSeeHtml('guest-sheet guest-share')
            ->assertSeeHtml('guest-share__url')
            ->assertSeeHtml('guest-share__copy')
            ->assertDontSeeHtml('surface-card flex w-full max-w-md')
            ->assertDontSeeHtml('btn-primary w-full justify-center');
    }
}
