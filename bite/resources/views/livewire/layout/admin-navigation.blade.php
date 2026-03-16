<?php

use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;

new class extends Component
{
    public function switchLocale(string $locale): void
    {
        $locale = in_array($locale, ['en', 'ar']) ? $locale : 'en';
        session()->put('admin_locale', $locale);

        // Full page reload — dir attribute is on <html>
        $this->redirect(request()->header('Referer', route('dashboard')), navigate: false);
    }

    public function logout(Logout $logout): void
    {
        $logout();
        $this->redirect('/', navigate: true);
    }
}; ?>

<div class="relative z-20 shrink-0">
    <div class="border-b border-line/60 bg-ink text-panel md:hidden">
        <div class="flex items-center justify-between px-3 py-2.5 sm:px-4 sm:py-3">
            <div class="flex items-center gap-3">
                <div class="flex h-8 w-8 items-center justify-center rounded-lg border border-panel/15 bg-panel/10">
                    <span class="font-display text-lg font-black text-panel">B</span>
                </div>
                <div>
                    <p class="font-display text-lg font-extrabold leading-none">{{ Auth::user()->shop->name }}</p>
                    <p class="font-mono text-[9px] uppercase tracking-[0.22em] text-panel/55">{{ __('admin.console') }}</p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                {{-- Language Toggle --}}
                <div class="flex items-center gap-0.5 rounded-full border border-panel/15 bg-panel/10 p-0.5">
                    <button wire:click="switchLocale('en')" class="lang-toggle {{ app()->getLocale() === 'en' ? 'lang-toggle-active' : '' }}" type="button">
                        EN
                    </button>
                    <button wire:click="switchLocale('ar')" class="lang-toggle {{ app()->getLocale() === 'ar' ? 'lang-toggle-active' : '' }}" type="button">
                        عربي
                    </button>
                </div>

                <button wire:click="logout" class="btn-secondary !border-panel/30 !bg-panel/10 !px-3 !py-2 !text-panel">
                    {{ __('admin.exit') }}
                </button>
            </div>
        </div>

        <nav class="overflow-x-auto border-t border-panel/10 px-3 py-2.5">
            <div class="flex min-w-max items-center gap-1.5 pr-3">
                <a href="{{ route('dashboard') }}" wire:navigate class="tag {{ request()->routeIs('dashboard') ? '!border-crema !bg-crema !text-panel' : '!bg-panel/10 !text-panel/70 !border-panel/20' }}">{{ __('admin.dashboard') }}</a>
                <a href="{{ route('pos.dashboard') }}" wire:navigate class="tag {{ request()->routeIs('pos.dashboard') ? '!border-crema !bg-crema !text-panel' : '!bg-panel/10 !text-panel/70 !border-panel/20' }}">{{ __('admin.pos_register') }}</a>
                <a href="{{ route('kds.view') }}" wire:navigate class="tag {{ request()->routeIs('kds.view') ? '!border-crema !bg-crema !text-panel' : '!bg-panel/10 !text-panel/70 !border-panel/20' }}">{{ __('admin.kitchen_display') }}</a>
                <a href="{{ route('admin.reports') }}" wire:navigate class="tag {{ request()->routeIs('admin.reports') ? '!border-crema !bg-crema !text-panel' : '!bg-panel/10 !text-panel/70 !border-panel/20' }}">{{ __('admin.reports') }}</a>
                <a href="{{ route('admin.shift-report') }}" wire:navigate class="tag {{ request()->routeIs('admin.shift-report') ? '!border-crema !bg-crema !text-panel' : '!bg-panel/10 !text-panel/70 !border-panel/20' }}">{{ __('admin.shift_report') }}</a>
                <a href="{{ route('admin.pricing-rules') }}" wire:navigate class="tag {{ request()->routeIs('admin.pricing-rules') ? '!border-crema !bg-crema !text-panel' : '!bg-panel/10 !text-panel/70 !border-panel/20' }}">Pricing Rules</a>
                <a href="{{ route('admin.settings') }}" wire:navigate class="tag {{ request()->routeIs('admin.settings') ? '!border-crema !bg-crema !text-panel' : '!bg-panel/10 !text-panel/70 !border-panel/20' }}">{{ __('admin.settings') }}</a>
                @if(Auth::user()->role === 'admin')
                    <a href="{{ route('billing') }}" wire:navigate class="tag {{ request()->routeIs('billing') ? '!border-crema !bg-crema !text-panel' : '!bg-panel/10 !text-panel/70 !border-panel/20' }}">{{ __('admin.billing') }}</a>
                @endif
            </div>
        </nav>
    </div>

    <aside class="hidden h-full w-72 flex-col border-r border-panel/10 bg-ink text-panel md:flex">
        <div class="border-b border-panel/10 p-6">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl border border-panel/20 bg-panel/10">
                    <span class="font-display text-xl font-black text-panel">B</span>
                </div>
                <div class="min-w-0">
                    <p class="truncate font-display text-2xl font-extrabold leading-none">{{ Auth::user()->shop->name }}</p>
                    <p class="mt-1 font-mono text-[10px] uppercase tracking-[0.22em] text-panel/55">{{ __('admin.product_console') }}</p>
                </div>
            </div>

            <div class="mt-5 rounded-xl border border-panel/15 bg-panel/10 p-3">
                <p class="font-mono text-[9px] uppercase tracking-[0.2em] text-panel/50">{{ __('admin.store_state') }}</p>
                <p class="mt-1 flex items-center gap-2 font-mono text-[10px] font-bold uppercase tracking-[0.15em]">
                    <span class="status-dot status-live"></span>
                    {{ __('admin.services_online') }}
                </p>
            </div>
        </div>

        <nav class="flex-1 space-y-6 overflow-y-auto px-4 py-5">
            <section class="space-y-2">
                <p class="px-3 font-mono text-[9px] font-semibold uppercase tracking-[0.24em] text-panel/45">{{ __('admin.operations') }}</p>
                <x-admin-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" icon="dashboard">
                    {{ __('admin.dashboard') }}
                </x-admin-nav-link>
                <x-admin-nav-link :href="route('pos.dashboard')" :active="request()->routeIs('pos.dashboard')" icon="terminal">
                    {{ __('admin.pos_register') }}
                </x-admin-nav-link>
                <x-admin-nav-link :href="route('kds.view')" :active="request()->routeIs('kds.view')" icon="kitchen">
                    {{ __('admin.kitchen_display') }}
                </x-admin-nav-link>
                <x-admin-nav-link :href="route('admin.reports')" :active="request()->routeIs('admin.reports')" icon="dashboard">
                    {{ __('admin.reports') }}
                </x-admin-nav-link>
                <x-admin-nav-link :href="route('admin.shift-report')" :active="request()->routeIs('admin.shift-report')" icon="dashboard">
                    {{ __('admin.shift_report') }}
                </x-admin-nav-link>
                <x-admin-nav-link :href="route('admin.audit-logs')" :active="request()->routeIs('admin.audit-logs')" icon="dashboard">
                    {{ __('admin.audit_logs') }}
                </x-admin-nav-link>
            </section>

            <section class="space-y-2">
                <p class="px-3 font-mono text-[9px] font-semibold uppercase tracking-[0.24em] text-panel/45">{{ __('admin.catalog') }}</p>
                <x-admin-nav-link :href="route('admin.menu-builder')" :active="request()->routeIs('admin.menu-builder')" icon="coffee">
                    {{ __('admin.menu_builder') }}
                </x-admin-nav-link>
                <x-admin-nav-link :href="route('admin.products')" :active="request()->routeIs('admin.products')" icon="coffee">
                    {{ __('admin.product_catalog') }}
                </x-admin-nav-link>
                <x-admin-nav-link :href="route('admin.modifiers')" :active="request()->routeIs('admin.modifiers')" icon="modifiers">
                    {{ __('admin.modifiers') }}
                </x-admin-nav-link>
                <x-admin-nav-link :href="route('admin.pricing-rules')" :active="request()->routeIs('admin.pricing-rules')" icon="dashboard">
                    Pricing Rules
                </x-admin-nav-link>
                <x-admin-nav-link :href="route('guest.menu', Auth::user()->shop->slug)" :active="false" icon="coffee" :navigate="false" target="_blank" rel="noopener">
                    {{ __('admin.guest_menu') }}
                </x-admin-nav-link>
            </section>

            <section class="space-y-2">
                <p class="px-3 font-mono text-[9px] font-semibold uppercase tracking-[0.24em] text-panel/45">{{ __('admin.administration') }}</p>
                <x-admin-nav-link :href="route('admin.settings')" :active="request()->routeIs('admin.settings')" icon="dashboard">
                    {{ __('admin.settings') }}
                </x-admin-nav-link>
                @if(Auth::user()->role === 'admin')
                    <x-admin-nav-link :href="route('billing')" :active="request()->routeIs('billing')" icon="billing">
                        {{ __('admin.billing') }}
                    </x-admin-nav-link>
                @endif
            </section>
        </nav>

        <div class="border-t border-panel/10 p-4">
            <div class="mb-4 flex items-center gap-3 rounded-xl border border-panel/15 bg-panel/10 p-3">
                <div class="flex h-9 w-9 items-center justify-center rounded-full border border-panel/25 bg-panel/10 font-mono text-xs font-bold uppercase">
                    {{ substr(Auth::user()->name, 0, 1) }}
                </div>
                <div class="min-w-0">
                    <p class="truncate font-display text-base font-bold leading-none">{{ Auth::user()->name }}</p>
                    <p class="mt-1 truncate font-mono text-[9px] uppercase tracking-[0.18em] text-panel/55">{{ Auth::user()->email }}</p>
                </div>
            </div>

            {{-- Language Toggle --}}
            <div class="mb-3 flex items-center justify-center gap-0.5 rounded-full border border-panel/15 bg-panel/10 p-0.5">
                <button wire:click="switchLocale('en')" class="lang-toggle {{ app()->getLocale() === 'en' ? 'lang-toggle-active' : '' }}" type="button">
                    EN
                </button>
                <button wire:click="switchLocale('ar')" class="lang-toggle {{ app()->getLocale() === 'ar' ? 'lang-toggle-active' : '' }}" type="button">
                    عربي
                </button>
            </div>

            <button wire:click="logout" class="btn-secondary w-full !border-panel/30 !bg-panel/10 !text-panel">
                {{ __('admin.log_out') }}
            </button>
        </div>
    </aside>
</div>
