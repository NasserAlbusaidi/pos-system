<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">POS PIN</h2>
        <p class="mt-1 text-sm text-gray-600">Set a 4-digit PIN for quick POS access and manager overrides.</p>
    </header>

    @if (session()->has('message'))
        <div class="mt-4 text-sm text-green-600">{{ session('message') }}</div>
    @endif

    <form wire:submit.prevent="updatePin" class="mt-6 space-y-6">
        <div>
            <x-input-label for="pin" :value="__('PIN (4 digits)')" />
            <x-text-input id="pin" wire:model="pin" type="password" class="mt-1 block w-full" maxlength="4" inputmode="numeric" />
            <x-input-error :messages="$errors->get('pin')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="pin_confirmation" :value="__('Confirm PIN')" />
            <x-text-input id="pin_confirmation" wire:model="pin_confirmation" type="password" class="mt-1 block w-full" maxlength="4" inputmode="numeric" />
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save PIN') }}</x-primary-button>
        </div>
    </form>
</section>
