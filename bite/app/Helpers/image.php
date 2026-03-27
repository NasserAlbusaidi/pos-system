<?php

use App\Models\Product;
use Illuminate\Support\Facades\Storage;

if (! function_exists('productImage')) {
    /**
     * Resolve the public URL for a product image variant.
     *
     * Returns null if the product is null or has no image_url.
     * Valid variants: 'thumb' (200px), 'card' (400px), 'full' (800px).
     *
     * Returns disk-appropriate URLs:
     *   - Local dev (public disk): '/storage/products/abc123-card.webp'
     *   - Production (gcs disk):   'https://storage.googleapis.com/BUCKET/products/abc123-card.webp'
     */
    function productImage(?Product $product, string $variant = 'card'): ?string
    {
        if ($product === null || $product->image_url === null) {
            return null;
        }

        // image_url is e.g. "products/abc123-full.webp"
        // Replace "-full." with "-{$variant}."
        $variantPath = preg_replace('/-full\./', "-{$variant}.", $product->image_url);

        return Storage::disk(config('filesystems.default'))->url($variantPath);
    }
}
