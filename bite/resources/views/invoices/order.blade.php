<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #{{ $order->id }}</title>
    <style>
        body { font-family: 'DM Sans', Arial, sans-serif; color: #1A1918; background: #FDFCF8; }
        .card { max-width: 720px; margin: 40px auto; background: #fff; border: 2px solid #1A1918; padding: 24px; }
        .header { display: flex; justify-content: space-between; align-items: center; }
        .mono { font-family: 'IBM Plex Mono', monospace; text-transform: uppercase; letter-spacing: 0.12em; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { border-bottom: 1px solid #D1D1CB; padding: 8px 0; text-align: left; }
        .totals { margin-top: 16px; display: grid; grid-template-columns: 1fr auto; gap: 6px; }
        .total { font-weight: bold; }
    </style>
</head>
<body>
    <div class="card">
        <div class="header">
            <div>
                <div class="mono">Invoice</div>
                <h1 style="margin: 4px 0 0;">{{ $order->shop->name }}</h1>
            </div>
            <div style="text-align:right;">
                <div class="mono">Order #{{ $order->id }}</div>
                <div>{{ $order->created_at->format('Y-m-d H:i') }}</div>
            </div>
        </div>

        <div style="margin-top: 16px;">
            <div class="mono">Order Type</div>
            <div>Guest Pickup</div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Qty</th>
                    <th>Price</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $item)
                    <tr>
                        <td>
                            <div>{{ $item->product_name_snapshot }}</div>
                            @if($item->modifiers->isNotEmpty())
                                <div class="mono" style="opacity:0.5;">
                                    @foreach($item->modifiers as $modifier)
                                        + {{ $modifier->modifier_option_name_snapshot }}
                                    @endforeach
                                </div>
                            @endif
                        </td>
                        <td>{{ $item->quantity }}</td>
                        <td>${{ number_format($item->price_snapshot, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="totals">
            <div class="mono">Subtotal</div>
            <div>${{ number_format($order->subtotal_amount ?? $order->total_amount, 2) }}</div>
            <div class="mono">Tax</div>
            <div>${{ number_format($order->tax_amount ?? 0, 2) }}</div>
            <div class="mono total">Total</div>
            <div class="total">${{ number_format($order->total_amount, 2) }}</div>
        </div>
    </div>
</body>
</html>
