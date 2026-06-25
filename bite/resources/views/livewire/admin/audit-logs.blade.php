<div class="space-y-[18px] fade-rise">
    <x-slot:header>{{ __('admin.audit_logs') }}</x-slot:header>

    {{-- ===== FILTERS ===== --}}
    <section class="surface-card">
        <div class="flex items-center justify-between border-b border-line px-[22px] py-4">
            <div class="flex items-center gap-2.5">
                <span class="h-[18px] w-1 rounded-sm" style="background: var(--bite-lime);"></span>
                <h2 class="font-display text-[18px] font-bold leading-none text-forest">{{ __('admin.audit_filters') }}</h2>
            </div>
            <span class="tag">{{ __('admin.audit_events_count', ['count' => $filterCounts['all'] ?? 0]) }}</span>
        </div>

        <div class="grid grid-cols-1 gap-4 p-5 sm:p-6 md:grid-cols-3">
            {{-- Search --}}
            <label class="flex flex-col gap-2">
                <span class="font-mono text-[10px] font-semibold uppercase tracking-[0.12em] text-ink-soft">{{ __('admin.audit_search') }}</span>
                <input
                    type="text"
                    wire:model.live="search"
                    class="field text-sm"
                    placeholder="{{ __('admin.audit_filter_placeholder') }}"
                >
            </label>

            {{-- Category (logFilter) --}}
            <label class="flex flex-col gap-2">
                <span class="font-mono text-[10px] font-semibold uppercase tracking-[0.12em] text-ink-soft">{{ __('admin.audit_category') }}</span>
                <select wire:model.live="logFilter" class="field text-sm">
                    @foreach(['all', 'orders', 'products', 'operations', 'auth'] as $key)
                        <option value="{{ $key }}">{{ __('admin.audit_tab_' . $key) }}@if(! empty($filterCounts[$key])) ({{ $filterCounts[$key] }})@endif</option>
                    @endforeach
                </select>
            </label>

            {{-- User --}}
            <label class="flex flex-col gap-2">
                <span class="font-mono text-[10px] font-semibold uppercase tracking-[0.12em] text-ink-soft">{{ __('admin.audit_user') }}</span>
                <select wire:model.live="userFilter" class="field text-sm">
                    <option value="">{{ __('admin.audit_all_staff') }}</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </select>
            </label>
        </div>
    </section>

    {{-- ===== ACTIVITY ===== --}}
    <section class="surface-card">
        <div class="flex items-center justify-between border-b border-line px-[22px] py-4">
            <div class="flex items-center gap-2.5">
                <span class="h-[18px] w-1 rounded-sm" style="background: var(--bite-lime);"></span>
                <h2 class="font-display text-[18px] font-bold leading-none text-forest">{{ __('admin.audit_recent_activity') }}</h2>
            </div>
            <span class="tag">{{ __('admin.audit_last_entries') }}</span>
        </div>

        <div class="overflow-x-auto" wire:loading.class="opacity-60">
            <table class="w-full border-collapse text-start">
                <thead>
                    <tr class="border-b border-line font-mono text-[10px] font-bold uppercase tracking-[0.14em] text-ink-soft">
                        <th class="whitespace-nowrap px-[22px] py-3.5 text-start">{{ __('admin.audit_time') }}</th>
                        <th class="whitespace-nowrap px-[22px] py-3.5 text-start">{{ __('admin.audit_user') }}</th>
                        <th class="whitespace-nowrap px-[22px] py-3.5 text-start">{{ __('admin.audit_action') }}</th>
                        <th class="whitespace-nowrap px-[22px] py-3.5 text-start">{{ __('admin.audit_target') }}</th>
                        <th class="whitespace-nowrap px-[22px] py-3.5 text-start">{{ __('admin.audit_meta') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        @php
                            $bucket = match (true) {
                                str_contains($log->action, 'creat') => 'created',
                                str_contains($log->action, 'updat') || str_contains($log->action, 'edit') => 'updated',
                                str_contains($log->action, 'delet') || str_contains($log->action, 'remov') => 'deleted',
                                str_contains($log->action, 'login') || str_contains($log->action, 'pin') || str_contains($log->action, 'auth') || str_contains($log->action, 'impersonat') => 'auth',
                                default => 'event',
                            };
                            $badgeStyle = match ($bucket) {
                                'created' => 'background: var(--bite-lime); color: var(--bite-forest);',
                                'updated' => 'background: var(--bite-lime-100); color: var(--bite-pine);',
                                'deleted' => 'background: rgb(var(--alert) / 0.12); color: rgb(var(--alert));',
                                default => 'background: var(--bite-mist); color: var(--bite-ash);',
                            };
                            $displayMeta = $log->displayMeta();
                        @endphp
                        <tr class="border-b border-line transition-colors hover:bg-cream">
                            {{-- Time --}}
                            <td class="whitespace-nowrap px-[22px] py-3.5 font-mono text-[12px] text-ink">
                                {{ $log->created_at->format('H:i') }} <span class="text-ink-soft">· {{ $log->created_at->format('M j') }}</span>
                            </td>

                            {{-- Actor (name + role) --}}
                            <td class="px-[22px] py-3.5">
                                <div class="text-sm text-ink">{{ $log->user?->name ?? __('admin.audit_system') }}</div>
                                @if($log->user?->role)
                                    <div class="mt-1 font-mono text-[10px] uppercase tracking-[0.1em] text-ink-soft">{{ ucfirst($log->user->role) }}</div>
                                @endif
                            </td>

                            {{-- Action: badge + precise action string (audit must stay exact) --}}
                            <td class="px-[22px] py-3.5">
                                <span class="inline-flex rounded-full px-2.5 py-1.5 font-mono text-[10px] font-bold uppercase tracking-[0.1em]" style="{{ $badgeStyle }}">
                                    {{ __('admin.audit_badge_' . $bucket) }}
                                </span>
                                <div class="mt-1 font-mono text-[10px] text-ink-soft/70">{{ $log->action }}</div>
                            </td>

                            {{-- Target --}}
                            <td class="px-[22px] py-3.5 text-sm text-ink">
                                @if($log->auditable_type && $log->auditable_id)
                                    {{ class_basename($log->auditable_type) }} <span class="text-ink-soft">· #{{ $log->auditable_id }}</span>
                                @else
                                    <span class="text-ink-soft">—</span>
                                @endif
                            </td>

                            {{-- Meta --}}
                            <td class="px-[22px] py-3.5 font-mono text-[10px] text-ink-soft">
                                @if(! empty($displayMeta))
                                    <span class="block max-w-[260px] truncate break-all">{{ json_encode($displayMeta) }}</span>
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-[22px] py-12 text-center font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">{{ __('admin.audit_no_events') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Footer: real counts (component caps the query at 200; no pagination control) --}}
        @if($logs->isNotEmpty())
            <div class="flex items-center justify-between border-t border-line px-[22px] py-3.5">
                <span class="font-mono text-[11px] tracking-[0.04em] text-ink-soft">
                    {{ __('admin.audit_showing_count', ['shown' => $logs->count(), 'total' => $filterCounts['all'] ?? $logs->count()]) }}
                </span>
            </div>
        @endif
    </section>
</div>
