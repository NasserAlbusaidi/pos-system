<?php

namespace App\Livewire\Admin;

use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Shop;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

class MenuEngineering extends Component
{
    public Shop $shop;

    public int $rangeDays = 30;

    public function mount(): void
    {
        $this->shop = Auth::user()->shop;
    }

    public function updatedRangeDays(): void
    {
        // Livewire re-renders automatically when a public property changes.
    }

    #[Layout('layouts.admin')]
    public function render()
    {
        $analysis = $this->loadAnalysis();

        return view('livewire.admin.menu-engineering', [
            'products' => $analysis['products'],
            'counts' => $analysis['counts'],
            'avgQuantity' => $analysis['avgQuantity'],
            'avgRevenue' => $analysis['avgRevenue'],
        ]);
    }

    private function loadAnalysis(): array
    {
        $shopId = Auth::user()->shop_id;
        $from = now()->subDays($this->rangeDays - 1)->startOfDay();
        $to = now()->endOfDay();
        $validStatuses = ['completed', 'ready', 'preparing', 'paid'];

        // Aggregate sales data per product using DB-level grouping
        $salesData = OrderItem::query()
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.shop_id', $shopId)
            ->whereIn('orders.status', $validStatuses)
            ->whereBetween('orders.paid_at', [$from, $to])
            ->select(
                'order_items.product_id',
                DB::raw('SUM(order_items.quantity) as total_quantity'),
                DB::raw('SUM(order_items.price_snapshot * order_items.quantity) as total_revenue')
            )
            ->groupBy('order_items.product_id')
            ->get()
            ->keyBy('product_id');

        // Load all shop products with their categories (including those with 0 sales)
        $allProducts = Product::where('shop_id', $shopId)
            ->with('category')
            ->get();

        if ($allProducts->isEmpty()) {
            return [
                'products' => collect(),
                'counts' => ['star' => 0, 'cash_cow' => 0, 'puzzle' => 0, 'dog' => 0],
                'avgQuantity' => 0,
                'avgRevenue' => 0,
            ];
        }

        // Merge product info with sales data
        $productAnalysis = $allProducts->map(function (Product $product) use ($salesData) {
            $sales = $salesData->get($product->id);

            return (object) [
                'id' => $product->id,
                'name_en' => $product->name_en,
                'category_name' => $product->category?->name_en ?? 'Uncategorized',
                'price' => (float) $product->price,
                'total_quantity' => $sales ? (int) $sales->total_quantity : 0,
                'total_revenue' => $sales ? (float) $sales->total_revenue : 0,
                'classification' => null,
                'suggestion' => null,
            ];
        });

        // Calculate averages across all products
        $totalProducts = $productAnalysis->count();
        $avgQuantity = $totalProducts > 0
            ? $productAnalysis->sum('total_quantity') / $totalProducts
            : 0;
        $avgRevenue = $totalProducts > 0
            ? $productAnalysis->sum('total_revenue') / $totalProducts
            : 0;

        // Classify each product and assign suggestions
        $classified = $productAnalysis->map(function ($item) use ($avgQuantity, $avgRevenue) {
            $highQuantity = $item->total_quantity > $avgQuantity;
            $highRevenue = $item->total_revenue > $avgRevenue;

            $classification = match (true) {
                $highQuantity && $highRevenue => 'star',
                $highQuantity && ! $highRevenue => 'cash_cow',
                ! $highQuantity && $highRevenue => 'puzzle',
                default => 'dog',
            };

            $suggestion = match ($classification) {
                'star' => 'Keep promoting. This is your best performer.',
                'cash_cow' => 'Popular but low revenue. Consider a small price increase.',
                'puzzle' => 'High revenue per sale but low volume. Improve visibility or placement.',
                'dog' => 'Low sales and low revenue. Consider removing or reworking.',
            };

            $item->classification = $classification;
            $item->suggestion = $suggestion;

            return $item;
        });

        // Sort: stars first, then cash cows, puzzles, dogs — within each group by revenue desc
        $sortOrder = ['star' => 0, 'cash_cow' => 1, 'puzzle' => 2, 'dog' => 3];
        $sorted = $classified->sortBy([
            fn ($a, $b) => ($sortOrder[$a->classification] ?? 4) <=> ($sortOrder[$b->classification] ?? 4),
            fn ($a, $b) => $b->total_revenue <=> $a->total_revenue,
        ])->values();

        $counts = [
            'star' => $classified->where('classification', 'star')->count(),
            'cash_cow' => $classified->where('classification', 'cash_cow')->count(),
            'puzzle' => $classified->where('classification', 'puzzle')->count(),
            'dog' => $classified->where('classification', 'dog')->count(),
        ];

        return [
            'products' => $sorted,
            'counts' => $counts,
            'avgQuantity' => round($avgQuantity, 1),
            'avgRevenue' => round($avgRevenue, 3),
        ];
    }
}
