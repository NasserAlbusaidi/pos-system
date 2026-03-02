<?php

use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;

new class extends Component
{
    public function logout(Logout $logout): void
    {
        $logout();
        $this->redirect('/', navigate: true);
    }
}; ?>

<div class="relative z-20 shrink-0">
    <div class="border-b border-line/60 bg-ink text-panel md:hidden">
        <div class="flex items-center justify-between px-4 py-3">
            <div class="flex items-center gap-3">
                <div class="flex h-8 w-8 items-center justify-center rounded-lg border border-panel/15 bg-panel/10">
                    <span class="font-display text-lg font-black text-panel">B</span>
                </div>
                <div>
                    <p class="font-display text-lg font-extrabold leading-none">{{ Auth::user()->shop->name }}</p>
                    <p class="font-mono text-[9px] uppercase tracking-[0.22em] text-panel/55">Bite Console</p>
                </div>
            </div>

            <button wire:click="logout" class="btn-secondary !border-panel/30 !bg-panel/10 !px-3 !py-2 !text-panel">
                Exit
            </button>
        </div>

        <nav class="overflow-x-auto border-t border-panel/10 px-3 py-3">
            <div class="flex min-w-max items-center gap-2">
                <a href="{{ route('dashboard') }}" wire:navigate class="tag {{ request()->routeIs('dashboard') ? '!border-crema !bg-crema !text-panel' : '!bg-panel/10 !text-panel/70 !border-panel/20' }}">Dashboard</a>
                <a href="{{ route('pos.dashboard') }}" wire:navigate class="tag {{ request()->routeIs('pos.dashboard') ? '!border-crema !bg-crema !text-panel' : '!bg-panel/10 !text-panel/70 !border-panel/20' }}">POS</a>
                <a href="{{ route('kds.view') }}" wire:navigate class="tag {{ request()->routeIs('kds.view') ? '!border-crema !bg-crema !text-panel' : '!bg-panel/10 !text-panel/70 !border-panel/20' }}">Kitchen</a>
                <a href="{{ route('admin.reports') }}" wire:navigate class="tag {{ request()->routeIs('admin.reports') ? '!border-crema !bg-crema !text-panel' : '!bg-panel/10 !text-panel/70 !border-panel/20' }}">Reports</a>
                <a href="{{ route('admin.shift-report') }}" wire:navigate class="tag {{ request()->routeIs('admin.shift-report') ? '!border-crema !bg-crema !text-panel' : '!bg-panel/10 !text-panel/70 !border-panel/20' }}">Shift Report</a>
                <a href="{{ route('admin.settings') }}" wire:navigate class="tag {{ request()->routeIs('admin.settings') ? '!border-crema !bg-crema !text-panel' : '!bg-panel/10 !text-panel/70 !border-panel/20' }}">Settings</a>
                @if(Auth::user()->role === 'admin')
                    <a href="{{ route('billing') }}" wire:navigate class="tag {{ request()->routeIs('billing') ? '!border-crema !bg-crema !text-panel' : '!bg-panel/10 !text-panel/70 !border-panel/20' }}">Billing</a>
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
                    <p class="mt-1 font-mono text-[10px] uppercase tracking-[0.22em] text-panel/55">Bite Product Console</p>
                </div>
            </div>

            <div class="mt-5 rounded-xl border border-panel/15 bg-panel/10 p-3">
                <p class="font-mono text-[9px] uppercase tracking-[0.2em] text-panel/50">Store State</p>
                <p class="mt-1 flex items-center gap-2 font-mono text-[10px] font-bold uppercase tracking-[0.15em]">
                    <span class="status-dot status-live"></span>
                    Services Online
                </p>
            </div>
        </div>

        <nav class="flex-1 space-y-6 overflow-y-auto px-4 py-5">
            <section class="space-y-2">
                <p class="px-3 font-mono text-[9px] font-semibold uppercase tracking-[0.24em] text-panel/45">Operations</p>
                <x-admin-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" icon="dashboard">
                    Dashboard
                </x-admin-nav-link>
                <x-admin-nav-link :href="route('pos.dashboard')" :active="request()->routeIs('pos.dashboard')" icon="terminal">
                    POS Register
                </x-admin-nav-link>
                <x-admin-nav-link :href="route('kds.view')" :active="request()->routeIs('kds.view')" icon="kitchen">
                    Kitchen Display
                </x-admin-nav-link>
                <x-admin-nav-link :href="route('admin.reports')" :active="request()->routeIs('admin.reports')" icon="dashboard">
                    Reports
                </x-admin-nav-link>
                <x-admin-nav-link :href="route('admin.shift-report')" :active="request()->routeIs('admin.shift-report')" icon="dashboard">
                    Shift Report
                </x-admin-nav-link>
                <x-admin-nav-link :href="route('admin.inventory')" :active="request()->routeIs('admin.inventory')" icon="dashboard">
                    Inventory
                </x-admin-nav-link>
                <x-admin-nav-link :href="route('admin.audit-logs')" :active="request()->routeIs('admin.audit-logs')" icon="dashboard">
                    Audit Logs
                </x-admin-nav-link>
            </section>

            <section class="space-y-2">
                <p class="px-3 font-mono text-[9px] font-semibold uppercase tracking-[0.24em] text-panel/45">Catalog</p>
                <x-admin-nav-link :href="route('admin.menu-builder')" :active="request()->routeIs('admin.menu-builder')" icon="coffee">
                    Menu Builder
                </x-admin-nav-link>
                <x-admin-nav-link :href="route('admin.products')" :active="request()->routeIs('admin.products')" icon="coffee">
                    Product Catalog
                </x-admin-nav-link>
                <x-admin-nav-link :href="route('admin.modifiers')" :active="request()->routeIs('admin.modifiers')" icon="modifiers">
                    Modifiers
                </x-admin-nav-link>
                <x-admin-nav-link :href="route('guest.menu', Auth::user()->shop->slug)" :active="false" icon="coffee" :navigate="false" target="_blank" rel="noopener">
                    Guest Menu
                </x-admin-nav-link>
            </section>

            <section class="space-y-2">
                <p class="px-3 font-mono text-[9px] font-semibold uppercase tracking-[0.24em] text-panel/45">Administration</p>
                <x-admin-nav-link :href="route('admin.settings')" :active="request()->routeIs('admin.settings')" icon="dashboard">
                    Settings
                </x-admin-nav-link>
                @if(Auth::user()->role === 'admin')
                    <x-admin-nav-link :href="route('billing')" :active="request()->routeIs('billing')" icon="billing">
                        Billing
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

            <button wire:click="logout" class="btn-secondary w-full !border-panel/30 !bg-panel/10 !text-panel">
                Log Out
            </button>
        </div>
    </aside>
</div>
