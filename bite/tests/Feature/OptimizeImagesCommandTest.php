<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use App\Services\ImageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OptimizeImagesCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['filesystems.default' => 'public']);
    }

    /**
     * Test 1: Command processes a product with an unoptimized image.
     * After running, the product's image_url is updated to the -full.webp path.
     */
    public function test_command_processes_product_with_image(): void
    {
        Storage::fake('public');

        $shop = Shop::factory()->create();
        $category = Category::factory()->create(['shop_id' => $shop->id]);

        $fakeImage = UploadedFile::fake()->image('burger.jpg', 400, 400);
        $storedPath = $fakeImage->storeAs('products', 'burger.jpg', 'public');

        $product = Product::forceCreate([
            'shop_id' => $shop->id,
            'category_id' => $category->id,
            'name_en' => 'Burger',
            'name_ar' => 'برغر',
            'price' => 2.500,
            'image_url' => $storedPath,
        ]);

        // Bind a real ImageService (WebP may not be supported in CI — that's fine)
        $this->app->instance(ImageService::class, new ImageService);

        $this->artisan('images:optimize')
            ->assertExitCode(0);

        $product->refresh();
        $this->assertStringContainsString('-full.', $product->image_url);
    }

    /**
     * Test 2: Command skips products where image_url is null.
     */
    public function test_command_skips_null_image_products(): void
    {
        Storage::fake('public');

        $shop = Shop::factory()->create();
        $category = Category::factory()->create(['shop_id' => $shop->id]);

        $product = Product::forceCreate([
            'shop_id' => $shop->id,
            'category_id' => $category->id,
            'name_en' => 'No Image Product',
            'name_ar' => 'منتج بلا صورة',
            'price' => 1.000,
            'image_url' => null,
        ]);

        $this->artisan('images:optimize')
            ->assertExitCode(0);

        $product->refresh();
        $this->assertNull($product->image_url);
    }

    /**
     * Test 3: Command skips products where image_url already contains "-full."
     * (already processed through the pipeline).
     */
    public function test_command_skips_already_processed_images(): void
    {
        Storage::fake('public');

        $shop = Shop::factory()->create();
        $category = Category::factory()->create(['shop_id' => $shop->id]);

        $product = Product::forceCreate([
            'shop_id' => $shop->id,
            'category_id' => $category->id,
            'name_en' => 'Already Optimized',
            'name_ar' => 'محسّن مسبقاً',
            'price' => 1.500,
            'image_url' => 'products/abc123-full.webp',
        ]);

        $originalUrl = $product->image_url;

        $this->artisan('images:optimize')
            ->expectsOutputToContain('SKIP')
            ->assertExitCode(0);

        $product->refresh();
        $this->assertSame($originalUrl, $product->image_url, 'Already-processed image_url should not change');
    }

    /**
     * Test 4: Command reports count of processed, skipped, and failed products.
     */
    public function test_command_reports_counts(): void
    {
        Storage::fake('public');

        $shop = Shop::factory()->create();
        $category = Category::factory()->create(['shop_id' => $shop->id]);

        // Skipped: already optimized
        Product::forceCreate([
            'shop_id' => $shop->id,
            'category_id' => $category->id,
            'name_en' => 'Already Done',
            'name_ar' => 'منجز',
            'price' => 1.000,
            'image_url' => 'products/done-full.webp',
        ]);

        // Failed: file missing from disk
        Product::forceCreate([
            'shop_id' => $shop->id,
            'category_id' => $category->id,
            'name_en' => 'Ghost Product',
            'name_ar' => 'منتج وهمي',
            'price' => 1.000,
            'image_url' => 'products/ghost.jpg',
        ]);

        $this->artisan('images:optimize')
            ->expectsOutputToContain('Done.')
            ->assertExitCode(0);
    }

    /**
     * Test 5: Command handles missing source files gracefully.
     * A product with a db image_url that doesn't exist on disk
     * should log a warning and continue without crashing.
     */
    public function test_command_handles_missing_source_files_gracefully(): void
    {
        Storage::fake('public');

        $shop = Shop::factory()->create();
        $category = Category::factory()->create(['shop_id' => $shop->id]);

        $product = Product::forceCreate([
            'shop_id' => $shop->id,
            'category_id' => $category->id,
            'name_en' => 'Ghost Product',
            'name_ar' => 'منتج وهمي',
            'price' => 2.000,
            'image_url' => 'products/ghost.jpg',
            // NOTE: file NOT created on disk
        ]);

        $originalUrl = $product->image_url;

        $this->artisan('images:optimize')
            ->expectsOutputToContain('MISS')
            ->assertExitCode(0);

        // image_url should remain unchanged (skipped, not updated)
        $product->refresh();
        $this->assertSame($originalUrl, $product->image_url);
    }
}
