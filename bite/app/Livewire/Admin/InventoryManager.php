<?php

namespace App\Livewire\Admin;

use App\Models\Ingredient;
use App\Models\Supplier;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

class InventoryManager extends Component
{
    public $name = '';

    public $unit = 'unit';

    public $stock_quantity = 0;

    public $reorder_threshold = 0;

    public $supplierName = '';

    public $supplierEmail = '';

    public $supplierPhone = '';

    public $ingredientStocks = [];

    public $ingredientThresholds = [];

    public function addIngredient()
    {
        $this->validate([
            'name' => 'required|string|min:2',
            'unit' => 'required|string|min:1',
            'stock_quantity' => 'required|numeric|min:0',
            'reorder_threshold' => 'required|numeric|min:0',
        ]);

        Ingredient::create([
            'shop_id' => Auth::user()->shop_id,
            'name' => $this->name,
            'unit' => $this->unit,
            'stock_quantity' => $this->stock_quantity,
            'reorder_threshold' => $this->reorder_threshold,
        ]);

        $this->reset(['name', 'unit', 'stock_quantity', 'reorder_threshold']);
        session()->flash('message', 'Ingredient added.');
    }

    public function saveIngredient($ingredientId)
    {
        $ingredient = Ingredient::where('shop_id', Auth::user()->shop_id)->findOrFail($ingredientId);

        $stock = $this->ingredientStocks[$ingredientId] ?? $ingredient->stock_quantity;
        $threshold = $this->ingredientThresholds[$ingredientId] ?? $ingredient->reorder_threshold;

        $ingredient->update([
            'stock_quantity' => max(0, (float) $stock),
            'reorder_threshold' => max(0, (float) $threshold),
        ]);

        session()->flash('message', 'Ingredient updated.');
    }

    public function deleteIngredient($ingredientId)
    {
        $ingredient = Ingredient::where('shop_id', Auth::user()->shop_id)->findOrFail($ingredientId);
        $ingredient->delete();
    }

    public function addSupplier()
    {
        $this->validate([
            'supplierName' => 'required|string|min:2',
            'supplierEmail' => 'nullable|email',
            'supplierPhone' => 'nullable|string',
        ]);

        Supplier::create([
            'shop_id' => Auth::user()->shop_id,
            'name' => $this->supplierName,
            'email' => $this->supplierEmail,
            'phone' => $this->supplierPhone,
        ]);

        $this->reset(['supplierName', 'supplierEmail', 'supplierPhone']);
        session()->flash('message', 'Supplier added.');
    }

    public function deleteSupplier($supplierId)
    {
        $supplier = Supplier::where('shop_id', Auth::user()->shop_id)->findOrFail($supplierId);
        $supplier->delete();
    }

    #[Layout('layouts.admin')]
    public function render()
    {
        $ingredients = Ingredient::where('shop_id', Auth::user()->shop_id)->orderBy('name')->get();
        $suppliers = Supplier::where('shop_id', Auth::user()->shop_id)->orderBy('name')->get();

        foreach ($ingredients as $ingredient) {
            if (! array_key_exists($ingredient->id, $this->ingredientStocks)) {
                $this->ingredientStocks[$ingredient->id] = $ingredient->stock_quantity;
            }
            if (! array_key_exists($ingredient->id, $this->ingredientThresholds)) {
                $this->ingredientThresholds[$ingredient->id] = $ingredient->reorder_threshold;
            }
        }

        return view('livewire.admin.inventory-manager', [
            'ingredients' => $ingredients,
            'suppliers' => $suppliers,
        ]);
    }
}
