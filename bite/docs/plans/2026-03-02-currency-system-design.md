# Currency System Design

## Problem

All prices are hardcoded to `$` with 2 decimal places. Oman uses OMR (3 decimal places). Shop owners need configurable currency display.

## Decision

Global `formatPrice($amount, $shop)` helper function. Per-shop currency config stored in the shops table.

## Database

Add to `shops` table:

| Column | Type | Default |
|--------|------|---------|
| `currency_code` | `string(3)` | `'OMR'` |
| `currency_symbol` | `string(10)` | `'OMR'` |
| `currency_decimals` | `tinyInteger` | `3` |

No changes to existing money columns (`decimal(10,2)`).

## Helper

File: `app/Helpers/currency.php`, autoloaded via Composer.

```php
function formatPrice(float $amount, $shop): string
{
    $decimals = $shop->currency_decimals ?? 3;
    $symbol = $shop->currency_symbol ?? 'OMR';

    return $symbol . ' ' . number_format($amount, $decimals);
}
```

Output: `OMR 1.500`, `OMR 0.250`, or `$ 1.50` depending on shop config.

## Replacements

39 occurrences across 10 files:

- `guest-menu.blade.php` (11)
- `shop-dashboard.blade.php` (6)
- `pos-dashboard.blade.php` (6)
- `invoices/order.blade.php` (4)
- `guest/order-tracker.blade.php` (4)
- `admin/reports-dashboard.blade.php` (4)
- `product-manager.blade.php` (1)
- `modifier-manager.blade.php` (1)
- `admin/menu-builder.blade.php` (1)
- `PrintNodeService.php` (1)

Pattern: `${{ number_format($x, 2) }}` becomes `{{ formatPrice($x, $shop) }}`.

## Tests

- `formatPrice` returns correct symbol and decimals for OMR defaults
- Custom shop config (USD) formats correctly
- New shops get OMR defaults from migration
- Guest menu page renders `OMR` instead of `$`
