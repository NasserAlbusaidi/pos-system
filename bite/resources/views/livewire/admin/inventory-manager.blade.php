<div class="space-y-6 fade-rise">
    <x-slot:header>Inventory</x-slot:header>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <div class="surface-card p-5 sm:p-6">
            <h2 class="font-display text-xl font-extrabold leading-none text-ink mb-5">Add Ingredient</h2>
            <form wire:submit.prevent="addIngredient" class="space-y-4">
                <input type="text" wire:model="name" class="field text-sm" placeholder="Ingredient name">
                <input type="text" wire:model="unit" class="field text-sm" placeholder="Unit (ml, g, pcs)">
                <input type="number" step="0.01" wire:model="stock_quantity" class="field text-sm" placeholder="Stock quantity">
                <input type="number" step="0.01" wire:model="reorder_threshold" class="field text-sm" placeholder="Reorder threshold">
                <button type="submit" class="btn-primary w-full">Add Ingredient</button>
            </form>
        </div>

        <div class="xl:col-span-2 surface-card overflow-hidden">
            <div class="border-b border-line bg-muted/35 px-5 py-4">
                <h2 class="font-display text-xl font-extrabold leading-none">Stock Levels</h2>
            </div>
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-line bg-panel font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">
                        <th class="px-5 py-4">Ingredient</th>
                        <th class="px-5 py-4">Stock</th>
                        <th class="px-5 py-4">Threshold</th>
                        <th class="px-5 py-4">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line/65">
                    @forelse($ingredients as $ingredient)
                        <tr class="hover:bg-muted/35 transition-colors">
                            <td class="px-5 py-4 text-sm font-semibold uppercase tracking-tight text-ink">
                                {{ $ingredient->name }}
                                <div class="text-[10px] text-ink-soft">Unit: {{ $ingredient->unit }}</div>
                            </td>
                            <td class="px-5 py-4">
                                <input type="number" step="0.01" wire:model.lazy="ingredientStocks.{{ $ingredient->id }}" class="field w-28 text-center font-mono text-xs">
                                @if($ingredientStocks[$ingredient->id] <= $ingredient->reorder_threshold)
                                    <div class="font-mono text-[10px] font-semibold text-alert mt-1">Low stock</div>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                <input type="number" step="0.01" wire:model.lazy="ingredientThresholds.{{ $ingredient->id }}" class="field w-28 text-center font-mono text-xs">
                            </td>
                            <td class="px-5 py-4">
                                <button wire:click="saveIngredient({{ $ingredient->id }})" class="font-mono text-[10px] font-semibold uppercase tracking-[0.14em] text-signal hover:text-signal transition-colors">Save</button>
                                <button wire:click="deleteIngredient({{ $ingredient->id }})" class="ml-4 font-mono text-[10px] font-semibold uppercase tracking-[0.14em] text-alert hover:text-alert transition-colors">Delete</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="p-12 text-center font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">No ingredients yet...</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <div class="surface-card p-5 sm:p-6">
            <h2 class="font-display text-xl font-extrabold leading-none text-ink mb-5">Add Supplier</h2>
            <form wire:submit.prevent="addSupplier" class="space-y-4">
                <input type="text" wire:model="supplierName" class="field text-sm" placeholder="Supplier name">
                <input type="email" wire:model="supplierEmail" class="field text-sm" placeholder="Email">
                <input type="text" wire:model="supplierPhone" class="field text-sm" placeholder="Phone">
                <button type="submit" class="btn-primary w-full">Add Supplier</button>
            </form>
        </div>

        <div class="xl:col-span-2 surface-card overflow-hidden">
            <div class="border-b border-line bg-muted/35 px-5 py-4">
                <h2 class="font-display text-xl font-extrabold leading-none">Suppliers</h2>
            </div>
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-line bg-panel font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">
                        <th class="px-5 py-4">Name</th>
                        <th class="px-5 py-4">Contact</th>
                        <th class="px-5 py-4">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line/65">
                    @forelse($suppliers as $supplier)
                        <tr class="hover:bg-muted/35 transition-colors">
                            <td class="px-5 py-4 text-sm font-semibold uppercase tracking-tight text-ink">{{ $supplier->name }}</td>
                            <td class="px-5 py-4 text-[10px] text-ink-soft">
                                {{ $supplier->email ?? '—' }}<br>
                                {{ $supplier->phone ?? '' }}
                            </td>
                            <td class="px-5 py-4">
                                <button wire:click="deleteSupplier({{ $supplier->id }})" class="font-mono text-[10px] font-semibold uppercase tracking-[0.14em] text-alert hover:text-alert transition-colors">Delete</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="p-12 text-center font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">No suppliers yet...</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
