<?php

namespace App\Livewire\Admin;

use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

class MenuBuilder extends Component
{
    public Shop $shop;

    public $search = '';

    public $newCategoryNameEn = '';

    public $newCategoryNameAr = '';

    public function mount(): void
    {
        $this->shop = Auth::user()->shop;
    }

    public function createCategory()
    {
        $this->validate([
            'newCategoryNameEn' => 'required|string|min:2',
            'newCategoryNameAr' => 'nullable|string|min:2',
        ]);

        Category::create([
            'shop_id' => Auth::user()->shop_id,
            'name_en' => $this->newCategoryNameEn,
            'name_ar' => $this->newCategoryNameAr ?: null,
            'sort_order' => Category::where('shop_id', Auth::user()->shop_id)->count() + 1,
        ]);

        $this->newCategoryNameEn = '';
        $this->newCategoryNameAr = '';
    }

    public function renameCategory($categoryId, $nameEn, $nameAr = null)
    {
        $shopId = Auth::user()->shop_id;
        $nameEn = trim((string) $nameEn);
        if ($nameEn === '') {
            return;
        }

        $category = Category::where('shop_id', $shopId)->findOrFail($categoryId);
        $data = ['name_en' => Str::limit($nameEn, 60, '')];
        if ($nameAr !== null) {
            $data['name_ar'] = Str::limit(trim((string) $nameAr), 60, '') ?: null;
        }
        $category->update($data);

        AuditLog::record('category.renamed', $category, ['name_en' => $nameEn]);
    }

    public function deleteCategory($categoryId)
    {
        $category = Category::where('shop_id', Auth::user()->shop_id)
            ->withCount('products')
            ->findOrFail($categoryId);

        if ($category->products_count > 0) {
            session()->flash('message', 'Move or delete products before removing this category.');

            return;
        }

        $category->delete();
        AuditLog::record('category.deleted', $category);
    }

    public function reorderProduct($productId, $newCategoryId, $items)
    {
        $shopId = Auth::user()->shop_id;

        $product = Product::where('shop_id', $shopId)->findOrFail((int) $productId);
        $targetCategory = Category::where('shop_id', $shopId)->findOrFail((int) $newCategoryId);
        $product->update(['category_id' => $targetCategory->id]);

        foreach ($this->validatedSortPayload((array) $items, $shopId) as $itemId => $sortOrder) {
            Product::where('shop_id', $shopId)
                ->where('id', $itemId)
                ->update(['sort_order' => $sortOrder]);
        }
    }

    public function updateProductCategory($productId, $newCategoryId)
    {
        $shopId = Auth::user()->shop_id;
        $product = Product::where('shop_id', $shopId)->findOrFail((int) $productId);
        $targetCategory = Category::where('shop_id', $shopId)->findOrFail((int) $newCategoryId);

        $product->update([
            'category_id' => $targetCategory->id,
        ]);
    }

    public function updateOrder($items)
    {
        $shopId = Auth::user()->shop_id;

        foreach ($this->validatedSortPayload((array) $items, $shopId) as $itemId => $sortOrder) {
            Product::where('shop_id', $shopId)
                ->where('id', $itemId)
                ->update(['sort_order' => $sortOrder]);
        }
    }

    protected function validatedSortPayload(array $items, int $shopId): array
    {
        $payload = [];

        foreach ($items as $item) {
            $itemId = (int) ($item['value'] ?? 0);
            if ($itemId <= 0) {
                continue;
            }

            $payload[$itemId] = max(0, (int) ($item['order'] ?? 0));
        }

        if ($payload === []) {
            return [];
        }

        $validIds = Product::where('shop_id', $shopId)
            ->whereIn('id', array_keys($payload))
            ->pluck('id')
            ->all();

        if (count($validIds) !== count($payload)) {
            abort(422, 'Invalid product payload.');
        }

        return $payload;
    }

    public function toggleVisibility($productId)
    {
        $product = Product::where('shop_id', Auth::user()->shop_id)
            ->findOrFail($productId);

        $product->update([
            'is_visible' => ! $product->is_visible,
        ]);
    }

    public function deleteProduct($productId)
    {
        $product = Product::where('shop_id', Auth::user()->shop_id)->findOrFail($productId);
        $product->modifierGroups()->detach();
        $product->delete();

        AuditLog::record('product.deleted', $product);
    }

    #[Layout('layouts.admin')]
    public function render()
    {
        $categories = Category::where('shop_id', Auth::user()->shop_id)
            ->with(['products' => function ($query) {
                $query->where(function ($q) {
                    $q->where('name_en', 'like', '%'.$this->search.'%')
                        ->orWhere('name_ar', 'like', '%'.$this->search.'%');
                })
                    ->orderBy('sort_order');
            }])
            ->orderBy('sort_order')
            ->get();

        return view('livewire.admin.menu-builder', [
            'categories' => $categories,
        ]);
    }
}
