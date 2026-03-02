<div class="max-w-3xl mx-auto">
    <x-slot name="header">
        {{ $shop ? 'Manage Shop: ' . $shop->name : 'Onboard New Shop' }}
    </x-slot>

    <form wire:submit="save" class="space-y-8">
        <!-- Shop Details -->
        <div class="bg-paper border border-ink p-8 space-y-6">
            <h3 class="font-mono font-black text-sm uppercase tracking-widest border-b border-ink pb-4">Tenant Details</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block font-mono text-[10px] uppercase tracking-widest mb-2">Shop Name</label>
                    <input wire:model.live="name" type="text" class="w-full bg-muted border border-ink p-3 font-mono text-sm focus:border-crema outline-none">
                    @error('name') <span class="text-red-500 text-[10px] font-mono">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block font-mono text-[10px] uppercase tracking-widest mb-2">Slug (Subdomain)</label>
                    <input wire:model="slug" type="text" class="w-full bg-muted border border-ink p-3 font-mono text-sm focus:border-crema outline-none">
                    @error('slug') <span class="text-red-500 text-[10px] font-mono">{{ $message }}</span> @enderror
                </div>
            </div>

            <div>
                <label class="block font-mono text-[10px] uppercase tracking-widest mb-2">Status</label>
                <div class="flex space-x-4">
                    <label class="flex items-center space-x-2 cursor-pointer">
                        <input type="radio" wire:model="status" value="active" class="text-crema focus:ring-crema">
                        <span class="font-mono text-sm">Active</span>
                    </label>
                    <label class="flex items-center space-x-2 cursor-pointer">
                        <input type="radio" wire:model="status" value="suspended" class="text-crema focus:ring-crema">
                        <span class="font-mono text-sm">Suspended</span>
                    </label>
                    <label class="flex items-center space-x-2 cursor-pointer">
                        <input type="radio" wire:model="status" value="trial" class="text-crema focus:ring-crema">
                        <span class="font-mono text-sm">Trial</span>
                    </label>
                </div>
                @error('status') <span class="text-red-500 text-[10px] font-mono">{{ $message }}</span> @enderror
            </div>
        </div>

        <!-- Initial Owner (Only on Create) -->
        @if(!$shop)
            <div class="bg-paper border border-ink p-8 space-y-6">
                <h3 class="font-mono font-black text-sm uppercase tracking-widest border-b border-ink pb-4">Owner Account</h3>
                
                <div class="grid grid-cols-1 gap-6">
                    <div>
                        <label class="block font-mono text-[10px] uppercase tracking-widest mb-2">Owner Name</label>
                        <input wire:model="ownerName" type="text" class="w-full bg-muted border border-ink p-3 font-mono text-sm focus:border-crema outline-none">
                        @error('ownerName') <span class="text-red-500 text-[10px] font-mono">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block font-mono text-[10px] uppercase tracking-widest mb-2">Email Address</label>
                        <input wire:model="ownerEmail" type="email" class="w-full bg-muted border border-ink p-3 font-mono text-sm focus:border-crema outline-none">
                        @error('ownerEmail') <span class="text-red-500 text-[10px] font-mono">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block font-mono text-[10px] uppercase tracking-widest mb-2">Initial Password</label>
                        <input wire:model="ownerPassword" type="password" class="w-full bg-muted border border-ink p-3 font-mono text-sm focus:border-crema outline-none">
                        @error('ownerPassword') <span class="text-red-500 text-[10px] font-mono">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>
        @endif

        <div class="flex justify-end space-x-4">
            <a href="{{ route('super-admin.shops.index') }}" class="px-6 py-3 border border-ink font-mono text-xs font-black uppercase tracking-widest hover:bg-muted transition-colors">
                Cancel
            </a>
            <button type="submit" class="bg-ink text-paper px-8 py-3 font-mono text-xs font-black uppercase tracking-widest hover:bg-crema hover:border-crema transition-colors border border-ink shadow-[4px_4px_0_0_#000000]">
                {{ $shop ? 'Update Shop' : 'Provision Shop' }}
            </button>
        </div>
    </form>
</div>