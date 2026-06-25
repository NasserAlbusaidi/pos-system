<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use App\Services\ImageService;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoMenuSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class DatabaseSeederReadinessTest extends TestCase
{
    use RefreshDatabase;

    private function stubDemoImagePipeline(): void
    {
        Storage::fake('public');
        Http::preventStrayRequests();
        Http::fake(['images.pexels.com/*' => Http::response('fake-image-bytes', 200)]);

        $this->mock(ImageService::class, function ($mock): void {
            $mock->shouldReceive('processUpload')
                ->andReturnUsing(fn (string $path, ?string $disk = null): string => (string) preg_replace('/\.jpg$/', '-full.webp', $path));
        });
    }

    public function test_default_seed_creates_usable_demo_and_platform_credentials(): void
    {
        $this->stubDemoImagePipeline();

        $this->seed(DatabaseSeeder::class);

        $shop = Shop::where('slug', 'demo')->firstOrFail();
        $admin = User::where('email', 'admin@bite.com')->firstOrFail();
        $superAdmin = User::where('email', 'super@bite.com')->firstOrFail();

        $this->assertTrue((bool) ($shop->branding['onboarding_completed'] ?? false));
        $this->assertTrue($shop->trial_ends_at->isFuture());
        $this->assertSame(18, Product::where('shop_id', $shop->id)->count());
        $this->assertSame(18, Product::where('shop_id', $shop->id)->whereNotNull('image_url')->count());
        $this->assertSame(0, Product::where('shop_id', $shop->id)->where('image_url', 'like', 'http%')->count());

        $this->assertSame($shop->id, $admin->shop_id);
        $this->assertSame('admin', $admin->role);
        $this->assertTrue(Hash::check('password', $admin->password));

        $this->assertSame($shop->id, $superAdmin->shop_id);
        $this->assertSame('admin', $superAdmin->role);
        $this->assertTrue($superAdmin->is_super_admin);
        $this->assertTrue(Hash::check('password', $superAdmin->password));

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk();
        $this->actingAs($superAdmin)
            ->get(route('super-admin.dashboard'))
            ->assertOk();
        $this->get(route('guest.menu', $shop))
            ->assertOk()
            ->assertSee('Bite Demo Coffee')
            ->assertSee('Karak Tea')
            ->assertSee('/storage/products/', false)
            ->assertDontSee('https://images.pexels.com/photos/', false);
    }

    public function test_demo_menu_seeder_repairs_existing_demo_photos_without_touching_other_shops(): void
    {
        $this->stubDemoImagePipeline();

        $otherShop = Shop::factory()->create([
            'name' => 'First Shop',
            'slug' => 'first-shop',
        ]);
        $otherCategory = Category::factory()->create(['shop_id' => $otherShop->id]);
        Product::factory()->create([
            'shop_id' => $otherShop->id,
            'category_id' => $otherCategory->id,
            'name_en' => 'Karak Tea',
            'image_url' => null,
        ]);

        $demoShop = Shop::factory()->create([
            'name' => 'Bite Demo Coffee',
            'slug' => 'demo',
            'trial_ends_at' => now()->addMonth(),
        ]);
        $demoCategory = Category::factory()->create([
            'shop_id' => $demoShop->id,
            'name_en' => 'Hot Drinks',
        ]);
        Product::factory()->create([
            'shop_id' => $demoShop->id,
            'category_id' => $demoCategory->id,
            'name_en' => 'Karak Tea',
            'image_url' => null,
        ]);

        $this->seed(DemoMenuSeeder::class);

        $demoProduct = Product::where('shop_id', $demoShop->id)
            ->where('name_en', 'Karak Tea')
            ->firstOrFail();

        $this->assertSame(1, Product::where('shop_id', $demoShop->id)->where('name_en', 'Karak Tea')->count());
        $this->assertStringStartsWith('products/demo-', (string) $demoProduct->image_url);
        $this->assertStringEndsWith('-full.webp', (string) $demoProduct->image_url);
        $this->assertSame(0, Product::where('shop_id', $otherShop->id)->whereNotNull('image_url')->count());
    }

    public function test_default_seed_is_blocked_in_production(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        try {
            (new DatabaseSeeder)->run();
            $this->fail('Default database seeder should not create demo credentials in production.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('default passwords', $e->getMessage());
        }

        $this->assertSame(0, Shop::where('slug', 'demo')->count());
        $this->assertSame(0, User::whereIn('email', ['admin@bite.com', 'super@bite.com'])->count());
    }
}
