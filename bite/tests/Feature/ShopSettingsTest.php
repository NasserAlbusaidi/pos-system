<?php

namespace Tests\Feature;

use App\Livewire\ShopSettings;
use App\Models\AuditLog;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Shop;
use App\Models\User;
use App\Services\WhatsAppService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ShopSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_update_shop_branding()
    {
        $shop = Shop::factory()->create();
        $user = User::factory()->create(['shop_id' => $shop->id, 'role' => 'admin']);

        Livewire::actingAs($user)
            ->test(ShopSettings::class)
            ->set('name', 'Updated Shop Name')
            ->set('paper', '#ffffff')
            ->set('ink', '#000000')
            ->set('accent', '#ff0000')
            ->set('currency_code', 'OMR')
            ->set('currency_symbol', 'ر.ع.')
            ->set('currency_decimals', 3)
            ->call('save')
            ->assertDispatched('toast', message: 'Shop settings saved.', variant: 'success');

        $shop->refresh();
        $this->assertEquals('Updated Shop Name', $shop->name);
        $this->assertEquals('#ffffff', $shop->branding['paper']);
        $this->assertEquals('#ff0000', $shop->branding['accent']);
    }

    public function test_save_persists_profile_and_business_hours_to_branding(): void
    {
        $shop = Shop::factory()->create();
        $user = User::factory()->create(['shop_id' => $shop->id, 'role' => 'admin']);

        Livewire::actingAs($user)
            ->test(ShopSettings::class)
            ->set('name', 'Olive & Thyme')
            ->set('phone', '+968 9123 4567')
            ->set('address', 'Al Mouj Marina, Muscat')
            ->set('about', 'All-day cafe by the marina.')
            ->set('timezone', 'Asia/Muscat')
            ->set('businessHours.friday.closed', true)
            ->set('businessHours.sunday.open', '07:00')
            ->set('businessHours.sunday.close', '23:00')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('toast', message: 'Shop settings saved.', variant: 'success');

        $shop->refresh();
        $this->assertSame('+968 9123 4567', $shop->branding['phone']);
        $this->assertSame('Al Mouj Marina, Muscat', $shop->branding['address']);
        $this->assertSame('Asia/Muscat', $shop->branding['timezone']);
        $this->assertTrue($shop->branding['business_hours']['friday']['closed']);
        $this->assertSame('07:00', $shop->branding['business_hours']['sunday']['open']);
        $this->assertSame('23:00', $shop->branding['business_hours']['sunday']['close']);
    }

    public function test_mount_loads_profile_and_backfills_seven_days(): void
    {
        $shop = Shop::factory()->create([
            'branding' => [
                'phone' => '+968 5000 0000',
                'about' => 'Saved about text.',
                'timezone' => 'Asia/Dubai',
                'business_hours' => [
                    'monday' => ['open' => '06:30', 'close' => '20:00', 'closed' => false],
                ],
            ],
        ]);
        $user = User::factory()->create(['shop_id' => $shop->id, 'role' => 'admin']);

        Livewire::actingAs($user)
            ->test(ShopSettings::class)
            ->assertSet('phone', '+968 5000 0000')
            ->assertSet('about', 'Saved about text.')
            ->assertSet('timezone', 'Asia/Dubai')
            ->assertSet('businessHours.monday.open', '06:30')
            // unsaved days are backfilled with defaults so the form always has 7 rows
            ->assertSet('businessHours.saturday.open', '09:00')
            ->assertSet('businessHours.saturday.closed', false);
    }

    public function test_save_rejects_invalid_timezone(): void
    {
        $shop = Shop::factory()->create();
        $user = User::factory()->create(['shop_id' => $shop->id, 'role' => 'admin']);

        Livewire::actingAs($user)
            ->test(ShopSettings::class)
            ->set('timezone', 'Mars/Olympus')
            ->call('save')
            ->assertHasErrors(['timezone']);

        $shop->refresh();
        $this->assertArrayNotHasKey('timezone', $shop->branding ?? []);
    }

    public function test_whatsapp_alerts_require_a_usable_number_when_enabled(): void
    {
        $shop = Shop::factory()->create();
        $user = User::factory()->create(['shop_id' => $shop->id, 'role' => 'admin']);

        Livewire::actingAs($user)
            ->test(ShopSettings::class)
            ->set('whatsapp_notifications_enabled', true)
            ->set('whatsapp_number', '++--() ')
            ->call('save')
            ->assertHasErrors(['whatsapp_number']);

        $shop->refresh();
        $this->assertEmpty($shop->branding['whatsapp_notifications_enabled'] ?? null);
        $this->assertArrayNotHasKey('whatsapp_number', $shop->branding ?? []);
    }

    public function test_whatsapp_number_is_normalized_when_settings_are_saved(): void
    {
        $shop = Shop::factory()->create();
        $user = User::factory()->create(['shop_id' => $shop->id, 'role' => 'admin']);

        Livewire::actingAs($user)
            ->test(ShopSettings::class)
            ->set('whatsapp_notifications_enabled', true)
            ->set('whatsapp_number', '+968 99 123 456')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('toast', message: 'Shop settings saved.', variant: 'success');

        $shop->refresh();
        $this->assertSame('96899123456', $shop->branding['whatsapp_number']);
        $this->assertTrue($shop->branding['whatsapp_notifications_enabled']);
    }

    public function test_whatsapp_service_treats_invalid_stored_number_as_disabled(): void
    {
        $shop = Shop::factory()->create([
            'branding' => [
                'whatsapp_notifications_enabled' => true,
                'whatsapp_number' => '++--()',
            ],
        ]);
        $order = Order::forceCreate([
            'shop_id' => $shop->id,
            'status' => 'paid',
            'customer_name' => 'Counter Guest',
            'subtotal_amount' => 4.250,
            'tax_amount' => 0,
            'total_amount' => 4.250,
            'paid_at' => now(),
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => null,
            'product_name_snapshot_en' => 'Karak Tea',
            'product_name_snapshot_ar' => null,
            'price_snapshot' => 4.250,
            'quantity' => 1,
        ]);

        $service = new WhatsAppService;

        $this->assertFalse($service->isEnabled($shop));
        $this->assertNull($service->getNumber($shop));
        $this->assertNull($service->buildOrderLink($shop, $order));
    }

    public function test_manager_cannot_manage_admin_staff_records(): void
    {
        $shop = Shop::factory()->create();
        $manager = User::factory()->create(['shop_id' => $shop->id, 'role' => 'manager']);
        $admin = User::factory()->create(['shop_id' => $shop->id, 'role' => 'admin']);

        Livewire::actingAs($manager)
            ->test(ShopSettings::class)
            ->call('editStaff', $admin->id)
            ->assertForbidden();

        Livewire::actingAs($manager)
            ->test(ShopSettings::class)
            ->set('editingStaffId', $admin->id)
            ->set('staffName', 'Demoted Admin')
            ->set('staffEmail', 'demoted@example.test')
            ->set('staffRole', 'server')
            ->call('updateStaff')
            ->assertForbidden();

        Livewire::actingAs($manager)
            ->test(ShopSettings::class)
            ->call('removeStaff', $admin->id)
            ->assertForbidden();

        $admin->refresh();
        $this->assertSame('admin', $admin->role);
        $this->assertNotSame('Demoted Admin', $admin->name);
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_manager_cannot_manage_peer_manager_records(): void
    {
        $shop = Shop::factory()->create();
        $manager = User::factory()->create(['shop_id' => $shop->id, 'role' => 'manager']);
        $peerManager = User::factory()->create(['shop_id' => $shop->id, 'role' => 'manager']);

        Livewire::actingAs($manager)
            ->test(ShopSettings::class)
            ->call('editStaff', $peerManager->id)
            ->assertForbidden();

        Livewire::actingAs($manager)
            ->test(ShopSettings::class)
            ->set('editingStaffId', $peerManager->id)
            ->set('staffName', 'Demoted Peer')
            ->set('staffEmail', 'demoted-peer@example.test')
            ->set('staffRole', 'server')
            ->call('updateStaff')
            ->assertForbidden();

        Livewire::actingAs($manager)
            ->test(ShopSettings::class)
            ->call('removeStaff', $peerManager->id)
            ->assertForbidden();

        $peerManager->refresh();
        $this->assertSame('manager', $peerManager->role);
        $this->assertNotSame('Demoted Peer', $peerManager->name);
        $this->assertDatabaseHas('users', ['id' => $peerManager->id]);
    }

    public function test_manager_can_manage_non_admin_staff_records(): void
    {
        $shop = Shop::factory()->create();
        $manager = User::factory()->create(['shop_id' => $shop->id, 'role' => 'manager']);
        $server = User::factory()->create(['shop_id' => $shop->id, 'role' => 'server']);

        Livewire::actingAs($manager)
            ->test(ShopSettings::class)
            ->call('editStaff', $server->id)
            ->set('staffName', 'Updated Server')
            ->set('staffEmail', 'updated-server@example.test')
            ->set('staffRole', 'kitchen')
            ->call('updateStaff')
            ->assertHasNoErrors();

        $server->refresh();
        $this->assertSame('Updated Server', $server->name);
        $this->assertSame('updated-server@example.test', $server->email);
        $this->assertSame('kitchen', $server->role);
    }

    public function test_admin_can_manage_non_admin_staff_records(): void
    {
        $shop = Shop::factory()->create();
        $admin = User::factory()->create(['shop_id' => $shop->id, 'role' => 'admin']);
        $server = User::factory()->create(['shop_id' => $shop->id, 'role' => 'server']);

        Livewire::actingAs($admin)
            ->test(ShopSettings::class)
            ->call('editStaff', $server->id)
            ->set('staffName', 'Floor Manager')
            ->set('staffEmail', 'floor-manager@example.test')
            ->set('staffRole', 'manager')
            ->call('updateStaff')
            ->assertHasNoErrors();

        $server->refresh();
        $this->assertSame('Floor Manager', $server->name);
        $this->assertSame('floor-manager@example.test', $server->email);
        $this->assertSame('manager', $server->role);
    }

    public function test_staff_add_update_and_remove_are_audited_without_raw_pin(): void
    {
        $shop = Shop::factory()->create(['trial_ends_at' => now()->addDays(14)]);
        $admin = User::factory()->create(['shop_id' => $shop->id, 'role' => 'admin']);

        Livewire::actingAs($admin)
            ->test(ShopSettings::class)
            ->set('staffName', 'Audit Server')
            ->set('staffEmail', 'audit-server@example.test')
            ->set('staffRole', 'server')
            ->set('staffPin', '2468')
            ->call('addStaff')
            ->assertHasNoErrors();

        $staff = User::where('email', 'audit-server@example.test')->firstOrFail();
        $created = AuditLog::where('action', 'staff.created')->firstOrFail();

        $this->assertSame($shop->id, $created->shop_id);
        $this->assertSame($admin->id, $created->user_id);
        $this->assertSame(User::class, $created->auditable_type);
        $this->assertSame($staff->id, $created->auditable_id);
        $this->assertSame('server', $created->meta['role']);
        $this->assertTrue($created->meta['pin_set']);
        $this->assertStringNotContainsString('2468', json_encode($created->meta));

        Livewire::actingAs($admin)
            ->test(ShopSettings::class)
            ->call('editStaff', $staff->id)
            ->set('staffName', 'Audit Manager')
            ->set('staffEmail', 'audit-manager@example.test')
            ->set('staffRole', 'manager')
            ->set('staffPin', '1357')
            ->call('updateStaff')
            ->assertHasNoErrors();

        $updated = AuditLog::where('action', 'staff.updated')->firstOrFail();

        $this->assertSame($staff->id, $updated->auditable_id);
        $this->assertSame('server', $updated->meta['previous_role']);
        $this->assertSame('manager', $updated->meta['role']);
        $this->assertTrue($updated->meta['pin_changed']);
        $this->assertStringNotContainsString('1357', json_encode($updated->meta));

        Livewire::actingAs($admin)
            ->test(ShopSettings::class)
            ->call('removeStaff', $staff->id)
            ->assertDispatched('toast', message: 'Staff member removed.', variant: 'success');

        $removed = AuditLog::where('action', 'staff.removed')->firstOrFail();

        $this->assertSame($staff->id, $removed->auditable_id);
        $this->assertSame('manager', $removed->meta['role']);
        $this->assertSame('audit-manager@example.test', $removed->meta['email']);
        $this->assertStringNotContainsString('1357', json_encode($removed->meta));
    }
}
