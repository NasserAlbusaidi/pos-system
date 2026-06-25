<?php

namespace Tests\Feature\Auth;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Volt\Volt;
use RuntimeException;
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
            ->set('password', 'launch-password')
            ->set('password_confirmation', 'launch-password');

        $component->call('register');

        $component->assertRedirect('/onboarding');

        $user = User::where('email', 'test@example.com')->first();

        $this->assertNotNull($user);
        $this->assertNotNull($user->shop_id);
        $this->assertSame('admin', $user->role);
        $this->assertSame('Test Bistro', $user->shop->name);
        $this->assertSame('trial', $user->shop->status);
        $this->assertTrue($user->shop->trial_ends_at->isFuture());

        $this->assertAuthenticated();
    }

    public function test_registration_rejects_weak_owner_password(): void
    {
        Volt::test('pages.auth.register')
            ->set('name', 'Weak Owner')
            ->set('restaurant_name', 'Weak Cafe')
            ->set('email', 'weak-owner@example.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('register')
            ->assertHasErrors(['password']);

        $this->assertDatabaseMissing('users', ['email' => 'weak-owner@example.com']);
    }

    public function test_registration_auto_login_rotates_csrf_token(): void
    {
        $this->startSession();
        $tokenBefore = session()->token();

        Volt::test('pages.auth.register')
            ->set('name', 'Token Rotate')
            ->set('restaurant_name', 'Token Cafe')
            ->set('email', 'token-rotate@example.com')
            ->set('password', 'launch-password')
            ->set('password_confirmation', 'launch-password')
            ->call('register')
            ->assertRedirect('/onboarding');

        $this->assertAuthenticated();
        $this->assertNotSame($tokenBefore, session()->token());
    }

    public function test_signup_does_not_auto_seed_demo_menu(): void
    {
        $component = Volt::test('pages.auth.register')
            ->set('name', 'No Demo Seed')
            ->set('restaurant_name', 'No Demo Cafe')
            ->set('email', 'nodemo@example.com')
            ->set('password', 'launch-password')
            ->set('password_confirmation', 'launch-password');

        $component->call('register')->assertRedirect('/onboarding');

        $user = User::where('email', 'nodemo@example.com')->firstOrFail();

        $this->assertSame(0, Category::where('shop_id', $user->shop_id)->count());
        $this->assertSame(0, Product::where('shop_id', $user->shop_id)->count());
    }

    public function test_registration_still_succeeds_when_welcome_email_queue_fails(): void
    {
        Mail::shouldReceive('to')
            ->once()
            ->andThrow(new RuntimeException('Mail transport unavailable'));

        Volt::test('pages.auth.register')
            ->set('name', 'Mail Resilient Owner')
            ->set('restaurant_name', 'Mail Resilient Cafe')
            ->set('email', 'mail-resilient@example.com')
            ->set('password', 'launch-password')
            ->set('password_confirmation', 'launch-password')
            ->call('register')
            ->assertRedirect('/onboarding');

        $user = User::where('email', 'mail-resilient@example.com')->first();

        $this->assertNotNull($user);
        $this->assertSame('Mail Resilient Cafe', $user->shop->name);
        $this->assertAuthenticatedAs($user);
    }
}
