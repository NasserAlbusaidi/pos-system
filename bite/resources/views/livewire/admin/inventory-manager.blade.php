<div class="h-full space-y-8">
    <x-slot:header>Inventory</x-slot:header>

    @if (session()->has('message'))
        <div class="bg-matcha text-paper p-3 font-mono text-[9px] font-black uppercase tracking-widest border border-ink shadow-[4px_4px_0_0_#000000]">
            {{ session('message') }}
        </div>
    @endif

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
        <div class="bg-paper border border-ink p-6 shadow-[4px_4px_0_0_#000000]">
            <h2 class="font-mono font-black text-xs uppercase tracking-[0.4em] mb-6">Add Ingredient</h2>
            <form wire:submit.prevent="addIngredient" class="space-y-4">
                <input type="text" wire:model="name" class="w-full bg-muted border-2 border-ink p-3 font-mono text-xs" placeholder="Ingredient name">
                <input type="text" wire:model="unit" class="w-full bg-muted border-2 border-ink p-3 font-mono text-xs" placeholder="Unit (ml, g, pcs)">
                <input type="number" step="0.01" wire:model="stock_quantity" class="w-full bg-muted border-2 border-ink p-3 font-mono text-xs" placeholder="Stock quantity">
                <input type="number" step="0.01" wire:model="reorder_threshold" class="w-full bg-muted border-2 border-ink p-3 font-mono text-xs" placeholder="Reorder threshold">
                <button type="submit" class="w-full bg-ink text-paper py-3 font-mono font-black text-[9px] uppercase tracking-[0.3em] shadow-[6px_6px_0_0_#FF4D00]">Add Ingredient</button>
            </form>
        </div>

        <div class="xl:col-span-2 bg-paper border-2 border-ink shadow-[8px_8px_0_0_#000000] overflow-hidden">
            <div class="p-6 bg-muted border-b border-ink">
                <h2 class="font-mono font-black text-xs uppercase tracking-[0.4em]">Stock Levels</h2>
            </div>
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-ink/10 font-mono text-[10px] uppercase tracking-widest text-ink/40">
                        <th class="p-6">Ingredient</th>
                        <th class="p-6">Stock</th>
                        <th class="p-6">Threshold</th>
                        <th class="p-6">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink/5">
                    @forelse($ingredients as $ingredient)
                        <tr class="hover:bg-muted/40 transition-colors">
                            <td class="p-6 font-mono text-xs uppercase tracking-tighter">
                                {{ $ingredient->name }}
                                <div class="text-[9px] opacity-40">Unit: {{ $ingredient->unit }}</div>
                            </td>
                            <td class="p-6">
                                <input type="number" step="0.01" wire:model.lazy="ingredientStocks.{{ $ingredient->id }}" class="w-28 bg-muted border border-ink p-2 font-mono text-xs">
                                @if($ingredientStocks[$ingredient->id] <= $ingredient->reorder_threshold)
                                    <div class="text-[9px] font-mono uppercase text-berry mt-1">Low stock</div>
                                @endif
                            </td>
                            <td class="p-6">
                                <input type="number" step="0.01" wire:model.lazy="ingredientThresholds.{{ $ingredient->id }}" class="w-28 bg-muted border border-ink p-2 font-mono text-xs">
                            </td>
                            <td class="p-6">
                                <button wire:click="saveIngredient({{ $ingredient->id }})" class="font-mono text-[9px] uppercase tracking-widest underline text-matcha">Save</button>
                                <button wire:click="deleteIngredient({{ $ingredient->id }})" onclick="return confirm('Delete ingredient?')" class="ml-4 font-mono text-[9px] uppercase tracking-widest underline text-berry">Delete</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="p-12 text-center font-mono text-xs opacity-30 italic uppercase tracking-widest">No ingredients yet...</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
        <div class="bg-paper border border-ink p-6 shadow-[4px_4px_0_0_#000000]">
            <h2 class="font-mono font-black text-xs uppercase tracking-[0.4em] mb-6">Add Supplier</h2>
            <form wire:submit.prevent="addSupplier" class="space-y-4">
                <input type="text" wire:model="supplierName" class="w-full bg-muted border-2 border-ink p-3 font-mono text-xs" placeholder="Supplier name">
                <input type="email" wire:model="supplierEmail" class="w-full bg-muted border-2 border-ink p-3 font-mono text-xs" placeholder="Email">
                <input type="text" wire:model="supplierPhone" class="w-full bg-muted border-2 border-ink p-3 font-mono text-xs" placeholder="Phone">
                <button type="submit" class="w-full bg-ink text-paper py-3 font-mono font-black text-[9px] uppercase tracking-[0.3em] shadow-[6px_6px_0_0_#FF4D00]">Add Supplier</button>
            </form>
        </div>

        <div class="xl:col-span-2 bg-paper border-2 border-ink shadow-[8px_8px_0_0_#000000] overflow-hidden">
            <div class="p-6 bg-muted border-b border-ink">
                <h2 class="font-mono font-black text-xs uppercase tracking-[0.4em]">Suppliers</h2>
            </div>
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-ink/10 font-mono text-[10px] uppercase tracking-widest text-ink/40">
                        <th class="p-6">Name</th>
                        <th class="p-6">Contact</th>
                        <th class="p-6">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink/5">
                    @forelse($suppliers as $supplier)
                        <tr class="hover:bg-muted/40 transition-colors">
                            <td class="p-6 font-mono text-xs uppercase tracking-tighter">{{ $supplier->name }}</td>
                            <td class="p-6 font-mono text-[9px] uppercase opacity-40">
                                {{ $supplier->email ?? '—' }}<br>
                                {{ $supplier->phone ?? '' }}
                            </td>
                            <td class="p-6">
                                <button wire:click="deleteSupplier({{ $supplier->id }})" onclick="return confirm('Delete supplier?')" class="font-mono text-[9px] uppercase tracking-widest underline text-berry">Delete</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="p-12 text-center font-mono text-xs opacity-30 italic uppercase tracking-widest">No suppliers yet...</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
