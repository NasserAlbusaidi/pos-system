<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Support\Facades\Auth;

class InvoiceController extends Controller
{
    public function show(Order $order)
    {
        // Scope to authenticated user's shop — returns uniform 404 for both
        // non-existent orders and orders from other shops (prevents ID enumeration).
        $order = Order::where('shop_id', Auth::user()->shop_id)
            ->where('id', $order->id)
            ->firstOrFail();

        $order->load('items.modifiers', 'shop');

        return view('invoices.order', [
            'order' => $order,
        ]);
    }
}
