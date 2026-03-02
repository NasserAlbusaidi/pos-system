<div>
    <x-slot:header>Shop Settings</x-slot:header>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-12">
        <!-- Configuration Form -->
        <div class="lg:col-span-7">
            <div class="bg-paper border border-ink p-10 shadow-[8px_8px_0_0_#000000] space-y-12">
                <div class="flex items-center space-x-4 border-b border-ink/10 pb-6">
                    <h2 class="font-mono font-black text-xs uppercase tracking-[0.4em] bg-ink text-paper px-3 py-1 italic">Brand Settings</h2>
                    <div class="flex-1 h-px bg-ink/5"></div>
                </div>

                @if (session()->has('message'))
                    <div class="bg-matcha text-paper p-4 font-mono text-[10px] font-black uppercase tracking-widest border border-ink shadow-[4px_4px_0_0_#000000]">
                        {{ session('message') }}
                    </div>
                @endif

                <form wire:submit.prevent="save" class="space-y-12">
                    <!-- Shop Name -->
                    <div class="space-y-4">
                        <label class="font-mono text-[10px] uppercase font-bold tracking-[0.3em] opacity-40 italic">Shop Name</label>
                        <input type="text" wire:model="name" class="w-full bg-muted border-none p-5 font-mono text-xl font-bold focus:ring-2 focus:ring-crema transition-all uppercase tracking-tighter">
                        @error('name') <span class="text-berry text-[10px] font-mono uppercase">{{ $message }}</span> @enderror
                    </div>

                    <!-- Theme Colors -->
                    <div class="space-y-8">
                        <label class="font-mono text-[10px] uppercase font-bold tracking-[0.3em] opacity-40 italic">Brand Colors</label>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                            <div class="space-y-4">
                                <span class="text-[9px] font-mono font-bold opacity-30">Accent (Crema)</span>
                                <input type="color" wire:model="accent" class="w-full h-16 bg-muted border-2 border-ink p-1 cursor-pointer">
                                <input type="text" wire:model="accent" class="w-full bg-transparent border-b border-ink/20 font-mono text-xs text-center uppercase">
                            </div>

                            <div class="space-y-4">
                                <span class="text-[9px] font-mono font-bold opacity-30">Background (Paper)</span>
                                <input type="color" wire:model="paper" class="w-full h-16 bg-muted border-2 border-ink p-1 cursor-pointer">
                                <input type="text" wire:model="paper" class="w-full bg-transparent border-b border-ink/20 font-mono text-xs text-center uppercase">
                            </div>

                            <div class="space-y-4">
                                <span class="text-[9px] font-mono font-bold opacity-30">Text (Ink)</span>
                                <input type="color" wire:model="ink" class="w-full h-16 bg-muted border-2 border-ink p-1 cursor-pointer">
                                <input type="text" wire:model="ink" class="w-full bg-transparent border-b border-ink/20 font-mono text-xs text-center uppercase">
                            </div>
                        </div>
                    </div>

                    <!-- Tax Settings -->
                    <div class="space-y-4">
                        <label class="font-mono text-[10px] uppercase font-bold tracking-[0.3em] opacity-40 italic">Tax Rate (%)</label>
                        <input type="number" step="0.01" min="0" max="100" wire:model="tax_rate" class="w-full bg-muted border-none p-5 font-mono text-xl font-bold focus:ring-2 focus:ring-crema transition-all uppercase tracking-tighter">
                        @error('tax_rate') <span class="text-berry text-[10px] font-mono uppercase">{{ $message }}</span> @enderror
                    </div>

                    <button type="submit" class="w-full bg-ink text-paper py-6 font-mono font-black uppercase tracking-[0.3em] shadow-[8px_8px_0_0_#FF4D00] hover:translate-y-[-2px] hover:shadow-[10px_10px_0_0_#FF4D00] transition-all">
                        [ Save Settings ]
                    </button>
                </form>
            </div>
        </div>

        <!-- Real-time Preview -->
        <div class="lg:col-span-5 sticky top-32">
            <div class="bg-ink text-paper p-8 space-y-8">
                <h3 class="font-mono text-[9px] uppercase tracking-[0.4em] opacity-40 mb-8 font-black">Live Preview</h3>
                
                <div class="border border-paper/20 p-8 space-y-8 overflow-hidden" 
                     style="background-color: {{ $paper }}; color: {{ $ink }};">
                    
                    <div class="flex items-center space-x-3">
                        <div class="w-6 h-6 flex items-center justify-center" style="background-color: {{ $ink }}; color: {{ $paper }};">
                            <span class="font-mono font-black text-xs">B</span>
                        </div>
                        <h1 class="font-mono font-black text-sm uppercase tracking-widest">{{ $name }}</h1>
                    </div>

                    <div class="space-y-4">
                        <div class="h-2 w-24" style="background-color: {{ $ink }}; opacity: 0.1;"></div>
                        <div class="h-8 w-full border" style="border-color: {{ $ink }};"></div>
                        <button class="w-full py-3 font-mono font-black text-[9px] uppercase tracking-widest transition-all"
                                style="background-color: {{ $accent }}; color: {{ $paper }};">
                            [ BUTTON PREVIEW ]
                        </button>
                    </div>
                </div>

                <p class="font-mono text-[9px] opacity-30 italic leading-relaxed uppercase tracking-widest">
                    Note: Changes applied here will propagate instantly to the Guest Menu and Order Tracker.
                </p>
            </div>
        </div>
    </div>
</div>
