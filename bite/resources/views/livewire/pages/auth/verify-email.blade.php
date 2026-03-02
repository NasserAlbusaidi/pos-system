<?php

use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    /**
     * Send an email verification notification to the user.
     */
    public function sendVerification(): void
    {
        if (Auth::user()->hasVerifiedEmail()) {
            $branding = Auth::user()->shop?->branding ?? [];
            $onboardingCompleted = $branding['onboarding_completed'] ?? false;
            $default = $onboardingCompleted
                ? route('dashboard', absolute: false)
                : '/onboarding';

            $this->redirectIntended(default: $default, navigate: true);

            return;
        }

        Auth::user()->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }

    /**
     * Log the current user out of the application.
     */
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }
}; ?>

<div class="space-y-6">
    <div class="space-y-2">
        <p class="section-headline">Email Verification</p>
        <h1 class="font-display text-3xl font-extrabold leading-none text-ink">Confirm Your Email</h1>
        <p class="text-sm leading-relaxed text-ink-soft">
            {{ __('Before getting started, verify your email address using the link we just sent. Need another one? You can resend it below.') }}
        </p>
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="rounded-lg border border-signal/35 bg-signal/10 px-3 py-2 font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-signal">
            {{ __('A new verification link has been sent to the email address you provided during registration.') }}
        </div>
    @endif

    <div class="flex items-center justify-between gap-3 pt-2">
        <x-primary-button wire:click="sendVerification">
            {{ __('Resend Verification Email') }}
        </x-primary-button>

        <button wire:click="logout" type="submit" class="font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft underline decoration-line hover:text-ink">
            {{ __('Log Out') }}
        </button>
    </div>
</div>
