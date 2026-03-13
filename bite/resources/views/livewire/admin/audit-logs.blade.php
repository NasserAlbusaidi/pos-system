<div class="space-y-6 fade-rise">
    <x-slot:header>{{ __('admin.audit_logs') }}</x-slot:header>

    <div class="surface-card p-5 sm:p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
        <input
            type="text"
            wire:model.live="search"
            class="field text-sm"
            placeholder="{{ __('admin.audit_filter_placeholder') }}"
        >
        <select wire:model.live="userFilter" class="field text-sm">
            <option value="">{{ __('admin.audit_all_staff') }}</option>
            @foreach($users as $user)
                <option value="{{ $user->id }}">{{ $user->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="surface-card overflow-hidden">
        <div class="border-b border-line bg-muted/35 px-5 py-4 flex items-center justify-between">
            <h2 class="font-display text-xl font-extrabold leading-none">{{ __('admin.audit_recent_activity') }}</h2>
            <span class="tag">{{ __('admin.audit_last_entries') }}</span>
        </div>

        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="border-b border-line bg-panel font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">
                    <th class="px-5 py-4">{{ __('admin.audit_time') }}</th>
                    <th class="px-5 py-4">{{ __('admin.audit_user') }}</th>
                    <th class="px-5 py-4">{{ __('admin.audit_action') }}</th>
                    <th class="px-5 py-4">{{ __('admin.audit_target') }}</th>
                    <th class="px-5 py-4">{{ __('admin.audit_meta') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line/65">
                @forelse($logs as $log)
                    <tr class="hover:bg-muted/35 transition-colors">
                        <td class="px-5 py-4 font-mono text-[10px] font-semibold uppercase tracking-[0.14em] text-ink">
                            {{ $log->created_at->format('Y-m-d H:i') }}
                        </td>
                        <td class="px-5 py-4 font-mono text-[10px] font-semibold uppercase tracking-[0.14em] text-ink">
                            {{ $log->user?->name ?? __('admin.audit_system') }}
                        </td>
                        <td class="px-5 py-4 font-mono text-[10px] font-semibold uppercase tracking-[0.14em] text-ink-soft">
                            {{ $log->action }}
                        </td>
                        <td class="px-5 py-4 font-mono text-[10px] font-semibold uppercase tracking-[0.14em] text-ink-soft/60">
                            @if($log->auditable_type && $log->auditable_id)
                                {{ class_basename($log->auditable_type) }} #{{ $log->auditable_id }}
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-5 py-4 font-mono text-[9px] text-ink-soft">
                            @if(!empty($log->meta))
                                {{ json_encode($log->meta) }}
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="p-12 text-center font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">{{ __('admin.audit_no_events') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
