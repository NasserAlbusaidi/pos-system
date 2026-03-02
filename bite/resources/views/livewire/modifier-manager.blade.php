<div class="h-full">
    <x-slot:header>Modifier Management</x-slot:header>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-12">
        <!-- New Group Form -->
        <div class="lg:col-span-1 space-y-12">
            <div class="bg-paper border border-ink p-8 space-y-8">
                <h2 class="font-mono font-black text-xs uppercase tracking-widest bg-ink text-paper px-3 py-1 inline-block italic">Create New Group</h2>
                
                <form wire:submit.prevent="save" class="space-y-10">
                    <div class="space-y-2">
                        <label class="font-mono text-[9px] uppercase font-bold tracking-[0.3em] opacity-40">Group Name</label>
                        <input type="text" wire:model="name" class="w-full bg-transparent border-b border-ink/20 focus:border-crema focus:ring-0 font-mono text-lg transition-all" placeholder="e.g. Milk Choice">
                        @error('name') <span class="text-berry text-[9px] font-mono uppercase">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-8">
                        <div class="space-y-2">
                            <label class="font-mono text-[9px] uppercase font-bold tracking-[0.3em] opacity-40">Min Required</label>
                            <input type="number" wire:model="min_selection" class="w-full bg-transparent border-b border-ink/20 focus:border-crema focus:ring-0 font-mono text-lg transition-all" placeholder="0">
                        </div>
                        <div class="space-y-2">
                            <label class="font-mono text-[9px] uppercase font-bold tracking-[0.3em] opacity-40">Max Allowed</label>
                            <input type="number" wire:model="max_selection" class="w-full bg-transparent border-b border-ink/20 focus:border-crema focus:ring-0 font-mono text-lg transition-all" placeholder="1">
                        </div>
                    </div>

                    <button type="submit" class="btn-primary w-full py-5 shadow-[6px_6px_0_0_#FF4D00]">[ Save Group ]</button>
                </form>
            </div>

            <!-- New Option Form (Only if group selected) -->
            @if($selectedGroupId)
                <div class="bg-paper border border-ink p-8 space-y-8 animate-in slide-in-from-top-4 duration-200">
                    <h2 class="font-mono font-black text-xs uppercase tracking-widest bg-crema text-paper px-3 py-1 inline-block italic">Add Option</h2>
                    
                    <form wire:submit.prevent="addOption" class="space-y-10">
                        <div class="space-y-2">
                            <label class="font-mono text-[9px] uppercase font-bold tracking-[0.3em] opacity-40">Option Name</label>
                            <input type="text" wire:model="optionName" class="w-full bg-transparent border-b border-ink/20 focus:border-crema focus:ring-0 font-mono text-lg transition-all" placeholder="e.g. Oat Milk">
                            @error('optionName') <span class="text-berry text-[9px] font-mono uppercase">{{ $message }}</span> @enderror
                        </div>

                        <div class="space-y-2">
                            <label class="font-mono text-[9px] uppercase font-bold tracking-[0.3em] opacity-40">Extra Cost</label>
                            <input type="number" step="0.01" wire:model="optionPrice" class="w-full bg-transparent border-b border-ink/20 focus:border-crema focus:ring-0 font-mono text-lg transition-all" placeholder="1.00">
                            @error('optionPrice') <span class="text-berry text-[9px] font-mono uppercase">{{ $message }}</span> @enderror
                        </div>

                        <button type="submit" class="btn-primary w-full py-5 shadow-[6px_6px_0_0_#000000] bg-ink">[ Add Option ]</button>
                    </form>
                </div>
            @endif
        </div>

        <!-- Groups List -->
        <div class="lg:col-span-2 space-y-8">
            <div class="bg-paper border border-ink p-8">
                <h3 class="font-mono font-black text-[10px] uppercase tracking-[0.4em] opacity-30 mb-8">// Modifier Groups</h3>
                
                <div class="grid grid-cols-1 gap-px bg-ink/10">
                    @foreach(\App\Models\ModifierGroup::where('shop_id', Auth::user()->shop_id)->with('options')->get() as $group)
                        <div @class([
                            'p-6 flex flex-col space-y-6 transition-all duration-150',
                            'bg-muted shadow-[inset_4px_0_0_0_#FF4D00]' => $selectedGroupId == $group->id,
                            'bg-paper hover:bg-muted/30 cursor-pointer' => $selectedGroupId != $group->id
                        ]) wire:click="$set('selectedGroupId', {{ $group->id }})">
                            <div class="flex justify-between items-center">
                                <div class="flex items-center space-x-6">
                                    <div class="w-10 h-10 bg-ink text-paper flex items-center justify-center font-mono font-black text-xs">{{ $loop->iteration }}</div>
                                    <div>
                                        <div class="font-mono font-black text-sm uppercase tracking-tight">{{ $group->name }}</div>
                                        <div class="font-mono text-[9px] text-ink/30 uppercase mt-1">Rule: Select {{ $group->min_selection }}-{{ $group->max_selection }}</div>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-12">
                                    <div class="font-mono font-black text-[10px] opacity-40 uppercase">{{ $group->options->count() }} Options</div>
                                    @if($selectedGroupId == $group->id)
                                        <span class="text-crema font-mono text-[10px] font-black italic">SELECTED</span>
                                    @endif
                                </div>
                            </div>

                            @if($group->options->isNotEmpty())
                                <div class="pl-16 grid grid-cols-2 md:grid-cols-3 gap-4 pb-4">
                                    @foreach($group->options as $option)
                                        <div class="p-3 border border-ink/5 bg-paper/50 flex justify-between items-center">
                                            <span class="font-mono text-[10px] font-bold uppercase truncate pr-2">{{ $option->name }}</span>
                                            <span class="font-mono text-[10px] text-crema font-black">+{{ formatPrice($option->price_adjustment, $shop) }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>