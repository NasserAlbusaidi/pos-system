<?php

use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;

new class extends Component
{
    /**
     * Log the current user out of the application.
     */
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }
}; ?>

<nav x-data="{ open: false }" class="border-b border-line/70 bg-panel/80 backdrop-blur-xl">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 items-center justify-between">
            <div class="flex items-center gap-5">
                <div class="shrink-0">
                    <a href="{{ route('dashboard') }}" wire:navigate class="inline-flex items-center gap-2 rounded-xl border border-line bg-panel px-3 py-2">
                        <span class="inline-flex h-7 w-7 items-center justify-center rounded-md bg-ink text-panel font-display text-lg font-black">B</span>
                        <span class="font-display text-xl font-extrabold tracking-tight text-ink">Bite</span>
                    </a>
                </div>

                <div class="hidden items-center gap-2 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" wire:navigate>
                        {{ __('Dashboard') }}
                    </x-nav-link>
                </div>
            </div>

            <div class="hidden items-center gap-3 sm:flex">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center gap-3 rounded-xl border border-line bg-panel px-3 py-2 text-ink transition duration-150 hover:border-ink-soft">
                            <span class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-line bg-muted font-mono text-xs font-bold uppercase text-ink">
                                {{ substr(auth()->user()->name, 0, 1) }}
                            </span>

                            <div class="text-left leading-tight">
                                <div class="font-display text-base font-bold" x-data="{{ json_encode(['name' => auth()->user()->name]) }}" x-text="name" x-on:profile-updated.window="name = $event.detail.name"></div>
                                <div class="font-mono text-[9px] font-semibold uppercase tracking-[0.18em] text-ink-soft">Account</div>
                            </div>

                            <div class="ms-1">
                                <svg class="h-4 w-4 fill-current text-ink-soft" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile')" wire:navigate>
                            {{ __('Profile') }}
                        </x-dropdown-link>

                        <button wire:click="logout" class="w-full text-start">
                            <x-dropdown-link>
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </button>
                    </x-slot>
                </x-dropdown>
            </div>

            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center rounded-lg border border-line bg-panel p-2 text-ink-soft transition duration-150 hover:border-ink hover:text-ink">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div :class="{'block': open, 'hidden': ! open}" class="hidden border-t border-line/70 bg-panel/95 sm:hidden">
        <div class="space-y-2 p-4">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" wire:navigate>
                {{ __('Dashboard') }}
            </x-responsive-nav-link>
        </div>

        <div class="border-t border-line/70 px-4 py-4">
            <div class="mb-3 rounded-xl border border-line bg-panel p-3">
                <div class="font-display text-xl font-bold text-ink" x-data="{{ json_encode(['name' => auth()->user()->name]) }}" x-text="name" x-on:profile-updated.window="name = $event.detail.name"></div>
                <div class="mt-1 font-mono text-[10px] uppercase tracking-[0.18em] text-ink-soft">{{ auth()->user()->email }}</div>
            </div>

            <div class="space-y-2">
                <x-responsive-nav-link :href="route('profile')" wire:navigate>
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                <button wire:click="logout" class="w-full text-start">
                    <x-responsive-nav-link>
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </button>
            </div>
        </div>
    </div>
</nav>
