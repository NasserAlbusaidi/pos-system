<?php

use App\Mail\WelcomeTo;
use App\Models\User;
use App\Services\ShopProvisioningService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $name = '';
    public string $restaurant_name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    /**
     * Handle an incoming registration request.
     */
    public function register(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'restaurant_name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ]);

        $shopName = ! empty($validated['restaurant_name'])
            ? $validated['restaurant_name']
            : null;

        $user = app(ShopProvisioningService::class)->provisionOwner(
            name: $validated['name'],
            email: $validated['email'],
            password: Hash::make($validated['password']),
            shopName: $shopName,
        );

        // Send welcome email (queued)
        Mail::to($user)->queue(new WelcomeTo($user));

        Auth::login($user);

        $this->redirect('/onboarding', navigate: true);
    }
}; ?>

<div class="space-y-6">
    <div class="space-y-2">
        <p class="section-headline">Get Started Free</p>
        <h1 class="font-display text-3xl font-extrabold leading-none text-ink">Create Your Shop</h1>
        <p class="text-sm leading-relaxed text-ink-soft">
            Start your 14-day free trial. No credit card required.
        </p>
    </div>

    <form wire:submit="register" class="space-y-4">
        <div>
            <x-input-label for="name" :value="__('Your Name')" />
            <x-text-input wire:model="name" id="name" class="mt-1 block w-full" type="text" name="name" required autofocus autocomplete="name" placeholder="e.g. Nasser Al Busaidi" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="restaurant_name" :value="__('Restaurant Name')" />
            <x-text-input wire:model="restaurant_name" id="restaurant_name" class="mt-1 block w-full" type="text" name="restaurant_name" autocomplete="organization" placeholder="Optional — defaults to your name's Restaurant" />
            <x-input-error :messages="$errors->get('restaurant_name')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input wire:model="email" id="email" class="mt-1 block w-full" type="email" name="email" required autocomplete="username" placeholder="you@restaurant.com" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input wire:model="password" id="password" class="mt-1 block w-full"
                            type="password"
                            name="password"
                            required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
            <x-text-input wire:model="password_confirmation" id="password_confirmation" class="mt-1 block w-full"
                            type="password"
                            name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="pt-2">
            <x-primary-button class="w-full justify-center">
                {{ __('Start Free Trial') }}
            </x-primary-button>
        </div>

        <div class="flex items-center justify-center gap-3">
            <a class="font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft underline decoration-line hover:text-ink" href="{{ route('login') }}" wire:navigate>
                {{ __('Already have an account? Sign in') }}
            </a>
        </div>
    </form>

    <div class="border-t border-line pt-4">
        <div class="flex items-center gap-2">
            <span class="status-dot status-live"></span>
            <span class="font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">
                Used by restaurants across Oman
            </span>
        </div>
    </div>
</div>
