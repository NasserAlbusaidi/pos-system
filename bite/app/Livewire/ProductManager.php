<?php

namespace App\Livewire;

use App\Models\Product;
use App\Models\Shop;
use App\Services\BillingService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

class ProductManager extends Component
{
    use WithFileUploads;

    public Shop $shop;

    public $editingProductId = null;

    public $currentImageUrl = null;

    public $name_en;

    public $name_ar;

    public $description_en;

    public $description_ar;

    public $price;

    public $tax_rate;

    public $category_id;

    public $image;

    public $selectedModifierGroups = [];

    public function mount()
    {
        $this->shop = Auth::user()->shop;
        $editId = request()->query('edit');
        if ($editId) {
            $this->editProduct($editId);
        }
    }

    protected function rules()
    {
        $shopId = Auth::user()->shop_id;

        return [
            'name_en' => 'required|string|min:3',
            'name_ar' => 'nullable|string|min:3',
            'description_en' => 'nullable|string',
            'description_ar' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'category_id' => [
                'required',
                Rule::exists('categories', 'id')->where('shop_id', $shopId),
            ],
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:1024', // 1MB Max
            'selectedModifierGroups' => 'nullable|array',
            'selectedModifierGroups.*' => Rule::exists('modifier_groups', 'id')->where('shop_id', $shopId),
        ];
    }

    public function editProduct($productId)
    {
        $product = Product::where('shop_id', Auth::user()->shop_id)
            ->with('modifierGroups')
            ->findOrFail($productId);

        $this->editingProductId = $product->id;
        $this->currentImageUrl = $product->image_url;
        $this->name_en = $product->name_en;
        $this->name_ar = $product->name_ar;
        $this->description_en = $product->description_en;
        $this->description_ar = $product->description_ar;
        $this->price = $product->price;
        $this->tax_rate = $product->tax_rate;
        $this->category_id = $product->category_id;
        $this->selectedModifierGroups = $product->modifierGroups->pluck('id')->all();
        $this->image = null;
    }

    public function cancelEdit()
    {
        $this->reset(['editingProductId', 'currentImageUrl', 'name_en', 'name_ar', 'description_en', 'description_ar', 'price', 'tax_rate', 'category_id', 'image', 'selectedModifierGroups']);
    }

    public function save()
    {
        $this->validate();

        // Check plan limits before creating a new product (not when editing).
        if (! $this->editingProductId) {
            $billing = app(BillingService::class);
            if (! $billing->canAccess($this->shop, 'add_product')) {
                $limits = $billing->getPlanLimits($this->shop);
                $this->dispatch('toast',
                    message: "Product limit reached ({$limits['product_limit']} products on your current plan). Upgrade to Pro for unlimited products.",
                    variant: 'error'
                );

                return;
            }
        }

        if ($this->editingProductId) {
            $product = Product::where('shop_id', Auth::user()->shop_id)
                ->findOrFail($this->editingProductId);

            $imageUrl = $product->image_url;
            if ($this->image) {
                $imageUrl = $this->image->store('products', 'public');
            }

            $product->update([
                'category_id' => $this->category_id,
                'name_en' => $this->name_en,
                'name_ar' => $this->name_ar,
                'description_en' => $this->description_en,
                'description_ar' => $this->description_ar,
                'price' => $this->price,
                'tax_rate' => $this->tax_rate,
                'image_url' => $imageUrl,
            ]);

            $product->modifierGroups()->sync($this->selectedModifierGroups ?? []);

            $this->reset(['editingProductId', 'currentImageUrl', 'name_en', 'name_ar', 'description_en', 'description_ar', 'price', 'tax_rate', 'category_id', 'image', 'selectedModifierGroups']);
            session()->flash('message', 'Product updated successfully.');

            return;
        }

        $imageUrl = null;
        if ($this->image) {
            $imageUrl = $this->image->store('products', 'public');
        }

        $product = Product::forceCreate([
            'shop_id' => Auth::user()->shop_id,
            'category_id' => $this->category_id,
            'name_en' => $this->name_en,
            'name_ar' => $this->name_ar,
            'description_en' => $this->description_en,
            'description_ar' => $this->description_ar,
            'price' => $this->price,
            'tax_rate' => $this->tax_rate,
            'image_url' => $imageUrl,
        ]);

        $product->modifierGroups()->sync($this->selectedModifierGroups ?? []);

        $this->reset(['name_en', 'name_ar', 'description_en', 'description_ar', 'price', 'tax_rate', 'category_id', 'image', 'selectedModifierGroups']);
        session()->flash('message', 'Product added successfully.');
    }

    #[Layout('layouts.admin')]
    public function render()
    {
        return view('livewire.product-manager');
    }
}
