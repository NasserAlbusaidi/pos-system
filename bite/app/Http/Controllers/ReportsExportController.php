<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Shop;
use App\Support\ShopClock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReportsExportController extends Controller
{
    public function orders(Request $request)
    {
        $shop = Auth::user()->shop;
        $localToday = ShopClock::localDate($shop);

        $request->validate([
            'from' => 'nullable|date|before_or_equal:'.$localToday,
            'to' => 'nullable|date|before_or_equal:'.$localToday.'|after_or_equal:from',
        ]);

        $shopId = $shop->id;
        $fromDate = $request->query('from') ?: ShopClock::localDate($shop, offsetDays: -29);
        $toDate = $request->query('to') ?: ShopClock::localDate($shop);
        [$from] = ShopClock::localDayUtcRange($shop, (string) $fromDate);
        [, $to] = ShopClock::localDayUtcRange($shop, (string) $toDate);

        $orders = Order::where('shop_id', $shopId)
            ->where(function ($query) use ($shopId, $from, $to) {
                $query->where(function ($query) use ($from, $to) {
                    $query->revenueRecognized()
                        ->whereBetween('paid_at', [$from, $to]);
                })
                    ->orWhereHas('payments', function ($query) use ($shopId, $from, $to) {
                        $query->reportableForPaymentSummary($shopId, $from, $to);
                    });
            })
            ->with(['payments' => fn ($query) => $query->reportableForPaymentSummary($shopId, $from, $to)])
            ->orderBy('paid_at')
            ->get();

        $filename = 'orders-export-'.str_replace('-', '', (string) $fromDate).'-'.str_replace('-', '', (string) $toDate).'.csv';

        return response()->streamDownload(function () use ($orders, $shop) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'order_id',
                'paid_at',
                'subtotal',
                'tax',
                'total',
                'payment_method',
                'payment_breakdown',
                'status',
                'payment_activity_at',
            ]);
            foreach ($orders as $order) {
                fputcsv($handle, [
                    $order->id,
                    $this->formatLocalDateTime($order->paid_at, $shop),
                    $order->subtotal_amount,
                    $order->tax_amount,
                    $order->total_amount,
                    $order->payment_method,
                    $this->paymentBreakdown($order),
                    $order->status,
                    $this->paymentActivityAt($order, $shop),
                ]);
            }
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function paymentBreakdown(Order $order): string
    {
        return $order->trustedPayments()
            ->sortBy('id')
            ->map(fn ($payment) => $payment->method.':'.number_format((float) $payment->amount, 3, '.', ''))
            ->implode('; ');
    }

    private function paymentActivityAt(Order $order, Shop $shop): string
    {
        $paidAt = $order->trustedPayments()
            ->pluck('paid_at')
            ->filter()
            ->max();

        return $this->formatLocalDateTime($paidAt, $shop);
    }

    private function formatLocalDateTime(mixed $value, Shop $shop): string
    {
        if (! $value) {
            return '';
        }

        return $value->timezone(ShopClock::timezone($shop))->format('Y-m-d H:i');
    }
}
