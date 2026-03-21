<div class="space-y-6 fade-rise">
    <x-slot:header>{{ __('admin.settings_shop_settings') }}</x-slot:header>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        {{-- Left column: Settings form --}}
        <div class="xl:col-span-2 space-y-6">

            {{-- Shop Identity --}}
            <section class="surface-card">
                <div class="border-b border-line bg-muted/30 px-5 py-4">
                    <h2 class="font-display text-2xl font-extrabold leading-none text-ink">{{ __('admin.settings_shop_identity') }}</h2>
                    <p class="mt-1 font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">{{ __('admin.settings_name_branding') }}</p>
                </div>

                <form wire:submit.prevent="save" class="p-5 space-y-6">
                    {{-- Shop Name --}}
                    <div class="space-y-1.5">
                        <label for="shop-name" class="font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">{{ __('admin.settings_shop_name') }}</label>
                        <input id="shop-name" type="text" wire:model="name" class="field" placeholder="{{ __('admin.placeholder_shop_name') }}">
                        @error('name') <p class="text-alert text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- Menu Theme --}}
                    <div x-data="{ previewTheme: @entangle('theme') }" wire:ignore.self>
                        <p class="font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft" style="margin-bottom: 12px;">Menu Theme</p>

                        {{-- Theme Cards — fully inline-styled for reliability --}}
                        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px;">
                            @php
                                $themes = [
                                    'warm' => ['label' => 'Warm', 'bg' => '#f5f0e6', 'card' => '#fffcf8', 'img' => '#e4e3dc', 'text' => '#2c2520'],
                                    'modern' => ['label' => 'Modern', 'bg' => '#ffffff', 'card' => '#ffffff', 'img' => '#e4e3dc', 'text' => '#0f0f0f'],
                                    'dark' => ['label' => 'Dark', 'bg' => '#0e0e12', 'card' => '#1e1e24', 'img' => '#2a2a32', 'text' => '#f0eeea'],
                                ];
                            @endphp

                            @foreach($themes as $themeKey => $t)
                                <button type="button"
                                    x-on:click="previewTheme = '{{ $themeKey }}'; $wire.set('theme', '{{ $themeKey }}')"
                                    :style="previewTheme === '{{ $themeKey }}'
                                        ? 'border: 2px solid rgb(var(--crema)); box-shadow: 0 0 0 3px rgb(236 105 46 / 0.18);'
                                        : 'border: 2px solid rgb(var(--line));'"
                                    style="position: relative; display: block; padding: 0; border-radius: 10px; cursor: pointer; background: rgb(var(--panel)); text-align: center; width: 100%; overflow: hidden; transition: border-color 200ms ease, box-shadow 200ms ease;">

                                    {{-- Checkmark --}}
                                    <span x-show="previewTheme === '{{ $themeKey }}'" x-cloak
                                          style="position: absolute; top: 6px; right: 6px; width: 20px; height: 20px; border-radius: 50%; background: rgb(var(--crema)); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 700; z-index: 2; box-shadow: 0 1px 4px rgb(0 0 0 / 0.25);">&#10003;</span>

                                    {{-- Mockup area --}}
                                    <div style="background: {{ $t['bg'] }}; padding: 10px; aspect-ratio: 4 / 3; display: flex; flex-direction: column; gap: 4px; justify-content: center; overflow: hidden;">
                                        @for($i = 0; $i < ($themeKey === 'warm' ? 2 : 3); $i++)
                                            <div style="background: {{ $t['card'] }}; border-radius: {{ $themeKey === 'warm' ? '4px' : ($themeKey === 'dark' ? '3px' : '0') }}; overflow: hidden; {{ $themeKey === 'modern' ? 'border: 1px solid #ddd;' : '' }} {{ $themeKey === 'dark' ? 'box-shadow: 0 0 6px rgb(200 160 80 / 0.1);' : '' }}">
                                                <div style="height: {{ $themeKey === 'warm' ? '20px' : '14px' }}; background: {{ $t['img'] }};"></div>
                                                <div style="padding: 3px 5px;">
                                                    <div style="height: 3px; width: {{ 40 + ($i * 10) }}%; background: {{ $t['text'] }}; border-radius: 1px; opacity: 0.7;"></div>
                                                </div>
                                            </div>
                                        @endfor
                                    </div>

                                    {{-- Label --}}
                                    <div style="padding: 7px 6px 9px; border-top: 1px solid rgb(var(--line) / 0.4); font-size: 12px; font-weight: 600; color: rgb(var(--ink));">
                                        {{ $t['label'] }}
                                    </div>
                                </button>
                            @endforeach
                        </div>

                        {{-- Live Preview --}}
                        <div style="border: 1px solid rgb(var(--line)); border-radius: 10px; overflow: hidden; margin-top: 14px;"
                             :style="`background: rgb(${
                                 previewTheme === 'dark' ? '14 14 18' :
                                 previewTheme === 'modern' ? '255 255 255' :
                                 '245 240 230'
                             }); transition: background-color 300ms ease;`">
                            <div style="padding: 14px;">
                                <p :style="`font-family: ${
                                       previewTheme === 'dark' ? 'DM Serif Display, Georgia, serif' :
                                       previewTheme === 'modern' ? 'Inter, system-ui, sans-serif' :
                                       'Playfair Display, Georgia, serif'
                                   }; font-size: 14px; font-weight: 600; margin-bottom: 10px; transition: color 300ms ease; color: rgb(${
                                       previewTheme === 'dark' ? '240 238 234' :
                                       previewTheme === 'modern' ? '15 15 15' :
                                       '44 37 32'
                                   });`">Beverages</p>
                                <div :style="`display: grid; grid-template-columns: ${
                                         previewTheme === 'modern' || previewTheme === 'dark' ? '1fr' : 'repeat(2, 1fr)'
                                     }; gap: 8px;`">
                                    @foreach(['Latte' => '1.500', 'Espresso' => '0.900'] as $itemName => $itemPrice)
                                        <div :style="`border-radius: ${
                                                 previewTheme === 'modern' ? '2px' : previewTheme === 'dark' ? '8px' : '12px'
                                             }; overflow: hidden; border: ${
                                                 previewTheme === 'modern' ? '1px solid #c3c7cb' : 'none'
                                             }; background: rgb(${
                                                 previewTheme === 'dark' ? '30 30 36' :
                                                 previewTheme === 'modern' ? '255 255 255' :
                                                 '255 255 252'
                                             }); transition: all 300ms ease;`">
                                            <div :style="`height: 36px; background: rgb(${
                                                     previewTheme === 'dark' ? '40 40 48' : '228 227 220'
                                                 }); transition: background-color 300ms ease;`"></div>
                                            <div style="padding: 8px;">
                                                <div :style="`font-size: 11px; font-weight: 600; transition: color 300ms ease; color: rgb(${
                                                         previewTheme === 'dark' ? '240 238 234' :
                                                         previewTheme === 'modern' ? '15 15 15' :
                                                         '44 37 32'
                                                     });`">{{ $itemName }}</div>
                                                <div :style="`font-size: 9px; font-family: 'JetBrains Mono', monospace; margin-top: 2px; transition: color 300ms ease; color: rgb(${
                                                         previewTheme === 'dark' ? '240 238 234' :
                                                         previewTheme === 'modern' ? '15 15 15' :
                                                         '44 37 32'
                                                     });`">{{ $itemPrice }}</div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Brand Colors --}}
                    <div class="space-y-3">
                        <p class="font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">{{ __('admin.settings_brand_colors') }}</p>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div class="space-y-1.5">
                                <label class="text-xs text-ink-soft">{{ __('admin.settings_accent') }}</label>
                                <div class="flex items-center gap-2">
                                    <input type="color" wire:model="accent" class="h-10 w-12 cursor-pointer rounded border border-line p-0.5">
                                    <input type="text" wire:model="accent" class="field flex-1 font-mono text-xs uppercase" placeholder="#CC5500">
                                </div>
                                @error('accent') <p class="text-alert text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-xs text-ink-soft">{{ __('admin.settings_background') }}</label>
                                <div class="flex items-center gap-2">
                                    <input type="color" wire:model="paper" class="h-10 w-12 cursor-pointer rounded border border-line p-0.5">
                                    <input type="text" wire:model="paper" class="field flex-1 font-mono text-xs uppercase" placeholder="#FDFCF8">
                                </div>
                                @error('paper') <p class="text-alert text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-xs text-ink-soft">{{ __('admin.settings_text') }}</label>
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
                        <label for="tax-rate" class="font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">{{ __('admin.settings_tax_rate') }}</label>
                        <input id="tax-rate" type="number" step="0.01" min="0" max="100" wire:model="tax_rate" class="field max-w-xs" placeholder="5.00">
                        @error('tax_rate') <p class="text-alert text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- Currency Configuration --}}
                    <div class="space-y-3">
                        <p class="font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">{{ __('admin.settings_currency') }}</p>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div class="space-y-1.5">
                                <label for="currency-code" class="text-xs text-ink-soft">{{ __('admin.settings_currency_code') }}</label>
                                <input id="currency-code" type="text" wire:model="currency_code" class="field font-mono uppercase" placeholder="OMR" maxlength="3">
                                @error('currency_code') <p class="text-alert text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div class="space-y-1.5">
                                <label for="currency-symbol" class="text-xs text-ink-soft">{{ __('admin.settings_currency_symbol') }}</label>
                                <input id="currency-symbol" type="text" wire:model="currency_symbol" class="field" placeholder="ر.ع." maxlength="10">
                                @error('currency_symbol') <p class="text-alert text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div class="space-y-1.5">
                                <label for="currency-decimals" class="text-xs text-ink-soft">{{ __('admin.settings_currency_decimals') }}</label>
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
                        <label for="receipt-header" class="font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">{{ __('admin.settings_receipt_header') }}</label>
                        <textarea id="receipt-header" wire:model="receipt_header" rows="3" class="field font-mono text-xs" placeholder="Business Name&#10;123 Street, City&#10;VAT: 1234567890"></textarea>
                        <p class="text-[10px] text-ink-soft/60">{{ __('admin.settings_receipt_header_hint') }}</p>
                        @error('receipt_header') <p class="text-alert text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- Default Language --}}
                    <div class="space-y-1.5">
                        <label for="language" class="font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">{{ __('admin.settings_default_language') }}</label>
                        <select id="language" wire:model="language" class="field max-w-xs">
                            <option value="en">{{ __('admin.settings_english') }}</option>
                            <option value="ar">{{ __('admin.settings_arabic') }}</option>
                        </select>
                        <p class="text-[10px] text-ink-soft/60">{{ __('admin.settings_language_hint') }}</p>
                        @error('language') <p class="text-alert text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex items-center gap-3 pt-2">
                        <button type="submit" class="btn-primary">
                            <span wire:loading.remove wire:target="save">{{ __('admin.settings_save') }}</span>
                            <span wire:loading wire:target="save" class="inline-flex items-center gap-2">
                                <span class="loading-spinner"></span>
                                {{ __('admin.settings_saving') }}
                            </span>
                        </button>
                    </div>
                </form>
            </section>

            {{-- Guest Menu QR Code --}}
            <section class="surface-card">
                <div class="border-b border-line bg-muted/30 px-5 py-4">
                    <h2 class="font-display text-2xl font-extrabold leading-none text-ink">{{ __('admin.settings_guest_menu') }}</h2>
                    <p class="mt-1 font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">{{ __('admin.settings_qr_customers') }}</p>
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
                            <p class="font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">{{ __('admin.settings_menu_url') }}</p>
                            <p class="text-sm text-ink break-all font-mono">{{ $menuUrl }}</p>
                            <div class="flex flex-wrap gap-2 justify-center sm:justify-start">
                                <button
                                    x-on:click="navigator.clipboard.writeText('{{ $menuUrl }}').then(() => { copied = true; setTimeout(() => copied = false, 2000) })"
                                    class="btn-secondary"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                                    <span x-text="copied ? '{{ __('admin.settings_copied') }}' : '{{ __('admin.settings_copy_link') }}'"></span>
                                </button>
                                <a
                                    href="https://api.qrserver.com/v1/create-qr-code/?size=600x600&format=png&data={{ urlencode($menuUrl) }}"
                                    download="menu-qr.png"
                                    class="btn-secondary"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                    {{ __('admin.settings_download_qr') }}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {{-- WhatsApp Notifications --}}
            <section class="surface-card">
                <div class="border-b border-line bg-muted/30 px-5 py-4">
                    <h2 class="font-display text-2xl font-extrabold leading-none text-ink">{{ __('admin.notifications') }}</h2>
                    <p class="mt-1 font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">{{ __('admin.settings_whatsapp_alerts') }}</p>
                </div>

                <div class="p-5 space-y-5">
                    <div class="space-y-1.5">
                        <label for="whatsapp-number" class="font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">{{ __('admin.settings_whatsapp_number') }}</label>
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center rounded-lg border border-line bg-muted/40 px-3 py-2.5 font-mono text-xs font-semibold text-ink-soft">+</span>
                            <input id="whatsapp-number" type="tel" wire:model="whatsapp_number" class="field flex-1 font-mono text-sm" placeholder="96899123456">
                        </div>
                        <p class="text-[10px] text-ink-soft/60">{{ __('admin.settings_whatsapp_hint') }}</p>
                        @error('whatsapp_number') <p class="text-alert text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex items-center justify-between rounded-xl border border-line bg-panel px-4 py-3">
                        <div>
                            <p class="text-sm font-semibold text-ink">{{ __('admin.settings_whatsapp_enable') }}</p>
                            <p class="text-[10px] text-ink-soft">{{ __('admin.settings_whatsapp_receive') }}</p>
                        </div>
                        <label class="relative inline-flex cursor-pointer items-center">
                            <input type="checkbox" wire:model="whatsapp_notifications_enabled" class="peer sr-only">
                            <div class="peer h-6 w-11 rounded-full bg-ink-soft/20 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-line after:bg-panel after:transition-all after:content-[''] peer-checked:bg-signal peer-checked:after:translate-x-full peer-focus:outline-none"></div>
                        </label>
                    </div>

                    @if($whatsapp_number && $whatsapp_notifications_enabled)
                        <div class="flex items-center gap-2 rounded-lg border border-signal/30 bg-signal/10 px-3 py-2">
                            <svg class="h-4 w-4 text-signal" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                            </svg>
                            <span class="font-mono text-[10px] font-semibold uppercase tracking-[0.14em] text-signal">{{ __('admin.settings_whatsapp_active') }} — +{{ $whatsapp_number }}</span>
                        </div>
                    @endif
                </div>
            </section>

            {{-- Staff Management --}}
            <section class="surface-card">
                <div class="border-b border-line bg-muted/30 px-5 py-4">
                    <h2 class="font-display text-2xl font-extrabold leading-none text-ink">{{ __('admin.settings_staff') }}</h2>
                    <p class="mt-1 font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">{{ __('admin.settings_staff_desc') }}</p>
                </div>

                {{-- Add/Edit Staff Form --}}
                <div class="border-b border-line bg-panel-muted/20 p-5">
                    <p class="section-headline mb-3">{{ $editingStaffId ? __('admin.settings_edit_staff') : __('admin.settings_add_staff') }}</p>
                    <form wire:submit.prevent="{{ $editingStaffId ? 'updateStaff' : 'addStaff' }}" class="space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="space-y-1.5">
                                <label for="staff-name" class="text-xs text-ink-soft">{{ __('admin.settings_staff_name') }}</label>
                                <input id="staff-name" type="text" wire:model="staffName" class="field" placeholder="{{ __('admin.placeholder_full_name') }}">
                                @error('staffName') <p class="text-alert text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div class="space-y-1.5">
                                <label for="staff-email" class="text-xs text-ink-soft">{{ __('admin.settings_staff_email') }}</label>
                                <input id="staff-email" type="email" wire:model="staffEmail" class="field" placeholder="{{ __('admin.placeholder_email') }}">
                                @error('staffEmail') <p class="text-alert text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="space-y-1.5">
                                <label for="staff-role" class="text-xs text-ink-soft">{{ __('admin.settings_staff_role') }}</label>
                                <select id="staff-role" wire:model="staffRole" class="field">
                                    <option value="owner">{{ __('admin.settings_role_owner') }}</option>
                                    <option value="manager">{{ __('admin.settings_role_manager') }}</option>
                                    <option value="cashier">{{ __('admin.settings_role_cashier') }}</option>
                                    <option value="kitchen">{{ __('admin.settings_role_kitchen') }}</option>
                                </select>
                                @error('staffRole') <p class="text-alert text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div class="space-y-1.5">
                                <label for="staff-pin" class="text-xs text-ink-soft">{{ __('admin.settings_staff_pin') }}</label>
                                <input id="staff-pin" type="text" wire:model="staffPin" class="field font-mono tracking-[0.3em]" placeholder="----" maxlength="4" inputmode="numeric" pattern="[0-9]{4}">
                                @error('staffPin') <p class="text-alert text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <button type="submit" class="btn-primary">
                                <span wire:loading.remove wire:target="{{ $editingStaffId ? 'updateStaff' : 'addStaff' }}">
                                    {{ $editingStaffId ? __('admin.settings_update_staff') : __('admin.settings_add_staff_btn') }}
                                </span>
                                <span wire:loading wire:target="{{ $editingStaffId ? 'updateStaff' : 'addStaff' }}" class="inline-flex items-center gap-2">
                                    <span class="loading-spinner"></span>
                                    {{ __('admin.settings_saving') }}
                                </span>
                            </button>
                            @if($editingStaffId)
                                <button type="button" wire:click="cancelEditStaff" class="btn-secondary">{{ __('admin.settings_cancel') }}</button>
                            @endif
                        </div>
                    </form>
                </div>

                {{-- Staff Table --}}
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse text-left">
                        <thead>
                            <tr class="border-b border-line bg-panel font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">
                                <th class="px-5 py-3">{{ __('admin.settings_staff_name') }}</th>
                                <th class="px-5 py-3">{{ __('admin.settings_staff_email') }}</th>
                                <th class="px-5 py-3">{{ __('admin.settings_staff_role') }}</th>
                                <th class="px-5 py-3">{{ __('admin.settings_pin_header') }}</th>
                                <th class="px-5 py-3 text-right">{{ __('admin.settings_actions') }}</th>
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
                                                {{ __('admin.settings_pin_set') }}
                                            </span>
                                        @else
                                            <span class="text-ink-soft/50 font-mono text-[10px] font-semibold uppercase tracking-[0.16em]">{{ __('admin.settings_pin_none') }}</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3 text-right">
                                        <div class="flex items-center justify-end gap-2 sm:opacity-0 sm:group-hover:opacity-100 transition-opacity">
                                            <button
                                                wire:click="editStaff({{ $member->id }})"
                                                class="btn-secondary !py-1.5 !px-3 !text-[9px]"
                                            >
                                                {{ __('admin.settings_edit') }}
                                            </button>
                                            @if($member->id !== auth()->id())
                                                <button
                                                    x-on:click="$dispatch('confirm-action', {
                                                        title: '{{ __('admin.settings_remove_staff') }}',
                                                        message: '{{ __('admin.settings_remove_confirm', ['name' => '']) }}' + {{ Js::from($member->name) }},
                                                        action: 'removeStaff',
                                                        actionArgs: [{{ $member->id }}],
                                                        componentId: $wire.id,
                                                        destructive: true,
                                                    })"
                                                    class="btn-danger !py-1.5 !px-3 !text-[9px]"
                                                >
                                                    {{ __('admin.settings_remove') }}
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-5 py-8 text-center text-sm text-ink-soft">{{ __('admin.settings_no_staff') }}</td>
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
                        <h2 class="font-display text-lg font-extrabold leading-none text-ink">{{ __('admin.settings_live_preview') }}</h2>
                        <p class="mt-1 font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">{{ __('admin.settings_brand_appearance') }}</p>
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
                                    {{ __('admin.settings_button_preview') }}
                                </button>
                            </div>
                        </div>

                        <p class="mt-3 font-mono text-[10px] text-ink-soft/60 leading-relaxed">
                            {{ __('admin.settings_changes_propagate') }}
                        </p>
                    </div>
                </section>

                {{-- Quick Stats --}}
                <section class="surface-card p-5">
                    <p class="section-headline">{{ __('admin.settings_quick_info') }}</p>
                    <div class="mt-3 space-y-2.5">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-ink-soft">{{ __('admin.settings_staff_count') }}</span>
                            <span class="font-mono font-bold text-ink">{{ $staff->count() }}</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-ink-soft">{{ __('admin.settings_currency') }}</span>
                            <span class="font-mono font-bold text-ink">{{ $currency_code }} ({{ $currency_symbol }})</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-ink-soft">{{ __('admin.settings_tax_rate_label') }}</span>
                            <span class="font-mono font-bold text-ink">{{ $tax_rate }}%</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-ink-soft">{{ __('admin.language') }}</span>
                            <span class="font-mono font-bold text-ink">{{ $language === 'ar' ? 'العربية' : 'English' }}</span>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>
</div>
