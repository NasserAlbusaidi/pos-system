<div class="space-y-6 fade-rise">
    <x-slot:header>Shop Settings</x-slot:header>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        {{-- Left column: Settings form --}}
        <div class="xl:col-span-2 space-y-6">

            {{-- Shop Identity --}}
            <section class="surface-card">
                <div class="border-b border-line bg-muted/30 px-5 py-4">
                    <h2 class="font-display text-2xl font-extrabold leading-none text-ink">Shop Identity</h2>
                    <p class="mt-1 font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">Name and branding</p>
                </div>

                <form wire:submit.prevent="save" class="p-5 space-y-6">
                    {{-- Shop Name --}}
                    <div class="space-y-1.5">
                        <label for="shop-name" class="font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">Shop Name</label>
                        <input id="shop-name" type="text" wire:model="name" class="field" placeholder="My Coffee Shop">
                        @error('name') <p class="text-alert text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- Brand Colors --}}
                    <div class="space-y-3">
                        <p class="font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">Brand Colors</p>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
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

                    {{-- Tax Rate --}}
                    <div class="space-y-1.5">
                        <label for="tax-rate" class="font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">Tax Rate (%)</label>
                        <input id="tax-rate" type="number" step="0.01" min="0" max="100" wire:model="tax_rate" class="field max-w-xs" placeholder="5.00">
                        @error('tax_rate') <p class="text-alert text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- Currency Configuration --}}
                    <div class="space-y-3">
                        <p class="font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">Currency</p>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div class="space-y-1.5">
                                <label for="currency-code" class="text-xs text-ink-soft">Code</label>
                                <input id="currency-code" type="text" wire:model="currency_code" class="field font-mono uppercase" placeholder="OMR" maxlength="5">
                                @error('currency_code') <p class="text-alert text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div class="space-y-1.5">
                                <label for="currency-symbol" class="text-xs text-ink-soft">Symbol</label>
                                <input id="currency-symbol" type="text" wire:model="currency_symbol" class="field" placeholder="ر.ع." maxlength="10">
                                @error('currency_symbol') <p class="text-alert text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div class="space-y-1.5">
                                <label for="currency-decimals" class="text-xs text-ink-soft">Decimals</label>
                                <select id="currency-decimals" wire:model="currency_decimals" class="field">
                                    <option value="0">0</option>
                                    <option value="2">2</option>
                                    <option value="3">3</option>
                                </select>
                                @error('currency_decimals') <p class="text-alert text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>

                    {{-- Receipt Header --}}
                    <div class="space-y-1.5">
                        <label for="receipt-header" class="font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">Receipt Header</label>
                        <textarea id="receipt-header" wire:model="receipt_header" rows="3" class="field font-mono text-xs" placeholder="Business Name&#10;123 Street, City&#10;VAT: 1234567890"></textarea>
                        <p class="text-[10px] text-ink-soft/60">Printed at the top of customer receipts. Include business name, address, VAT number, etc.</p>
                        @error('receipt_header') <p class="text-alert text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex items-center gap-3 pt-2">
                        <button type="submit" class="btn-primary">
                            <span wire:loading.remove wire:target="save">Save Settings</span>
                            <span wire:loading wire:target="save" class="inline-flex items-center gap-2">
                                <span class="loading-spinner"></span>
                                Saving...
                            </span>
                        </button>
                    </div>
                </form>
            </section>

            {{-- Guest Menu QR Code --}}
            <section class="surface-card">
                <div class="border-b border-line bg-muted/30 px-5 py-4">
                    <h2 class="font-display text-2xl font-extrabold leading-none text-ink">Guest Menu</h2>
                    <p class="mt-1 font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">QR code and link for customers</p>
                </div>

                <div class="p-5" x-data="{ copied: false }">
                    <div class="flex flex-col sm:flex-row items-center gap-6">
                        <div class="shrink-0">
                            <img
                                src="https://api.qrserver.com/v1/create-qr-code/?size=300x300&data={{ urlencode($menuUrl) }}"
                                alt="Guest menu QR code"
                                class="w-40 h-40 rounded-lg border border-line bg-white p-2"
                            >
                        </div>
                        <div class="flex-1 space-y-3 text-center sm:text-left">
                            <p class="font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">Menu URL</p>
                            <p class="text-sm text-ink break-all font-mono">{{ $menuUrl }}</p>
                            <div class="flex flex-wrap gap-2 justify-center sm:justify-start">
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
                    </div>
                </div>
            </section>

            {{-- Staff Management --}}
            <section class="surface-card">
                <div class="border-b border-line bg-muted/30 px-5 py-4">
                    <h2 class="font-display text-2xl font-extrabold leading-none text-ink">Staff</h2>
                    <p class="mt-1 font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">Manage team members and roles</p>
                </div>

                {{-- Add/Edit Staff Form --}}
                <div class="border-b border-line bg-panel-muted/20 p-5">
                    <p class="section-headline mb-3">{{ $editingStaffId ? 'Edit Staff Member' : 'Add Staff Member' }}</p>
                    <form wire:submit.prevent="{{ $editingStaffId ? 'updateStaff' : 'addStaff' }}" class="space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="space-y-1.5">
                                <label for="staff-name" class="text-xs text-ink-soft">Name</label>
                                <input id="staff-name" type="text" wire:model="staffName" class="field" placeholder="Full name">
                                @error('staffName') <p class="text-alert text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div class="space-y-1.5">
                                <label for="staff-email" class="text-xs text-ink-soft">Email</label>
                                <input id="staff-email" type="email" wire:model="staffEmail" class="field" placeholder="staff@example.com">
                                @error('staffEmail') <p class="text-alert text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="space-y-1.5">
                                <label for="staff-role" class="text-xs text-ink-soft">Role</label>
                                <select id="staff-role" wire:model="staffRole" class="field">
                                    <option value="owner">Owner</option>
                                    <option value="manager">Manager</option>
                                    <option value="cashier">Cashier</option>
                                    <option value="kitchen">Kitchen</option>
                                </select>
                                @error('staffRole') <p class="text-alert text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div class="space-y-1.5">
                                <label for="staff-pin" class="text-xs text-ink-soft">PIN Code (4 digits)</label>
                                <input id="staff-pin" type="text" wire:model="staffPin" class="field font-mono tracking-[0.3em]" placeholder="----" maxlength="4" inputmode="numeric" pattern="[0-9]{4}">
                                @error('staffPin') <p class="text-alert text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <button type="submit" class="btn-primary">
                                <span wire:loading.remove wire:target="{{ $editingStaffId ? 'updateStaff' : 'addStaff' }}">
                                    {{ $editingStaffId ? 'Update Staff' : 'Add Staff' }}
                                </span>
                                <span wire:loading wire:target="{{ $editingStaffId ? 'updateStaff' : 'addStaff' }}" class="inline-flex items-center gap-2">
                                    <span class="loading-spinner"></span>
                                    Saving...
                                </span>
                            </button>
                            @if($editingStaffId)
                                <button type="button" wire:click="cancelEditStaff" class="btn-secondary">Cancel</button>
                            @endif
                        </div>
                    </form>
                </div>

                {{-- Staff Table --}}
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse text-left">
                        <thead>
                            <tr class="border-b border-line bg-panel font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">
                                <th class="px-5 py-3">Name</th>
                                <th class="px-5 py-3">Email</th>
                                <th class="px-5 py-3">Role</th>
                                <th class="px-5 py-3">PIN</th>
                                <th class="px-5 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @forelse($staff as $member)
                                <tr class="group transition-colors hover:bg-muted/30">
                                    <td class="px-5 py-3 text-sm font-medium text-ink">{{ $member->name }}</td>
                                    <td class="px-5 py-3 text-sm text-ink-soft">{{ $member->email }}</td>
                                    <td class="px-5 py-3">
                                        <span class="tag">{{ ucfirst($member->role ?? 'staff') }}</span>
                                    </td>
                                    <td class="px-5 py-3">
                                        @if($member->pin_code)
                                            <span class="inline-flex items-center gap-1 text-signal font-mono text-[10px] font-semibold uppercase tracking-[0.16em]">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                                Set
                                            </span>
                                        @else
                                            <span class="text-ink-soft/50 font-mono text-[10px] font-semibold uppercase tracking-[0.16em]">None</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3 text-right">
                                        <div class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                            <button
                                                wire:click="editStaff({{ $member->id }})"
                                                class="btn-secondary !py-1.5 !px-3 !text-[9px]"
                                            >
                                                Edit
                                            </button>
                                            @if($member->id !== auth()->id())
                                                <button
                                                    x-on:click="$dispatch('confirm-action', {
                                                        title: 'Remove Staff',
                                                        message: 'Remove {{ addslashes($member->name) }} from this shop? This cannot be undone.',
                                                        action: 'removeStaff',
                                                        actionArgs: [{{ $member->id }}],
                                                        componentId: $wire.id,
                                                        destructive: true,
                                                    })"
                                                    class="btn-danger !py-1.5 !px-3 !text-[9px]"
                                                >
                                                    Remove
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-5 py-8 text-center text-sm text-ink-soft">No staff members found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        {{-- Right column: Live preview --}}
        <div class="xl:col-span-1">
            <div class="sticky top-24 space-y-6">
                <section class="surface-card overflow-hidden">
                    <div class="border-b border-line bg-muted/30 px-5 py-4">
                        <h2 class="font-display text-lg font-extrabold leading-none text-ink">Live Preview</h2>
                        <p class="mt-1 font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">Brand appearance</p>
                    </div>

                    <div class="p-5">
                        <div class="rounded-lg border border-line overflow-hidden p-6 space-y-5"
                             style="background-color: {{ $paper }}; color: {{ $ink }};">

                            <div class="flex items-center gap-3">
                                <div class="w-7 h-7 rounded flex items-center justify-center" style="background-color: {{ $ink }}; color: {{ $paper }};">
                                    <span class="font-mono font-black text-xs">B</span>
                                </div>
                                <h3 class="font-mono font-black text-sm uppercase tracking-widest">{{ $name }}</h3>
                            </div>

                            <div class="space-y-3">
                                <div class="h-2 w-20 rounded" style="background-color: {{ $ink }}; opacity: 0.1;"></div>
                                <div class="h-8 w-full rounded border" style="border-color: {{ $ink }};"></div>
                                <button class="w-full py-2.5 rounded font-mono font-black text-[9px] uppercase tracking-widest transition-all"
                                        style="background-color: {{ $accent }}; color: {{ $paper }};">
                                    Button Preview
                                </button>
                            </div>
                        </div>

                        <p class="mt-3 font-mono text-[10px] text-ink-soft/60 leading-relaxed">
                            Changes propagate instantly to the Guest Menu and Order Tracker.
                        </p>
                    </div>
                </section>

                {{-- Quick Stats --}}
                <section class="surface-card p-5">
                    <p class="section-headline">Quick Info</p>
                    <div class="mt-3 space-y-2.5">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-ink-soft">Staff Count</span>
                            <span class="font-mono font-bold text-ink">{{ $staff->count() }}</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-ink-soft">Currency</span>
                            <span class="font-mono font-bold text-ink">{{ $currency_code }} ({{ $currency_symbol }})</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-ink-soft">Tax Rate</span>
                            <span class="font-mono font-bold text-ink">{{ $tax_rate }}%</span>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>
</div>
