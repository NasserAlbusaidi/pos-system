<x-admin-layout>
    <x-slot name="header">
        Dashboard
    </x-slot>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
        <!-- Stat Cards -->
        <div class="bg-paper border border-ink p-6 shadow-[4px_4px_0_0_#1A1918]">
            <div class="font-mono text-[10px] uppercase font-bold text-ink/40 mb-2">Daily Revenue</div>
            <div class="text-3xl font-mono font-bold">$0.00</div>
        </div>

        <div class="bg-paper border border-ink p-6 shadow-[4px_4px_0_0_#4A7A58]">
            <div class="font-mono text-[10px] uppercase font-bold text-ink/40 mb-2">Orders Today</div>
            <div class="text-3xl font-mono font-bold">0</div>
        </div>

        <div class="bg-paper border border-ink p-6 shadow-[4px_4px_0_0_#CC5500]">
            <div class="font-mono text-[10px] uppercase font-bold text-ink/40 mb-2">Active Orders</div>
            <div class="text-3xl font-mono font-bold">0</div>
        </div>

        <div class="bg-paper border border-ink p-6 shadow-[4px_4px_0_0_#1A1918]">
            <div class="font-mono text-[10px] uppercase font-bold text-ink/40 mb-2">System Status</div>
            <div class="text-sm font-mono font-bold uppercase text-matcha">Online</div>
        </div>
    </div>

    <!-- Main Grid -->
    <div class="mt-12 grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-2 border-t border-graphite pt-8">
            <h3 class="font-mono font-bold uppercase text-sm tracking-[0.2em] mb-6">Recent Activity //</h3>
            <div class="bg-vellum border border-graphite p-12 text-center opacity-30">
                <p class="font-mono text-xs uppercase">No Recent Transactions</p>
            </div>
        </div>
    </div>
</x-admin-layout>
