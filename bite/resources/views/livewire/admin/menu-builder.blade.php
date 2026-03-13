<div class="space-y-6 fade-rise" x-data>
    <x-slot:header>{{ __('admin.menu_builder') }}</x-slot:header>

    <!-- Toolbar -->
    <div class="surface-card p-5 sm:p-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex-1 max-w-md">
                <label class="section-headline mb-2 block">{{ __('admin.menu_search') }}</label>
                <input type="text" wire:model.live="search" class="field text-sm" placeholder="{{ __('admin.menu_search_placeholder') }}">
            </div>

            <div class="flex items-center gap-3">
                <input type="text" wire:model="newCategoryNameEn" placeholder="{{ __('admin.menu_category_en_placeholder') }}" class="field text-sm">
                <input type="text" wire:model="newCategoryNameAr" placeholder="{{ __('admin.menu_category_ar_placeholder') }}" class="field text-sm" dir="rtl">
                <button wire:click="createCategory" class="btn-primary whitespace-nowrap">{{ __('admin.menu_add_category') }}</button>
            </div>
        </div>
    </div>

    <!-- Canvas -->
    <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6 items-start pb-24">
        @foreach($categories as $category)
            <div class="surface-card flex flex-col min-h-[400px]">
                <!-- Category Header -->
                <div class="border-b border-line bg-muted/35 px-5 py-4 flex justify-between items-center">
                    <div class="flex items-center gap-3">
                        <h3 class="font-display text-lg font-extrabold leading-none text-ink">{{ $category->name_en }}</h3>
                        <button
                            @click="let nameEn = prompt('Category name (English):', @js($category->name_en)); if (nameEn !== null) { nameEn = nameEn.trim(); if (nameEn.length) { let nameAr = prompt('Category name (Arabic):', @js($category->name_ar ?? '')); @this.renameCategory({{ $category->id }}, nameEn, nameAr) } }"
                            class="font-mono text-[10px] font-semibold uppercase tracking-[0.14em] text-ink-soft hover:text-ink transition-colors"
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
                            class="font-mono text-[10px] font-semibold uppercase tracking-[0.14em] text-alert/80 hover:text-alert transition-colors"
                        >
                            {{ __('admin.menu_delete') }}
                        </button>
                    </div>
                    <span class="tag">{{ __('admin.menu_items_count', ['count' => $category->products->count()]) }}</span>
                </div>

                <!-- Drop Zone -->
                <div
                    class="flex-1 p-4 space-y-3 bg-muted/20"
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
                            class="group rounded-xl border border-line bg-panel p-4 cursor-move hover:border-crema hover:translate-x-0.5 transition-all duration-200 flex justify-between items-center"
                        >
                            <div class="flex items-center space-x-4">
                                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-muted font-mono text-[9px] font-semibold text-ink-soft">{{ $product->id }}</div>
                                <div>
                                    <div class="text-sm font-semibold uppercase tracking-tight text-ink">{{ $product->name_en }}</div>
                                    <div class="font-mono text-[10px] font-semibold text-ink-soft"><x-price :amount="$product->price" :shop="$shop" /></div>
                                </div>
                            </div>

                            <div class="flex items-center space-x-3 opacity-0 group-hover:opacity-100 transition-opacity">
                                <a href="{{ route('admin.products', ['edit' => $product->id]) }}" class="font-mono text-[10px] font-semibold uppercase tracking-[0.14em] text-ink-soft hover:text-crema transition-colors">{{ __('admin.menu_edit') }}</a>
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
                                <svg class="w-4 h-4 text-ink-soft/40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16" /></svg>
                            </div>
                        </div>
                    @endforeach

                    @if($category->products->isEmpty())
                        <div class="rounded-xl border border-dashed border-line p-12 text-center">
                            <span class="font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">{{ __('admin.menu_no_items') }}</span>
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    <!-- Sortable Script -->
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js" integrity="sha384-eeLEhtwdMwD3X9y+8P3Cn7Idl/M+w8H4uZqkgD/2eJVkWIN1yKzEj6XegJ9dL3q0" crossorigin="anonymous"></script>
</div>
