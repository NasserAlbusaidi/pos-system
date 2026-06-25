<?php

namespace Tests\Feature\Livewire;

use App\Livewire\PosDashboard;
use App\Models\AuditLog;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class PosDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_see_unpaid_orders_on_dashboard(): void
    {
        $shop = Shop::create(['name' => 'Bite', 'slug' => 'bite']);
        $user = User::factory()->create(['shop_id' => $shop->id, 'role' => 'server']);

        $order = Order::forceCreate([
            'shop_id' => $shop->id,
            'status' => 'unpaid',
            'total_amount' => 10.00,
        ]);

        $otherShop = Shop::create(['name' => 'Other', 'slug' => 'other']);
        $otherOrder = Order::forceCreate([
            'shop_id' => $otherShop->id,
            'status' => 'unpaid',
            'total_amount' => 5.00,
        ]);

        Livewire::actingAs($user)
            ->test(PosDashboard::class)
            ->assertSee('ID_'.$order->id)
            ->assertSee('10.000')
            ->assertSeeHtml('class="omr-symbol"');
    }

    public function test_pos_shows_paid_and_preparing_orders_for_refund_recovery(): void
    {
        $shop = Shop::create(['name' => 'Bite', 'slug' => 'bite']);
        $user = User::factory()->create(['shop_id' => $shop->id, 'role' => 'manager']);

        $paidOrder = Order::forceCreate([
            'shop_id' => $shop->id,
            'status' => 'paid',
            'total_amount' => 10.000,
            'payment_method' => 'cash',
            'paid_at' => now(),
        ]);
        Payment::forceCreate([
            'shop_id' => $shop->id,
            'order_id' => $paidOrder->id,
            'amount' => 10.000,
            'method' => 'cash',
            'created_by' => $user->id,
            'paid_at' => now(),
        ]);

        $preparingOrder = Order::forceCreate([
            'shop_id' => $shop->id,
            'status' => 'preparing',
            'total_amount' => 8.000,
            'payment_method' => 'card',
            'paid_at' => now(),
        ]);
        Payment::forceCreate([
            'shop_id' => $shop->id,
            'order_id' => $preparingOrder->id,
            'amount' => 8.000,
            'method' => 'card',
            'created_by' => $user->id,
            'paid_at' => now(),
        ]);

        Livewire::actingAs($user)
            ->test(PosDashboard::class)
            ->assertSee('ID_'.$paidOrder->id)
            ->assertSee('ID_'.$preparingOrder->id)
            ->assertSee(__('admin.refund_void_order'));
    }

    public function test_active_order_card_shows_customer_name(): void
    {
        $shop = Shop::create(['name' => 'Bite', 'slug' => 'bite']);
        $user = User::factory()->create(['shop_id' => $shop->id, 'role' => 'server']);

        Order::forceCreate([
            'shop_id' => $shop->id,
            'status' => 'unpaid',
            'customer_name' => 'Aisha Pickup',
            'total_amount' => 10.00,
        ]);

        Livewire::actingAs($user)
            ->test(PosDashboard::class)
            ->assertSee('Aisha Pickup');
    }

    public function test_staff_can_mark_order_as_paid(): void
    {
        $shop = Shop::create(['name' => 'Bite', 'slug' => 'bite']);
        $user = User::factory()->create(['shop_id' => $shop->id, 'role' => 'server']);

        $order = Order::forceCreate([
            'shop_id' => $shop->id,
            'status' => 'unpaid',
            'total_amount' => 10.00,
        ]);

        Livewire::actingAs($user)
            ->test(PosDashboard::class)
            ->call('markAsPaid', $order->id, 'card');

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'paid',
            'payment_method' => 'card',
        ]);

        $this->assertNotNull($order->fresh()->paid_at);
    }

    public function test_staff_can_mark_order_as_delivered(): void
    {
        $shop = Shop::create(['name' => 'Bite', 'slug' => 'bite']);
        $user = User::factory()->create(['shop_id' => $shop->id, 'role' => 'server']);

        $order = Order::forceCreate([
            'shop_id' => $shop->id,
            'status' => 'ready',
            'total_amount' => 10.00,
        ]);

        Livewire::actingAs($user)
            ->test(PosDashboard::class)
            ->call('markAsDelivered', $order->id);

        $this->assertEquals('completed', $order->fresh()->status);
    }

    public function test_manager_override_order_cancellation_audit_names_approver(): void
    {
        $shop = Shop::create(['name' => 'Bite', 'slug' => 'bite']);
        $server = User::factory()->create(['shop_id' => $shop->id, 'role' => 'server', 'name' => 'Counter Server']);
        $manager = User::factory()->create([
            'shop_id' => $shop->id,
            'role' => 'manager',
            'name' => 'Shift Manager',
            'pin_code' => Hash::make('4321'),
        ]);

        $order = Order::forceCreate([
            'shop_id' => $shop->id,
            'status' => 'unpaid',
            'subtotal_amount' => 10.000,
            'tax_amount' => 0,
            'total_amount' => 10.000,
        ]);

        Livewire::actingAs($server)
            ->test(PosDashboard::class)
            ->call('cancelOrder', $order->id)
            ->assertSet('showManagerModal', true)
            ->set('managerPin', '4321')
            ->call('confirmManagerOverride');

        $log = AuditLog::where('action', 'order.cancelled')->latest()->firstOrFail();

        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertSame($server->id, $log->user_id);
        $this->assertSame('Counter Server', $log->meta['cancelled_by']);
        $this->assertSame($manager->id, $log->meta['manager_approved_by_id']);
        $this->assertSame('Shift Manager', $log->meta['manager_approved_by_name']);
        $this->assertSame('manager', $log->meta['manager_approved_by_role']);
    }

    public function test_partial_item_split_preserves_kitchen_item_note_on_both_orders(): void
    {
        $shop = Shop::create(['name' => 'Bite', 'slug' => 'bite']);
        $user = User::factory()->create(['shop_id' => $shop->id, 'role' => 'server']);

        $order = Order::forceCreate([
            'shop_id' => $shop->id,
            'status' => 'unpaid',
            'subtotal_amount' => 10.000,
            'tax_amount' => 0,
            'total_amount' => 10.000,
        ]);

        $item = OrderItem::create([
            'order_id' => $order->id,
            'product_name_snapshot_en' => 'Chicken wrap',
            'product_name_snapshot_ar' => null,
            'price_snapshot' => 5.000,
            'quantity' => 2,
            'note' => 'No nuts - severe allergy',
        ]);

        Livewire::actingAs($user)
            ->test(PosDashboard::class)
            ->call('openSplit', $order->id)
            ->set("splitQuantities.{$item->id}", 1)
            ->call('applySplit')
            ->assertSet('splitError', null);

        $splitOrder = Order::where('parent_order_id', $order->id)->firstOrFail();

        $this->assertSame('No nuts - severe allergy', $item->fresh()->note);
        $this->assertSame('No nuts - severe allergy', $splitOrder->items()->firstOrFail()->note);
    }

    public function test_polling_pauses_while_a_modal_is_open(): void
    {
        // A wire:poll re-render that races a user action while a modal is open
        // can morph the modal away or desync the component snapshot, leaving the
        // POS "consumed" until a full page refresh. Polling must stop while any
        // modal is open and resume once it closes.
        $shop = Shop::create(['name' => 'Bite', 'slug' => 'bite']);
        $user = User::factory()->create(['shop_id' => $shop->id, 'role' => 'admin']);

        $component = Livewire::actingAs($user)->test(PosDashboard::class);

        // Idle: the dashboard polls for incoming orders.
        $component->assertSeeHtml('wire:poll.5s');

        // New Sale modal open: polling is suspended.
        $component->call('openNewOrder')
            ->assertSet('showNewOrder', true)
            ->assertDontSeeHtml('wire:poll');

        // Closing the modal resumes polling.
        $component->call('closeNewOrder')
            ->assertSeeHtml('wire:poll.5s');
    }
}
