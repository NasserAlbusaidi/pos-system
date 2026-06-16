<?php

namespace Tests\Feature;

use App\Models\Shop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoLaravelRouteTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_menu_serves_the_livewire_guest_menu(): void
    {
        Shop::create(['name' => 'Bite Demo Coffee', 'slug' => 'demo']);

        $response = $this->withSession(['guest_locale' => 'en'])
            ->get('/menu/demo?table=12');

        $response->assertOk();
        $response->assertSee('bite-ordering-stage', false);
        $response->assertSee('wire:snapshot', false);
        $response->assertSee('Bite Demo Coffee');
        $response->assertSee('Table 12');
        $response->assertDontSee('<main class="phone-shell" id="app" aria-live="polite"></main>', false);
        $response->assertDontSee('./app.js?v=20260609-omani-rial-sign2', false);
    }
}
