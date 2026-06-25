<?php

namespace App\Livewire;

use App\Models\AuditLog;
use App\Models\Shop;
use App\Models\User;
use App\Services\BillingService;
use App\Services\PinCodePolicy;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Component;

class PinLogin extends Component
{
    public Shop $shop;

    public $pin = '';

    public $error = null;

    public function mount(Shop $shop)
    {
        abort_if($shop->status === 'suspended' || ! app(BillingService::class)->isSubscribed($shop), 404);

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
        if ($pin === '' || ! preg_match('/^\d{4}$/', $pin)) {
            RateLimiter::hit($throttleKey, 60);
            $this->error = 'Authentication failed.';

            return;
        }

        $matches = app(PinCodePolicy::class)->matchingUsers($this->shop->id, $pin);

        if ($matches->count() !== 1) {
            RateLimiter::hit($throttleKey, 60);
            $this->error = 'Authentication failed.';

            return;
        }

        $user = $matches->first();

        RateLimiter::clear($throttleKey);
        Auth::login($user);

        AuditLog::create([
            'shop_id' => $this->shop->id,
            'user_id' => $user->id,
            'action' => 'pin.login',
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'meta' => [
                'role' => $user->role,
                'ip' => request()->ip(),
            ],
        ]);

        // Rotate the session on the shared terminal: Auth::login already migrates
        // the session id, but regenerate() also rotates the CSRF token (the residual
        // fixation surface). Matches the impersonation + logout flows.
        session()->regenerate();

        return $this->redirect($this->postLoginRoute($user), navigate: true);
    }

    protected function postLoginRoute(User $user): string
    {
        return $user->role === 'kitchen'
            ? route('kds.view')
            : route('pos.dashboard');
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
