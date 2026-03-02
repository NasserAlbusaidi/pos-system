<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Support\Facades\Auth;

class ReceiptController extends Controller
{
    public function show(Order $order)
    {
        if ($order->shop_id !== Auth::user()->shop_id) {
            abort(403);
        }

        $order->load('items.modifiers', 'payments', 'shop');

        return view('receipt-print', [
            'order' => $order,
            'shop' => $order->shop,
        ]);
    }
}
