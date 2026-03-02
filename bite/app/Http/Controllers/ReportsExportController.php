<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReportsExportController extends Controller
{
    public function orders(Request $request)
    {
        $request->validate([
            'from' => 'nullable|date|before_or_equal:today',
            'to' => 'nullable|date|before_or_equal:today|after_or_equal:from',
        ]);

        $shopId = Auth::user()->shop_id;
        $from = $request->query('from') ? now()->parse($request->query('from'))->startOfDay() : now()->subDays(30)->startOfDay();
        $to = $request->query('to') ? now()->parse($request->query('to'))->endOfDay() : now()->endOfDay();

        $orders = Order::where('shop_id', $shopId)
            ->where('status', 'completed')
            ->whereBetween('paid_at', [$from, $to])
            ->orderBy('paid_at')
            ->get();

        $filename = 'orders-export-'.$from->format('Ymd').'-'.$to->format('Ymd').'.csv';

        return response()->streamDownload(function () use ($orders) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['order_id', 'paid_at', 'subtotal', 'tax', 'total', 'payment_method']);
            foreach ($orders as $order) {
                fputcsv($handle, [
                    $order->id,
                    optional($order->paid_at)->format('Y-m-d H:i'),
                    $order->subtotal_amount,
                    $order->tax_amount,
                    $order->total_amount,
                    $order->payment_method,
                ]);
            }
            fclose($handle);
        }, $filename);
    }
}
