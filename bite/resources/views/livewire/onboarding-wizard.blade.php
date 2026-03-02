<div class="min-h-screen flex items-center justify-center p-4 sm:p-6" x-data="{ copied: false }">
    <div class="w-full max-w-2xl fade-rise">

        {{-- Progress Bar --}}
        <div class="mb-8">
            <div class="flex items-center justify-between mb-3">
                @for ($i = 1; $i <= $totalSteps; $i++)
                    <button
                        wire:click="goToStep({{ $i }})"
                        class="flex items-center justify-center w-9 h-9 rounded-full font-mono text-xs font-bold transition-all duration-300
                            {{ $step === $i
                                ? 'bg-ink text-panel shadow-lg'
                                : ($step > $i
                                    ? 'bg-signal text-panel'
                                    : 'bg-panel-muted text-ink-soft border border-line') }}"
                    >
                        @if ($step > $i)
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg>
                        @else
                            {{ $i }}
                        @endif
                    </button>
                    @if ($i < $totalSteps)
                        <div class="flex-1 h-0.5 mx-2 rounded transition-all duration-500 {{ $step > $i ? 'bg-signal' : 'bg-line' }}"></div>
                    @endif
                @endfor
            </div>
            <p class="text-center font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">
                Step {{ $step }} of {{ $totalSteps }}
            </p>
        </div>

        {{-- Card Shell --}}
        <div class="surface-card">

            {{-- ═══════════════════════════════════════════════ --}}
            {{-- STEP 1: Welcome                                --}}
            {{-- ═══════════════════════════════════════════════ --}}
            @if ($step === 1)
                <div class="p-8 sm:p-10 text-center space-y-6">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-ink text-panel mb-2">
                        <span class="font-mono font-black text-2xl">B</span>
                    </div>

                    <div>
                        <h1 class="font-display text-3xl sm:text-4xl font-extrabold text-ink leading-tight">
                            Welcome to Bite!
                        </h1>
                        <p class="mt-3 text-sm text-ink-soft leading-relaxed max-w-md mx-auto">
                            Let's get <strong class="text-ink">{{ $shopName }}</strong> ready to take orders.
                            This quick setup takes about 2 minutes.
                        </p>
                    </div>

                    <div class="grid grid-cols-3 gap-4 text-center pt-2 max-w-sm mx-auto">
                        <div>
                            <div class="w-10 h-10 mx-auto rounded-lg bg-panel-muted flex items-center justify-center mb-2">
                                <svg class="w-5 h-5 text-ink-soft" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </div>
                            <p class="font-mono text-[9px] font-bold uppercase tracking-[0.12em] text-ink-soft">Currency</p>
                        </div>
                        <div>
                            <div class="w-10 h-10 mx-auto rounded-lg bg-panel-muted flex items-center justify-center mb-2">
                                <svg class="w-5 h-5 text-ink-soft" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                            </div>
                            <p class="font-mono text-[9px] font-bold uppercase tracking-[0.12em] text-ink-soft">Menu</p>
                        </div>
                        <div>
                            <div class="w-10 h-10 mx-auto rounded-lg bg-panel-muted flex items-center justify-center mb-2">
                                <svg class="w-5 h-5 text-ink-soft" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                            </div>
                            <p class="font-mono text-[9px] font-bold uppercase tracking-[0.12em] text-ink-soft">Staff</p>
                        </div>
                    </div>

                    <div class="pt-4">
                        <button wire:click="nextStep" class="btn-primary text-sm">
                            Get Started
                        </button>
                    </div>
                </div>
            @endif

            {{-- ═══════════════════════════════════════════════ --}}
            {{-- STEP 2: Shop Profile                           --}}
            {{-- ═══════════════════════════════════════════════ --}}
            @if ($step === 2)
                <div class="border-b border-line bg-muted/30 px-6 py-5">
                    <h2 class="font-display text-2xl font-extrabold leading-none text-ink">Shop Profile</h2>
                    <p class="mt-1 font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">Currency, tax, and branding</p>
                </div>

                <form wire:submit.prevent="saveShopProfile" class="p-6 space-y-6">
                    {{-- Currency --}}
                    <div class="space-y-3">
                        <p class="font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">Currency</p>
                        <div class="grid grid-cols-3 gap-4">
                            <div class="space-y-1.5">
                                <label class="text-xs text-ink-soft">Code</label>
                                <input type="text" wire:model="currency_code" class="field font-mono uppercase" placeholder="OMR" maxlength="3">
                                @error('currency_code') <p class="text-alert text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-xs text-ink-soft">Symbol</label>
                                <input type="text" wire:model="currency_symbol" class="field" placeholder="ر.ع." maxlength="10">
                                @error('currency_symbol') <p class="text-alert text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-xs text-ink-soft">Decimals</label>
                                <select wire:model="currency_decimals" class="field">
                                    <option value="0">0</option>
                                    <option value="2">2</option>
                                    <option value="3">3</option>
                                </select>
                                @error('currency_decimals') <p class="text-alert text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>

                    {{-- Tax Rate --}}
                    <div class="space-y-1.5">
                        <label class="font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">Tax Rate (%)</label>
                        <input type="number" step="0.01" min="0" max="100" wire:model="tax_rate" class="field max-w-[180px]" placeholder="5.00">
                        @error('tax_rate') <p class="text-alert text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- Brand Colors --}}
                    <div class="space-y-3">
                        <p class="font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">Brand Colors</p>
                        <div class="grid grid-cols-3 gap-4">
                            <div class="space-y-1.5">
                                <label class="text-xs text-ink-soft">Accent</label>
                                <div class="flex items-center gap-2">
                                    <input type="color" wire:model="accent" class="h-10 w-12 cursor-pointer rounded border border-line p-0.5">
                                    <input type="text" wire:model="accent" class="field flex-1 font-mono text-xs uppercase" placeholder="#CC5500">
                                </div>
                                @error('accent') <p class="text-alert text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-xs text-ink-soft">Background</label>
                                <div class="flex items-center gap-2">
                                    <input type="color" wire:model="paper" class="h-10 w-12 cursor-pointer rounded border border-line p-0.5">
                                    <input type="text" wire:model="paper" class="field flex-1 font-mono text-xs uppercase" placeholder="#FDFCF8">
                                </div>
                                @error('paper') <p class="text-alert text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-xs text-ink-soft">Text</label>
                                <div class="flex items-center gap-2">
                                    <input type="color" wire:model="ink" class="h-10 w-12 cursor-pointer rounded border border-line p-0.5">
                                    <input type="text" wire:model="ink" class="field flex-1 font-mono text-xs uppercase" placeholder="#1A1918">
                                </div>
                                @error('ink') <p class="text-alert text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="flex items-center justify-between pt-2">
                        <button type="button" wire:click="previousStep" class="btn-secondary">
                            Back
                        </button>
                        <div class="flex items-center gap-3">
                            <button type="button" wire:click="nextStep" class="btn-secondary">
                                Skip
                            </button>
                            <button type="submit" class="btn-primary">
                                <span wire:loading.remove wire:target="saveShopProfile">Save & Continue</span>
                                <span wire:loading wire:target="saveShopProfile" class="inline-flex items-center gap-2">
                                    <span class="loading-spinner"></span>
                                    Saving...
                                </span>
                            </button>
                        </div>
                    </div>
                </form>
            @endif

            {{-- ═══════════════════════════════════════════════ --}}
            {{-- STEP 3: First Menu Items                       --}}
            {{-- ═══════════════════════════════════════════════ --}}
            @if ($step === 3)
                <div class="border-b border-line bg-muted/30 px-6 py-5">
                    <h2 class="font-display text-2xl font-extrabold leading-none text-ink">First Menu Items</h2>
                    <p class="mt-1 font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">Add a few products to get started</p>
                </div>

                <form wire:submit.prevent="saveMenuItems" class="p-6 space-y-4">
                    <p class="text-sm text-ink-soft">
                        Add your first products. They'll be placed in a "Menu" category. You can organize them later in the Menu Builder.
                    </p>

                    <div class="space-y-3">
                        @foreach ($menuItems as $index => $item)
                            <div class="flex items-start gap-3" wire:key="menu-item-{{ $index }}">
                                <div class="flex-1 space-y-1.5">
                                    <input
                                        type="text"
                                        wire:model="menuItems.{{ $index }}.name"
                                        class="field"
                                        placeholder="Item name (e.g. Karak Tea)"
                                    >
                                    @error("menuItems.{$index}.name") <p class="text-alert text-xs mt-1">{{ $message }}</p> @enderror
                                </div>
                                <div class="w-32 space-y-1.5">
                                    <div class="relative">
                                        <input
                                            type="number"
                                            step="0.001"
                                            min="0"
                                            wire:model="menuItems.{{ $index }}.price"
                                            class="field font-mono pr-12"
                                            placeholder="0.000"
                                        >
                                        <span class="absolute right-3 top-1/2 -translate-y-1/2 font-mono text-[10px] font-bold uppercase text-ink-soft">{{ $currency_code }}</span>
                                    </div>
                                    @error("menuItems.{$index}.price") <p class="text-alert text-xs mt-1">{{ $message }}</p> @enderror
                                </div>
                                @if (count($menuItems) > 1)
                                    <button
                                        type="button"
                                        wire:click="removeMenuItem({{ $index }})"
                                        class="mt-2.5 text-ink-soft hover:text-alert transition-colors"
                                        title="Remove item"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                    </button>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    @if (count($menuItems) < 10)
                        <button
                            type="button"
                            wire:click="addMenuItem"
                            class="inline-flex items-center gap-1.5 font-mono text-[10px] font-bold uppercase tracking-[0.16em] text-ink-soft hover:text-ink transition-colors"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                            Add Another Item
                        </button>
                    @endif

                    {{-- Actions --}}
                    <div class="flex items-center justify-between pt-4">
                        <button type="button" wire:click="previousStep" class="btn-secondary">
                            Back
                        </button>
                        <div class="flex items-center gap-3">
                            <button type="button" wire:click="nextStep" class="btn-secondary">
                                Skip
                            </button>
                            <button type="submit" class="btn-primary">
                                <span wire:loading.remove wire:target="saveMenuItems">Save & Continue</span>
                                <span wire:loading wire:target="saveMenuItems" class="inline-flex items-center gap-2">
                                    <span class="loading-spinner"></span>
                                    Saving...
                                </span>
                            </button>
                        </div>
                    </div>
                </form>
            @endif

            {{-- ═══════════════════════════════════════════════ --}}
            {{-- STEP 4: Create Staff PINs                      --}}
            {{-- ═══════════════════════════════════════════════ --}}
            @if ($step === 4)
                <div class="border-b border-line bg-muted/30 px-6 py-5">
                    <h2 class="font-display text-2xl font-extrabold leading-none text-ink">Create Staff PINs</h2>
                    <p class="mt-1 font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">Add team members who will use the POS</p>
                </div>

                <div class="p-6 space-y-6">
                    {{-- Add Staff Form --}}
                    <form wire:submit.prevent="addStaff" class="space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="space-y-1.5">
                                <label class="text-xs text-ink-soft">Name</label>
                                <input type="text" wire:model="staffName" class="field" placeholder="Full name">
                                @error('staffName') <p class="text-alert text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-xs text-ink-soft">Email</label>
                                <input type="email" wire:model="staffEmail" class="field" placeholder="staff@example.com">
                                @error('staffEmail') <p class="text-alert text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="space-y-1.5">
                                <label class="text-xs text-ink-soft">Role</label>
                                <select wire:model="staffRole" class="field">
                                    <option value="manager">Manager</option>
                                    <option value="cashier">Cashier</option>
                                    <option value="kitchen">Kitchen</option>
                                    <option value="server">Server</option>
                                </select>
                                @error('staffRole') <p class="text-alert text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-xs text-ink-soft">4-Digit PIN</label>
                                <input type="text" wire:model="staffPin" class="field font-mono tracking-[0.3em]" placeholder="----" maxlength="4" inputmode="numeric" pattern="[0-9]{4}">
                                @error('staffPin') <p class="text-alert text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>
                        <button type="submit" class="btn-primary">
                            <span wire:loading.remove wire:target="addStaff">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                Add Staff
                            </span>
                            <span wire:loading wire:target="addStaff" class="inline-flex items-center gap-2">
                                <span class="loading-spinner"></span>
                                Adding...
                            </span>
                        </button>
                    </form>

                    {{-- Staff List --}}
                    @if (count($staffMembers) > 0)
                        <div class="border-t border-line pt-4">
                            <p class="section-headline mb-3">Added Staff</p>
                            <div class="space-y-2">
                                @foreach ($staffMembers as $member)
                                    <div class="flex items-center justify-between py-2.5 px-3 rounded-lg bg-panel-muted/30 border border-line/50">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-full bg-ink/10 flex items-center justify-center">
                                                <span class="font-mono text-xs font-bold text-ink">{{ strtoupper(substr($member['name'], 0, 1)) }}</span>
                                            </div>
                                            <div>
                                                <p class="text-sm font-medium text-ink">{{ $member['name'] }}</p>
                                                <p class="font-mono text-[10px] text-ink-soft">{{ $member['email'] }}</p>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="tag">{{ ucfirst($member['role']) }}</span>
                                            @if ($member['has_pin'])
                                                <span class="inline-flex items-center gap-1 text-signal font-mono text-[10px] font-semibold uppercase tracking-[0.12em]">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                                    PIN
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Actions --}}
                    <div class="flex items-center justify-between pt-2">
                        <button type="button" wire:click="previousStep" class="btn-secondary">
                            Back
                        </button>
                        <div class="flex items-center gap-3">
                            <button type="button" wire:click="nextStep" class="btn-secondary">
                                Skip
                            </button>
                            <button type="button" wire:click="saveStaffAndContinue" class="btn-primary">
                                Continue
                            </button>
                        </div>
                    </div>
                </div>
            @endif

            {{-- ═══════════════════════════════════════════════ --}}
            {{-- STEP 5: Done!                                  --}}
            {{-- ═══════════════════════════════════════════════ --}}
            @if ($step === 5)
                <div class="p-8 sm:p-10 space-y-8">
                    <div class="text-center space-y-3">
                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-signal/10 text-signal mb-2">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                        </div>
                        <h2 class="font-display text-3xl font-extrabold text-ink">You're All Set!</h2>
                        <p class="text-sm text-ink-soft max-w-md mx-auto">
                            <strong class="text-ink">{{ $shop->name }}</strong> is ready to go. Share your QR code with customers or jump straight into the POS.
                        </p>
                    </div>

                    {{-- QR Code --}}
                    <div class="flex flex-col items-center gap-4">
                        <div class="rounded-xl border border-line bg-white p-3">
                            <img
                                src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data={{ urlencode($menuUrl) }}"
                                alt="Guest menu QR code"
                                class="w-36 h-36"
                            >
                        </div>
                        <div class="text-center">
                            <p class="font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft mb-1">Guest Menu</p>
                            <p class="text-xs text-ink font-mono break-all">{{ $menuUrl }}</p>
                        </div>
                        <div class="flex flex-wrap gap-2 justify-center">
                            <button
                                x-on:click="navigator.clipboard.writeText('{{ $menuUrl }}').then(() => { copied = true; setTimeout(() => copied = false, 2000) })"
                                class="btn-secondary"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                                <span x-text="copied ? 'Copied!' : 'Copy Link'"></span>
                            </button>
                            <a
                                href="https://api.qrserver.com/v1/create-qr-code/?size=600x600&format=png&data={{ urlencode($menuUrl) }}"
                                download="menu-qr.png"
                                class="btn-secondary"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                Download QR
                            </a>
                        </div>
                    </div>

                    {{-- Demo Menu Button --}}
                    <div class="border-t border-line pt-6">
                        <div class="rounded-xl border border-line bg-panel-muted/20 p-5 text-center space-y-3">
                            <p class="font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">Need sample data?</p>
                            <p class="text-sm text-ink-soft">
                                Load a demo cafe menu with 18 products, 4 categories, and modifier groups to explore all features.
                            </p>
                            @if ($demoMenuLoaded)
                                <div class="inline-flex items-center gap-2 text-signal font-mono text-[11px] font-bold uppercase tracking-[0.12em]">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                    Demo Menu Loaded
                                </div>
                            @else
                                <button wire:click="loadDemoMenu" class="btn-secondary">
                                    <span wire:loading.remove wire:target="loadDemoMenu">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                                        Load Demo Menu
                                    </span>
                                    <span wire:loading wire:target="loadDemoMenu" class="inline-flex items-center gap-2">
                                        <span class="loading-spinner"></span>
                                        Loading...
                                    </span>
                                </button>
                            @endif
                        </div>
                    </div>

                    {{-- Navigation Links --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <button wire:click="completeOnboarding" class="btn-primary w-full justify-center py-3">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" /></svg>
                            Go to Dashboard
                        </button>
                        <a href="{{ route('pos.dashboard') }}" class="btn-secondary w-full justify-center py-3" wire:navigate>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
                            Open POS Terminal
                        </a>
                    </div>
                </div>
            @endif

        </div>

        {{-- Footer branding --}}
        <p class="mt-6 text-center font-mono text-[10px] text-ink-soft/50 uppercase tracking-[0.2em]">
            Powered by Bite
        </p>
    </div>
</div>
