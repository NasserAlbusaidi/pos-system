<?php

namespace App\Livewire;

use App\Livewire\Concerns\AuthorizesRole;
use App\Models\AuditLog;
use App\Models\ModifierGroup;
use App\Models\ModifierOption;
use App\Models\Shop;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

class ModifierManager extends Component
{
    use AuthorizesRole;

    protected function allowedRoles(): array
    {
        return ['manager', 'admin'];
    }

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

        $option = ModifierOption::create([
            'modifier_group_id' => $group->id,
            'name_en' => $this->optionNameEn,
            'name_ar' => $this->optionNameAr,
            'price_adjustment' => $this->optionPrice,
        ]);

        AuditLog::record('modifier.option.created', $option, $option->auditSnapshot());

        $this->reset(['optionNameEn', 'optionNameAr', 'optionPrice']);
    }

    protected function rules()
    {
        return [
            'name_en' => 'required|string|min:2',
            'name_ar' => 'nullable|string|min:2',
            'min_selection' => 'required|integer|min:0',
            'max_selection' => 'required|integer|min:1|gte:min_selection',
        ];
    }

    public function save()
    {
        $this->validate();

        $group = ModifierGroup::create([
            'shop_id' => Auth::user()->shop_id,
            'name_en' => $this->name_en,
            'name_ar' => $this->name_ar,
            'min_selection' => $this->min_selection,
            'max_selection' => $this->max_selection,
        ]);

        AuditLog::record('modifier.group.created', $group, $group->auditSnapshot());

        $this->reset(['name_en', 'name_ar', 'min_selection', 'max_selection']);
    }

    public function deleteGroup(int $groupId): void
    {
        $group = ModifierGroup::where('shop_id', Auth::user()->shop_id)
            ->findOrFail($groupId);
        $snapshot = $group->auditSnapshot();

        // Detach from all products first
        $group->products()->detach();

        // Delete options then group
        $group->options()->delete();
        $group->delete();

        AuditLog::record('modifier.group.deleted', $group, $snapshot);

        if ($this->selectedGroupId == $groupId) {
            $this->selectedGroupId = null;
        }
    }

    public function deleteOption(int $optionId): void
    {
        $option = ModifierOption::whereHas('group', function ($q) {
            $q->where('shop_id', Auth::user()->shop_id);
        })->findOrFail($optionId);
        $snapshot = $option->auditSnapshot();

        $option->delete();

        AuditLog::record('modifier.option.deleted', $option, $snapshot);
    }

    #[Layout('layouts.admin')]
    public function render()
    {
        return view('livewire.modifier-manager');
    }
}
