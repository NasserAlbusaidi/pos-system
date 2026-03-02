<?php

namespace App\Helpers;

use App\Models\Shop;
use Illuminate\Support\Str;

class SlugGenerator
{
    /**
     * Generate a unique, URL-safe slug from the given name.
     *
     * Examples:
     *   "Nasser's Cafe"      -> "nassers-cafe"
     *   "Nasser's Cafe" (dup) -> "nassers-cafe-x7k2"
     */
    public static function fromName(string $name): string
    {
        $base = Str::slug($name);

        // Fallback if slug ends up empty (e.g. all special characters)
        if ($base === '') {
            $base = 'shop';
        }

        // Check uniqueness
        if (! Shop::where('slug', $base)->exists()) {
            return $base;
        }

        // Append random suffix until unique
        $maxAttempts = 10;

        for ($i = 0; $i < $maxAttempts; $i++) {
            $suffix = Str::lower(Str::random(4));
            $candidate = "{$base}-{$suffix}";

            if (! Shop::where('slug', $candidate)->exists()) {
                return $candidate;
            }
        }

        // Final fallback: timestamp-based
        return $base . '-' . dechex(time());
    }
}
