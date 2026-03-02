<?php

namespace App\Livewire;

use App\Models\ModifierGroup;
use App\Models\ModifierOption;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

class ModifierManager extends Component
{
    // Group properties
    public $name;

    public $min_selection = 0;

    public $max_selection = 1;

    // Option properties
    public $selectedGroupId;

    public $optionName;

    public $optionPrice = 0;

    public function addOption()
    {
        $this->validate([
            'selectedGroupId' => 'required',
            'optionName' => 'required|string',
            'optionPrice' => 'required|numeric',
        ]);

        // Ensure the group belongs to the authenticated user's shop
        $group = ModifierGroup::where('shop_id', Auth::user()->shop_id)
            ->where('id', $this->selectedGroupId)
            ->firstOrFail();

        ModifierOption::create([
            'modifier_group_id' => $group->id,
            'name' => $this->optionName,
            'price_adjustment' => $this->optionPrice,
        ]);

        $this->reset(['optionName', 'optionPrice']);
    }

    protected function rules()
    {
        return [
            'name' => 'required|string|min:2',
            'min_selection' => 'required|integer|min:0',
            'max_selection' => 'required|integer|min:1',
        ];
    }

    public function save()
    {
        $this->validate();

        ModifierGroup::create([
            'shop_id' => Auth::user()->shop_id,
            'name' => $this->name,
            'min_selection' => $this->min_selection,
            'max_selection' => $this->max_selection,
        ]);

        $this->reset(['name', 'min_selection', 'max_selection']);
    }

    #[Layout('layouts.admin')]
    public function render()
    {
        return view('livewire.modifier-manager');
    }
}
