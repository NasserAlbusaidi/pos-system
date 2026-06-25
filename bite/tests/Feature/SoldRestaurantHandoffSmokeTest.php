<?php

namespace Tests\Feature;

use App\Livewire\CashReconciliation;
use App\Livewire\KitchenDisplay;
use App\Livewire\OnboardingWizard;
use App\Livewire\PinLogin;
use App\Livewire\PosDashboard;
use App\Models\ModifierGroup;
use App\Models\ModifierOption;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ShiftClosure;
use App\Models\Shop;
use App\Models\User;
use App\Services\BillingService;
use App\Services\ImageService;
use App\Services\ShopProvisioningService;
use App\Support\ShopClock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Livewire\Volt\Volt;
use Tests\TestCase;

class SoldRestaurantHandoffSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_restaurant_handoff_supports_staff_guest_orders_pos_kds_and_reports(): void
    {
        $this->stubDemoImagePipeline();

        $owner = app(ShopProvisioningService::class)->provisionOwner(
            name: 'Mina Farah',
            email: 'owner@atlas-night.test',
            password: 'launch-password',
            shopName: 'Atlas Night Kitchen',
            slug: 'atlas-night-kitchen',
            status: 'trial',
        );

        $shop = $owner->shop()->firstOrFail();
        $billing = app(BillingService::class);

        Volt::test('pages.auth.login')
            ->set('form.email', 'owner@atlas-night.test')
            ->set('form.password', 'launch-password')
            ->call('login')
            ->assertHasNoErrors()
            ->assertRedirect('/onboarding');
        $this->assertAuthenticatedAs($owner);
        auth()->logout();

        $this->assertSame('trial', $shop->status);
        $this->assertTrue($shop->trial_ends_at->isFuture());
        $this->assertSame('pro', $billing->getCurrentPlan($shop));
        $this->assertTrue($billing->canAccess($shop, 'add_staff'));
        $this->assertTrue($billing->canAccess($shop, 'reports'));

        $this->actingAs($owner)
            ->get(route('onboarding'))
            ->assertOk()
            ->assertSee('Atlas Night Kitchen');

        Livewire::actingAs($owner)
            ->test(OnboardingWizard::class)
            ->set('step', 2)
            ->set('currency_code', 'OMR')
            ->set('currency_symbol', 'ر.ع.')
            ->set('currency_decimals', 3)
            ->set('tax_rate', 5)
            ->set('accent', '#0f766e')
            ->set('paper', '#fffaf0')
            ->set('ink', '#111827')
            ->call('saveShopProfile')
            ->assertHasNoErrors()
            ->set('staffName', 'Mina Floor Lead')
            ->set('staffEmail', 'manager@atlas-night.test')
            ->set('staffRole', 'manager')
            ->set('staffPin', '2468')
            ->call('addStaff')
            ->assertHasNoErrors()
            ->set('staffName', 'Omar Expo')
            ->set('staffEmail', 'kitchen@atlas-night.test')
            ->set('staffRole', 'kitchen')
            ->set('staffPin', '9753')
            ->call('addStaff')
            ->assertHasNoErrors()
            ->set('staffName', 'Sara Counter')
            ->set('staffEmail', 'server@atlas-night.test')
            ->set('staffRole', 'server')
            ->set('staffPin', '1357')
            ->call('addStaff')
            ->assertHasNoErrors()
            ->call('loadDemoMenu')
            ->assertSet('demoMenuLoaded', true)
            ->call('completeOnboardingAndOpenPos')
            ->assertHasNoErrors()
            ->assertRedirect(route('pos.dashboard'));

        $shop->refresh();

        $this->assertTrue((bool) ($shop->branding['onboarding_completed'] ?? false));
        $this->assertSame(18, Product::where('shop_id', $shop->id)->count());
        $this->assertSame(18, Product::where('shop_id', $shop->id)->whereNotNull('image_url')->count());
        $this->assertSame(0, Product::where('shop_id', $shop->id)->where('image_url', 'like', 'http%')->count());
        $this->assertSame(3, ModifierGroup::where('shop_id', $shop->id)->count());

        $manager = User::where('email', 'manager@atlas-night.test')->firstOrFail();
        $kitchen = User::where('email', 'kitchen@atlas-night.test')->firstOrFail();
        $server = User::where('email', 'server@atlas-night.test')->firstOrFail();

        $this->assertSame('manager', $manager->role);
        $this->assertSame('kitchen', $kitchen->role);
        $this->assertSame('server', $server->role);
        $this->assertTrue(Hash::check('1357', $server->pin_code));

        $this->get(route('guest.menu', $shop))
            ->assertOk()
            ->assertSee('Atlas Night Kitchen')
            ->assertSee('Kunafa')
            ->assertSee('/storage/products/', false)
            ->assertDontSee('https://images.pexels.com/photos/', false);

        [$cart, $kunafa, $latte] = $this->handoffCart($shop);

        $this->postJson(route('api.guest.orders.quote'), [
            'shop' => $shop->slug,
            'cart' => $cart,
        ])->assertOk()
            ->assertJsonPath('data.items.0.name', $kunafa->name_en)
            ->assertJsonPath('data.items.1.name', $latte->name_en);

        $firstOrderToken = $this->postJson(route('api.guest.orders.store'), [
            'shop' => $shop->slug,
            'cart' => $cart,
            'customer_name' => 'Noura Walk-in',
            'loyalty_phone' => '99887766',
            'order_note' => 'No plastic cutlery.',
            'idempotency_key' => (string) Str::uuid(),
        ])->assertCreated()
            ->assertJsonPath('data.status', 'received')
            ->assertJsonPath('data.shop.slug', $shop->slug)
            ->json('data.tracking_token');

        $secondOrderToken = $this->postJson(route('api.guest.orders.store'), [
            'shop' => $shop->slug,
            'cart' => [
                [
                    'id' => $kunafa->id,
                    'name' => $kunafa->name_en,
                    'quantity' => 1,
                    'selectedModifiers' => [],
                    'note' => 'Extra syrup on the side.',
                ],
            ],
            'customer_name' => 'Yousuf Pickup',
            'loyalty_phone' => '99776655',
            'idempotency_key' => (string) Str::uuid(),
        ])->assertCreated()
            ->json('data.tracking_token');

        $firstOrder = Order::where('tracking_token', $firstOrderToken)->firstOrFail();
        $secondOrder = Order::where('tracking_token', $secondOrderToken)->firstOrFail();

        $this->assertSame('unpaid', $firstOrder->status);
        $this->assertSame('unpaid', $secondOrder->status);
        $this->assertSame(2, Order::where('shop_id', $shop->id)->count());

        auth()->logout();

        Livewire::test(PinLogin::class, ['shop' => $shop])
            ->set('pin', '1357')
            ->call('login')
            ->assertRedirect(route('pos.dashboard'));

        $this->assertAuthenticatedAs($server);

        Livewire::actingAs($server)
            ->test(PosDashboard::class)
            ->call('markAsPaid', $firstOrder->id, 'cash')
            ->assertHasNoErrors();

        $firstOrder->refresh();

        $this->assertSame('paid', $firstOrder->status);
        $this->assertNotNull($firstOrder->paid_at);
        $this->assertEqualsWithDelta(
            (float) $firstOrder->total_amount,
            (float) Payment::where('order_id', $firstOrder->id)->sum('amount'),
            0.0001,
        );

        $this->actingAs($server)
            ->get(route('pos.dashboard'))
            ->assertOk();
        $this->actingAs($server)
            ->get(route('admin.settings'))
            ->assertForbidden();

        auth()->logout();

        Livewire::test(PinLogin::class, ['shop' => $shop])
            ->set('pin', '9753')
            ->call('login')
            ->assertRedirect(route('kds.view'));

        $this->assertAuthenticatedAs($kitchen);

        Livewire::actingAs($kitchen)
            ->test(KitchenDisplay::class)
            ->assertSee('Noura Walk-in')
            ->call('updateStatus', $firstOrder->id, 'preparing')
            ->call('updateStatus', $firstOrder->id, 'ready')
            ->assertHasNoErrors();

        $this->assertSame('ready', $firstOrder->fresh()->status);

        $this->actingAs($kitchen)
            ->get(route('kds.view'))
            ->assertOk();
        $this->actingAs($kitchen)
            ->get(route('pos.dashboard'))
            ->assertForbidden();

        $this->actingAs($manager)
            ->get(route('admin.reports'))
            ->assertOk()
            ->assertSee('Kunafa')
            ->assertSee('CASH');

        $ordersBeforeClose = Order::where('shop_id', $shop->id)->count();
        $paymentsBeforeClose = Payment::where('shop_id', $shop->id)->count();
        $expectedCash = (float) Payment::where('shop_id', $shop->id)
            ->where('method', 'cash')
            ->sum('amount');

        Livewire::actingAs($manager)
            ->test(CashReconciliation::class)
            ->set('actualCash', $expectedCash)
            ->set('notes', 'End-of-day handoff close.')
            ->call('reconcile')
            ->assertSet('difference', 0.0)
            ->call('closeShift')
            ->assertRedirect(route('dashboard'));

        $closure = ShiftClosure::where('shop_id', $shop->id)
            ->where('business_date', ShopClock::localDate($shop))
            ->firstOrFail();

        $this->assertSame($manager->id, $closure->closed_by);
        $this->assertEqualsWithDelta($expectedCash, (float) $closure->expected_cash, 0.0001);
        $this->assertSame('End-of-day handoff close.', $closure->notes);

        $this->postJson(route('api.guest.orders.quote'), [
            'shop' => $shop->slug,
            'cart' => $cart,
        ])->assertStatus(422)
            ->assertJsonPath('message', __('guest.shift_closed'))
            ->assertJsonPath('field', 'order');

        $this->postJson(route('api.guest.orders.store'), [
            'shop' => $shop->slug,
            'cart' => $cart,
            'customer_name' => 'Late Guest',
            'loyalty_phone' => '99112233',
            'idempotency_key' => (string) Str::uuid(),
        ])->assertStatus(422)
            ->assertJsonPath('message', __('guest.shift_closed'))
            ->assertJsonPath('field', 'order');

        Livewire::actingAs($server)
            ->test(PosDashboard::class)
            ->call('openNewOrder')
            ->call('addToCart', $kunafa->id)
            ->call('chargeNewOrder', 'cash')
            ->assertSet('newOrderError', 'Shift is closed for today. Payments are locked until the next business day.')
            ->assertSet('showNewOrder', true);

        $this->assertSame($ordersBeforeClose, Order::where('shop_id', $shop->id)->count());
        $this->assertSame($paymentsBeforeClose, Payment::where('shop_id', $shop->id)->count());

        $this->actingAs($owner)
            ->get(route('billing'))
            ->assertOk();
    }

    /**
     * @return array{0: array<int, array<string, mixed>>, 1: Product, 2: Product}
     */
    private function handoffCart(Shop $shop): array
    {
        $kunafa = Product::where('shop_id', $shop->id)
            ->where('name_en', 'Kunafa')
            ->firstOrFail();
        $latte = Product::where('shop_id', $shop->id)
            ->where('name_en', 'Latte')
            ->firstOrFail();
        $size = ModifierGroup::where('shop_id', $shop->id)
            ->where('name_en', 'Size')
            ->firstOrFail();
        $regular = ModifierOption::where('modifier_group_id', $size->id)
            ->where('name_en', 'Regular')
            ->firstOrFail();
        $milk = ModifierGroup::where('shop_id', $shop->id)
            ->where('name_en', 'Milk Type')
            ->firstOrFail();
        $oat = ModifierOption::where('modifier_group_id', $milk->id)
            ->where('name_en', 'Oat Milk')
            ->firstOrFail();

        return [
            [
                [
                    'id' => $kunafa->id,
                    'name' => $kunafa->name_en,
                    'quantity' => 2,
                    'selectedModifiers' => [],
                    'note' => 'Cut for sharing.',
                ],
                [
                    'id' => $latte->id,
                    'name' => $latte->name_en,
                    'quantity' => 1,
                    'selectedModifiers' => [
                        $size->id => [$regular->id],
                        $milk->id => [$oat->id],
                    ],
                ],
            ],
            $kunafa,
            $latte,
        ];
    }

    private function stubDemoImagePipeline(): void
    {
        Storage::fake('public');
        Http::preventStrayRequests();
        Http::fake(['images.pexels.com/*' => Http::response('fake-image-bytes', 200)]);

        $this->mock(ImageService::class, function ($mock): void {
            $mock->shouldReceive('processUpload')
                ->andReturnUsing(fn (string $path, ?string $disk = null): string => (string) preg_replace('/\.jpg$/', '-full.webp', $path));
        });
    }
}
