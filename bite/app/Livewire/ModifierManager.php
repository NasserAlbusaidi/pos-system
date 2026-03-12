<?php

namespace App\Livewire;

use App\Models\ModifierGroup;
use App\Models\ModifierOption;
use App\Models\Shop;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

class ModifierManager extends Component
{
    public Shop $shop;

    // Group properties
    public $name_en;

    public $name_ar;

    public $min_selection = 0;

    public $max_selection = 1;

    // Option properties
    public $selectedGroupId;

    public $optionNameEn;

    public $optionNameAr;

    public $optionPrice = 0;

    public function mount(): void
    {
        $this->shop = Auth::user()->shop;
    }

    public function addOption()
    {
        $this->validate([
            'selectedGroupId' => 'required',
            'optionNameEn' => 'required|string',
            'optionNameAr' => 'nullable|string',
            'optionPrice' => 'required|numeric',
        ]);

        // Ensure the group belongs to the authenticated user's shop
        $group = ModifierGroup::where('shop_id', Auth::user()->shop_id)
            ->where('id', $this->selectedGroupId)
            ->firstOrFail();

        ModifierOption::create([
            'modifier_group_id' => $group->id,
            'name_en' => $this->optionNameEn,
            'name_ar' => $this->optionNameAr,
            'price_adjustment' => $this->optionPrice,
        ]);

        $this->reset(['optionNameEn', 'optionNameAr', 'optionPrice']);
    }

    protected function rules()
    {
        return [
            'name_en' => 'required|string|min:2',
            'name_ar' => 'nullable|string|min:2',
            'min_selection' => 'required|integer|min:0',
            'max_selection' => 'required|integer|min:1',
        ];
    }

    public function save()
    {
        $this->validate();

        ModifierGroup::create([
            'shop_id' => Auth::user()->shop_id,
            'name_en' => $this->name_en,
            'name_ar' => $this->name_ar,
            'min_selection' => $this->min_selection,
            'max_selection' => $this->max_selection,
        ]);

        $this->reset(['name_en', 'name_ar', 'min_selection', 'max_selection']);
    }

    #[Layout('layouts.admin')]
    public function render()
    {
        return view('livewire.modifier-manager');
    }
}
