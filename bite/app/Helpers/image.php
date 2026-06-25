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
     *   - Legacy external photos:   'https://images.pexels.com/photos/...'
     */
    function productImage(?Product $product, string $variant = 'card'): ?string
    {
        if ($product === null || $product->image_url === null) {
            return null;
        }

        $imageUrl = trim($product->image_url);

        if ($imageUrl === '') {
            return null;
        }

        $scheme = parse_url($imageUrl, PHP_URL_SCHEME);

        if ($scheme !== null) {
            return in_array(strtolower($scheme), ['http', 'https'], true) ? $imageUrl : null;
        }

        // image_url is e.g. "products/abc123-full.webp"
        // Replace "-full." with "-{$variant}."
        $variantPath = preg_replace('/-full\./', "-{$variant}.", $imageUrl);

        $disk = config('filesystems.default');

        if ($disk === 'public') {
            return '/storage/'.ltrim($variantPath, '/');
        }

        return Storage::disk($disk)->url($variantPath);
    }
}
