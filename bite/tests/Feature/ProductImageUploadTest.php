<?php

namespace Tests\Feature;

use App\Livewire\ProductManager;
use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use App\Services\ImageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ProductImageUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_upload_writes_variants_to_default_disk(): void
    {
        $disk = $this->fakeDefaultDisk('gcs');
        [$user, $category] = $this->makeCatalogUser();

        Livewire::actingAs($user)
            ->test(ProductManager::class)
            ->set('name_en', 'Flat White')
            ->set('price', 1.500)
            ->set('category_id', $category->id)
            ->set('image', UploadedFile::fake()->image('flat-white.jpg', 900, 700))
            ->call('save');

        $product = Product::where('name_en', 'Flat White')->firstOrFail();
        $baseName = preg_replace('/-full\.[^.]+$/', '', $product->image_url);
        $extension = pathinfo($product->image_url, PATHINFO_EXTENSION);

        Storage::disk($disk)->assertExists("{$baseName}-thumb.{$extension}");
        Storage::disk($disk)->assertExists("{$baseName}-card.{$extension}");
        Storage::disk($disk)->assertExists("{$baseName}-full.{$extension}");
    }

    public function test_delete_removes_variants_from_default_disk(): void
    {
        $disk = $this->fakeDefaultDisk('gcs');

        Storage::disk($disk)->put('products/coffee-thumb.webp', 'fake');
        Storage::disk($disk)->put('products/coffee-card.webp', 'fake');
        Storage::disk($disk)->put('products/coffee-full.webp', 'fake');

        app(ImageService::class)->deleteVariants('products/coffee-full.webp');

        Storage::disk($disk)->assertMissing('products/coffee-thumb.webp');
        Storage::disk($disk)->assertMissing('products/coffee-card.webp');
        Storage::disk($disk)->assertMissing('products/coffee-full.webp');
    }

    public function test_url_helper_resolves_uploaded_image(): void
    {
        $this->fakeDefaultDisk('gcs');

        $product = new Product;
        $product->image_url = 'products/latte-full.webp';

        $url = productImage($product, 'card');

        $this->assertNotNull($url);
        $this->assertStringContainsString('products/latte-card.webp', $url);
    }

    private function fakeDefaultDisk(string $disk): string
    {
        config(['filesystems.default' => $disk]);
        Storage::fake(config('filesystems.default'));

        return config('filesystems.default');
    }

    private function makeCatalogUser(): array
    {
        $shop = Shop::factory()->create();
        $user = User::factory()->create([
            'shop_id' => $shop->id,
            'role' => 'admin',
        ]);
        $category = Category::factory()->create(['shop_id' => $shop->id]);

        return [$user, $category];
    }
}
