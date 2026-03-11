<div>
    <x-slot name="header">
        Shops Directory
    </x-slot>

    <div class="flex justify-between items-center mb-6">
        <div class="w-1/3">
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search shops..." class="w-full bg-paper border border-ink p-3 font-mono text-xs focus:ring-0 focus:border-crema outline-none">
        </div>
        <a href="{{ route('super-admin.shops.create') }}" class="bg-ink text-paper px-6 py-3 font-mono text-xs font-black uppercase tracking-widest hover:bg-crema hover:border-crema transition-colors border border-ink">
            + Add Shop
        </a>
    </div>

    <div class="bg-paper border border-ink overflow-hidden">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="border-b border-ink bg-muted/20">
                    <th class="p-4 font-mono text-[10px] uppercase tracking-widest text-ink/50">Shop Name</th>
                    <th class="p-4 font-mono text-[10px] uppercase tracking-widest text-ink/50">Slug</th>
                    <th class="p-4 font-mono text-[10px] uppercase tracking-widest text-ink/50">Status</th>
                    <th class="p-4 font-mono text-[10px] uppercase tracking-widest text-ink/50">Created</th>
                    <th class="p-4 font-mono text-[10px] uppercase tracking-widest text-ink/50 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-ink/10">
                @foreach($shops as $shop)
                    <tr class="hover:bg-muted/10 transition-colors">
                        <td class="p-4 font-bold">{{ $shop->name }}</td>
                        <td class="p-4 font-mono text-xs text-ink/60">{{ $shop->slug }}</td>
                        <td class="p-4">
                            <span class="inline-flex items-center px-2 py-1 border text-[10px] font-mono uppercase tracking-wide
                                {{ $shop->status === 'active' ? 'bg-green-100 text-green-800 border-green-200' : 'bg-red-100 text-red-800 border-red-200' }}">
                                {{ $shop->status }}
                            </span>
                        </td>
                        <td class="p-4 font-mono text-xs text-ink/40">{{ $shop->created_at->format('Y-m-d') }}</td>
                        <td class="p-4 text-right space-x-4">
                            @if($shop->status === 'active')
                                <form action="{{ route('super-admin.impersonate', $shop->users()->first()->id ?? 0) }}" method="POST" class="inline" onsubmit="return confirm({{ Js::from('Access Shop Admin for ' . $shop->name . '?') }})">
                                    @csrf
                                    <button type="submit" class="text-[10px] font-mono underline hover:text-crema uppercase cursor-pointer">
                                        [ Login As Owner ]
                                    </button>
                                </form>
                            @endif
                            <a href="{{ route('super-admin.shops.edit', $shop) }}" class="text-[10px] font-mono underline hover:text-crema uppercase">
                                Manage
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-4">
        {{ $shops->links() }}
    </div>
</div>