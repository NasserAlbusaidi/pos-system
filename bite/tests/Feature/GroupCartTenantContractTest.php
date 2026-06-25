<?php

namespace Tests\Feature;

use App\Models\GroupCart;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class GroupCartTenantContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_shop_id_is_not_mass_assignable(): void
    {
        $cart = new GroupCart;

        $cart->fill([
            'shop_id' => 123,
            'group_token' => (string) Str::uuid(),
            'items' => [],
            'participant_count' => 1,
            'expires_at' => now()->addHour(),
        ]);

        $this->assertNull($cart->shop_id);
        $this->assertNotNull($cart->group_token);
        $this->assertSame([], $cart->items);
        $this->assertSame(1, $cart->participant_count);
    }
}
