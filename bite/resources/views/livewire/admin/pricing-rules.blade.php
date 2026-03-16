<div class="space-y-6 fade-rise">
    <x-slot:header>Pricing Rules</x-slot:header>

    @if(session()->has('message'))
        <div class="surface-card p-4 border-l-4 border-signal">
            <p class="font-mono text-[11px] font-semibold uppercase tracking-[0.14em] text-signal">{{ session('message') }}</p>
        </div>
    @endif

    <!-- Form -->
    <div class="surface-card p-5 sm:p-6">
        <div class="border-b border-line pb-4 mb-5">
            <h2 class="font-display text-xl font-extrabold leading-none text-ink">
                {{ $editingId ? 'Edit Rule' : 'New Pricing Rule' }}
            </h2>
            <p class="mt-1 font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">
                Auto-apply discounts during specific time windows
            </p>
        </div>

        <form wire:submit="save" class="space-y-5">
            <!-- Rule Name -->
            <div>
                <label class="section-headline mb-2 block">Rule Name</label>
                <input type="text" wire:model="name" class="field text-sm" placeholder="e.g. Happy Hour Cold Drinks">
                @error('name') <p class="mt-1 text-xs text-alert">{{ $message }}</p> @enderror
            </div>

            <!-- Target: Category or Product -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="section-headline mb-2 block">Category (optional)</label>
                    <select wire:model="category_id" class="field text-sm">
                        <option value="">All categories</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name_en }}</option>
                        @endforeach
                    </select>
                    @error('category_id') <p class="mt-1 text-xs text-alert">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="section-headline mb-2 block">Product (optional, overrides category)</label>
                    <select wire:model="product_id" class="field text-sm">
                        <option value="">No specific product</option>
                        @foreach($products as $prod)
                            <option value="{{ $prod->id }}">{{ $prod->name_en }}</option>
                        @endforeach
                    </select>
                    @error('product_id') <p class="mt-1 text-xs text-alert">{{ $message }}</p> @enderror
                </div>
            </div>

            <!-- Discount Type + Value -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="section-headline mb-2 block">Discount Type</label>
                    <select wire:model="discount_type" class="field text-sm">
                        <option value="percentage">Percentage (%)</option>
                        <option value="fixed">Fixed Amount (OMR)</option>
                    </select>
                    @error('discount_type') <p class="mt-1 text-xs text-alert">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="section-headline mb-2 block">Discount Value</label>
                    <input type="number" wire:model="discount_value" class="field text-sm" step="0.001" min="0" placeholder="e.g. 30 for 30%">
                    @error('discount_value') <p class="mt-1 text-xs text-alert">{{ $message }}</p> @enderror
                </div>
            </div>

            <!-- Time Window -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="section-headline mb-2 block">Start Time</label>
                    <input type="time" wire:model="start_time" class="field text-sm">
                    @error('start_time') <p class="mt-1 text-xs text-alert">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="section-headline mb-2 block">End Time</label>
                    <input type="time" wire:model="end_time" class="field text-sm">
                    @error('end_time') <p class="mt-1 text-xs text-alert">{{ $message }}</p> @enderror
                </div>
            </div>

            <!-- Days of Week -->
            <div>
                <label class="section-headline mb-2 block">Days of Week</label>
                <p class="mb-3 font-mono text-[9px] uppercase tracking-[0.16em] text-ink-soft">Leave all unchecked for every day</p>
                <div class="flex flex-wrap gap-3">
                    @php
                        $dayLabels = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
                    @endphp
                    @foreach($dayLabels as $dayIndex => $dayLabel)
                        <label class="flex items-center gap-2 cursor-pointer rounded-lg border border-line bg-panel px-3 py-2 hover:border-crema transition-colors">
                            <input type="checkbox" wire:model="days_of_week" value="{{ $dayIndex }}" class="rounded border-line">
                            <span class="font-mono text-[10px] font-semibold uppercase tracking-[0.14em] text-ink">{{ $dayLabel }}</span>
                        </label>
                    @endforeach
                </div>
                @error('days_of_week') <p class="mt-1 text-xs text-alert">{{ $message }}</p> @enderror
            </div>

            <!-- Actions -->
            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="btn-primary">
                    {{ $editingId ? 'Update Rule' : 'Create Rule' }}
                </button>
                @if($editingId)
                    <button type="button" wire:click="cancelEdit" class="btn-secondary">Cancel</button>
                @endif
            </div>
        </form>
    </div>

    <!-- Rules List -->
    <div class="surface-card overflow-hidden">
        <div class="border-b border-line bg-muted/35 px-5 py-4 flex items-center justify-between">
            <h2 class="font-display text-xl font-extrabold leading-none">Pricing Rules</h2>
            <span class="tag">{{ $rules->count() }} {{ $rules->count() === 1 ? 'rule' : 'rules' }}</span>
        </div>

        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="border-b border-line bg-panel font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">
                    <th class="px-5 py-4">Status</th>
                    <th class="px-5 py-4">Name</th>
                    <th class="px-5 py-4">Target</th>
                    <th class="px-5 py-4">Discount</th>
                    <th class="px-5 py-4">Time Window</th>
                    <th class="px-5 py-4">Days</th>
                    <th class="px-5 py-4">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line/65">
                @forelse($rules as $rule)
                    @php
                        $isNow = $rule->isActiveNow();
                    @endphp
                    <tr class="hover:bg-muted/35 transition-colors">
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-2">
                                <button wire:click="toggleActive({{ $rule->id }})" class="flex items-center gap-1.5" title="{{ $rule->is_active ? 'Click to deactivate' : 'Click to activate' }}">
                                    <span class="status-dot {{ $rule->is_active ? 'status-live' : '' }}" style="{{ !$rule->is_active ? 'background: rgb(var(--ink) / 0.25);' : '' }}"></span>
                                    <span class="font-mono text-[9px] font-semibold uppercase tracking-[0.14em] {{ $rule->is_active ? 'text-signal' : 'text-ink-soft' }}">
                                        {{ $rule->is_active ? 'On' : 'Off' }}
                                    </span>
                                </button>
                                @if($isNow)
                                    <span class="inline-flex items-center rounded-full border border-signal/30 bg-signal/10 px-2 py-0.5 font-mono text-[8px] font-bold uppercase tracking-[0.16em] text-signal">
                                        Active now
                                    </span>
                                @endif
                            </div>
                        </td>
                        <td class="px-5 py-4 font-mono text-[10px] font-semibold uppercase tracking-[0.14em] text-ink">
                            {{ $rule->name }}
                        </td>
                        <td class="px-5 py-4 font-mono text-[10px] font-semibold uppercase tracking-[0.14em] text-ink-soft">
                            @if($rule->product)
                                Product: {{ $rule->product->name_en }}
                            @elseif($rule->category)
                                Category: {{ $rule->category->name_en }}
                            @else
                                All items
                            @endif
                        </td>
                        <td class="px-5 py-4 font-mono text-[10px] font-semibold uppercase tracking-[0.14em] text-ink">
                            @if($rule->discount_type === 'percentage')
                                {{ rtrim(rtrim(number_format($rule->discount_value, 3), '0'), '.') }}%
                            @else
                                {{ number_format($rule->discount_value, 3) }} OMR
                            @endif
                        </td>
                        <td class="px-5 py-4 font-mono text-[10px] font-semibold uppercase tracking-[0.14em] text-ink-soft">
                            {{ substr($rule->start_time, 0, 5) }} — {{ substr($rule->end_time, 0, 5) }}
                        </td>
                        <td class="px-5 py-4 font-mono text-[9px] font-semibold uppercase tracking-[0.14em] text-ink-soft">
                            @if($rule->days_of_week === null)
                                Every day
                            @else
                                @php
                                    $labels = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
                                    $days = collect($rule->days_of_week)->map(fn ($d) => $labels[$d] ?? '?')->implode(', ');
                                @endphp
                                {{ $days }}
                            @endif
                        </td>
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-3">
                                <button wire:click="edit({{ $rule->id }})" class="font-mono text-[10px] font-semibold uppercase tracking-[0.14em] text-ink-soft hover:text-crema transition-colors">
                                    Edit
                                </button>
                                <button
                                    x-data
                                    x-on:click="$dispatch('confirm-action', {
                                        title: 'Delete pricing rule',
                                        message: 'Are you sure you want to delete this pricing rule?',
                                        action: 'delete',
                                        actionArgs: [{{ $rule->id }}],
                                        componentId: $wire.id,
                                        destructive: true,
                                    })"
                                    class="font-mono text-[10px] font-semibold uppercase tracking-[0.14em] text-alert/80 hover:text-alert transition-colors"
                                >
                                    Delete
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="p-12 text-center font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">
                            No pricing rules yet. Create one above to get started.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
