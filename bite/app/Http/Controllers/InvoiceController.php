<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Support\Facades\Auth;

class InvoiceController extends Controller
{
    public function show(Order $order)
    {
        if ($order->shop_id !== Auth::user()->shop_id) {
            abort(403);
        }

        $order->load('items.modifiers', 'shop');

        return view('invoices.order', [
            'order' => $order,
        ]);
    }
}
