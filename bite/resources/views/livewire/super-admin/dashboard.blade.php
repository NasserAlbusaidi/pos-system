<div>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-12">
        <div class="bg-paper border border-ink p-8 shadow-[4px_4px_0_0_#000000]">
            <div class="font-mono text-[10px] font-bold uppercase tracking-widest text-ink/40 mb-2">Total Tenants</div>
            <div class="text-4xl font-mono font-black">{{ $totalShops }}</div>
        </div>
        <div class="bg-paper border border-ink p-8 shadow-[4px_4px_0_0_#3E6B48]">
            <div class="font-mono text-[10px] font-bold uppercase tracking-widest text-ink/40 mb-2">Active Shops</div>
            <div class="text-4xl font-mono font-black text-matcha">{{ $activeShops }}</div>
        </div>
        <div class="bg-paper border border-ink p-8 shadow-[4px_4px_0_0_#FF4D00]">
            <div class="font-mono text-[10px] font-bold uppercase tracking-widest text-ink/40 mb-2">Platform Status</div>
            <div class="text-xs font-mono font-black uppercase tracking-widest text-crema italic">ONLINE</div>
        </div>
    </div>

    <div class="bg-paper border-2 border-ink shadow-[8px_8px_0_0_#000000] overflow-hidden">
        <div class="p-6 bg-muted border-b border-ink flex justify-between items-center">
            <h2 class="font-mono font-black text-xs uppercase tracking-[0.4em]">Shop Directory</h2>
            <a href="{{ route('super-admin.shops.create') }}" class="bg-ink text-paper px-4 py-2 font-mono text-[9px] font-black uppercase tracking-widest hover:bg-crema hover:border-crema transition-colors border border-ink">+ Create Shop</a>
        </div>

        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="border-b border-ink/10 font-mono text-[10px] uppercase tracking-widest text-ink/40">
                    <th class="p-6">Shop ID</th>
                    <th class="p-6">Shop Name</th>
                    <th class="p-6">Products</th>
                    <th class="p-6">Orders</th>
                    <th class="p-6">Status</th>
                    <th class="p-6 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-ink/5">
                @foreach($shops as $shop)
                    <tr class="hover:bg-muted/50 transition-colors">
                        <td class="p-6 font-mono text-xs">{{ $shop->id }}</td>
                        <td class="p-6 font-bold uppercase tracking-tight">{{ $shop->name }}</td>
                        <td class="p-6 font-mono text-xs opacity-50">{{ $shop->products_count }} units</td>
                        <td class="p-6 font-mono text-xs opacity-50">{{ $shop->orders_count }} orders</td>
                        <td class="p-6">
                            <span class="px-2 py-1 font-mono text-[9px] font-black uppercase tracking-widest border {{ $shop->status === 'active' ? 'border-matcha text-matcha' : 'border-berry text-berry' }}">
                                {{ $shop->status }}
                            </span>
                        </td>
                        <td class="p-6 text-right space-x-4">
                            <button wire:click="toggleStatus({{ $shop->id }})" class="font-mono text-[9px] font-black uppercase tracking-widest underline hover:text-crema">Toggle Status</button>
                            <button wire:confirm="Are you sure you want to delete this shop?" wire:click="deleteShop({{ $shop->id }})" class="font-mono text-[9px] font-black uppercase tracking-widest underline text-berry">Delete Shop</button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>