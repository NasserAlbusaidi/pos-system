<div class="space-y-[18px] fade-rise">
    <x-slot:header>{{ __('admin.product_catalog') }}</x-slot:header>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-[18px]">
        {{-- Product form --}}
        <div class="lg:col-span-1">
            <section class="surface-card">
                <div class="flex items-center justify-between border-b border-line px-[22px] py-4">
                    <div class="flex items-center gap-2.5">
                        <span class="h-[18px] w-1 rounded-sm" style="background: var(--bite-lime);"></span>
                        <h2 class="font-display text-[18px] font-bold leading-none text-forest">
                            {{ $editingProductId ? __('admin.product_edit') : __('admin.product_add') }}
                        </h2>
                    </div>
                    @if ($editingProductId)
                        <span class="tag">{{ __('admin.menu_edit') }}</span>
                    @endif
                </div>

                <div class="p-[22px]">
                    <form wire:submit.prevent="save" class="space-y-6">
                        <div class="space-y-2">
                            <label class="font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">{{ __('admin.product_name_en') }}</label>
                            <input type="text" wire:model="name_en" class="field" placeholder="{{ __('admin.placeholder_product_name') }}">
                        </div>

                        <div class="space-y-2">
                            <label class="font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">{{ __('admin.product_name_ar') }}</label>
                            <input type="text" wire:model="name_ar" class="field" placeholder="لاتيه" dir="rtl">
                        </div>

                        <div class="space-y-2">
                            <label class="font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">{{ __('admin.product_desc_en') }}</label>
                            <input type="text" wire:model="description_en" class="field" placeholder="{{ __('admin.placeholder_product_desc') }}">
                        </div>

                        <div class="space-y-2">
                            <label class="font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">{{ __('admin.product_desc_ar') }}</label>
                            <input type="text" wire:model="description_ar" class="field" placeholder="إسبريسو مع حليب مبخر" dir="rtl">
                        </div>

                        <div class="space-y-2">
                            <label class="font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">{{ __('admin.product_price') }}</label>
                            <input type="number" step="0.01" wire:model="price" class="field" placeholder="4.50">
                        </div>

                        <div class="space-y-2">
                            <label class="font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">{{ __('admin.product_tax_rate') }}</label>
                            <input type="number" step="0.01" wire:model="tax_rate" class="field" placeholder="0">
                        </div>

                        <div class="space-y-4">
                            <label class="font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">{{ __('admin.product_image') }}</label>
                            <div class="flex flex-col items-center justify-center gap-4 rounded-2xl border-2 border-dashed border-line bg-cream p-6">
                                @if ($image)
                                    <img src="{{ $image->temporaryUrl() }}" class="h-20 w-20 rounded-lg border border-line object-cover">
                                @elseif ($currentImageUrl)
                                    <img src="{{ asset('storage/' . $currentImageUrl) }}" class="h-20 w-20 rounded-lg border border-line object-cover">
                                @else
                                    <div class="flex h-20 w-20 items-center justify-center rounded-lg border border-dashed border-line bg-mist font-mono text-[10px] text-ink-soft">{{ __('admin.product_no_image') }}</div>
                                @endif
                                <input type="file" wire:model="image" class="font-mono text-[10px] text-ink-soft">
                            </div>
                            @error('image') <span class="font-mono text-[10px] font-semibold text-alert">{{ $message }}</span> @enderror
                        </div>

                        <div class="space-y-2">
                            <label class="font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">{{ __('admin.product_category') }}</label>
                            <select wire:model="category_id" class="field">
                                <option value="">{{ __('admin.product_category_none') }}</option>
                                @foreach(\App\Models\Category::where('shop_id', Auth::user()->shop_id)->get() as $cat)
                                    <option value="{{ $cat->id }}">{{ strtoupper($cat->name_en) }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Modifiers --}}
                        <div class="space-y-2">
                            <label class="font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">{{ __('admin.product_modifiers') }}</label>
                            <div class="grid grid-cols-1 gap-2">
                                @foreach(\App\Models\ModifierGroup::where('shop_id', Auth::user()->shop_id)->get() as $group)
                                    <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-line bg-cream px-3 py-3 transition-colors hover:border-pine has-[:checked]:border-forest has-[:checked]:bg-white">
                                        <input type="checkbox" wire:model="selectedModifierGroups" value="{{ $group->id }}" class="text-forest focus:ring-0 border-ink">
                                        <span class="font-mono text-[10px] font-bold uppercase text-ink">{{ $group->name_en }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        @if ($editingProductId)
                            <div class="space-y-2">
                                <label class="font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">{{ __('admin.product_availability') }}</label>
                                @php $editProduct = \App\Models\Product::find($editingProductId); @endphp
                                @if($editProduct)
                                    <button
                                        type="button"
                                        wire:click="toggleAvailability({{ $editingProductId }})"
                                        class="w-full rounded-full border px-4 py-3 font-mono text-xs font-bold uppercase tracking-[0.14em] transition-all duration-200 {{ $editProduct->is_available ? 'border-olive bg-cream text-pine' : 'border-alert bg-white text-alert' }}"
                                    >
                                        {{ $editProduct->is_available ? __('admin.product_available') : __('admin.product_sold_out') }}
                                    </button>
                                @endif
                            </div>
                        @endif

                        <button type="submit" class="btn-primary w-full" style="background: var(--bite-forest); border-color: var(--bite-forest);">
                            {{ $editingProductId ? __('admin.product_update') : __('admin.product_save') }}
                        </button>

                        @if ($editingProductId)
                            <button type="button" wire:click="cancelEdit" class="btn-secondary w-full">
                                {{ __('admin.product_cancel_edit') }}
                            </button>
                        @endif
                    </form>
                </div>
            </section>
        </div>

        {{-- Product list --}}
        <div class="lg:col-span-2">
            @php $products = \App\Models\Product::where('shop_id', Auth::user()->shop_id)->get(); @endphp
            <section class="surface-card">
                <div class="flex items-center justify-between border-b border-line px-[22px] py-4">
                    <div class="flex items-center gap-2.5">
                        <span class="h-[18px] w-1 rounded-sm" style="background: var(--bite-lime);"></span>
                        <h2 class="font-display text-[18px] font-bold leading-none text-forest">{{ __('admin.product_current') }}</h2>
                    </div>
                    <span class="tag">{{ $products->count() }} {{ __('admin.product_count_label') }}</span>
                </div>

                <div class="p-0">
                    @if($products->isEmpty())
                        <div class="m-[22px] rounded-2xl border border-dashed border-line px-4 py-8 text-center font-mono text-[10px] uppercase tracking-[0.18em] text-ink-soft">
                            {{ __('admin.product_empty') }}
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="w-full border-collapse">
                                <thead>
                                    <tr class="border-b border-line">
                                        <th class="px-[22px] py-3 text-start font-mono text-[10px] font-bold uppercase tracking-[0.14em] text-ink-soft">{{ __('admin.product_th_product') }}</th>
                                        <th class="px-[22px] py-3 text-end font-mono text-[10px] font-bold uppercase tracking-[0.14em] text-ink-soft">{{ __('admin.product_price') }}</th>
                                        <th class="px-[22px] py-3 text-start font-mono text-[10px] font-bold uppercase tracking-[0.14em] text-ink-soft">{{ __('admin.product_availability') }}</th>
                                        <th class="px-[22px] py-3 text-end font-mono text-[10px] font-bold uppercase tracking-[0.14em] text-ink-soft">{{ __('admin.product_th_actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($products as $product)
                                        <tr class="group border-b border-line transition-colors hover:bg-cream {{ ! $product->is_available ? 'opacity-60' : '' }}">
                                            <td class="px-[22px] py-3.5">
                                                <div class="flex items-center gap-3">
                                                    @if(productImage($product, 'thumb'))
                                                        <img src="{{ productImage($product, 'thumb') }}" class="h-10 w-10 flex-none rounded-lg border border-line object-cover {{ ! $product->is_available ? 'opacity-40' : '' }}">
                                                    @else
                                                        <div class="flex h-10 w-10 flex-none items-center justify-center rounded-lg bg-mist font-mono text-xs font-bold text-pine {{ ! $product->is_available ? 'opacity-40' : '' }}">{{ $loop->iteration }}</div>
                                                    @endif
                                                    <div class="flex flex-col gap-0.5">
                                                        <span class="text-sm font-semibold uppercase tracking-tight text-ink {{ ! $product->is_available ? 'line-through opacity-50' : '' }}">{{ $product->name_en }}</span>
                                                        <span class="font-mono text-[10px] tracking-[0.06em] text-ink-soft">{{ $product->category->name_en }}</span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-[22px] py-3.5 text-end font-display text-sm font-bold text-forest">
                                                <x-price :amount="$product->price" :shop="$shop" />
                                            </td>
                                            <td class="px-[22px] py-3.5">
                                                <button
                                                    wire:click="toggleAvailability({{ $product->id }})"
                                                    wire:loading.attr="disabled"
                                                    wire:target="toggleAvailability({{ $product->id }})"
                                                    class="rounded-full border px-3 py-1 font-mono text-[10px] font-bold uppercase tracking-[0.12em] transition-all duration-200 {{ ! $product->is_available ? 'border-alert bg-white text-alert' : 'border-olive bg-cream text-pine' }}"
                                                    title="{{ $product->is_available ? __('admin.product_available') : __('admin.product_sold_out') }}"
                                                >
                                                    <span wire:loading.remove wire:target="toggleAvailability({{ $product->id }})">
                                                        {{ $product->is_available ? __('admin.product_available') : __('admin.product_sold_out') }}
                                                    </span>
                                                    <span wire:loading wire:target="toggleAvailability({{ $product->id }})" class="loading-spinner" style="width: 10px; height: 10px; border-width: 1px;"></span>
                                                </button>
                                            </td>
                                            <td class="px-[22px] py-3.5 text-end">
                                                <button wire:click="editProduct({{ $product->id }})" class="font-mono text-[10px] font-semibold uppercase tracking-[0.14em] text-pine transition-opacity hover:text-forest sm:opacity-0 sm:group-hover:opacity-100">{{ __('admin.menu_edit') }}</button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </section>
        </div>
    </div>
</div>
