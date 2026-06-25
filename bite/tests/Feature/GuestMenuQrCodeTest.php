<?php

namespace Tests\Feature;

use App\Models\Shop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuestMenuQrCodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_menu_qr_is_served_locally_as_svg(): void
    {
        $shop = Shop::forceCreate([
            'name' => 'QR Kitchen',
            'slug' => 'qr-kitchen',
            'trial_ends_at' => now()->addDay(),
        ]);

        $response = $this->get(route('guest.menu.qr', $shop));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/svg+xml; charset=UTF-8');
        $this->assertStringContainsString('public', (string) $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('max-age=86400', (string) $response->headers->get('Cache-Control'));
        $response->assertHeader('X-Bite-QR-Target', route('guest.menu', $shop));
        $response->assertSee('<svg', false);
    }

    public function test_guest_menu_qr_is_not_available_for_suspended_shop(): void
    {
        $shop = Shop::forceCreate([
            'name' => 'Suspended QR',
            'slug' => 'suspended-qr',
            'status' => 'suspended',
            'trial_ends_at' => now()->addDay(),
        ]);

        $this->get(route('guest.menu.qr', $shop))->assertNotFound();
    }
}
