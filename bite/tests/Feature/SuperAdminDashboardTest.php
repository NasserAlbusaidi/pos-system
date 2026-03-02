<?php

namespace Tests\Feature;

use App\Livewire\SuperAdmin\Dashboard;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SuperAdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_stats_and_actions()
    {
        $admin = User::factory()->superAdmin()->create();
        $shop = Shop::factory()->create();

        Livewire::actingAs($admin)
            ->test(Dashboard::class)
            ->assertSee($shop->name)
            ->assertSee('active')
            // Test Toggle
            ->call('toggleStatus', $shop->id)
            ->assertSee('suspended');

        $this->assertEquals('suspended', $shop->fresh()->status);

        // Test Delete
        Livewire::actingAs($admin)
            ->test(Dashboard::class)
            ->call('deleteShop', $shop->id);

        $this->assertDatabaseMissing('shops', ['id' => $shop->id]);
    }
}
