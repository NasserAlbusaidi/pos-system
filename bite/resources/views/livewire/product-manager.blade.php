<div class="h-full">
    <x-slot:header>Product Catalog</x-slot:header>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-12">
        <div class="lg:col-span-1">
            <div class="bg-paper border border-ink p-8 space-y-8">
                <h2 class="font-mono font-black text-xs uppercase tracking-widest bg-ink text-paper px-3 py-1 inline-block italic">
                    {{ $editingProductId ? 'Edit Product' : 'Add New Product' }}
                </h2>

                @if (session()->has('message'))
                    <div class="bg-matcha text-paper p-3 font-mono text-[9px] font-black uppercase tracking-widest border border-ink shadow-[4px_4px_0_0_#000000]">
                        {{ session('message') }}
                    </div>
                @endif
                
                <form wire:submit.prevent="save" class="space-y-10">
                    <div class="space-y-2">
                        <label class="font-mono text-[9px] uppercase font-bold tracking-[0.3em] opacity-40">Product Name</label>
                        <input type="text" wire:model="name" class="w-full bg-transparent border-b border-ink/20 focus:border-crema focus:ring-0 font-mono text-lg transition-all" placeholder="Latte">
                    </div>

                    <div class="space-y-2">
                        <label class="font-mono text-[9px] uppercase font-bold tracking-[0.3em] opacity-40">Price</label>
                        <input type="number" step="0.01" wire:model="price" class="w-full bg-transparent border-b border-ink/20 focus:border-crema focus:ring-0 font-mono text-lg transition-all" placeholder="4.50">
                    </div>

                    <div class="space-y-2">
                        <label class="font-mono text-[9px] uppercase font-bold tracking-[0.3em] opacity-40">Tax Rate (%)</label>
                        <input type="number" step="0.01" wire:model="tax_rate" class="w-full bg-transparent border-b border-ink/20 focus:border-crema focus:ring-0 font-mono text-lg transition-all" placeholder="0">
                    </div>

                    <div class="space-y-4">
                        <label class="font-mono text-[9px] uppercase font-bold tracking-[0.3em] opacity-40">Image</label>
                        <div class="border-2 border-dashed border-ink/10 p-4 flex flex-col items-center justify-center space-y-4">
                            @if ($image)
                                <img src="{{ $image->temporaryUrl() }}" class="w-20 h-20 object-cover border border-ink">
                            @elseif ($currentImageUrl)
                                <img src="{{ asset('storage/' . $currentImageUrl) }}" class="w-20 h-20 object-cover border border-ink">
                            @else
                                <div class="w-20 h-20 bg-muted border border-ink flex items-center justify-center font-mono text-[10px] opacity-20 italic">No Image</div>
                            @endif
                            <input type="file" wire:model="image" class="font-mono text-[9px]">
                        </div>
                        @error('image') <span class="text-berry text-[10px] font-mono uppercase">{{ $message }}</span> @enderror
                    </div>

                    <div class="space-y-4">
                        <label class="font-mono text-[9px] uppercase font-bold tracking-[0.3em] opacity-40">Category</label>
                        <select wire:model="category_id" class="w-full bg-transparent border-b border-ink/20 focus:border-crema focus:ring-0 font-mono py-2">
                            <option value="">-- None --</option>
                            @foreach(\App\Models\Category::where('shop_id', Auth::user()->shop_id)->get() as $cat)
                                <option value="{{ $cat->id }}">{{ strtoupper($cat->name) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Modifiers -->
                    <div class="space-y-4">
                        <label class="font-mono text-[9px] uppercase font-bold tracking-[0.3em] opacity-40">Modifiers</label>
                        <div class="grid grid-cols-1 gap-2">
                            @foreach(\App\Models\ModifierGroup::where('shop_id', Auth::user()->shop_id)->get() as $group)
                                <label class="flex items-center space-x-3 p-3 border border-ink/10 hover:border-ink cursor-pointer transition-all">
                                    <input type="checkbox" wire:model="selectedModifierGroups" value="{{ $group->id }}" class="text-crema focus:ring-0 border-ink">
                                    <span class="font-mono text-[10px] uppercase font-bold">{{ $group->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <button type="submit" class="btn-primary w-full py-5 shadow-[6px_6px_0_0_#FF4D00]">
                        {{ $editingProductId ? '[ Update Product ]' : '[ Save Product ]' }}
                    </button>

                    @if ($editingProductId)
                        <button type="button" wire:click="cancelEdit" class="w-full py-4 font-mono font-black text-[9px] uppercase tracking-[0.3em] border border-ink text-ink hover:bg-ink hover:text-paper transition-all">
                            Cancel Edit
                        </button>
                    @endif
                </form>
            </div>
        </div>

        <div class="lg:col-span-2 space-y-8">
            <div class="bg-paper border border-ink p-8">
                <h3 class="font-mono font-black text-[10px] uppercase tracking-[0.4em] opacity-30 mb-8">// Current Inventory</h3>
                
                <div class="grid grid-cols-1 gap-px bg-ink/10">
                    @foreach(\App\Models\Product::where('shop_id', Auth::user()->shop_id)->get() as $product)
                        <div class="bg-paper p-6 flex justify-between items-center group">
                            <div class="flex items-center space-x-6">
                                @if($product->image_url)
                                    <img src="{{ asset('storage/' . $product->image_url) }}" class="w-10 h-10 object-cover border border-ink">
                                @else
                                    <div class="w-10 h-10 bg-muted flex items-center justify-center font-mono font-black text-xs">{{ $loop->iteration }}</div>
                                @endif
                                <div>
                                    <div class="font-mono font-black text-sm uppercase tracking-tight">{{ $product->name }}</div>
                                    <div class="font-mono text-[9px] text-ink/30 uppercase mt-1">{{ $product->category->name }}</div>
                                </div>
                            </div>
                            <div class="flex items-center space-x-12">
                                <div class="font-mono font-black text-sm">{{ formatPrice($product->price, $shop) }}</div>
                                <button wire:click="editProduct({{ $product->id }})" class="opacity-0 group-hover:opacity-100 font-mono text-[9px] uppercase font-black tracking-widest text-crema underline transition-opacity">Edit</button>
                                <button wire:click="openRecipe({{ $product->id }})" class="opacity-0 group-hover:opacity-100 font-mono text-[9px] uppercase font-black tracking-widest text-ink/60 underline transition-opacity">Recipe</button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    @if($recipeProductId)
        <div class="fixed inset-0 z-[120] flex items-center justify-center bg-ink/80 backdrop-blur-sm p-6">
            <div class="bg-paper border-4 border-ink w-full max-w-xl shadow-[12px_12px_0_0_#FF4D00]">
                <div class="p-6 border-b border-ink flex justify-between items-center bg-muted/30">
                    <div>
                        <h3 class="font-mono font-black text-xl uppercase tracking-tighter">Recipe Builder</h3>
                        <p class="font-mono text-[9px] uppercase tracking-[0.3em] opacity-40">Set ingredient quantities per item</p>
                    </div>
                    <button wire:click="closeRecipe" class="text-ink hover:text-crema transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="p-6 space-y-3 max-h-[60vh] overflow-y-auto">
                    @forelse($ingredients as $ingredient)
                        <div class="flex items-center justify-between border border-ink/10 p-3">
                            <div>
                                <div class="font-mono text-xs uppercase tracking-widest">{{ $ingredient->name }}</div>
                                <div class="font-mono text-[9px] uppercase opacity-40">Unit: {{ $ingredient->unit }}</div>
                            </div>
                            <input type="number" step="0.01" min="0" wire:model.lazy="recipeIngredients.{{ $ingredient->id }}" class="w-24 bg-muted border border-ink p-2 font-mono text-xs text-center" placeholder="0">
                        </div>
                    @empty
                        <div class="text-center font-mono text-[9px] uppercase tracking-widest opacity-40">Add ingredients first.</div>
                    @endforelse
                </div>

                <div class="p-6 border-t border-ink flex gap-4 bg-muted/10">
                    <button wire:click="closeRecipe" class="flex-1 py-3 font-mono font-black text-[10px] uppercase tracking-widest border-2 border-ink hover:bg-paper transition-all">Cancel</button>
                    <button wire:click="saveRecipe" class="flex-1 py-3 bg-ink text-paper font-mono font-black text-[10px] uppercase tracking-widest hover:bg-crema hover:border-crema shadow-[6px_6px_0_0_#FF4D00] hover:shadow-none hover:translate-x-1 hover:translate-y-1 transition-all">Save Recipe</button>
                </div>
            </div>
        </div>
    @endif
</div>
