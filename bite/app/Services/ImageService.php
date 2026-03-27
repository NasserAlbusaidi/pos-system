<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\ImageManager;

class ImageService
{
    private static ?bool $webpSupported = null;

    /**
     * Returns true if GD has WebP support.
     * Result is cached in a static property for the process lifetime.
     */
    public function supportsWebp(): bool
    {
        if (self::$webpSupported === null) {
            $gdInfo = gd_info();
            self::$webpSupported = ! empty($gdInfo['WebP Support']);
        }

        return self::$webpSupported;
    }

    /**
     * Process an uploaded image into 3 size variants using stream-based operations.
     *
     * $storedPath is the relative path returned by $file->store()
     * e.g. "products/abc123.jpg"
     *
     * Steps:
     *  a) Read file contents via Storage::disk($disk)->get($storedPath)
     *  b) Determine output extension: 'webp' if supportsWebp(), else 'jpg'
     *  c) Derive base name without extension
     *  d) Create Intervention ImageManager with GD driver
     *  e) For each variant ['thumb' => 200, 'card' => 400, 'full' => 800]:
     *     - Read original image from binary string
     *     - Scale down (longest edge)
     *     - Encode to WebP (quality 80) or JPEG (quality 85)
     *     - Write via Storage::disk($disk)->put() — compatible with GCS and local disks
     *  f) ONLY AFTER all 3 variants are confirmed saved, delete the original
     *  g) Return the full-size variant path (new image_url)
     *
     * @throws \Throwable on encode/save failure (original is preserved)
     */
    public function processUpload(string $storedPath, string $disk = 'public'): string
    {
        $contents = Storage::disk($disk)->get($storedPath);
        $ext = $this->supportsWebp() ? 'webp' : 'jpg';

        // Derive base name without extension: e.g. "products/abc123"
        $baseName = preg_replace('/\.[^.]+$/', '', $storedPath);

        $manager = new ImageManager(new GdDriver);

        $variants = [
            'thumb' => 200,
            'card'  => 400,
            'full'  => 800,
        ];

        foreach ($variants as $variant => $size) {
            $image = $manager->read($contents);
            $image->scaleDown(width: $size, height: $size);

            $encoded = $this->supportsWebp()
                ? $image->toWebp(quality: 80)
                : $image->toJpeg(quality: 85);

            $variantPath = "{$baseName}-{$variant}.{$ext}";
            Storage::disk($disk)->put($variantPath, $encoded->toString());
        }

        // Only delete the original after ALL variants are saved
        Storage::disk($disk)->delete($storedPath);

        return "{$baseName}-full.{$ext}";
    }

    /**
     * Delete all variants for a given image_url.
     *
     * $imageUrl is the stored image_url e.g. "products/abc123-full.webp"
     * Derives baseName by removing the "-full" suffix and extension.
     * Deletes thumb, card, and full variants — no exception on missing files.
     */
    public function deleteVariants(string $imageUrl, string $disk = 'public'): void
    {
        // Match the base name before "-full.ext"
        if (! preg_match('/^(.+)-full(\.[^.]+)$/', $imageUrl, $matches)) {
            return;
        }

        $baseName = $matches[1];
        $ext = $matches[2];

        $variants = ['thumb', 'card', 'full'];
        $paths = array_map(fn ($variant) => "{$baseName}-{$variant}{$ext}", $variants);

        Storage::disk($disk)->delete($paths);
    }
}
