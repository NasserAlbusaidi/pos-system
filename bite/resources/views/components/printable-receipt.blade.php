@props(['order', 'shop'])

@php
    $branding = $shop->branding ?? [];
    $receiptHeader = $branding['receipt_header'] ?? '';
    $businessName = $branding['business_name'] ?? $shop->name;
    $businessAddress = $branding['address'] ?? '';
    $vatNumber = $branding['vat_number'] ?? '';
@endphp

<div class="printable-receipt" id="receipt-content">
    {{-- Header --}}
    <div class="receipt-header">
        <h1 class="receipt-business-name">{{ $businessName }}</h1>
        @if($businessAddress)
            <p class="receipt-address">{{ $businessAddress }}</p>
        @endif
        @if($vatNumber)
            <p class="receipt-vat">VAT: {{ $vatNumber }}</p>
        @endif
        @if($receiptHeader)
            <p class="receipt-custom-header">{{ $receiptHeader }}</p>
        @endif
    </div>

    <div class="receipt-divider"></div>

    {{-- Order Info --}}
    <div class="receipt-order-info">
        <div class="receipt-row">
            <span>Order #{{ $order->id }}</span>
            <span>{{ $order->created_at->format('d/m/Y') }}</span>
        </div>
        <div class="receipt-row">
            <span>{{ $order->customer_name ?: 'Walk-in' }}</span>
            <span>{{ $order->created_at->format('H:i') }}</span>
        </div>
    </div>

    <div class="receipt-divider"></div>

    {{-- Items --}}
    <div class="receipt-items">
        <div class="receipt-row receipt-items-header">
            <span>Item</span>
            <span>Total</span>
        </div>

        @foreach($order->items as $item)
            <div class="receipt-item">
                <div class="receipt-row">
                    <span class="receipt-item-name">{{ $item->quantity }}x {{ $item->product_name_snapshot_en }}</span>
                    <span class="receipt-item-price">{{ formatPrice($item->price_snapshot * $item->quantity, $shop) }}</span>
                </div>
                @if($item->modifiers->isNotEmpty())
                    @foreach($item->modifiers as $modifier)
                        <div class="receipt-modifier">
                            <span>+ {{ $modifier->modifier_option_name_snapshot_en }}</span>
                            @if($modifier->price_adjustment_snapshot > 0)
                                <span>{{ formatPrice($modifier->price_adjustment_snapshot, $shop) }}</span>
                            @endif
                        </div>
                    @endforeach
                @endif
            </div>
        @endforeach
    </div>

    <div class="receipt-divider"></div>

    {{-- Totals --}}
    <div class="receipt-totals">
        @if($order->subtotal_amount && $order->subtotal_amount != $order->total_amount)
            <div class="receipt-row">
                <span>Subtotal</span>
                <span>{{ formatPrice($order->subtotal_amount, $shop) }}</span>
            </div>
        @endif
        @if($order->tax_amount > 0)
            <div class="receipt-row">
                <span>Tax</span>
                <span>{{ formatPrice($order->tax_amount, $shop) }}</span>
            </div>
        @endif
        <div class="receipt-row receipt-total-row">
            <span>TOTAL</span>
            <span>{{ formatPrice($order->total_amount, $shop) }}</span>
        </div>
    </div>

    <div class="receipt-divider"></div>

    {{-- Payments --}}
    @if($order->payments->isNotEmpty())
        <div class="receipt-payments">
            <p class="receipt-section-label">Payment</p>
            @foreach($order->payments as $payment)
                <div class="receipt-row">
                    <span>{{ ucfirst($payment->method) }}</span>
                    <span>{{ formatPrice($payment->amount, $shop) }}</span>
                </div>
            @endforeach
        </div>

        <div class="receipt-divider"></div>
    @endif

    {{-- Footer --}}
    <div class="receipt-footer">
        <p>Thank you!</p>
        <p class="receipt-powered">Powered by Bite</p>
    </div>
</div>
