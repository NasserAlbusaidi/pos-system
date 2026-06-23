<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Admin\AuditLogs;
use App\Models\AuditLog;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AuditLogsTest extends TestCase
{
    use RefreshDatabase;

    private function adminFor(Shop $shop): User
    {
        return User::factory()->create(['shop_id' => $shop->id, 'role' => 'admin']);
    }

    public function test_admin_sees_audit_events_with_derived_badge(): void
    {
        $shop = Shop::factory()->create();
        $actor = $this->adminFor($shop);

        AuditLog::create([
            'shop_id' => $shop->id,
            'user_id' => $actor->id,
            'action' => 'product.created',
            'auditable_type' => 'App\\Models\\Product',
            'auditable_id' => 7,
            'meta' => ['name' => 'Garden bowl'],
        ]);

        $this->actingAs($actor);

        Livewire::test(AuditLogs::class)
            ->assertOk()
            ->assertSee(__('admin.audit_filters'))      // restyled filter card
            ->assertSee('product.created')              // precise action preserved
            ->assertSee(__('admin.audit_badge_created')) // derived badge label
            ->assertSee('Product');                     // class_basename target cell
    }

    public function test_category_filter_scopes_by_action_prefix(): void
    {
        $shop = Shop::factory()->create();
        $actor = $this->adminFor($shop);

        AuditLog::create(['shop_id' => $shop->id, 'user_id' => $actor->id, 'action' => 'product.created', 'meta' => []]);
        AuditLog::create(['shop_id' => $shop->id, 'user_id' => $actor->id, 'action' => 'order.voided', 'meta' => []]);

        $this->actingAs($actor);

        Livewire::test(AuditLogs::class)
            ->set('logFilter', 'products')
            ->assertSee('product.created')
            ->assertDontSee('order.voided');
    }

    public function test_search_filters_by_action(): void
    {
        $shop = Shop::factory()->create();
        $actor = $this->adminFor($shop);

        AuditLog::create(['shop_id' => $shop->id, 'user_id' => $actor->id, 'action' => 'product.created', 'meta' => []]);
        AuditLog::create(['shop_id' => $shop->id, 'user_id' => $actor->id, 'action' => 'pin.login', 'meta' => []]);

        $this->actingAs($actor);

        Livewire::test(AuditLogs::class)
            ->set('search', 'pin.login')
            ->assertSee('pin.login')
            ->assertDontSee('product.created');
    }

    public function test_does_not_render_another_shops_events(): void
    {
        $shop = Shop::factory()->create();
        $actor = $this->adminFor($shop);

        $other = Shop::factory()->create();
        AuditLog::create([
            'shop_id' => $other->id,
            'action' => 'product.created',
            'auditable_type' => 'App\\Models\\Product',
            'auditable_id' => 99,
            'meta' => [],
        ]);

        $this->actingAs($actor);

        Livewire::test(AuditLogs::class)->assertDontSee('#99');
    }

    public function test_low_trust_role_is_blocked(): void
    {
        $shop = Shop::factory()->create();
        $server = User::factory()->create(['shop_id' => $shop->id, 'role' => 'server']);

        $this->actingAs($server);

        Livewire::test(AuditLogs::class)->assertForbidden();
    }
}
