<div class="space-y-6 fade-rise">
    <x-slot:header>Product Catalog</x-slot:header>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-1">
            <div class="surface-card p-5 sm:p-6">
                <div class="space-y-6">
                    <h2 class="font-display text-xl font-extrabold leading-none text-ink">
                        {{ $editingProductId ? 'Edit Product' : 'Add New Product' }}
                    </h2>

                    <form wire:submit.prevent="save" class="space-y-6">
                        <div class="space-y-2">
                            <label class="font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">Product Name</label>
                            <input type="text" wire:model="name" class="field" placeholder="Latte">
                        </div>

                        <div class="space-y-2">
                            <label class="font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">Price</label>
                            <input type="number" step="0.01" wire:model="price" class="field" placeholder="4.50">
                        </div>

                        <div class="space-y-2">
                            <label class="font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">Tax Rate (%)</label>
                            <input type="number" step="0.01" wire:model="tax_rate" class="field" placeholder="0">
                        </div>

                        <div class="space-y-4">
                            <label class="font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">Image</label>
                            <div class="rounded-xl border-2 border-dashed border-line p-6 flex flex-col items-center justify-center space-y-4">
                                @if ($image)
                                    <img src="{{ $image->temporaryUrl() }}" class="h-20 w-20 rounded-lg object-cover border border-line">
                                @elseif ($currentImageUrl)
                                    <img src="{{ asset('storage/' . $currentImageUrl) }}" class="h-20 w-20 rounded-lg object-cover border border-line">
                                @else
                                    <div class="flex h-20 w-20 items-center justify-center rounded-lg border border-dashed border-line bg-muted font-mono text-[10px] text-ink-soft">No Image</div>
                                @endif
                                <input type="file" wire:model="image" class="font-mono text-[10px] text-ink-soft">
                            </div>
                            @error('image') <span class="font-mono text-[10px] font-semibold text-alert">{{ $message }}</span> @enderror
                        </div>

                        <div class="space-y-4">
                            <label class="font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">Category</label>
                            <select wire:model="category_id" class="field">
                                <option value="">-- None --</option>
                                @foreach(\App\Models\Category::where('shop_id', Auth::user()->shop_id)->get() as $cat)
                                    <option value="{{ $cat->id }}">{{ strtoupper($cat->name) }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Modifiers -->
                        <div class="space-y-4">
                            <label class="font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">Modifiers</label>
                            <div class="grid grid-cols-1 gap-2">
                                @foreach(\App\Models\ModifierGroup::where('shop_id', Auth::user()->shop_id)->get() as $group)
                                    <label class="flex items-center gap-3 rounded-lg border border-line bg-panel px-3 py-3 cursor-pointer transition-colors hover:border-ink-soft has-[:checked]:border-crema has-[:checked]:bg-crema/5">
                                        <input type="checkbox" wire:model="selectedModifierGroups" value="{{ $group->id }}" class="text-crema focus:ring-0 border-ink">
                                        <span class="font-mono text-[10px] uppercase font-bold">{{ $group->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <button type="submit" class="btn-primary w-full">
                            {{ $editingProductId ? 'Update Product' : 'Save Product' }}
                        </button>

                        @if ($editingProductId)
                            <button type="button" wire:click="cancelEdit" class="btn-secondary w-full">
                                Cancel Edit
                            </button>
                        @endif
                    </form>
                </div>
            </div>
        </div>

        <div class="lg:col-span-2 space-y-6">
            <div class="surface-card">
                <div class="border-b border-line bg-muted/35 px-5 py-4">
                    <h3 class="font-display text-xl font-extrabold leading-none">Current Inventory</h3>
                </div>

                <div class="divide-y divide-line/65">
                    @foreach(\App\Models\Product::where('shop_id', Auth::user()->shop_id)->get() as $product)
                        <div class="flex items-center justify-between px-5 py-4 transition-colors hover:bg-muted/35 group">
                            <div class="flex items-center space-x-6">
                                @if($product->image_url)
                                    <img src="{{ asset('storage/' . $product->image_url) }}" class="h-10 w-10 rounded-lg object-cover border border-line">
                                @else
                                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-muted font-mono text-xs font-bold text-ink-soft">{{ $loop->iteration }}</div>
                                @endif
                                <div>
                                    <div class="text-sm font-semibold uppercase tracking-tight text-ink">{{ $product->name }}</div>
                                    <div class="mt-0.5 font-mono text-[10px] text-ink-soft">{{ $product->category->name }}</div>
                                </div>
                            </div>
                            <div class="flex items-center space-x-12">
                                <div class="font-mono text-sm font-bold">{{ formatPrice($product->price, $shop) }}</div>
                                <button wire:click="editProduct({{ $product->id }})" class="opacity-0 group-hover:opacity-100 font-mono text-[10px] font-semibold uppercase tracking-[0.14em] text-crema hover:text-crema transition-opacity">Edit</button>
                                <button wire:click="openRecipe({{ $product->id }})" class="opacity-0 group-hover:opacity-100 font-mono text-[10px] font-semibold uppercase tracking-[0.14em] text-ink-soft hover:text-ink transition-opacity">Recipe</button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    @if($recipeProductId)
        <div class="fixed inset-0 z-[120] flex items-center justify-center bg-ink/75 backdrop-blur-sm p-6">
            <div class="surface-card w-full max-w-xl">
                <div class="flex items-center justify-between border-b border-line bg-muted/35 px-5 py-4">
                    <div>
                        <h3 class="font-display text-xl font-extrabold leading-none text-ink">Recipe Builder</h3>
                        <p class="mt-1 font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">Set ingredient quantities per item</p>
                    </div>
                    <button wire:click="closeRecipe" class="text-ink hover:text-crema transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="p-5 space-y-3 max-h-[60vh] overflow-y-auto">
                    @forelse($ingredients as $ingredient)
                        <div class="flex items-center justify-between rounded-lg border border-line bg-panel px-4 py-3">
                            <div>
                                <div class="font-mono text-xs uppercase tracking-widest">{{ $ingredient->name }}</div>
                                <div class="font-mono text-[9px] uppercase opacity-40">Unit: {{ $ingredient->unit }}</div>
                            </div>
                            <input type="number" step="0.01" min="0" wire:model.lazy="recipeIngredients.{{ $ingredient->id }}" class="field w-24 text-center font-mono text-xs" placeholder="0">
                        </div>
                    @empty
                        <div class="text-center font-mono text-[10px] uppercase tracking-[0.16em] text-ink-soft">Add ingredients first.</div>
                    @endforelse
                </div>

                <div class="grid grid-cols-2 gap-3 border-t border-line bg-muted/20 p-5">
                    <button wire:click="closeRecipe" class="btn-secondary w-full">Cancel</button>
                    <button wire:click="saveRecipe" class="btn-primary w-full">Save Recipe</button>
                </div>
            </div>
        </div>
    @endif
</div>
