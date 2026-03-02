<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

class ShopSettings extends Component
{
    public $name;

    public $paper;

    public $ink;

    public $accent;

    public $tax_rate = 0;

    public function mount()
    {
        $shop = Auth::user()->shop;
        $this->name = $shop->name;

        $branding = $shop->branding ?? [];
        $this->paper = $this->normalizeHex($branding['paper'] ?? '#FDFCF8', '#FDFCF8');
        $this->ink = $this->normalizeHex($branding['ink'] ?? '#1A1918', '#1A1918');
        $this->accent = $this->normalizeHex($branding['accent'] ?? '#CC5500', '#CC5500');
        $this->tax_rate = $shop->tax_rate ?? 0;
    }

    protected function normalizeHex(string $value, string $fallback): string
    {
        $hex = ltrim($value, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        if (strlen($hex) !== 6) {
            $hex = ltrim($fallback, '#');
        }

        return '#'.strtolower($hex);
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|string|min:3',
            'paper' => ['required', 'regex:/^#(?:[A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/'],
            'ink' => ['required', 'regex:/^#(?:[A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/'],
            'accent' => ['required', 'regex:/^#(?:[A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/'],
            'tax_rate' => 'required|numeric|min:0|max:100',
        ]);

        $shop = Auth::user()->shop;
        $paper = $this->normalizeHex($this->paper, '#FDFCF8');
        $ink = $this->normalizeHex($this->ink, '#1A1918');
        $accent = $this->normalizeHex($this->accent, '#CC5500');
        $branding = $shop->branding ?? [];
        $shop->update([
            'name' => $this->name,
            'tax_rate' => $this->tax_rate,
            'branding' => array_merge($branding, [
                'paper' => $paper,
                'ink' => $ink,
                'accent' => $accent,
            ]),
        ]);

        $this->paper = $paper;
        $this->ink = $ink;
        $this->accent = $accent;

        session()->flash('message', 'Shop configuration updated successfully.');
    }

    #[Layout('layouts.admin')]
    public function render()
    {
        return view('livewire.shop-settings');
    }
}
