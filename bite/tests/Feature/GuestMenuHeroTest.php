<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use App\Support\BrandingUrl;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuestMenuHeroTest extends TestCase
{
    use RefreshDatabase;

    private function seedShop(array $branding = []): Shop
    {
        $shop = Shop::create([
            'name' => 'Sourdough',
            'slug' => 'sourdough',
            'branding' => $branding,
        ]);
        $category = Category::create(['shop_id' => $shop->id, 'name_en' => 'Bread']);
        Product::forceCreate([
            'shop_id' => $shop->id,
            'category_id' => $category->id,
            'name_en' => 'Loaf',
            'price' => 2.500,
            'is_available' => true,
            'is_visible' => true,
        ]);

        return $shop;
    }

    public function test_hero_does_not_render_table_context_copy(): void
    {
        $shop = $this->seedShop();

        $response = $this->get(route('guest.menu', $shop->slug));

        $response->assertStatus(200);
        // Pilot scope is shop-level QR + counter pickup — no per-table ordering.
        $response->assertDontSee('order from your table');
        $response->assertDontSee('من طاولتك', false);
    }

    public function test_hero_renders_dine_in_chip(): void
    {
        $shop = $this->seedShop();

        $response = $this->get(route('guest.menu', $shop->slug));

        $response->assertStatus(200);
        $response->assertSee('Dine-in');
        $response->assertSee('Open now');
    }

    public function test_malicious_branding_url_is_not_rendered_in_src(): void
    {
        $shop = $this->seedShop([
            'cover_url' => 'javascript:alert(1)',
            'logo_url' => 'data:text/html,<script>alert(1)</script>',
        ]);

        $response = $this->get(route('guest.menu', $shop->slug));

        $response->assertStatus(200);
        $response->assertDontSee('javascript:alert', false);
        $response->assertDontSee('data:text/html', false);
    }

    public function test_safe_https_branding_url_is_rendered(): void
    {
        $shop = $this->seedShop([
            'cover_url' => 'https://storage.googleapis.com/bite/cover.webp',
            'logo_url' => 'https://storage.googleapis.com/bite/logo.webp',
        ]);

        $response = $this->get(route('guest.menu', $shop->slug));

        $response->assertStatus(200);
        $response->assertSee('https://storage.googleapis.com/bite/cover.webp', false);
        $response->assertSee('https://storage.googleapis.com/bite/logo.webp', false);
    }

    /**
     * @dataProvider brandingUrlCases
     */
    public function test_branding_url_sanitizer(mixed $input, ?string $expected): void
    {
        $this->assertSame($expected, BrandingUrl::safe($input));
    }

    public static function brandingUrlCases(): array
    {
        return [
            'null' => [null, null],
            'empty' => ['', null],
            'whitespace' => ['   ', null],
            'non-string' => [['x'], null],
            'javascript scheme' => ['javascript:alert(1)', null],
            'data scheme' => ['data:text/html,<script>', null],
            'vbscript scheme' => ['vbscript:msgbox(1)', null],
            'uppercase js scheme' => ['JavaScript:alert(1)', null],
            'tab-obfuscated js scheme' => ["java\tscript:alert(1)", null],
            'newline-obfuscated js scheme' => ["java\nscript:alert(1)", null],
            'nullbyte-obfuscated js scheme' => ["java\0script:alert(1)", null],
            'leading control char' => ["\x01javascript:alert(1)", null],
            'protocol-relative url' => ['//evil.example.com/a.webp', null],
            'https url' => ['https://cdn.example.com/a.webp', 'https://cdn.example.com/a.webp'],
            'http url' => ['http://cdn.example.com/a.webp', 'http://cdn.example.com/a.webp'],
            'relative path' => ['storage/shops/1/cover.webp', 'storage/shops/1/cover.webp'],
            'root-relative path' => ['/storage/shops/1/cover.webp', '/storage/shops/1/cover.webp'],
            'trimmed' => ['  https://cdn.example.com/a.webp  ', 'https://cdn.example.com/a.webp'],
        ];
    }
}
