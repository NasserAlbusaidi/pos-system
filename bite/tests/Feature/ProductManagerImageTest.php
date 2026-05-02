<?php

namespace Tests\Feature;

use App\Livewire\ProductManager;
use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ProductManagerImageTest extends TestCase
{
    use RefreshDatabase;

    private Shop $shop;

    private User $user;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        config(['filesystems.default' => 'public']);

        $this->shop = Shop::factory()->create();
        $this->user = User::factory()->create(['shop_id' => $this->shop->id]);
        $this->category = Category::factory()->create(['shop_id' => $this->shop->id]);
    }

    public function test_saving_product_with_jpeg_image_creates_webp_variants(): void
    {
        Storage::fake('public');

        $image = UploadedFile::fake()->image('product.jpg', 800, 600);

        Livewire::actingAs($this->user)
            ->test(ProductManager::class)
            ->set('name_en', 'Croissant')
            ->set('price', 1.500)
            ->set('category_id', $this->category->id)
            ->set('image', $image)
            ->call('save');

        $product = Product::where('name_en', 'Croissant')->first();
        $this->assertNotNull($product);
        $this->assertStringEndsWith('-full.webp', $product->image_url);
    }

    public function test_saving_product_with_png_image_creates_webp_variants(): void
    {
        Storage::fake('public');

        $image = UploadedFile::fake()->image('product.png', 800, 600);

        Livewire::actingAs($this->user)
            ->test(ProductManager::class)
            ->set('name_en', 'Sourdough Loaf')
            ->set('price', 2.500)
            ->set('category_id', $this->category->id)
            ->set('image', $image)
            ->call('save');

        $product = Product::where('name_en', 'Sourdough Loaf')->first();
        $this->assertNotNull($product);
        $this->assertStringEndsWith('-full.webp', $product->image_url);
    }

    public function test_editing_product_with_new_image_deletes_old_variants(): void
    {
        Storage::fake('public');

        // Create old variant files on disk
        Storage::disk('public')->put('products/old-thumb.webp', 'fake');
        Storage::disk('public')->put('products/old-card.webp', 'fake');
        Storage::disk('public')->put('products/old-full.webp', 'fake');

        $product = Product::forceCreate([
            'shop_id' => $this->shop->id,
            'category_id' => $this->category->id,
            'name_en' => 'Old Product',
            'price' => 1.000,
            'image_url' => 'products/old-full.webp',
        ]);

        $newImage = UploadedFile::fake()->image('new.jpg', 800, 600);

        Livewire::actingAs($this->user)
            ->test(ProductManager::class)
            ->call('editProduct', $product->id)
            ->set('image', $newImage)
            ->call('save');

        // Old variants should be deleted
        Storage::disk('public')->assertMissing('products/old-thumb.webp');
        Storage::disk('public')->assertMissing('products/old-card.webp');
        Storage::disk('public')->assertMissing('products/old-full.webp');
    }

    public function test_validation_rejects_files_over_5mb(): void
    {
        Storage::fake('public');

        // Create a fake file larger than 5MB (5121KB)
        $oversizedImage = UploadedFile::fake()->image('large.jpg')->size(5121);

        Livewire::actingAs($this->user)
            ->test(ProductManager::class)
            ->set('name_en', 'Test Product')
            ->set('price', 1.000)
            ->set('category_id', $this->category->id)
            ->set('image', $oversizedImage)
            ->call('save')
            ->assertHasErrors(['image']);
    }

    public function test_validation_rejects_gif_files(): void
    {
        Storage::fake('public');

        $gifImage = UploadedFile::fake()->image('animated.gif')->size(100);

        Livewire::actingAs($this->user)
            ->test(ProductManager::class)
            ->set('name_en', 'Test Product')
            ->set('price', 1.000)
            ->set('category_id', $this->category->id)
            ->set('image', $gifImage)
            ->call('save')
            ->assertHasErrors(['image']);
    }
}
