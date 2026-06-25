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

@php
    $user = Auth::user();
    $shop = $user->shop;
    $role = $user->role;
    $canUseDashboard = in_array($role, ['server', 'manager', 'admin'], true);
    $canUsePos = in_array($role, ['server', 'manager', 'admin'], true);
    $canUseKds = in_array($role, ['kitchen', 'manager', 'admin'], true);
    $canManage = in_array($role, ['manager', 'admin'], true);
    $canUseBilling = $role === 'admin';
@endphp

<div class="relative z-20 shrink-0">
    {{-- ===== Mobile top bar ===== --}}
    <div class="bg-forest text-white md:hidden">
        <div class="flex items-center justify-between px-3 py-2.5 sm:px-4 sm:py-3">
            <div class="flex items-center gap-3">
                <div class="flex h-9 w-9 items-center justify-center rounded-[11px] bg-lime">
                    <span class="font-display text-lg font-bold italic leading-none text-forest">B</span>
                </div>
                <div>
                    <p class="font-display text-lg font-bold leading-none">{{ $shop->name }}</p>
                    <p class="mt-1 font-mono text-[9px] uppercase tracking-[0.22em] text-white/55">{{ __('admin.console') }}</p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                {{-- Language Toggle --}}
                <div class="flex items-center gap-0.5 rounded-full border border-white/15 bg-white/10 p-0.5">
                    <button wire:click="switchLocale('en')" class="lang-toggle {{ app()->getLocale() === 'en' ? 'lang-toggle-active' : '!border-transparent !bg-transparent !text-white/70' }}" type="button">EN</button>
                    <button wire:click="switchLocale('ar')" class="lang-toggle {{ app()->getLocale() === 'ar' ? 'lang-toggle-active' : '!border-transparent !bg-transparent !text-white/70' }}" type="button">عربي</button>
                </div>

                <button wire:click="logout" class="btn-secondary !border-white/30 !px-3 !py-2 !text-white">{{ __('admin.exit') }}</button>
            </div>
        </div>

        <nav class="overflow-x-auto border-t border-white/10 px-3 py-2.5">
            <div class="flex min-w-max items-center gap-1.5 pr-3">
                @php $mobileActive = '!border-lime !bg-lime !text-forest'; $mobileIdle = '!bg-white/10 !text-white/70 !border-white/15'; @endphp
                @if($canUseDashboard)
                    <a href="{{ route('dashboard') }}" wire:navigate class="tag {{ request()->routeIs('dashboard') ? $mobileActive : $mobileIdle }}">{{ __('admin.dashboard') }}</a>
                @endif
                @if($canUsePos)
                    <a href="{{ route('pos.dashboard') }}" wire:navigate class="tag {{ request()->routeIs('pos.dashboard') ? $mobileActive : $mobileIdle }}">{{ __('admin.pos_register') }}</a>
                @endif
                @if($canUseKds)
                    <a href="{{ route('kds.view') }}" wire:navigate class="tag {{ request()->routeIs('kds.view') ? $mobileActive : $mobileIdle }}">{{ __('admin.kitchen_display') }}</a>
                @endif
                @if($canManage)
                    <a href="{{ route('admin.reports') }}" wire:navigate class="tag {{ request()->routeIs('admin.reports') ? $mobileActive : $mobileIdle }}">{{ __('admin.reports') }}</a>
                    <a href="{{ route('admin.shift-report') }}" wire:navigate class="tag {{ request()->routeIs('admin.shift-report') ? $mobileActive : $mobileIdle }}">{{ __('admin.shift_report') }}</a>
                    <a href="{{ route('admin.pricing-rules') }}" wire:navigate class="tag {{ request()->routeIs('admin.pricing-rules') ? $mobileActive : $mobileIdle }}">{{ __('admin.pricing_rules') }}</a>
                    <a href="{{ route('admin.settings') }}" wire:navigate class="tag {{ request()->routeIs('admin.settings') ? $mobileActive : $mobileIdle }}">{{ __('admin.settings') }}</a>
                @endif
                @if($canUseBilling)
                    <a href="{{ route('billing') }}" wire:navigate class="tag {{ request()->routeIs('billing') ? $mobileActive : $mobileIdle }}">{{ __('admin.billing') }}</a>
                @endif
            </div>
        </nav>
    </div>

    {{-- ===== Desktop sidebar ===== --}}
    <aside class="hidden h-full w-[270px] flex-col bg-forest text-white md:flex">
        <div class="border-b border-white/10 p-[22px] pb-[18px]">
            <div class="flex items-center gap-3">
                <div class="flex h-[42px] w-[42px] shrink-0 items-center justify-center rounded-[13px] bg-lime">
                    <span class="font-display text-2xl font-bold italic leading-none text-forest">B</span>
                </div>
                <div class="min-w-0">
                    <p class="truncate font-display text-[19px] font-bold leading-none">{{ $shop->name }}</p>
                    <p class="mt-[5px] text-[10px] font-semibold uppercase tracking-[0.2em] text-white/50">{{ __('admin.product_console') }}</p>
                </div>
            </div>

            <div class="mt-4 rounded-xl border border-white/15 bg-white/[0.06] px-[13px] py-[11px]">
                <p class="text-[9px] font-semibold uppercase tracking-[0.2em] text-white/50">{{ __('admin.store_state') }}</p>
                <p class="mt-[7px] flex items-center gap-2 text-[11px] font-bold uppercase tracking-[0.12em] text-lime">
                    <span class="h-2 w-2 rounded-full bg-lime" style="animation: pulseDot 1.8s ease-in-out infinite;"></span>
                    {{ __('admin.services_online') }}
                </p>
            </div>
        </div>

        <nav class="flex-1 space-y-[22px] overflow-y-auto px-3.5 py-[18px]">
            <section class="space-y-1">
                <p class="px-3 pb-1 text-[9px] font-semibold uppercase tracking-[0.22em] text-white/45">{{ __('admin.operations') }}</p>
                @if($canUseDashboard)
                    <x-admin-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" icon="dashboard">{{ __('admin.dashboard') }}</x-admin-nav-link>
                @endif
                @if($canUsePos)
                    <x-admin-nav-link :href="route('pos.dashboard')" :active="request()->routeIs('pos.dashboard')" icon="terminal">{{ __('admin.pos_register') }}</x-admin-nav-link>
                @endif
                @if($canUseKds)
                    <x-admin-nav-link :href="route('kds.view')" :active="request()->routeIs('kds.view')" icon="kitchen">{{ __('admin.kitchen_display') }}</x-admin-nav-link>
                @endif
                @if($canManage)
                    <x-admin-nav-link :href="route('admin.reports')" :active="request()->routeIs('admin.reports')" icon="chart">{{ __('admin.reports') }}</x-admin-nav-link>
                    <x-admin-nav-link :href="route('admin.shift-report')" :active="request()->routeIs('admin.shift-report')" icon="clock">{{ __('admin.shift_report') }}</x-admin-nav-link>
                    <x-admin-nav-link :href="route('admin.audit-logs')" :active="request()->routeIs('admin.audit-logs')" icon="log">{{ __('admin.audit_logs') }}</x-admin-nav-link>
                @endif
            </section>

            @if($canManage)
                <section class="space-y-1">
                    <p class="px-3 pb-1 text-[9px] font-semibold uppercase tracking-[0.22em] text-white/45">{{ __('admin.catalog') }}</p>
                    <x-admin-nav-link :href="route('admin.menu-builder')" :active="request()->routeIs('admin.menu-builder')" icon="catalog">{{ __('admin.menu_builder') }}</x-admin-nav-link>
                    <x-admin-nav-link :href="route('admin.products')" :active="request()->routeIs('admin.products')" icon="coffee">{{ __('admin.product_catalog') }}</x-admin-nav-link>
                    <x-admin-nav-link :href="route('admin.modifiers')" :active="request()->routeIs('admin.modifiers')" icon="modifiers">{{ __('admin.modifiers') }}</x-admin-nav-link>
                    <x-admin-nav-link :href="route('admin.pricing-rules')" :active="request()->routeIs('admin.pricing-rules')" icon="tag">{{ __('admin.pricing_rules') }}</x-admin-nav-link>
                    <x-admin-nav-link :href="route('guest.menu', $shop->slug)" :active="false" icon="qr" :navigate="false" target="_blank" rel="noopener">{{ __('admin.guest_menu') }}</x-admin-nav-link>
                </section>
            @endif

            @if($canManage || $canUseBilling)
                <section class="space-y-1">
                    <p class="px-3 pb-1 text-[9px] font-semibold uppercase tracking-[0.22em] text-white/45">{{ __('admin.administration') }}</p>
                    @if($canManage)
                        <x-admin-nav-link :href="route('admin.settings')" :active="request()->routeIs('admin.settings')" icon="settings">{{ __('admin.settings') }}</x-admin-nav-link>
                    @endif
                    @if($canUseBilling)
                    <x-admin-nav-link :href="route('billing')" :active="request()->routeIs('billing')" icon="billing">{{ __('admin.billing') }}</x-admin-nav-link>
                    @endif
                </section>
            @endif
        </nav>

        <div class="border-t border-white/10 p-3.5">
            <div class="flex items-center gap-[11px] rounded-xl border border-white/15 bg-white/[0.06] px-3 py-2.5">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-white/20 bg-white/12 text-sm font-bold text-white">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>
                <div class="min-w-0 flex-1">
                    <p class="truncate font-display text-sm font-semibold leading-none">{{ $user->name }}</p>
                    <p class="mt-1 truncate text-[10px] uppercase tracking-[0.14em] text-white/55">{{ ucfirst($role) }} · {{ __('admin.console') }}</p>
                </div>
                {{-- Language Toggle --}}
                <div class="flex shrink-0 overflow-hidden rounded-full border border-white/18 text-[10px] font-bold">
                    <button wire:click="switchLocale('en')" type="button" class="px-2 py-1 {{ app()->getLocale() === 'en' ? 'bg-lime text-forest' : 'text-white/70 hover:text-white' }}">EN</button>
                    <button wire:click="switchLocale('ar')" type="button" class="px-2 py-1 {{ app()->getLocale() === 'ar' ? 'bg-lime text-forest' : 'text-white/70 hover:text-white' }}">ع</button>
                </div>
            </div>

            <button wire:click="logout" class="mt-3 w-full rounded-full border-2 border-white/25 px-4 py-2.5 font-mono text-[11px] font-bold uppercase tracking-[0.18em] text-white/80 transition-colors hover:border-white/50 hover:text-white">
                {{ __('admin.log_out') }}
            </button>
        </div>
    </aside>
</div>
