<?php

namespace App\Livewire;

use App\Models\Shop;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Component;

class PinLogin extends Component
{
    public Shop $shop;

    public $pin = '';

    public $error = null;

    public function mount(Shop $shop)
    {
        $this->shop = $shop;
    }

    public function login()
    {
        $throttleKey = $this->throttleKey();
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            $this->error = "Too many attempts. Try again in {$seconds} seconds.";

            return;
        }

        $pin = trim($this->pin);
        if ($pin === '' || ! preg_match('/^\d{4,6}$/', $pin)) {
            RateLimiter::hit($throttleKey, 60);
            $this->error = 'Authentication failed.';

            return;
        }

        $user = User::where('shop_id', $this->shop->id)
            ->whereNotNull('pin_code')
            ->get()
            ->first(fn ($user) => Hash::check($pin, $user->pin_code));

        if (! $user) {
            RateLimiter::hit($throttleKey, 60);
            $this->error = 'Authentication failed.';

            return;
        }

        RateLimiter::clear($throttleKey);
        Auth::login($user);

        return $this->redirect(route('pos.dashboard'), navigate: true);
    }

    protected function throttleKey(): string
    {
        return 'pin-login:'.$this->shop->id.'|'.request()->ip();
    }

    public function render()
    {
        return view('livewire.pin-login');
    }
}
