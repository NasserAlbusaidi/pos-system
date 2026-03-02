<div class="h-full flex flex-col space-y-8" x-data>
    <x-slot:header>Menu Builder</x-slot:header>

    <!-- Toolbar -->
    <div class="flex justify-between items-center bg-paper border border-ink p-6 shadow-[4px_4px_0_0_#000000]">
        <div class="flex-1 max-w-md relative">
            <span class="absolute left-4 top-1/2 -translate-y-1/2 font-mono text-[10px] opacity-30">SEARCH:</span>
            <input type="text" wire:model.live="search" class="w-full bg-muted border-none pl-24 py-3 font-mono text-sm focus:ring-2 focus:ring-crema transition-all" placeholder="...">
        </div>
        
        <div class="flex items-center space-x-4">
            <div class="flex items-center space-x-2">
                <input type="text" wire:model="newCategoryName" placeholder="Category Name" class="bg-muted border-none font-mono text-[10px] px-4 py-3 focus:ring-crema">
                <button wire:click="createCategory" class="bg-ink text-paper px-6 py-3 font-mono font-black text-[10px] uppercase tracking-widest hover:bg-crema transition-colors border border-ink">+ Add Category</button>
            </div>
        </div>
    </div>

    @if (session()->has('message'))
        <div class="bg-berry text-paper p-3 font-mono text-[9px] font-black uppercase tracking-widest border border-ink shadow-[4px_4px_0_0_#000000]">
            {{ session('message') }}
        </div>
    @endif

    <!-- Canvas -->
    <div class="flex-1 grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-12 items-start pb-24">
        @foreach($categories as $category)
            <div class="bg-paper border-2 border-ink flex flex-col min-h-[400px] shadow-[8px_8px_0_0_#000000]">
                <!-- Category Header -->
                <div class="p-4 bg-ink text-paper flex justify-between items-center">
                    <div class="flex items-center space-x-3">
                        <h3 class="font-mono font-black text-xs uppercase tracking-[0.3em] italic">{{ $category->name }}</h3>
                        <button
                            @click="let name = prompt('Rename category:', @js($category->name)); if (name !== null) { name = name.trim(); if (name.length) { @this.renameCategory({{ $category->id }}, name) } }"
                            class="text-[9px] font-mono uppercase tracking-widest underline opacity-70 hover:opacity-100"
                        >
                            Rename
                        </button>
                        <button
                            wire:click="deleteCategory({{ $category->id }})"
                            onclick="return confirm('Delete this category? It must be empty first.')"
                            class="text-[9px] font-mono uppercase tracking-widest underline text-berry/90 hover:text-berry"
                        >
                            Delete
                        </button>
                    </div>
                    <span class="font-mono text-[9px] opacity-50">{{ $category->products->count() }} items</span>
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
                            class="group bg-paper border border-ink p-4 cursor-move hover:border-crema hover:translate-x-1 transition-all flex justify-between items-center"
                        >
                            <div class="flex items-center space-x-4">
                                <div class="w-8 h-8 bg-muted flex items-center justify-center font-mono text-[9px] opacity-40">{{ $product->id }}</div>
                                <div>
                                    <div class="font-mono font-bold text-xs uppercase">{{ $product->name }}</div>
                                    <div class="font-mono text-[10px] text-ink/30">{{ formatPrice($product->price, $shop) }}</div>
                                </div>
                            </div>

                            <div class="flex items-center space-x-3 opacity-0 group-hover:opacity-100 transition-opacity">
                                <a href="{{ route('admin.products', ['edit' => $product->id]) }}" class="font-mono text-[9px] font-black uppercase underline text-ink/70 hover:text-ink">Edit</a>
                                <button wire:click="toggleVisibility({{ $product->id }})" class="font-mono text-[9px] font-black uppercase {{ $product->is_visible ? 'text-matcha' : 'text-berry' }} underline">
                                    {{ $product->is_visible ? 'Visible' : 'Hidden' }}
                                </button>
                                <button wire:click="deleteProduct({{ $product->id }})" onclick="return confirm('Delete this product?')" class="font-mono text-[9px] font-black uppercase underline text-berry/90 hover:text-berry">Delete</button>
                                <svg class="w-4 h-4 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16" /></svg>
                            </div>
                        </div>
                    @endforeach

                    @if($category->products->isEmpty())
                        <div class="py-12 flex flex-col items-center justify-center border border-dashed border-ink/10 opacity-30 grayscale">
                            <span class="font-mono text-[9px] uppercase tracking-widest">No Items</span>
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    <!-- Sortable Script -->
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
</div>
