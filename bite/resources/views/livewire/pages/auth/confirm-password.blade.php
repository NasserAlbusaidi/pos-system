<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $password = '';

    /**
     * Confirm the current user's password.
     */
    public function confirmPassword(): void
    {
        $this->validate([
            'password' => ['required', 'string'],
        ]);

        if (! Auth::guard('web')->validate([
            'email' => Auth::user()->email,
            'password' => $this->password,
        ])) {
            throw ValidationException::withMessages([
                'password' => __('auth.password'),
            ]);
        }

        session(['auth.password_confirmed_at' => time()]);

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div class="space-y-6">
    <div class="space-y-2">
        <p class="section-headline">Secure Action</p>
        <h1 class="font-display text-3xl font-extrabold leading-none text-ink">Confirm Password</h1>
        <p class="text-sm leading-relaxed text-ink-soft">
            {{ __('This is a secure area of the application. Confirm your password before continuing.') }}
        </p>
    </div>

    <form wire:submit="confirmPassword" class="space-y-4">
        <div>
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input wire:model="password"
                          id="password"
                          class="mt-1 block w-full"
                          type="password"
                          name="password"
                          required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="flex justify-end pt-2">
            <x-primary-button>
                {{ __('Confirm') }}
            </x-primary-button>
        </div>
    </form>
</div>
