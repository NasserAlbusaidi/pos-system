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
     * Process an uploaded image into 3 size variants.
     *
     * $storedPath is the relative path returned by $file->store()
     * e.g. "products/abc123.jpg"
     *
     * Steps:
     *  a) Get absolute path via Storage::disk($disk)->path($storedPath)
     *  b) Determine output extension: 'webp' if supportsWebp(), else 'jpg'
     *  c) Derive base name without extension
     *  d) Create Intervention ImageManager with GD driver
     *  e) For each variant ['thumb' => 200, 'card' => 400, 'full' => 800]:
     *     - Read original image
     *     - Scale down (longest edge)
     *     - Encode to WebP (quality 80) or JPEG (quality 85)
     *     - Save to disk
     *  f) ONLY AFTER all 3 variants are confirmed saved, delete the original
     *  g) Return the full-size variant path (new image_url)
     *
     * @throws \Throwable on encode/save failure (original is preserved)
     */
    public function processUpload(string $storedPath, string $disk = 'public'): string
    {
        $absolutePath = Storage::disk($disk)->path($storedPath);
        $ext = $this->supportsWebp() ? 'webp' : 'jpg';

        // Derive base name without extension: e.g. "products/abc123"
        $baseName = preg_replace('/\.[^.]+$/', '', $storedPath);

        $manager = new ImageManager(new GdDriver);

        $variants = [
            'thumb' => 200,
            'card' => 400,
            'full' => 800,
        ];

        $savedVariants = [];

        foreach ($variants as $variant => $size) {
            $image = $manager->read($absolutePath);
            $image->scaleDown(width: $size, height: $size);

            if ($this->supportsWebp()) {
                $encoded = $image->toWebp(quality: 80);
            } else {
                $encoded = $image->toJpeg(quality: 85);
            }

            $variantPath = "{$baseName}-{$variant}.{$ext}";
            $this->saveVariant(Storage::disk($disk)->path($variantPath), $encoded->toString());
            $savedVariants[] = $variantPath;
        }

        // Only delete the original after ALL variants are confirmed saved
        Storage::disk($disk)->delete($storedPath);

        return "{$baseName}-full.{$ext}";
    }

    /**
     * Save a variant file to disk.
     * Extracted as a separate method to allow test mocking/overriding.
     */
    protected function saveVariant(string $absolutePath, string $data): void
    {
        $directory = dirname($absolutePath);
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        file_put_contents($absolutePath, $data);
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
