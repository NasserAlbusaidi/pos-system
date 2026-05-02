<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\ImageService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class OptimizeImages extends Command
{
    protected $signature = 'images:optimize {--dry-run : Show what would be processed without making changes}';

    protected $description = 'Generate optimized image variants for all existing product images';

    public function handle(ImageService $imageService): int
    {
        $products = Product::whereNotNull('image_url')->get();
        $processed = 0;
        $skipped = 0;
        $failed = 0;
        $disk = config('filesystems.default');

        $this->info("Found {$products->count()} products with images.");

        foreach ($products as $product) {
            // Skip already-processed images (contain -full. in the path)
            if (str_contains($product->image_url, '-full.')) {
                $skipped++;
                $this->line("  SKIP: {$product->name_en} (already optimized)");

                continue;
            }

            // Skip if source file doesn't exist on disk
            if (! Storage::disk($disk)->exists($product->image_url)) {
                $failed++;
                $this->warn("  MISS: {$product->name_en} — file not found: {$product->image_url}");

                continue;
            }

            if ($this->option('dry-run')) {
                $this->line("  WOULD PROCESS: {$product->name_en} ({$product->image_url})");
                $processed++;

                continue;
            }

            try {
                $newPath = $imageService->processUpload($product->image_url);
                $product->update(['image_url' => $newPath]);
                $processed++;
                $this->line("  OK: {$product->name_en} → {$newPath}");
            } catch (\Throwable $e) {
                $failed++;
                $this->error("  FAIL: {$product->name_en} — {$e->getMessage()}");
                report($e);
            }
        }

        $this->newLine();
        $this->info("Done. Processed: {$processed}, Skipped: {$skipped}, Failed: {$failed}");

        return self::SUCCESS;
    }
}
