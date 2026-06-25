{{-- Global confirmation modal - replaces browser confirm() dialogs --}}
<div
    x-data="{
        show: false,
        title: '',
        message: '',
        action: '',
        actionArgs: [],
        componentId: null,
        destructive: false,
        surface: 'default',
        eyebrow: '',
        confirmLabel: 'Confirm',
        cancelLabel: 'Cancel',

        open(event) {
            this.title = event.detail.title || 'Confirm';
            this.message = event.detail.message || 'Are you sure?';
            this.action = event.detail.action || '';
            this.actionArgs = event.detail.actionArgs || [];
            this.componentId = event.detail.componentId || null;
            this.destructive = event.detail.destructive ?? true;
            this.surface = event.detail.surface || 'default';
            this.eyebrow = event.detail.eyebrow || 'Please confirm this action';
            this.confirmLabel = event.detail.confirmLabel || 'Confirm';
            this.cancelLabel = event.detail.cancelLabel || 'Cancel';
            this.show = true;
            document.body.classList.add('overflow-y-hidden');
        },

        close() {
            this.show = false;
            document.body.classList.remove('overflow-y-hidden');
        },

        confirm() {
            if (this.action && this.componentId) {
                const component = Livewire.find(this.componentId);
                if (component) {
                    component.call(this.action, ...this.actionArgs);
                }
            }
            this.close();
        },
    }"
    x-on:confirm-action.window="open($event)"
    x-on:keydown.escape.window="show && close()"
    x-show="show"
    x-cloak
    class="confirm-modal fixed inset-0 z-[200] flex items-center justify-center bg-ink/75 p-4 backdrop-blur-sm sm:p-6"
    x-bind:class="{ 'confirm-modal--guest guest-shell': surface === 'guest' }"
    x-transition:enter="ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
>
    <div
        x-show="show"
        x-on:click.outside="close()"
        x-transition:enter="ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="surface-card confirm-modal__panel w-full max-w-md overflow-hidden"
    >
        <div class="confirm-modal__head border-b border-line bg-muted/30 px-5 py-4">
            <h3 class="confirm-modal__title font-display text-2xl font-extrabold leading-none text-ink" x-text="title"></h3>
            <p class="confirm-modal__eyebrow mt-1 font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft" x-text="eyebrow"></p>
        </div>

        <div class="confirm-modal__body p-5">
            <p class="confirm-modal__message text-sm leading-relaxed text-ink-soft" x-text="message"></p>
        </div>

        <div class="confirm-modal__actions flex gap-3 border-t border-line bg-muted/20 p-5">
            <button x-on:click="close()" class="btn-secondary confirm-modal__cancel flex-1 justify-center" x-text="cancelLabel">
            </button>
            <button
                x-on:click="confirm()"
                class="confirm-modal__confirm"
                x-bind:class="destructive ? 'btn-danger flex-1 justify-center' : 'btn-primary flex-1 justify-center'"
                x-text="confirmLabel"
            >
            </button>
        </div>
    </div>
</div>
