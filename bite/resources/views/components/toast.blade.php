{{-- Toast Notification Component --}}
{{-- Listens for session flashes and Livewire 'toast' events --}}
<div
    x-data="{
        toasts: [],
        nextId: 0,

        add(message, variant = 'success') {
            const id = this.nextId++
            this.toasts.push({ id, message, variant, visible: false })

            // Trigger enter animation on next tick
            this.$nextTick(() => {
                const toast = this.toasts.find(t => t.id === id)
                if (toast) toast.visible = true
            })

            // Auto-dismiss after 4 seconds
            setTimeout(() => this.remove(id), 4000)
        },

        remove(id) {
            const toast = this.toasts.find(t => t.id === id)
            if (toast) {
                toast.visible = false
                // Remove from DOM after exit animation
                setTimeout(() => {
                    this.toasts = this.toasts.filter(t => t.id !== id)
                }, 300)
            }
        },
    }"
    x-init="
        {{-- Show session flash messages on page load --}}
        @if(session('message'))
            add({{ Js::from(session('message')) }}, 'success')
        @endif
        @if(session('error'))
            add({{ Js::from(session('error')) }}, 'error')
        @endif
    "
    @toast.window="add($event.detail.message, $event.detail.variant || 'success')"
    class="fixed right-0 top-0 z-[200] flex flex-col items-end gap-3 p-4 sm:p-6"
    style="pointer-events: none;"
>
    <template x-for="toast in toasts" :key="toast.id">
        <div
            x-show="toast.visible"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="translate-x-full opacity-0"
            x-transition:enter-end="translate-x-0 opacity-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="translate-x-0 opacity-100"
            x-transition:leave-end="translate-x-full opacity-0"
            class="flex w-full max-w-sm items-center gap-3 rounded-xl border px-4 py-3 shadow-lg backdrop-blur-xl"
            :class="toast.variant === 'error'
                ? 'border-alert/35 bg-alert/10 text-alert'
                : 'border-signal/35 bg-signal/10 text-signal'"
            style="pointer-events: auto;"
        >
            <p
                class="flex-1 font-mono text-[10px] font-semibold uppercase tracking-[0.16em]"
                x-text="toast.message"
            ></p>
            <button
                @click="remove(toast.id)"
                class="shrink-0 rounded-md p-1 opacity-60 transition-opacity hover:opacity-100"
                aria-label="Close notification"
            >
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </template>
</div>
