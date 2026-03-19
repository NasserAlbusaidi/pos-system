<?php

namespace App\Livewire\Admin;

use App\Models\Category;
use App\Models\PricingRule;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

class PricingRules extends Component
{
    public $name = '';

    public $discount_type = 'percentage';

    public $discount_value = '';

    public $start_time = '';

    public $end_time = '';

    public $days_of_week = [];

    public $category_id = '';

    public $product_id = '';

    public $editingId = null;

    protected function rules(): array
    {
        return [
            'name' => 'required|string|min:2|max:120',
            'discount_type' => 'required|in:percentage,fixed',
            'discount_value' => 'required|numeric|min:0.001',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'days_of_week' => 'nullable|array',
            'days_of_week.*' => 'integer|between:0,6',
            'category_id' => 'nullable|integer|exists:categories,id',
            'product_id' => 'nullable|integer|exists:products,id',
        ];
    }

    public function save(): void
    {
        $this->validate();

        $shopId = Auth::user()->shop_id;

        // Verify category belongs to this shop
        if ($this->category_id) {
            Category::where('shop_id', $shopId)->findOrFail($this->category_id);
        }

        // Verify product belongs to this shop
        if ($this->product_id) {
            Product::where('shop_id', $shopId)->findOrFail($this->product_id);
        }

        $attributes = [
            'shop_id' => $shopId,
            'name' => $this->name,
            'discount_type' => $this->discount_type,
            'discount_value' => (float) $this->discount_value,
            'start_time' => $this->start_time.':00',
            'end_time' => $this->end_time.':00',
            'days_of_week' => ! empty($this->days_of_week) ? array_map('intval', $this->days_of_week) : null,
            'category_id' => $this->product_id ? null : ($this->category_id ?: null),
            'product_id' => $this->product_id ?: null,
        ];

        if ($this->editingId) {
            $rule = PricingRule::where('shop_id', $shopId)->findOrFail($this->editingId);
            $rule->update($attributes);
        } else {
            PricingRule::create($attributes);
        }

        $this->resetForm();
        session()->flash('message', $this->editingId ? 'Pricing rule updated.' : 'Pricing rule created.');
    }

    public function edit(int $id): void
    {
        $rule = PricingRule::where('shop_id', Auth::user()->shop_id)->findOrFail($id);

        $this->editingId = $rule->id;
        $this->name = $rule->name;
        $this->discount_type = $rule->discount_type;
        $this->discount_value = $rule->discount_value;
        $this->start_time = substr($rule->start_time, 0, 5); // H:i format
        $this->end_time = substr($rule->end_time, 0, 5);
        $this->days_of_week = $rule->days_of_week ?? [];
        $this->category_id = $rule->category_id ?? '';
        $this->product_id = $rule->product_id ?? '';
    }

    public function delete(int $id): void
    {
        PricingRule::where('shop_id', Auth::user()->shop_id)->findOrFail($id)->delete();
        session()->flash('message', 'Pricing rule deleted.');
    }

    public function toggleActive(int $id): void
    {
        $rule = PricingRule::where('shop_id', Auth::user()->shop_id)->findOrFail($id);
        $rule->update(['is_active' => ! $rule->is_active]);
    }

    public function cancelEdit(): void
    {
        $this->resetForm();
    }

    protected function resetForm(): void
    {
        $this->reset([
            'name',
            'discount_type',
            'discount_value',
            'start_time',
            'end_time',
            'days_of_week',
            'category_id',
            'product_id',
            'editingId',
        ]);
        $this->discount_type = 'percentage';
    }

    #[Layout('layouts.admin')]
    public function render()
    {
        $shopId = Auth::user()->shop_id;

        $rules = PricingRule::where('shop_id', $shopId)
            ->with(['category', 'product'])
            ->latest()
            ->get();

        $categories = Category::where('shop_id', $shopId)
            ->orderBy('name_en')
            ->get();

        $products = Product::where('shop_id', $shopId)
            ->orderBy('name_en')
            ->get();

        return view('livewire.admin.pricing-rules', [
            'rules' => $rules,
            'categories' => $categories,
            'products' => $products,
        ]);
    }
}
