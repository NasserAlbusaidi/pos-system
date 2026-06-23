<div class="space-y-[18px] fade-rise">
    <x-slot:header>{{ __('admin.modifier_management') }}</x-slot:header>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-[18px]">
        <!-- New Group Form -->
        <div class="lg:col-span-1 space-y-[18px]">
            <section class="surface-card">
                <div class="flex items-center justify-between border-b border-line px-[22px] py-4">
                    <div class="flex items-center gap-2.5">
                        <span class="h-[18px] w-1 rounded-sm" style="background: var(--bite-lime);"></span>
                        <h2 class="font-display text-[18px] font-bold leading-none text-forest">{{ __('admin.modifier_create_group') }}</h2>
                    </div>
                </div>
                <div class="p-[22px]">
                    <form wire:submit.prevent="save" class="space-y-5">
                        <div class="space-y-2">
                            <label class="font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">{{ __('admin.modifier_group_name_en') }}</label>
                            <input type="text" wire:model="name_en" class="field w-full" placeholder="{{ __('admin.placeholder_group_name') }}">
                            @error('name_en') <span class="font-mono text-[10px] font-semibold text-alert">{{ $message }}</span> @enderror
                        </div>

                        <div class="space-y-2">
                            <label class="font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">{{ __('admin.modifier_group_name_ar') }}</label>
                            <input type="text" wire:model="name_ar" class="field w-full" placeholder="مثال: نوع الحليب" dir="rtl">
                            @error('name_ar') <span class="font-mono text-[10px] font-semibold text-alert">{{ $message }}</span> @enderror
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <label class="font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">{{ __('admin.modifier_min_required') }}</label>
                                <input type="number" wire:model="min_selection" class="field w-full" placeholder="0">
                            </div>
                            <div class="space-y-2">
                                <label class="font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">{{ __('admin.modifier_max_allowed') }}</label>
                                <input type="number" wire:model="max_selection" class="field w-full" placeholder="1">
                            </div>
                        </div>

                        <button type="submit" class="btn-primary w-full" style="background: var(--bite-forest); border-color: var(--bite-forest);">{{ __('admin.modifier_save_group') }}</button>
                    </form>
                </div>
            </section>

            <!-- New Option Form (Only if group selected) -->
            @if($selectedGroupId)
                <section class="surface-card fade-rise">
                    <div class="flex items-center justify-between border-b border-line px-[22px] py-4">
                        <div class="flex items-center gap-2.5">
                            <span class="h-[18px] w-1 rounded-sm" style="background: var(--bite-lime);"></span>
                            <h2 class="font-display text-[18px] font-bold leading-none text-forest">{{ __('admin.modifier_add_option') }}</h2>
                        </div>
                    </div>
                    <div class="p-[22px]">
                        <form wire:submit.prevent="addOption" class="space-y-5">
                            <div class="space-y-2">
                                <label class="font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">{{ __('admin.modifier_option_name_en') }}</label>
                                <input type="text" wire:model="optionNameEn" class="field w-full" placeholder="{{ __('admin.placeholder_option_name') }}">
                                @error('optionNameEn') <span class="font-mono text-[10px] font-semibold text-alert">{{ $message }}</span> @enderror
                            </div>

                            <div class="space-y-2">
                                <label class="font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">{{ __('admin.modifier_option_name_ar') }}</label>
                                <input type="text" wire:model="optionNameAr" class="field w-full" placeholder="مثال: حليب الشوفان" dir="rtl">
                                @error('optionNameAr') <span class="font-mono text-[10px] font-semibold text-alert">{{ $message }}</span> @enderror
                            </div>

                            <div class="space-y-2">
                                <label class="font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">{{ __('admin.modifier_extra_cost') }}</label>
                                <input type="number" step="0.001" inputmode="decimal" wire:model="optionPrice" class="field w-full" placeholder="0.100">
                                @error('optionPrice') <span class="font-mono text-[10px] font-semibold text-alert">{{ $message }}</span> @enderror
                            </div>

                            <button type="submit" class="btn-primary w-full" style="background: var(--bite-forest); border-color: var(--bite-forest);">{{ __('admin.modifier_add_option_btn') }}</button>
                        </form>
                    </div>
                </section>
            @endif
        </div>

        <!-- Groups List -->
        <div class="lg:col-span-2 space-y-[18px]">
            @forelse(\App\Models\ModifierGroup::where('shop_id', Auth::user()->shop_id)->with('options')->get() as $group)
                <section @class([
                    'surface-card transition-all duration-150',
                    'cursor-pointer' => $selectedGroupId != $group->id,
                ]) @style([
                    'box-shadow: 0 0 0 2px var(--bite-lime) inset;' => $selectedGroupId == $group->id,
                ]) wire:click="$set('selectedGroupId', {{ $group->id }})">
                    <div class="flex items-center justify-between border-b border-line px-[22px] py-4">
                        <div class="flex items-center gap-3.5">
                            <span class="h-[38px] w-1 rounded-sm" style="background: var(--bite-lime);"></span>
                            <div class="flex flex-col gap-1.5">
                                <h2 class="font-display text-[18px] font-bold leading-none text-forest">{{ $group->name_en }}</h2>
                                <div class="flex items-center gap-2.5">
                                    <span class="font-mono text-[10px] font-semibold uppercase tracking-[0.12em] text-ink-soft">{{ __('admin.modifier_rule', ['min' => $group->min_selection, 'max' => $group->max_selection]) }}</span>
                                    <span class="tag">{{ __('admin.modifier_options_count', ['count' => $group->options->count()]) }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            @if($selectedGroupId == $group->id)
                                <span class="inline-flex items-center rounded-full px-2.5 py-1 font-mono text-[9px] font-semibold uppercase tracking-[0.14em] text-pine" style="background: var(--bite-lime-100);">{{ __('admin.modifier_selected') }}</span>
                            @endif
                            <button
                                wire:click.stop="deleteGroup({{ $group->id }})"
                                wire:confirm="{{ __('admin.modifier_delete_group_confirm') }}"
                                class="font-mono text-[10px] font-semibold uppercase tracking-[0.14em] text-alert transition-colors hover:opacity-80"
                                wire:loading.attr="disabled"
                            >
                                {{ __('admin.delete') }}
                            </button>
                        </div>
                    </div>

                    @if($group->options->isNotEmpty())
                        <div class="py-2">
                            @foreach($group->options as $option)
                                <div class="flex items-center justify-between px-[22px] py-3 transition-colors hover:bg-cream">
                                    <span class="text-sm font-semibold uppercase tracking-tight text-ink">{{ $option->name_en }}</span>
                                    <div class="flex items-center gap-4">
                                        <span class="font-mono text-[13px] font-semibold text-forest">+<x-price :amount="$option->price_adjustment" :shop="$shop" /></span>
                                        <button
                                            wire:click.stop="deleteOption({{ $option->id }})"
                                            wire:confirm="{{ __('admin.modifier_delete_option_confirm') }}"
                                            class="font-mono text-[11px] font-semibold text-ink-soft transition-colors hover:text-alert"
                                        >&times;</button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="p-[22px]">
                            <div class="rounded-2xl border border-dashed border-line px-4 py-8 text-center font-mono text-[10px] uppercase tracking-[0.18em] text-ink-soft">{{ __('admin.modifier_no_options') }}</div>
                        </div>
                    @endif
                </section>
            @empty
                <section class="surface-card">
                    <div class="flex items-center justify-between border-b border-line px-[22px] py-4">
                        <div class="flex items-center gap-2.5">
                            <span class="h-[18px] w-1 rounded-sm" style="background: var(--bite-lime);"></span>
                            <h2 class="font-display text-[18px] font-bold leading-none text-forest">{{ __('admin.modifier_groups') }}</h2>
                        </div>
                    </div>
                    <div class="p-[22px]">
                        <div class="rounded-2xl border border-dashed border-line px-4 py-8 text-center font-mono text-[10px] uppercase tracking-[0.18em] text-ink-soft">{{ __('admin.modifier_no_groups') }}</div>
                    </div>
                </section>
            @endforelse
        </div>
    </div>
</div>
