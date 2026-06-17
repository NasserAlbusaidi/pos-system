<?php

namespace Tests\Feature\Livewire;

use App\Livewire\PosDashboard;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Pilot payment policy (#57): an order settlement must cover the full balance.
 * Partial payments are rejected at the source so no order can sit 'unpaid'
 * forever. Multiple tenders (split by guest/method) are allowed as long as they
 * sum to the full balance.
 */
class PosPaymentFullBalanceTest extends TestCase
{
    use RefreshDatabase;

    private function makeUnpaidOrder(Shop $shop, float $total = 10.000): Order
    {
        return Order::forceCreate([
            'shop_id' => $shop->id,
            'status' => 'unpaid',
            'total_amount' => $total,
        ]);
    }

    private function actAsServer(Shop $shop): User
    {
        return User::factory()->create(['shop_id' => $shop->id, 'role' => 'server']);
    }

    public function test_partial_payment_is_rejected_and_records_nothing(): void
    {
        $shop = Shop::create(['name' => 'Bite', 'slug' => 'bite']);
        $user = $this->actAsServer($shop);
        $order = $this->makeUnpaidOrder($shop, 10.000);

        Livewire::actingAs($user)
            ->test(PosDashboard::class)
            ->call('openPayment', $order->id)
            ->set('paymentRows', [['amount' => 4.000, 'method' => 'cash']])
            ->call('applyPayments')
            ->assertNotSet('paymentError', null)      // error surfaced
            ->assertSet('paymentOrderId', $order->id); // modal stays open

        $this->assertSame('unpaid', $order->fresh()->status);
        $this->assertSame(0, Payment::where('order_id', $order->id)->count());
    }

    public function test_full_payment_in_one_tender_marks_paid(): void
    {
        $shop = Shop::create(['name' => 'Bite', 'slug' => 'bite']);
        $user = $this->actAsServer($shop);
        $order = $this->makeUnpaidOrder($shop, 10.000);

        Livewire::actingAs($user)
            ->test(PosDashboard::class)
            ->call('openPayment', $order->id)
            ->set('paymentRows', [['amount' => 10.000, 'method' => 'cash']])
            ->call('applyPayments')
            ->assertSet('paymentError', null);

        $this->assertSame('paid', $order->fresh()->status);
        $this->assertSame(1, Payment::where('order_id', $order->id)->count());
    }

    public function test_split_tenders_that_sum_to_balance_are_accepted(): void
    {
        $shop = Shop::create(['name' => 'Bite', 'slug' => 'bite']);
        $user = $this->actAsServer($shop);
        $order = $this->makeUnpaidOrder($shop, 10.000);

        Livewire::actingAs($user)
            ->test(PosDashboard::class)
            ->call('openPayment', $order->id)
            ->set('paymentRows', [
                ['amount' => 6.000, 'method' => 'cash'],
                ['amount' => 4.000, 'method' => 'card'],
            ])
            ->call('applyPayments')
            ->assertSet('paymentError', null);

        $this->assertSame('paid', $order->fresh()->status);
        $this->assertSame(2, Payment::where('order_id', $order->id)->count());
    }

    public function test_overpayment_is_rejected(): void
    {
        $shop = Shop::create(['name' => 'Bite', 'slug' => 'bite']);
        $user = $this->actAsServer($shop);
        $order = $this->makeUnpaidOrder($shop, 10.000);

        Livewire::actingAs($user)
            ->test(PosDashboard::class)
            ->call('openPayment', $order->id)
            ->set('paymentRows', [['amount' => 12.000, 'method' => 'cash']])
            ->call('applyPayments')
            ->assertNotSet('paymentError', null);

        $this->assertSame('unpaid', $order->fresh()->status);
        $this->assertSame(0, Payment::where('order_id', $order->id)->count());
    }
}
