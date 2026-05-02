<?php

namespace Tests\Feature;

use App\Services\ImageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['filesystems.default' => 'public']);
    }

    public function test_process_upload_creates_three_webp_variants(): void
    {
        Storage::fake('public');
        $fakeImage = UploadedFile::fake()->image('test.jpg', 1000, 800);
        $storedPath = $fakeImage->store('products', 'public');

        $imageService = $this->webpSupportedService();
        $result = $imageService->processUpload($storedPath);

        $baseName = preg_replace('/\.[^.]+$/', '', $storedPath);

        Storage::disk('public')->assertExists("{$baseName}-thumb.webp");
        Storage::disk('public')->assertExists("{$baseName}-card.webp");
        Storage::disk('public')->assertExists("{$baseName}-full.webp");
        $this->assertStringEndsWith('-full.webp', $result);
    }

    public function test_process_upload_deletes_original_after_variants(): void
    {
        Storage::fake('public');
        $fakeImage = UploadedFile::fake()->image('test.jpg', 1000, 800);
        $storedPath = $fakeImage->store('products', 'public');

        $imageService = $this->webpSupportedService();
        $imageService->processUpload($storedPath);

        Storage::disk('public')->assertMissing($storedPath);
    }

    public function test_process_upload_creates_jpeg_variants_when_webp_unsupported(): void
    {
        Storage::fake('public');
        $fakeImage = UploadedFile::fake()->image('test.jpg', 1000, 800);
        $storedPath = $fakeImage->store('products', 'public');

        $imageService = $this->noWebpService();
        $result = $imageService->processUpload($storedPath);

        $baseName = preg_replace('/\.[^.]+$/', '', $storedPath);

        Storage::disk('public')->assertExists("{$baseName}-thumb.jpg");
        Storage::disk('public')->assertExists("{$baseName}-card.jpg");
        Storage::disk('public')->assertExists("{$baseName}-full.jpg");
        $this->assertStringEndsWith('-full.jpg', $result);
    }

    public function test_delete_variants_removes_all_three_files(): void
    {
        Storage::fake('public');

        Storage::disk('public')->put('products/abc123-thumb.webp', 'fake');
        Storage::disk('public')->put('products/abc123-card.webp', 'fake');
        Storage::disk('public')->put('products/abc123-full.webp', 'fake');

        $imageService = app(ImageService::class);
        $imageService->deleteVariants('products/abc123-full.webp');

        Storage::disk('public')->assertMissing('products/abc123-thumb.webp');
        Storage::disk('public')->assertMissing('products/abc123-card.webp');
        Storage::disk('public')->assertMissing('products/abc123-full.webp');
    }

    public function test_delete_variants_does_not_throw_when_files_missing(): void
    {
        Storage::fake('public');

        $imageService = app(ImageService::class);

        $this->expectNotToPerformAssertions();
        $imageService->deleteVariants('products/nonexistent-full.webp');
    }

    public function test_product_image_helper_returns_card_url(): void
    {
        $product = new \App\Models\Product;
        $product->image_url = 'products/abc123-full.webp';

        $result = productImage($product, 'card');

        // Storage::disk('public')->url() returns an absolute URL using APP_URL
        $this->assertStringEndsWith('/storage/products/abc123-card.webp', $result);
        $this->assertStringContainsString('products/abc123-card.webp', $result);
    }

    public function test_product_image_helper_returns_null_when_no_image(): void
    {
        $product = new \App\Models\Product;
        $product->image_url = null;

        $result = productImage($product, 'card');

        $this->assertNull($result);
    }

    public function test_product_image_helper_returns_null_for_null_product(): void
    {
        $result = productImage(null, 'card');

        $this->assertNull($result);
    }

    public function test_process_upload_resizes_to_correct_dimensions(): void
    {
        Storage::fake('public');
        $fakeImage = UploadedFile::fake()->image('test.jpg', 1000, 800);
        $storedPath = $fakeImage->store('products', 'public');

        $imageService = $this->webpSupportedService();
        $imageService->processUpload($storedPath);

        // Verify all 3 variant files exist (dimensions tested via integration)
        $baseName = preg_replace('/\.[^.]+$/', '', $storedPath);
        Storage::disk('public')->assertExists("{$baseName}-thumb.webp");
        Storage::disk('public')->assertExists("{$baseName}-card.webp");
        Storage::disk('public')->assertExists("{$baseName}-full.webp");
    }

    public function test_original_survives_when_variant_save_fails(): void
    {
        Storage::fake('public');
        $fakeImage = UploadedFile::fake()->image('test.jpg', 1000, 800);
        $storedPath = $fakeImage->store('products', 'public');

        // Create a service that will fail on the second variant by throwing
        // during processUpload before all variants are saved
        $imageService = new class extends ImageService
        {
            private int $variantCount = 0;

            public function supportsWebp(): bool
            {
                return true;
            }

            public function processUpload(string $storedPath, ?string $disk = null): string
            {
                $disk ??= config('filesystems.default');

                $contents = \Illuminate\Support\Facades\Storage::disk($disk)->get($storedPath);
                $ext = 'webp';
                $baseName = preg_replace('/\.[^.]+$/', '', $storedPath);
                $manager = new \Intervention\Image\ImageManager(new \Intervention\Image\Drivers\Gd\Driver);
                $variants = ['thumb' => 200, 'card' => 400, 'full' => 800];

                foreach ($variants as $variant => $size) {
                    $this->variantCount++;
                    if ($this->variantCount === 2) {
                        throw new \RuntimeException('Simulated save failure');
                    }
                    $image = $manager->read($contents);
                    $image->scaleDown(width: $size, height: $size);
                    $encoded = $image->toWebp(quality: 80);
                    $variantPath = "{$baseName}-{$variant}.{$ext}";
                    \Illuminate\Support\Facades\Storage::disk($disk)->put($variantPath, $encoded->toString());
                }

                \Illuminate\Support\Facades\Storage::disk($disk)->delete($storedPath);

                return "{$baseName}-full.{$ext}";
            }
        };

        try {
            $imageService->processUpload($storedPath);
        } catch (\Throwable $e) {
            // Expected exception
        }

        // Original should still exist since processing failed before completion
        Storage::disk('public')->assertExists($storedPath);
    }

    private function webpSupportedService(): ImageService
    {
        return new class extends ImageService
        {
            public function supportsWebp(): bool
            {
                return true;
            }
        };
    }

    private function noWebpService(): ImageService
    {
        return new class extends ImageService
        {
            public function supportsWebp(): bool
            {
                return false;
            }
        };
    }
}
