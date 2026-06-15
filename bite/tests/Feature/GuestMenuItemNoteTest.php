<?php

namespace Tests\Feature;

use App\Livewire\GuestMenu;
use App\Livewire\KitchenDisplay;
use App\Models\Category;
use App\Models\GroupCart;
use App\Models\ModifierGroup;
use App\Models\ModifierOption;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use App\Services\PrintNodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use ReflectionMethod;
use Tests\TestCase;

class GuestMenuItemNoteTest extends TestCase
{
    use RefreshDatabase;

    public function test_solo_item_note_persists_to_order_items(): void
    {
        [$shop, $product] = $this->createMenu();

        Livewire::test(GuestMenu::class, ['shop' => $shop])
            ->set('itemNote', '  No nuts — severe allergy  ')
            ->call('addToCart', $product->id)
            ->call('submitOrder');

        $item = OrderItem::firstOrFail();
        $this->assertSame('No nuts — severe allergy', $item->note);
    }

    public function test_item_note_is_trimmed_and_capped_at_255(): void
    {
        [$shop, $product] = $this->createMenu();

        $long = str_repeat('x', 400);

        Livewire::test(GuestMenu::class, ['shop' => $shop])
            ->set('itemNote', $long)
            ->call('addToCart', $product->id)
            ->call('submitOrder');

        $item = OrderItem::firstOrFail();
        $this->assertSame(255, mb_strlen($item->note));
    }

    public function test_empty_note_persists_as_null(): void
    {
        [$shop, $product] = $this->createMenu();

        Livewire::test(GuestMenu::class, ['shop' => $shop])
            ->set('itemNote', '   ')
            ->call('addToCart', $product->id)
            ->call('submitOrder');

        $item = OrderItem::firstOrFail();
        $this->assertNull($item->note);
    }

    public function test_note_captured_through_the_product_sheet_flow(): void
    {
        // Product WITH a modifier group: the first addToCart opens the sheet,
        // the guest fills the note + a valid option, the second addToCart commits.
        $shop = Shop::create([
            'name' => 'Sourdough',
            'slug' => 'sourdough-'.Str::random(6),
        ]);
        $category = Category::create(['shop_id' => $shop->id, 'name_en' => 'Coffee']);
        $product = Product::forceCreate([
            'shop_id' => $shop->id,
            'category_id' => $category->id,
            'name_en' => 'Flat White',
            'price' => 1.500,
            'is_available' => true,
            'is_visible' => true,
        ]);
        $group = ModifierGroup::create([
            'shop_id' => $shop->id,
            'name_en' => 'Size',
            'min_selection' => 1,
            'max_selection' => 1,
        ]);
        $large = ModifierOption::create([
            'modifier_group_id' => $group->id,
            'name_en' => 'Large',
            'price_adjustment' => 0.400,
        ]);
        $product->modifierGroups()->attach($group->id);

        Livewire::test(GuestMenu::class, ['shop' => $shop])
            ->call('addToCart', $product->id)        // opens the sheet
            ->assertSet('showModifierModal', true)
            ->set('itemNote', 'Extra hot, please')
            ->set('selectedModifiers', [$group->id => (string) $large->id])
            ->call('addToCart', $product->id)        // commits
            ->assertSet('showModifierModal', false)
            ->assertSet('itemNote', '')              // reset after add
            ->call('submitOrder');

        $item = OrderItem::firstOrFail();
        $this->assertSame('Extra hot, please', $item->note);
    }

    public function test_note_renders_on_printed_kitchen_ticket(): void
    {
        $shop = Shop::factory()->create();
        $order = Order::forceCreate([
            'shop_id' => $shop->id,
            'status' => 'paid',
            'total_amount' => 1.200,
            'paid_at' => now(),
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => null,
            'product_name_snapshot_en' => 'Cardamom Bun',
            'product_name_snapshot_ar' => null,
            'price_snapshot' => 1.200,
            'quantity' => 1,
            'note' => 'NO WALNUTS - allergy',
        ]);

        $ticket = $this->buildTicket($order);

        $this->assertStringContainsString('Cardamom Bun', $ticket);
        $this->assertStringContainsString('NOTE: NO WALNUTS - allergy', $ticket);
    }

    public function test_printed_ticket_omits_note_line_when_absent(): void
    {
        $shop = Shop::factory()->create();
        $order = Order::forceCreate([
            'shop_id' => $shop->id,
            'status' => 'paid',
            'total_amount' => 2.500,
            'paid_at' => now(),
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => null,
            'product_name_snapshot_en' => 'Plain Loaf',
            'product_name_snapshot_ar' => null,
            'price_snapshot' => 2.500,
            'quantity' => 1,
            'note' => null,
        ]);

        $this->assertStringNotContainsString('NOTE:', $this->buildTicket($order));
    }

    public function test_group_item_note_persists_to_order_items(): void
    {
        [$shop, $product] = $this->createMenu();

        $component = Livewire::test(GuestMenu::class, ['shop' => $shop])
            ->call('createGroup')
            ->set('itemNote', 'Gluten free if possible')
            ->call('addToCart', $product->id);

        // Group cart stores the note on the item JSON.
        $groupCart = GroupCart::firstOrFail();
        $this->assertSame('Gluten free if possible', $groupCart->items[0]['note']);

        $component->call('submitOrder');

        $item = OrderItem::firstOrFail();
        $this->assertSame('Gluten free if possible', $item->note);
    }

    public function test_note_renders_on_kds(): void
    {
        $shop = Shop::factory()->create();
        $user = User::factory()->create([
            'shop_id' => $shop->id,
            'role' => 'kitchen',
        ]);

        $order = Order::forceCreate([
            'shop_id' => $shop->id,
            'status' => 'paid',
            'total_amount' => 5.00,
            'paid_at' => now(),
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => null,
            'product_name_snapshot_en' => 'Cardamom Bun',
            'product_name_snapshot_ar' => null,
            'price_snapshot' => 1.200,
            'quantity' => 1,
            'note' => 'NO WALNUTS - allergy',
        ]);

        Livewire::actingAs($user)
            ->test(KitchenDisplay::class)
            ->assertSee('Cardamom Bun')
            ->assertSee('NO WALNUTS - allergy');
    }

    public function test_kds_does_not_render_note_block_when_absent(): void
    {
        $shop = Shop::factory()->create();
        $user = User::factory()->create([
            'shop_id' => $shop->id,
            'role' => 'kitchen',
        ]);

        $order = Order::forceCreate([
            'shop_id' => $shop->id,
            'status' => 'paid',
            'total_amount' => 5.00,
            'paid_at' => now(),
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => null,
            'product_name_snapshot_en' => 'Plain Loaf',
            'product_name_snapshot_ar' => null,
            'price_snapshot' => 2.500,
            'quantity' => 1,
            'note' => null,
        ]);

        Livewire::actingAs($user)
            ->test(KitchenDisplay::class)
            ->assertSee('Plain Loaf')
            ->assertDontSeeHtml('kds-note');
    }

    private function buildTicket(Order $order): string
    {
        $method = new ReflectionMethod(PrintNodeService::class, 'buildTicket');
        $method->setAccessible(true);

        return $method->invoke(new PrintNodeService, $order, 'kitchen');
    }

    /**
     * @return array{0: Shop, 1: Product}
     */
    private function createMenu(): array
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
}
