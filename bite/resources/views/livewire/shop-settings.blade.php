<div class="fade-rise">
    <x-slot:header>{{ __('admin.settings_shop_settings') }}</x-slot:header>

    <div class="grid grid-cols-1 gap-[18px] xl:grid-cols-[220px_minmax(0,1fr)] xl:items-start">

        {{-- ===== LEFT: section sub-nav + quick info ===== --}}
        <aside class="hidden xl:flex xl:flex-col xl:gap-[18px] xl:sticky xl:top-24">
            <nav class="surface-card">
                <div class="border-b border-line px-4 py-3">
                    <span class="font-mono text-[9px] font-bold uppercase tracking-[0.16em] text-ink-soft">{{ __('admin.settings_sections') }}</span>
                </div>
                <div class="flex flex-col gap-1 p-2">
                    @foreach([
                        'profile' => __('admin.settings_section_profile'),
                        'hours' => __('admin.settings_business_hours'),
                        'localization' => __('admin.settings_section_localization'),
                        'brand' => __('admin.settings_section_brand'),
                        'receipt' => __('admin.settings_receipt_header'),
                        'whatsapp' => __('admin.settings_whatsapp_alerts'),
                        'qr' => __('admin.settings_guest_menu'),
                        'staff' => __('admin.settings_staff'),
                    ] as $sid => $slabel)
                        <a href="#sec-{{ $sid }}" class="rounded-lg px-3 py-2 text-sm font-medium text-ink-soft transition-colors hover:bg-cream hover:text-forest">{{ $slabel }}</a>
                    @endforeach
                </div>
            </nav>

            {{-- Quick Info --}}
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
        </aside>

        {{-- ===== RIGHT: settings cards ===== --}}
        <div class="flex min-w-0 flex-col gap-[18px]">

            {{-- Shop-level settings all post through save() --}}
            <form wire:submit.prevent="save" class="flex flex-col gap-[18px]">

                {{-- Shop profile --}}
                <section id="sec-profile" class="surface-card scroll-mt-24">
                    <div class="flex items-center justify-between border-b border-line px-[22px] py-4">
                        <div class="flex items-center gap-2.5">
                            <span class="h-[18px] w-1 rounded-sm" style="background: var(--bite-lime);"></span>
                            <h2 class="font-display text-[18px] font-bold leading-none text-forest">{{ __('admin.settings_shop_profile') }}</h2>
                        </div>
                        <span class="tag">{{ __('admin.settings_name_branding') }}</span>
                    </div>
                    <div class="grid grid-cols-1 gap-4 p-[22px] sm:grid-cols-2">
                        <label class="flex flex-col gap-1.5">
                            <span class="font-mono text-[10px] font-semibold uppercase tracking-[0.12em] text-ink-soft">{{ __('admin.settings_shop_name') }}</span>
                            <input type="text" wire:model="name" class="field" placeholder="{{ __('admin.placeholder_shop_name') }}">
                            @error('name') <p class="text-alert text-xs">{{ $message }}</p> @enderror
                        </label>

                        {{-- Public slug: read-only (editing breaks printed QR codes) --}}
                        <label class="flex flex-col gap-1.5">
                            <span class="font-mono text-[10px] font-semibold uppercase tracking-[0.12em] text-ink-soft">{{ __('admin.settings_public_slug') }}</span>
                            <div class="flex items-stretch overflow-hidden rounded-md border border-line bg-muted/40">
                                <span class="inline-flex items-center border-e border-line px-3 font-mono text-xs text-ink-soft">/menu/</span>
                                <input type="text" value="{{ $shop->slug }}" readonly class="min-w-0 flex-1 bg-transparent px-3 py-2.5 font-mono text-sm text-ink-soft" aria-label="{{ __('admin.settings_public_slug') }}">
                            </div>
                            <span class="text-[10px] text-ink-soft/60">{{ __('admin.settings_slug_locked_hint') }}</span>
                        </label>

                        <label class="flex flex-col gap-1.5">
                            <span class="font-mono text-[10px] font-semibold uppercase tracking-[0.12em] text-ink-soft">{{ __('admin.settings_phone') }}</span>
                            <input type="tel" wire:model="phone" class="field font-mono text-sm" placeholder="+968 9123 4567">
                            @error('phone') <p class="text-alert text-xs">{{ $message }}</p> @enderror
                        </label>

                        <label class="flex flex-col gap-1.5">
                            <span class="font-mono text-[10px] font-semibold uppercase tracking-[0.12em] text-ink-soft">{{ __('admin.settings_address') }}</span>
                            <input type="text" wire:model="address" class="field" placeholder="{{ __('admin.settings_address_placeholder') }}">
                            @error('address') <p class="text-alert text-xs">{{ $message }}</p> @enderror
                        </label>

                        <label class="flex flex-col gap-1.5 sm:col-span-2">
                            <span class="font-mono text-[10px] font-semibold uppercase tracking-[0.12em] text-ink-soft">{{ __('admin.settings_about') }}</span>
                            <textarea wire:model="about" rows="3" class="field text-sm" placeholder="{{ __('admin.settings_about_placeholder') }}"></textarea>
                            @error('about') <p class="text-alert text-xs">{{ $message }}</p> @enderror
                        </label>
                    </div>
                </section>

                {{-- Business hours --}}
                <section id="sec-hours" class="surface-card scroll-mt-24">
                    <div class="flex items-center justify-between border-b border-line px-[22px] py-4">
                        <div class="flex items-center gap-2.5">
                            <span class="h-[18px] w-1 rounded-sm" style="background: var(--bite-lime);"></span>
                            <h2 class="font-display text-[18px] font-bold leading-none text-forest">{{ __('admin.settings_business_hours') }}</h2>
                        </div>
                        <span class="tag">{{ $timezone }}</span>
                    </div>
                    <div class="flex flex-col gap-3 p-[22px]">
                        @foreach(\App\Livewire\ShopSettings::DAYS as $day)
                            @php $dayClosed = $businessHours[$day]['closed'] ?? false; @endphp
                            <div class="grid grid-cols-[96px_minmax(0,1fr)_auto] items-center gap-3 sm:grid-cols-[120px_minmax(0,1fr)_auto] sm:gap-4">
                                <span class="font-mono text-[12px] font-semibold uppercase tracking-[0.1em] {{ $dayClosed ? 'text-ink-soft' : 'text-forest' }}">{{ __('admin.settings_day_' . $day) }}</span>
                                <div class="flex items-center gap-2.5 {{ $dayClosed ? 'pointer-events-none opacity-40' : '' }}">
                                    <input type="time" wire:model="businessHours.{{ $day }}.open" @disabled($dayClosed) class="field max-w-[120px] font-mono text-sm">
                                    <span class="font-mono text-[11px] text-ink-soft">{{ __('admin.settings_to') }}</span>
                                    <input type="time" wire:model="businessHours.{{ $day }}.close" @disabled($dayClosed) class="field max-w-[120px] font-mono text-sm">
                                </div>
                                <label class="relative inline-flex cursor-pointer items-center justify-self-end" title="{{ __('admin.settings_closed') }}">
                                    <input type="checkbox" wire:model.live="businessHours.{{ $day }}.closed" class="peer sr-only">
                                    <div class="peer h-6 w-11 rounded-full bg-ink-soft/20 after:absolute after:start-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-line after:bg-panel after:transition-all after:content-[''] peer-checked:bg-alert peer-checked:after:translate-x-full peer-focus:outline-none"></div>
                                </label>
                            </div>
                        @endforeach
                        <p class="pt-1 text-end font-mono text-[9px] font-semibold uppercase tracking-[0.14em] text-ink-soft">{{ __('admin.settings_hours_hint') }}</p>
                    </div>
                </section>

                {{-- Localization & currency --}}
                <section id="sec-localization" class="surface-card scroll-mt-24">
                    <div class="flex items-center justify-between border-b border-line px-[22px] py-4">
                        <div class="flex items-center gap-2.5">
                            <span class="h-[18px] w-1 rounded-sm" style="background: var(--bite-lime);"></span>
                            <h2 class="font-display text-[18px] font-bold leading-none text-forest">{{ __('admin.settings_section_localization') }}</h2>
                        </div>
                        <span class="tag">{{ __('admin.settings_region_tag') }}</span>
                    </div>
                    <div class="grid grid-cols-1 gap-4 p-[22px] sm:grid-cols-2">
                        <label class="flex flex-col gap-1.5">
                            <span class="font-mono text-[10px] font-semibold uppercase tracking-[0.12em] text-ink-soft">{{ __('admin.settings_default_language') }}</span>
                            <select wire:model="language" class="field">
                                <option value="en">{{ __('admin.settings_english') }}</option>
                                <option value="ar">{{ __('admin.settings_arabic') }}</option>
                            </select>
                            <span class="text-[10px] text-ink-soft/60">{{ __('admin.settings_language_hint') }}</span>
                            @error('language') <p class="text-alert text-xs">{{ $message }}</p> @enderror
                        </label>

                        <label class="flex flex-col gap-1.5">
                            <span class="font-mono text-[10px] font-semibold uppercase tracking-[0.12em] text-ink-soft">{{ __('admin.settings_timezone') }}</span>
                            <select wire:model="timezone" class="field font-mono text-sm">
                                @foreach(['Asia/Muscat', 'Asia/Dubai', 'Asia/Riyadh', 'Asia/Bahrain', 'Asia/Qatar', 'Asia/Kuwait', 'Asia/Baghdad', 'UTC'] as $tz)
                                    <option value="{{ $tz }}">{{ $tz }}</option>
                                @endforeach
                            </select>
                            @error('timezone') <p class="text-alert text-xs">{{ $message }}</p> @enderror
                        </label>

                        <label class="flex flex-col gap-1.5">
                            <span class="font-mono text-[10px] font-semibold uppercase tracking-[0.12em] text-ink-soft">{{ __('admin.settings_currency_code') }}</span>
                            <input type="text" wire:model="currency_code" class="field font-mono uppercase" placeholder="OMR" maxlength="3">
                            @error('currency_code') <p class="text-alert text-xs">{{ $message }}</p> @enderror
                        </label>

                        <label class="flex flex-col gap-1.5">
                            <span class="font-mono text-[10px] font-semibold uppercase tracking-[0.12em] text-ink-soft">{{ __('admin.settings_currency_symbol') }}</span>
                            <input type="text" wire:model="currency_symbol" class="field" placeholder="ر.ع." maxlength="10">
                            @error('currency_symbol') <p class="text-alert text-xs">{{ $message }}</p> @enderror
                        </label>

                        <label class="flex flex-col gap-1.5">
                            <span class="font-mono text-[10px] font-semibold uppercase tracking-[0.12em] text-ink-soft">{{ __('admin.settings_currency_decimals') }}</span>
                            <select wire:model="currency_decimals" class="field">
                                <option value="0">0</option>
                                <option value="2">2</option>
                                <option value="3">3</option>
                            </select>
                            @error('currency_decimals') <p class="text-alert text-xs">{{ $message }}</p> @enderror
                        </label>

                        <label class="flex flex-col gap-1.5">
                            <span class="font-mono text-[10px] font-semibold uppercase tracking-[0.12em] text-ink-soft">{{ __('admin.settings_tax_rate') }}</span>
                            <input type="number" step="0.01" min="0" max="100" wire:model="tax_rate" class="field" placeholder="5.00">
                            @error('tax_rate') <p class="text-alert text-xs">{{ $message }}</p> @enderror
                        </label>
                    </div>
                </section>

                {{-- Brand & theme --}}
                <section id="sec-brand" class="surface-card scroll-mt-24">
                    <div class="flex items-center justify-between border-b border-line px-[22px] py-4">
                        <div class="flex items-center gap-2.5">
                            <span class="h-[18px] w-1 rounded-sm" style="background: var(--bite-lime);"></span>
                            <h2 class="font-display text-[18px] font-bold leading-none text-forest">{{ __('admin.settings_section_brand') }}</h2>
                        </div>
                        <span class="tag">{{ __('admin.settings_guest_menu') }}</span>
                    </div>

                    <div class="space-y-6 p-[22px]">
                        {{-- Menu Theme --}}
                        <div x-data="{ previewTheme: @entangle('theme') }" wire:ignore.self>
                            <p class="font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft" style="margin-bottom: 12px;">{{ __('admin.settings_menu_theme') }}</p>

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
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
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

                        {{-- Brand appearance live preview --}}
                        <div>
                            <p class="font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft" style="margin-bottom: 12px;">{{ __('admin.settings_live_preview') }}</p>
                            <div class="overflow-hidden rounded-lg border border-line p-6"
                                 style="background-color: {{ $paper }}; color: {{ $ink }};">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-7 w-7 items-center justify-center rounded" style="background-color: {{ $ink }}; color: {{ $paper }};">
                                        <span class="font-mono text-xs font-black">B</span>
                                    </div>
                                    <h3 class="font-mono text-sm font-black uppercase tracking-widest">{{ $name }}</h3>
                                </div>
                                <div class="mt-5 space-y-3">
                                    <div class="h-2 w-20 rounded" style="background-color: {{ $ink }}; opacity: 0.1;"></div>
                                    <div class="h-8 w-full rounded border" style="border-color: {{ $ink }};"></div>
                                    <button type="button" class="w-full rounded py-2.5 font-mono text-[9px] font-black uppercase tracking-widest"
                                            style="background-color: {{ $accent }}; color: {{ $paper }};">
                                        {{ __('admin.settings_button_preview') }}
                                    </button>
                                </div>
                            </div>
                            <p class="mt-3 font-mono text-[10px] leading-relaxed text-ink-soft/60">{{ __('admin.settings_changes_propagate') }}</p>
                        </div>
                    </div>
                </section>

                {{-- Receipt header --}}
                <section id="sec-receipt" class="surface-card scroll-mt-24">
                    <div class="flex items-center justify-between border-b border-line px-[22px] py-4">
                        <div class="flex items-center gap-2.5">
                            <span class="h-[18px] w-1 rounded-sm" style="background: var(--bite-lime);"></span>
                            <h2 class="font-display text-[18px] font-bold leading-none text-forest">{{ __('admin.settings_receipt_header') }}</h2>
                        </div>
                    </div>
                    <div class="space-y-1.5 p-[22px]">
                        <textarea wire:model="receipt_header" rows="3" class="field font-mono text-xs" placeholder="Business Name&#10;123 Street, City&#10;VAT: 1234567890"></textarea>
                        <p class="text-[10px] text-ink-soft/60">{{ __('admin.settings_receipt_header_hint') }}</p>
                        @error('receipt_header') <p class="text-alert text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </section>

                {{-- WhatsApp notifications --}}
                <section id="sec-whatsapp" class="surface-card scroll-mt-24">
                    <div class="flex items-center justify-between border-b border-line px-[22px] py-4">
                        <div class="flex items-center gap-2.5">
                            <span class="h-[18px] w-1 rounded-sm" style="background: var(--bite-lime);"></span>
                            <h2 class="font-display text-[18px] font-bold leading-none text-forest">{{ __('admin.settings_whatsapp_alerts') }}</h2>
                        </div>
                    </div>
                    <div class="space-y-5 p-[22px]">
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
                                <div class="peer h-6 w-11 rounded-full bg-ink-soft/20 after:absolute after:start-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-line after:bg-panel after:transition-all after:content-[''] peer-checked:bg-signal peer-checked:after:translate-x-full peer-focus:outline-none"></div>
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

                {{-- Sticky save bar --}}
                <div class="sticky bottom-3 z-10 flex items-center justify-end gap-3 rounded-2xl border border-line bg-cream/85 px-5 py-3 backdrop-blur-xl">
                    <p class="me-auto hidden font-mono text-[10px] uppercase tracking-[0.14em] text-ink-soft sm:block">{{ __('admin.settings_changes_propagate') }}</p>
                    <button type="submit" class="btn-primary">
                        <span wire:loading.remove wire:target="save">{{ __('admin.settings_save') }}</span>
                        <span wire:loading wire:target="save" class="inline-flex items-center gap-2">
                            <span class="loading-spinner"></span>
                            {{ __('admin.settings_saving') }}
                        </span>
                    </button>
                </div>
            </form>

            {{-- Guest Menu QR (own form-free card) --}}
            <section id="sec-qr" class="surface-card scroll-mt-24">
                <div class="flex items-center justify-between border-b border-line px-[22px] py-4">
                    <div class="flex items-center gap-2.5">
                        <span class="h-[18px] w-1 rounded-sm" style="background: var(--bite-lime);"></span>
                        <h2 class="font-display text-[18px] font-bold leading-none text-forest">{{ __('admin.settings_guest_menu') }}</h2>
                    </div>
                    <span class="tag">{{ __('admin.settings_qr_customers') }}</span>
                </div>

                <div class="p-[22px]" x-data="{ copied: false }">
                    <div class="flex flex-col items-center gap-6 sm:flex-row">
                        <div class="shrink-0">
                            <img
                                src="https://api.qrserver.com/v1/create-qr-code/?size=300x300&data={{ urlencode($menuUrl) }}"
                                alt="Guest menu QR code"
                                class="h-40 w-40 rounded-lg border border-line bg-white p-2"
                            >
                        </div>
                        <div class="flex-1 space-y-3 text-center sm:text-start">
                            <p class="font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">{{ __('admin.settings_menu_url') }}</p>
                            <p class="break-all font-mono text-sm text-ink">{{ $menuUrl }}</p>
                            <div class="flex flex-wrap justify-center gap-2 sm:justify-start">
                                <button
                                    type="button"
                                    x-on:click="navigator.clipboard.writeText('{{ $menuUrl }}').then(() => { copied = true; setTimeout(() => copied = false, 2000) })"
                                    class="btn-secondary"
                                >
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                                    <span x-text="copied ? '{{ __('admin.settings_copied') }}' : '{{ __('admin.settings_copy_link') }}'"></span>
                                </button>
                                <a
                                    href="https://api.qrserver.com/v1/create-qr-code/?size=600x600&format=png&data={{ urlencode($menuUrl) }}"
                                    download="menu-qr.png"
                                    class="btn-secondary"
                                >
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                    {{ __('admin.settings_download_qr') }}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {{-- Staff management (separate forms; cannot nest in the save form) --}}
            <section id="sec-staff" class="surface-card scroll-mt-24">
                <div class="flex items-center justify-between border-b border-line px-[22px] py-4">
                    <div class="flex items-center gap-2.5">
                        <span class="h-[18px] w-1 rounded-sm" style="background: var(--bite-lime);"></span>
                        <h2 class="font-display text-[18px] font-bold leading-none text-forest">{{ __('admin.settings_staff') }}</h2>
                    </div>
                    <span class="tag">{{ __('admin.settings_staff_desc') }}</span>
                </div>

                {{-- Add/Edit Staff Form --}}
                <div class="border-b border-line bg-muted/20 p-[22px]">
                    <p class="section-headline mb-3">{{ $editingStaffId ? __('admin.settings_edit_staff') : __('admin.settings_add_staff') }}</p>
                    <form wire:submit.prevent="{{ $editingStaffId ? 'updateStaff' : 'addStaff' }}" class="space-y-4">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
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
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
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
                    <table class="w-full border-collapse text-start">
                        <thead>
                            <tr class="border-b border-line bg-panel font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">
                                <th class="px-5 py-3 text-start">{{ __('admin.settings_staff_name') }}</th>
                                <th class="px-5 py-3 text-start">{{ __('admin.settings_staff_email') }}</th>
                                <th class="px-5 py-3 text-start">{{ __('admin.settings_staff_role') }}</th>
                                <th class="px-5 py-3 text-start">{{ __('admin.settings_pin_header') }}</th>
                                <th class="px-5 py-3 text-end">{{ __('admin.settings_actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @forelse($staff as $member)
                                <tr class="group transition-colors hover:bg-cream">
                                    <td class="px-5 py-3 text-sm font-medium text-ink">{{ $member->name }}</td>
                                    <td class="px-5 py-3 text-sm text-ink-soft">{{ $member->email }}</td>
                                    <td class="px-5 py-3">
                                        <span class="tag">{{ ucfirst($member->role ?? 'staff') }}</span>
                                    </td>
                                    <td class="px-5 py-3">
                                        @if($member->pin_code)
                                            <span class="inline-flex items-center gap-1 font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-signal">
                                                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                                {{ __('admin.settings_pin_set') }}
                                            </span>
                                        @else
                                            <span class="font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft/50">{{ __('admin.settings_pin_none') }}</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3 text-end">
                                        <div class="flex items-center justify-end gap-2 transition-opacity sm:opacity-0 sm:group-hover:opacity-100">
                                            <button
                                                wire:click="editStaff({{ $member->id }})"
                                                class="btn-secondary !px-3 !py-1.5 !text-[9px]"
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
                                                    class="btn-danger !px-3 !py-1.5 !text-[9px]"
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
    </div>
</div>
