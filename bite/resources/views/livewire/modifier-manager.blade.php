<div class="space-y-6 fade-rise">
    <x-slot:header>{{ __('admin.modifier_management') }}</x-slot:header>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- New Group Form -->
        <div class="lg:col-span-1 space-y-6">
            <div class="surface-card p-5 sm:p-6">
                <div class="space-y-6">
                    <h2 class="font-display text-xl font-extrabold leading-none text-ink">{{ __('admin.modifier_create_group') }}</h2>

                    <form wire:submit.prevent="save" class="space-y-6">
                        <div class="space-y-2">
                            <label class="font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">{{ __('admin.modifier_group_name_en') }}</label>
                            <input type="text" wire:model="name_en" class="w-full field transition-all" placeholder="e.g. Milk Choice">
                            @error('name_en') <span class="font-mono text-[10px] font-semibold text-alert">{{ $message }}</span> @enderror
                        </div>

                        <div class="space-y-2">
                            <label class="font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">{{ __('admin.modifier_group_name_ar') }}</label>
                            <input type="text" wire:model="name_ar" class="w-full field transition-all" placeholder="مثال: نوع الحليب" dir="rtl">
                            @error('name_ar') <span class="font-mono text-[10px] font-semibold text-alert">{{ $message }}</span> @enderror
                        </div>

                        <div class="grid grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label class="font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">{{ __('admin.modifier_min_required') }}</label>
                                <input type="number" wire:model="min_selection" class="w-full field transition-all" placeholder="0">
                            </div>
                            <div class="space-y-2">
                                <label class="font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">{{ __('admin.modifier_max_allowed') }}</label>
                                <input type="number" wire:model="max_selection" class="w-full field transition-all" placeholder="1">
                            </div>
                        </div>

                        <button type="submit" class="btn-primary w-full">{{ __('admin.modifier_save_group') }}</button>
                    </form>
                </div>
            </div>

            <!-- New Option Form (Only if group selected) -->
            @if($selectedGroupId)
                <div class="surface-card p-5 sm:p-6 fade-rise">
                    <div class="space-y-6">
                        <h2 class="font-display text-xl font-extrabold leading-none text-crema">{{ __('admin.modifier_add_option') }}</h2>

                        <form wire:submit.prevent="addOption" class="space-y-6">
                            <div class="space-y-2">
                                <label class="font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">{{ __('admin.modifier_option_name_en') }}</label>
                                <input type="text" wire:model="optionNameEn" class="w-full field transition-all" placeholder="e.g. Oat Milk">
                                @error('optionNameEn') <span class="font-mono text-[10px] font-semibold text-alert">{{ $message }}</span> @enderror
                            </div>

                            <div class="space-y-2">
                                <label class="font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">{{ __('admin.modifier_option_name_ar') }}</label>
                                <input type="text" wire:model="optionNameAr" class="w-full field transition-all" placeholder="مثال: حليب الشوفان" dir="rtl">
                                @error('optionNameAr') <span class="font-mono text-[10px] font-semibold text-alert">{{ $message }}</span> @enderror
                            </div>

                            <div class="space-y-2">
                                <label class="font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">{{ __('admin.modifier_extra_cost') }}</label>
                                <input type="number" step="0.01" wire:model="optionPrice" class="w-full field transition-all" placeholder="1.00">
                                @error('optionPrice') <span class="font-mono text-[10px] font-semibold text-alert">{{ $message }}</span> @enderror
                            </div>

                            <button type="submit" class="btn-primary w-full">{{ __('admin.modifier_add_option_btn') }}</button>
                        </form>
                    </div>
                </div>
            @endif
        </div>

        <!-- Groups List -->
        <div class="lg:col-span-2 space-y-6">
            <div class="surface-card">
                <div class="border-b border-line bg-muted/35 px-5 py-4">
                    <h3 class="font-display text-xl font-extrabold leading-none">{{ __('admin.modifier_groups') }}</h3>
                </div>

                <div class="divide-y divide-line/65">
                    @foreach(\App\Models\ModifierGroup::where('shop_id', Auth::user()->shop_id)->with('options')->get() as $group)
                        <div @class([
                            'px-5 py-4 flex flex-col space-y-6 transition-all duration-150',
                            'border-l-[3px] border-l-crema bg-crema/5' => $selectedGroupId == $group->id,
                            'bg-panel hover:bg-muted/35 cursor-pointer' => $selectedGroupId != $group->id
                        ]) wire:click="$set('selectedGroupId', {{ $group->id }})">
                            <div class="flex justify-between items-center">
                                <div class="flex items-center space-x-6">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-ink text-panel font-mono text-xs font-bold">{{ $loop->iteration }}</div>
                                    <div>
                                        <div class="text-sm font-semibold uppercase tracking-tight text-ink">{{ $group->name_en }}</div>
                                        <div class="mt-0.5 font-mono text-[10px] text-ink-soft">{{ __('admin.modifier_rule', ['min' => $group->min_selection, 'max' => $group->max_selection]) }}</div>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-12">
                                    <div class="tag">{{ __('admin.modifier_options_count', ['count' => $group->options->count()]) }}</div>
                                    @if($selectedGroupId == $group->id)
                                        <span class="inline-flex items-center rounded-full border border-crema/40 bg-crema/10 px-2.5 py-1 font-mono text-[9px] font-semibold uppercase tracking-[0.14em] text-crema">{{ __('admin.modifier_selected') }}</span>
                                    @endif
                                </div>
                            </div>

                            @if($group->options->isNotEmpty())
                                <div class="pl-16 grid grid-cols-2 md:grid-cols-3 gap-4 pb-4">
                                    @foreach($group->options as $option)
                                        <div class="rounded-lg border border-line bg-panel px-3 py-2.5 flex justify-between items-center">
                                            <span class="text-[11px] font-semibold uppercase tracking-tight text-ink truncate">{{ $option->name_en }}</span>
                                            <span class="font-mono text-[10px] font-semibold text-crema">+{{ formatPrice($option->price_adjustment, $shop) }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
