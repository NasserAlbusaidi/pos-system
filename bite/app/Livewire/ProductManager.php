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

    public $recipeProductId = null;

    public $recipeIngredients = [];

    public $name;

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
            'name' => 'required|string|min:3',
            'price' => 'required|numeric|min:0',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'category_id' => [
                'required',
                Rule::exists('categories', 'id')->where('shop_id', $shopId),
            ],
            'image' => 'nullable|image|max:1024', // 1MB Max
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
        $this->name = $product->name;
        $this->price = $product->price;
        $this->tax_rate = $product->tax_rate;
        $this->category_id = $product->category_id;
        $this->selectedModifierGroups = $product->modifierGroups->pluck('id')->all();
        $this->image = null;
    }

    public function cancelEdit()
    {
        $this->reset(['editingProductId', 'currentImageUrl', 'name', 'price', 'tax_rate', 'category_id', 'image', 'selectedModifierGroups']);
    }

    public function openRecipe($productId)
    {
        $product = Product::where('shop_id', Auth::user()->shop_id)
            ->with('ingredients')
            ->findOrFail($productId);

        $this->recipeProductId = $product->id;
        $this->recipeIngredients = $product->ingredients
            ->mapWithKeys(fn ($ingredient) => [$ingredient->id => $ingredient->pivot->quantity])
            ->all();
    }

    public function closeRecipe()
    {
        $this->reset(['recipeProductId', 'recipeIngredients']);
    }

    public function saveRecipe()
    {
        if (! $this->recipeProductId) {
            return;
        }

        $product = Product::where('shop_id', Auth::user()->shop_id)
            ->findOrFail($this->recipeProductId);

        $sync = [];
        foreach ($this->recipeIngredients as $ingredientId => $quantity) {
            $quantity = (float) $quantity;
            if ($quantity > 0) {
                $sync[$ingredientId] = ['quantity' => $quantity];
            }
        }

        $product->ingredients()->sync($sync);

        session()->flash('message', 'Recipe updated successfully.');
        $this->closeRecipe();
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
                'name' => $this->name,
                'price' => $this->price,
                'tax_rate' => $this->tax_rate,
                'image_url' => $imageUrl,
            ]);

            $product->modifierGroups()->sync($this->selectedModifierGroups ?? []);

            $this->reset(['editingProductId', 'currentImageUrl', 'name', 'price', 'tax_rate', 'category_id', 'image', 'selectedModifierGroups']);
            session()->flash('message', 'Product updated successfully.');

            return;
        }

        $imageUrl = null;
        if ($this->image) {
            $imageUrl = $this->image->store('products', 'public');
        }

        $product = Product::create([
            'shop_id' => Auth::user()->shop_id,
            'category_id' => $this->category_id,
            'name' => $this->name,
            'price' => $this->price,
            'tax_rate' => $this->tax_rate,
            'image_url' => $imageUrl,
        ]);

        $product->modifierGroups()->sync($this->selectedModifierGroups ?? []);

        $this->reset(['name', 'price', 'tax_rate', 'category_id', 'image', 'selectedModifierGroups']);
        session()->flash('message', 'Product added successfully.');
    }

    #[Layout('layouts.admin')]
    public function render()
    {
        return view('livewire.product-manager', [
            'ingredients' => \App\Models\Ingredient::where('shop_id', Auth::user()->shop_id)->orderBy('name')->get(),
        ]);
    }
}
