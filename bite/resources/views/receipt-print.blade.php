<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt - Order #{{ $order->id }}</title>
    <style>
        /* ── Reset ── */
        *, *::before, *::after {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        /* ── Screen styles ── */
        body {
            font-family: 'Courier New', Courier, monospace;
            background: #f0efea;
            color: #121924;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 24px 16px;
            min-height: 100vh;
        }

        .screen-controls {
            display: flex;
            gap: 12px;
            margin-bottom: 24px;
            width: 302px;
        }

        .screen-controls button {
            flex: 1;
            padding: 10px 16px;
            border: 1px solid #c3c7cb;
            border-radius: 8px;
            font-family: 'Courier New', Courier, monospace;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .btn-print-receipt {
            background: #121924;
            color: #fff;
            border-color: #121924 !important;
        }

        .btn-print-receipt:hover {
            background: #EC6D2E;
            border-color: #EC6D2E !important;
        }

        .btn-close-receipt {
            background: #fff;
            color: #121924;
        }

        .btn-close-receipt:hover {
            border-color: #121924;
        }

        /* ── Receipt container ── */
        .printable-receipt {
            width: 302px;
            background: #fff;
            padding: 20px 16px;
            border: 1px solid #e0dfda;
            border-radius: 4px;
            box-shadow: 0 4px 24px rgba(18, 25, 36, 0.08);
        }

        /* ── Receipt elements ── */
        .receipt-header {
            text-align: center;
            padding-bottom: 8px;
        }

        .receipt-business-name {
            font-size: 16px;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            margin-bottom: 4px;
        }

        .receipt-address,
        .receipt-vat,
        .receipt-custom-header {
            font-size: 10px;
            color: #4f5661;
            line-height: 1.5;
        }

        .receipt-divider {
            border: none;
            border-top: 1px dashed #c3c7cb;
            margin: 10px 0;
        }

        .receipt-row {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            font-size: 11px;
            line-height: 1.6;
        }

        .receipt-items-header {
            font-weight: 700;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #4f5661;
            padding-bottom: 4px;
            border-bottom: 1px solid #e8e7e2;
            margin-bottom: 6px;
        }

        .receipt-item {
            margin-bottom: 4px;
        }

        .receipt-item-name {
            font-size: 11px;
            font-weight: 600;
            max-width: 65%;
            word-break: break-word;
        }

        .receipt-item-price {
            font-size: 11px;
            font-weight: 600;
            white-space: nowrap;
        }

        .receipt-modifier {
            display: flex;
            justify-content: space-between;
            font-size: 10px;
            color: #4f5661;
            padding-left: 12px;
            line-height: 1.5;
        }

        .receipt-total-row {
            font-size: 14px;
            font-weight: 700;
            padding-top: 4px;
        }

        .receipt-section-label {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #4f5661;
            margin-bottom: 4px;
        }

        .receipt-footer {
            text-align: center;
            padding-top: 4px;
        }

        .receipt-footer p {
            font-size: 12px;
            font-weight: 600;
        }

        .receipt-powered {
            font-size: 9px !important;
            font-weight: 400 !important;
            color: #4f5661;
            margin-top: 4px;
        }

        /* ── Print styles ── */
        @media print {
            @page {
                size: 80mm auto;
                margin: 0;
            }

            body {
                background: #fff !important;
                padding: 0 !important;
                margin: 0 !important;
                min-height: auto !important;
            }

            .screen-controls {
                display: none !important;
            }

            .printable-receipt {
                width: 80mm !important;
                border: none !important;
                border-radius: 0 !important;
                box-shadow: none !important;
                padding: 4mm 3mm !important;
            }
        }
    </style>
</head>
<body>
    <div class="screen-controls">
        <button class="btn-print-receipt" onclick="window.print()">Print Receipt</button>
        <button class="btn-close-receipt" onclick="window.close()">Close</button>
    </div>

    <x-printable-receipt :order="$order" :shop="$shop" />

    <script>
        window.addEventListener('load', function () {
            setTimeout(function () {
                window.print();
            }, 300);
        });
    </script>
</body>
</html>
