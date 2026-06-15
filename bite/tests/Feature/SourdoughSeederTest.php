<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use App\Services\BillingService;
use App\Services\ImageService;
use Database\Seeders\SourdoughMenuSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Guards the Sourdough pilot seed (#30): the seeder must produce a pilot-ready
 * shop — full bilingual menu, non-expiring, and on a billing posture that lets
 * the 33-item menu clear the Free plan's 20-product cap (#19 billing bypass).
 *
 * Image processing and the Pexels download are stubbed so the test needs no
 * network and no GD/WebP support.
 */
class SourdoughSeederTest extends TestCase
{
    use RefreshDatabase;

    private function stubImagePipeline(): void
    {
        Storage::fake('public');
        Http::fake(['images.pexels.com/*' => Http::response('fake-image-bytes', 200)]);
        $this->mock(ImageService::class, function ($mock) {
            $mock->shouldReceive('processUpload')->andReturn('products/seed-fake-full.webp');
        });
    }

    public function test_seeder_creates_pilot_ready_sourdough_shop(): void
    {
        $this->stubImagePipeline();

        $this->seed(SourdoughMenuSeeder::class);

        $shop = Shop::where('slug', 'sourdough')->first();
        $this->assertNotNull($shop, 'Seeder should create the sourdough shop');

        // Full bilingual menu: 33 items across 6 categories.
        $this->assertSame(6, Category::where('shop_id', $shop->id)->count());
        $this->assertSame(33, Product::where('shop_id', $shop->id)->count());

        // Every product carries both locales.
        $missingArabic = Product::where('shop_id', $shop->id)
            ->where(fn ($q) => $q->whereNull('name_ar')->orWhere('name_ar', ''))
            ->count();
        $this->assertSame(0, $missingArabic, 'Every seeded product must be bilingual');

        // Non-expiring shop + billing bypass (#19): the long generic trial makes
        // BillingService treat the shop as Pro, so 33 products clear the Free
        // plan's 20-product cap and CheckSubscription never walls the admin.
        $this->assertTrue($shop->trial_ends_at->isFuture());
        $this->assertTrue($shop->trial_ends_at->gt(now()->addYears(5)));

        $billing = app(BillingService::class);
        $this->assertTrue($shop->onGenericTrial());
        $this->assertSame('pro', $billing->getCurrentPlan($shop));
        $this->assertTrue(
            $billing->canAccess($shop, 'add_product'),
            'Pilot shop must keep full product access despite exceeding the Free cap'
        );
    }

    public function test_seeder_is_idempotent(): void
    {
        $this->stubImagePipeline();

        $this->seed(SourdoughMenuSeeder::class);
        // A second run must not duplicate the shop or its products.
        $this->seed(SourdoughMenuSeeder::class);

        $this->assertSame(1, Shop::where('slug', 'sourdough')->count());
        $shop = Shop::where('slug', 'sourdough')->first();
        $this->assertSame(33, Product::where('shop_id', $shop->id)->count());
    }
}
