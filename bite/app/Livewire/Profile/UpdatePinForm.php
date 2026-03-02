<?php

namespace App\Livewire\Profile;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;

class UpdatePinForm extends Component
{
    public $pin = '';

    public $pin_confirmation = '';

    public function updatePin()
    {
        $this->validate([
            'pin' => ['required', 'digits:4', 'confirmed'],
        ]);

        $user = Auth::user();
        $user->update([
            'pin_code' => Hash::make($this->pin),
        ]);

        $this->reset(['pin', 'pin_confirmation']);
        session()->flash('message', 'PIN updated successfully.');
    }

    public function render()
    {
        return view('livewire.profile.update-pin-form');
    }
}
