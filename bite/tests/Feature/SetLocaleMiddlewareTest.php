<?php

namespace Tests\Feature;

use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SetLocaleMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_locale_defaults_to_shop_branding_language(): void
    {
        $shop = Shop::factory()->create([
            'branding' => ['language' => 'ar'],
        ]);
        $user = User::factory()->create(['shop_id' => $shop->id]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $this->assertEquals('ar', app()->getLocale());
    }

    public function test_admin_locale_defaults_to_english_when_no_branding(): void
    {
        $shop = Shop::factory()->create(['branding' => null]);
        $user = User::factory()->create(['shop_id' => $shop->id]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $this->assertEquals('en', app()->getLocale());
    }

    public function test_admin_session_override_takes_priority(): void
    {
        $shop = Shop::factory()->create([
            'branding' => ['language' => 'en'],
        ]);
        $user = User::factory()->create(['shop_id' => $shop->id]);

        $response = $this->actingAs($user)
            ->withSession(['admin_locale' => 'ar'])
            ->get(route('dashboard'));

        $response->assertOk();
        $this->assertEquals('ar', app()->getLocale());
    }

    public function test_guest_locale_from_session(): void
    {
        $shop = Shop::factory()->create([
            'branding' => ['language' => 'en'],
        ]);

        $response = $this->withSession(['guest_locale' => 'ar'])
            ->get(route('guest.menu', $shop->slug));

        $response->assertOk();
        $this->assertEquals('ar', app()->getLocale());
    }

    public function test_super_admin_always_english(): void
    {
        $shop = Shop::factory()->create([
            'branding' => ['language' => 'ar'],
        ]);
        $user = User::factory()->create([
            'shop_id' => $shop->id,
            'is_super_admin' => true,
        ]);

        $response = $this->actingAs($user)->get(route('super-admin.dashboard'));

        $response->assertOk();
        $this->assertEquals('en', app()->getLocale());
    }

    public function test_invalid_locale_falls_back_to_english(): void
    {
        $shop = Shop::factory()->create([
            'branding' => ['language' => 'fr'],
        ]);
        $user = User::factory()->create(['shop_id' => $shop->id]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $this->assertEquals('en', app()->getLocale());
    }
}
