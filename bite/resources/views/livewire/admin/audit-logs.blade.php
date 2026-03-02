<div class="h-full space-y-8">
    <x-slot:header>Audit Logs</x-slot:header>

    <div class="bg-paper border border-ink p-6 shadow-[4px_4px_0_0_#000000] grid grid-cols-1 md:grid-cols-2 gap-4">
        <input
            type="text"
            wire:model.live="search"
            class="w-full bg-muted border-2 border-ink p-3 font-mono text-xs"
            placeholder="Filter by action (e.g. order.paid)"
        >
        <select wire:model.live="userFilter" class="w-full bg-muted border-2 border-ink p-3 font-mono text-xs">
            <option value="">All Staff</option>
            @foreach($users as $user)
                <option value="{{ $user->id }}">{{ $user->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="bg-paper border-2 border-ink shadow-[8px_8px_0_0_#000000] overflow-hidden">
        <div class="p-6 bg-muted border-b border-ink flex items-center justify-between">
            <h2 class="font-mono font-black text-xs uppercase tracking-[0.4em]">Recent Activity</h2>
            <div class="font-mono text-[9px] uppercase tracking-widest opacity-40">Last 200 entries</div>
        </div>

        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="border-b border-ink/10 font-mono text-[10px] uppercase tracking-widest text-ink/40">
                    <th class="p-6">Time</th>
                    <th class="p-6">User</th>
                    <th class="p-6">Action</th>
                    <th class="p-6">Target</th>
                    <th class="p-6">Meta</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-ink/5">
                @forelse($logs as $log)
                    <tr class="hover:bg-muted/40 transition-colors">
                        <td class="p-6 font-mono text-[10px] uppercase tracking-widest">
                            {{ $log->created_at->format('Y-m-d H:i') }}
                        </td>
                        <td class="p-6 font-mono text-[10px] uppercase tracking-widest">
                            {{ $log->user?->name ?? 'System' }}
                        </td>
                        <td class="p-6 font-mono text-[10px] uppercase tracking-widest text-ink/70">
                            {{ $log->action }}
                        </td>
                        <td class="p-6 font-mono text-[10px] uppercase tracking-widest text-ink/50">
                            @if($log->auditable_type && $log->auditable_id)
                                {{ class_basename($log->auditable_type) }} #{{ $log->auditable_id }}
                            @else
                                —
                            @endif
                        </td>
                        <td class="p-6 font-mono text-[9px] uppercase tracking-widest text-ink/40">
                            @if(!empty($log->meta))
                                {{ json_encode($log->meta) }}
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="p-12 text-center font-mono text-xs opacity-30 italic uppercase tracking-widest">No audit events yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
