<?php

namespace Tests\Feature\Auth;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response
            ->assertOk()
            ->assertSeeVolt('pages.auth.register');
    }

    public function test_new_users_can_register(): void
    {
        $component = Volt::test('pages.auth.register')
            ->set('name', 'Test User')
            ->set('restaurant_name', 'Test Bistro')
            ->set('email', 'test@example.com')
            ->set('password', 'password')
            ->set('password_confirmation', 'password');

        $component->call('register');

        $component->assertRedirect('/onboarding');

        $user = User::where('email', 'test@example.com')->first();

        $this->assertNotNull($user);
        $this->assertNotNull($user->shop_id);
        $this->assertSame('admin', $user->role);
        $this->assertSame('Test Bistro', $user->shop->name);

        $this->assertAuthenticated();
    }

    public function test_signup_does_not_auto_seed_demo_menu(): void
    {
        $component = Volt::test('pages.auth.register')
            ->set('name', 'No Demo Seed')
            ->set('restaurant_name', 'No Demo Cafe')
            ->set('email', 'nodemo@example.com')
            ->set('password', 'password')
            ->set('password_confirmation', 'password');

        $component->call('register')->assertRedirect('/onboarding');

        $user = User::where('email', 'nodemo@example.com')->firstOrFail();

        $this->assertSame(0, Category::where('shop_id', $user->shop_id)->count());
        $this->assertSame(0, Product::where('shop_id', $user->shop_id)->count());
    }
}
