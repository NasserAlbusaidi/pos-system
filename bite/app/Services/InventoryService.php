<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    public function consumeOrder(Order $order): void
    {
        $order->loadMissing('items.product.ingredients');

        DB::transaction(function () use ($order) {
            foreach ($order->items as $item) {
                $product = $item->product;
                if (! $product) {
                    continue;
                }

                foreach ($product->ingredients as $ingredient) {
                    $consume = $ingredient->pivot->quantity * $item->quantity;
                    $ingredient->update([
                        'stock_quantity' => max(0, $ingredient->stock_quantity - $consume),
                    ]);
                }
            }
        });
    }
}
