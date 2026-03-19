<?php

namespace Tests\Unit;

use App\Models\Shop;
use PHPUnit\Framework\TestCase;

class CurrencyHelperTest extends TestCase
{
    public function test_format_price_with_omr_defaults(): void
    {
        $shop = new Shop;
        $shop->currency_symbol = 'OMR';
        $shop->currency_decimals = 3;

        $this->assertSame('OMR 1.500', formatPrice(1.5, $shop));
        $this->assertSame('OMR 0.250', formatPrice(0.25, $shop));
        $this->assertSame('OMR 0.000', formatPrice(0, $shop));
        $this->assertSame('OMR 1,234.500', formatPrice(1234.5, $shop));
    }

    public function test_format_price_with_usd_config(): void
    {
        $shop = new Shop;
        $shop->currency_symbol = '$';
        $shop->currency_decimals = 2;

        $this->assertSame('$ 1.50', formatPrice(1.5, $shop));
        $this->assertSame('$ 0.25', formatPrice(0.25, $shop));
    }

    public function test_format_price_with_arabic_symbol(): void
    {
        $shop = new Shop;
        $shop->currency_symbol = 'ر.ع.';
        $shop->currency_decimals = 3;

        $this->assertSame('ر.ع. 1.500', formatPrice(1.5, $shop));
    }

    public function test_format_price_fallback_when_null(): void
    {
        $shop = new Shop;
        $shop->currency_symbol = null;
        $shop->currency_decimals = null;

        $this->assertSame('OMR 1.500', formatPrice(1.5, $shop));
    }
}
