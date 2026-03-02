<?php

use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public LoginForm $form;

    /**
     * Handle an incoming authentication request.
     */
    public function login(): void
    {
        $this->validate();

        $this->form->authenticate();

        Session::regenerate();

        $user = auth()->user();
        
        if ($user->is_super_admin) {
            $this->redirectIntended(default: route('super-admin.dashboard', absolute: false), navigate: true);

            return;
        }

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div class="space-y-6">
    <div class="space-y-2">
        <p class="section-headline">Welcome Back</p>
        <h1 class="font-display text-3xl font-extrabold leading-none text-ink">Sign In</h1>
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form wire:submit="login" class="space-y-4">
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input wire:model="form.email" id="email" class="mt-1 block w-full" type="email" name="email" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('form.email')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input wire:model="form.password" id="password" class="mt-1 block w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('form.password')" class="mt-2" />
        </div>

        <div class="block">
            <label for="remember" class="inline-flex items-center gap-2">
                <input wire:model="form.remember" id="remember" type="checkbox" class="h-4 w-4 rounded border-line text-crema focus:ring-0" name="remember">
                <span class="font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">{{ __('Remember me') }}</span>
            </label>
        </div>

        <div class="flex items-center justify-between gap-3 pt-2">
            @if (Route::has('password.request'))
                <a class="font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft underline decoration-line hover:text-ink" href="{{ route('password.request') }}" wire:navigate>
                    {{ __('Forgot your password?') }}
                </a>
            @endif

            <x-primary-button class="ms-auto">
                {{ __('Log in') }}
            </x-primary-button>
        </div>
    </form>
</div>
