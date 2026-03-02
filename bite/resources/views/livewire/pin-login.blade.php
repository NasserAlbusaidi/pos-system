<div class="flex min-h-screen items-center justify-center px-4 py-8">
    <div class="surface-card w-full max-w-sm p-6 sm:p-8 fade-rise">
        <div class="mb-6 flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-lg border border-line bg-ink text-panel font-display text-2xl font-black">B</div>
            <div>
                <p class="font-display text-2xl font-extrabold leading-none text-ink">{{ $shop->name }}</p>
                <p class="mt-1 font-mono text-[10px] font-semibold uppercase tracking-[0.18em] text-ink-soft">POS PIN Login</p>
            </div>
        </div>

        @if($error)
            <div class="mb-4 rounded-lg border border-alert/35 bg-alert/10 px-3 py-2 font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-alert">
                {{ $error }}
            </div>
        @endif

        <form wire:submit.prevent="login" class="space-y-4">
            <input type="password" maxlength="4" wire:model="pin" class="field w-full text-center font-mono text-base font-bold uppercase tracking-[0.5em]" placeholder="PIN">
            <button type="submit" class="btn-primary w-full justify-center">
                Unlock
            </button>
        </form>
    </div>
</div>
