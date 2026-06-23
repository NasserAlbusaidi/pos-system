<div class="space-y-[18px] fade-rise" x-data>
    <x-slot:header>{{ __('admin.menu_builder') }}</x-slot:header>

    {{-- Toolbar / structure header --}}
    <section class="surface-card">
        <div class="flex flex-col gap-4 border-b border-line px-[22px] py-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-center gap-2.5">
                <span class="h-[18px] w-1 rounded-sm" style="background: var(--bite-lime);"></span>
                <h2 class="font-display text-[18px] font-bold leading-none text-forest">{{ __('admin.menu_structure') }}</h2>
            </div>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <input type="text" wire:model.live="search" class="field text-sm sm:w-64" placeholder="{{ __('admin.menu_search_placeholder') }}">
                <div class="flex items-center gap-1.5 self-start rounded-full border border-line bg-cream px-3 py-1.5 sm:self-auto">
                    <span class="h-1.5 w-1.5 rounded-full" style="background: var(--bite-green);"></span>
                    <span class="font-mono text-[9px] font-bold uppercase tracking-[0.12em] text-pine">{{ __('admin.menu_all_saved') }}</span>
                </div>
            </div>
        </div>

        <div class="p-[22px]">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                <div class="flex-1">
                    <label class="mb-2 block font-mono text-[10px] font-semibold uppercase tracking-[0.14em] text-ink-soft">{{ __('admin.menu_add_category') }}</label>
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                        <input type="text" wire:model="newCategoryNameEn" placeholder="{{ __('admin.menu_category_en_placeholder') }}" class="field text-sm sm:w-56">
                        <input type="text" wire:model="newCategoryNameAr" placeholder="{{ __('admin.menu_category_ar_placeholder') }}" class="field text-sm sm:w-56" dir="rtl">
                        <button wire:click="createCategory" class="btn-primary whitespace-nowrap" style="background: var(--bite-forest); border-color: var(--bite-forest);">{{ __('admin.menu_add_category') }}</button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Canvas --}}
    <div class="grid grid-cols-1 items-start gap-[18px] pb-24 lg:grid-cols-2 xl:grid-cols-3">
        @foreach($categories as $category)
            <section class="surface-card flex min-h-[400px] flex-col">
                {{-- Category Header --}}
                <div class="flex items-center justify-between border-b border-line px-[22px] py-4">
                    <div class="flex items-center gap-2.5">
                        <span class="h-[18px] w-1 rounded-sm" style="background: var(--bite-lime);"></span>
                        <h3 class="font-display text-[18px] font-bold leading-none text-forest">{{ $category->translated('name') }}</h3>
                        <button
                            @click="let nameEn = prompt('Category name (English):', @js($category->name_en)); if (nameEn !== null) { nameEn = nameEn.trim(); if (nameEn.length) { let nameAr = prompt('Category name (Arabic):', @js($category->name_ar ?? '')); @this.renameCategory({{ $category->id }}, nameEn, nameAr) } }"
                            class="font-mono text-[10px] font-semibold uppercase tracking-[0.14em] text-ink-soft transition-colors hover:text-ink"
                        >
                            {{ __('admin.menu_rename') }}
                        </button>
                        <button
                            x-on:click="$dispatch('confirm-action', {
                                title: '{{ __('admin.menu_delete_category') }}',
                                message: '{{ __('admin.menu_delete_category_confirm') }}',
                                action: 'deleteCategory',
                                actionArgs: [{{ $category->id }}],
                                componentId: $wire.id,
                                destructive: true,
                            })"
                            class="font-mono text-[10px] font-semibold uppercase tracking-[0.14em] text-alert/80 transition-colors hover:text-alert"
                        >
                            {{ __('admin.menu_delete') }}
                        </button>
                    </div>
                    <span class="tag">{{ __('admin.menu_items_count', ['count' => $category->products->count()]) }}</span>
                </div>

                {{-- Drop Zone --}}
                <div
                    class="flex-1 space-y-3 p-4"
                    data-category-id="{{ $category->id }}"
                    x-init="
                        new Sortable($el, {
                            group: 'shared',
                            animation: 150,
                            ghostClass: 'opacity-20',
                            onEnd: (evt) => {
                                let itemEl = evt.item;
                                let productId = itemEl.getAttribute('data-id');
                                let targetList = evt.to;
                                let newCategoryId = targetList.getAttribute('data-category-id');

                                let items = Array.from(targetList.querySelectorAll('[data-id]')).map((el, index) => {
                                    return { value: el.getAttribute('data-id'), order: index };
                                });

                                @this.reorderProduct(productId, newCategoryId, items);
                            }
                        })
                    "
                >
                    @foreach($category->products as $product)
                        <div
                            data-id="{{ $product->id }}"
                            class="group flex cursor-move items-center justify-between rounded-2xl border border-line bg-cream p-4 transition-all duration-200 hover:border-olive hover:translate-x-0.5"
                        >
                            <div class="flex items-center gap-4">
                                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-mist font-mono text-[9px] font-semibold text-ink-soft">{{ $product->id }}</div>
                                <div>
                                    <div class="text-sm font-semibold text-ink">{{ $product->translated('name') }}</div>
                                    <div class="font-mono text-[10px] font-semibold text-forest"><x-price :amount="$product->price" :shop="$shop" /></div>
                                </div>
                            </div>

                            <div class="flex items-center gap-3 opacity-0 transition-opacity group-hover:opacity-100">
                                <a href="{{ route('admin.products', ['edit' => $product->id]) }}" class="font-mono text-[10px] font-semibold uppercase tracking-[0.14em] text-ink-soft transition-colors hover:text-forest">{{ __('admin.menu_edit') }}</a>
                                <button wire:click="toggleVisibility({{ $product->id }})" class="font-mono text-[10px] font-semibold uppercase tracking-[0.14em] {{ $product->is_visible ? 'text-signal' : 'text-alert' }}">
                                    {{ $product->is_visible ? __('admin.menu_visible') : __('admin.menu_hidden') }}
                                </button>
                                <button
                                    x-on:click="$dispatch('confirm-action', {
                                        title: '{{ __('admin.menu_delete_product') }}',
                                        message: '{{ __('admin.menu_delete_product_confirm') }}',
                                        action: 'deleteProduct',
                                        actionArgs: [{{ $product->id }}],
                                        componentId: $wire.id,
                                        destructive: true,
                                    })"
                                    class="font-mono text-[10px] font-semibold uppercase tracking-[0.14em] text-alert hover:text-alert"
                                >{{ __('admin.menu_delete') }}</button>
                                <svg class="h-4 w-4 text-ink-soft/40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16" /></svg>
                            </div>
                        </div>
                    @endforeach

                    @if($category->products->isEmpty())
                        <div class="rounded-2xl border border-dashed border-line px-4 py-8 text-center font-mono text-[10px] uppercase tracking-[0.18em] text-ink-soft">
                            {{ __('admin.menu_no_items') }}
                        </div>
                    @endif
                </div>
            </section>
        @endforeach
    </div>

    {{-- Sortable is bundled in resources/js/app.js (window.Sortable) so it loads
         under our CSP script-src 'self'; the x-init above uses it for drag-reorder. --}}
</div>
