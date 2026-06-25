<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #{{ $order->id }}</title>
    <style>
        body { font-family: 'Rubik', system-ui, sans-serif; color: #1A1918; background: #FDFCF8; }
        .card { max-width: 720px; margin: 40px auto; background: #fff; border: 2px solid #1A1918; padding: 24px; }
        .header { display: flex; justify-content: space-between; align-items: center; }
        .mono { font-family: 'IBM Plex Mono', monospace; text-transform: uppercase; letter-spacing: 0.12em; font-size: 10px; }
        .status { display: inline-block; margin-top: 8px; border: 1px solid #1A1918; padding: 4px 8px; font-family: 'IBM Plex Mono', monospace; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.12em; }
        .status.due { color: #8A3A15; border-color: #8A3A15; }
        .status.paid { color: #0B6848; border-color: #0B6848; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { border-bottom: 1px solid #D1D1CB; padding: 8px 0; text-align: start; }
        .totals { margin-top: 16px; display: grid; grid-template-columns: 1fr auto; gap: 6px; }
        .payments { margin-top: 20px; border-top: 1px solid #D1D1CB; padding-top: 16px; }
        .payment-row { display: grid; grid-template-columns: 1fr auto; gap: 6px; margin-top: 6px; }
        .total { font-weight: bold; }
    </style>
</head>
<body>
    @php
        $paidTotal = $order->paid_total;
        $balanceDue = $order->balance_due;
        $trustedPayments = $order->trustedPayments();
    @endphp

    <div class="card">
        <div class="header">
            <div>
                <div class="mono">Invoice</div>
                <h1 style="margin: 4px 0 0;">{{ $order->shop->name }}</h1>
                @if($balanceDue > 0)
                    <div class="status due">Payment due</div>
                @else
                    <div class="status paid">Paid</div>
                @endif
            </div>
            <div style="text-align:right;">
                <div class="mono">Order #{{ $order->id }}</div>
                <div>{{ $order->created_at->format('Y-m-d H:i') }}</div>
            </div>
        </div>

        <div style="margin-top: 16px;">
            <div class="mono">Order Type</div>
            <div>{{ $order->sourceLabel() }}</div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Qty</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $item)
                    <tr>
                        <td>
                            <div>{{ $item->product_name_snapshot_en }}</div>
                            @if($item->modifiers->isNotEmpty())
                                <div class="mono" style="opacity:0.5;">
                                    @foreach($item->modifiers as $modifier)
                                        + {{ $modifier->modifier_option_name_snapshot_en }}
                                    @endforeach
                                </div>
                            @endif
                        </td>
                        <td>{{ $item->quantity }}</td>
                        <td>{{ formatPrice($item->price_snapshot * $item->quantity, $order->shop) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="totals">
            <div class="mono">Subtotal</div>
            <div>{{ formatPrice($order->subtotal_amount ?? $order->total_amount, $order->shop) }}</div>
            <div class="mono">Tax</div>
            <div>{{ formatPrice($order->tax_amount ?? 0, $order->shop) }}</div>
            <div class="mono total">Total</div>
            <div class="total">{{ formatPrice($order->total_amount, $order->shop) }}</div>
        </div>

        <div class="payments">
            <div class="mono">Payment</div>
            @foreach($trustedPayments as $payment)
                <div class="payment-row">
                    <div>{{ ucfirst($payment->method) }}</div>
                    <div>{{ formatPrice($payment->amount, $order->shop) }}</div>
                </div>
            @endforeach
            <div class="payment-row">
                <div class="mono">Amount paid</div>
                <div>{{ formatPrice($paidTotal, $order->shop) }}</div>
            </div>
            @if($balanceDue > 0)
                <div class="payment-row total">
                    <div class="mono">Balance due</div>
                    <div>{{ formatPrice($balanceDue, $order->shop) }}</div>
                </div>
            @endif
        </div>
    </div>
</body>
</html>
