<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class HandoffReadinessCheckCommandTest extends TestCase
{
    use RefreshDatabase;

    private string $logDirectory;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->logDirectory = storage_path('framework/testing/handoff-readiness-'.Str::uuid());
        File::ensureDirectoryExists($this->logDirectory);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->logDirectory);

        parent::tearDown();
    }

    public function test_handoff_check_passes_for_complete_restaurant_bundle_when_only_local_production_gate_is_skipped(): void
    {
        $shop = $this->createReadyShop();
        $logPath = $this->writeLog('[2026-06-25 12:00:00] local.INFO: Handoff drill clean.');
        $this->fakeHealthyHttpFor($shop);

        $exitCode = Artisan::call('bite:handoff-check', [
            'shop' => $shop->slug,
            '--skip-production-check' => true,
            '--log-path' => [$logPath],
            '--json' => true,
        ]);
        $payload = json_decode(Artisan::output(), true);

        $this->assertSame(0, $exitCode, 'Failed checks: '.implode(', ', $this->failedCheckNames($payload)));
        $this->assertTrue($payload['ok']);
        $this->assertEmpty($this->failedCheckNames($payload));
        $this->assertContains('Shop exists', $this->passedCheckNames($payload));
        $this->assertContains('Reports feature is available', $this->passedCheckNames($payload));
        $this->assertContains('Owner/admin password login is provisioned', $this->passedCheckNames($payload));
        $this->assertContains('POS PIN user exists', $this->passedCheckNames($payload));
        $this->assertContains('KDS PIN user exists', $this->passedCheckNames($payload));
        $this->assertContains('Orderable product exists', $this->passedCheckNames($payload));
        $this->assertContains('Orderable products have menu photos', $this->passedCheckNames($payload));
        $this->assertContains('Orderable menu photos are locally hosted', $this->passedCheckNames($payload));
        $this->assertContains('Orderable menu photo files exist', $this->passedCheckNames($payload));
        $this->assertContains('Orderable menu photo variants exist', $this->passedCheckNames($payload));
        $this->assertContains('WhatsApp alerts are disabled or usable', $this->passedCheckNames($payload));
        $this->assertContains('Reports dashboard route exists', $this->passedCheckNames($payload));
        $this->assertContains('Reports export route exists', $this->passedCheckNames($payload));
        $this->assertContains('Shift report route exists', $this->passedCheckNames($payload));
        $this->assertContains('Cash reconciliation route exists', $this->passedCheckNames($payload));
        $this->assertContains('Billing route exists', $this->passedCheckNames($payload));
        $this->assertContains('Shop settings route exists', $this->passedCheckNames($payload));
        $this->assertContains('Product manager route exists', $this->passedCheckNames($payload));
        $this->assertContains('Live health endpoint returns 200', $this->passedCheckNames($payload));
        $this->assertContains('Live guest menu returns 200', $this->passedCheckNames($payload));
        $this->assertContains('Live guest menu product images return images', $this->passedCheckNames($payload));
        $this->assertContains('Live guest QR returns SVG for guest menu', $this->passedCheckNames($payload));
        $this->assertContains('Live PIN screen returns 200', $this->passedCheckNames($payload));
        $this->assertContains('Owner dashboard loads', $this->passedCheckNames($payload));
        $this->assertContains('Owner POS loads', $this->passedCheckNames($payload));
        $this->assertContains('Owner product manager loads', $this->passedCheckNames($payload));
        $this->assertContains('Owner shop settings load', $this->passedCheckNames($payload));
        $this->assertContains('Owner reports dashboard loads', $this->passedCheckNames($payload));
        $this->assertContains('Owner reports export loads', $this->passedCheckNames($payload));
        $this->assertContains('Owner shift report loads', $this->passedCheckNames($payload));
        $this->assertContains('Owner cash reconciliation loads', $this->passedCheckNames($payload));
        $this->assertContains('Owner billing settings load', $this->passedCheckNames($payload));
        $this->assertContains('Post-migration schema gate', $this->passedCheckNames($payload));
        $this->assertContains('Recent application log gate', $this->passedCheckNames($payload));
    }

    public function test_handoff_check_fails_when_restaurant_setup_is_incomplete(): void
    {
        $shop = Shop::factory()->create([
            'slug' => 'empty-shop',
            'branding' => ['onboarding_completed' => false],
            'status' => 'active',
        ]);
        User::factory()->create([
            'shop_id' => $shop->id,
            'role' => 'admin',
            'pin_code' => null,
        ]);
        $logPath = $this->writeLog('[2026-06-25 12:00:00] local.INFO: Handoff drill clean.');
        $this->fakeHealthyHttpFor($shop);

        $exitCode = Artisan::call('bite:handoff-check', [
            'shop' => $shop->slug,
            '--skip-production-check' => true,
            '--log-path' => [$logPath],
            '--json' => true,
        ]);
        $payload = json_decode(Artisan::output(), true);

        $this->assertSame(1, $exitCode);
        $this->assertFalse($payload['ok']);
        $this->assertContains('Onboarding is complete', $this->failedCheckNames($payload));
        $this->assertContains('POS PIN user exists', $this->failedCheckNames($payload));
        $this->assertContains('KDS PIN user exists', $this->failedCheckNames($payload));
        $this->assertContains('Active category exists', $this->failedCheckNames($payload));
        $this->assertContains('Orderable product exists', $this->failedCheckNames($payload));
    }

    public function test_handoff_check_fails_when_reports_feature_is_unavailable(): void
    {
        $shop = $this->createReadyShop([
            'slug' => 'free-plan-restaurant',
            'trial_ends_at' => null,
        ]);
        $logPath = $this->writeLog('[2026-06-25 12:00:00] local.INFO: Handoff drill clean.');
        $this->fakeHealthyHttpFor($shop);

        $exitCode = Artisan::call('bite:handoff-check', [
            'shop' => $shop->slug,
            '--skip-production-check' => true,
            '--log-path' => [$logPath],
            '--json' => true,
        ]);
        $payload = json_decode(Artisan::output(), true);

        $this->assertSame(1, $exitCode);
        $this->assertFalse($payload['ok']);
        $this->assertContains('Reports feature is available', $this->failedCheckNames($payload));
        $this->assertContains('Owner reports dashboard loads', $this->failedCheckNames($payload));
        $this->assertContains('Owner reports export loads', $this->failedCheckNames($payload));
        $this->assertContains('Shop has active billing access', $this->passedCheckNames($payload));
    }

    public function test_handoff_check_fails_when_admin_uses_default_password_hash(): void
    {
        $shop = $this->createReadyShop(['slug' => 'default-password-restaurant']);
        User::where('shop_id', $shop->id)
            ->where('role', 'admin')
            ->update(['password' => Hash::make('password')]);
        $logPath = $this->writeLog('[2026-06-25 12:00:00] local.INFO: Handoff drill clean.');
        $this->fakeHealthyHttpFor($shop);

        $exitCode = Artisan::call('bite:handoff-check', [
            'shop' => $shop->slug,
            '--skip-production-check' => true,
            '--log-path' => [$logPath],
            '--json' => true,
        ]);
        $payload = json_decode(Artisan::output(), true);

        $this->assertSame(1, $exitCode);
        $this->assertContains('Owner/admin user exists', $this->passedCheckNames($payload));
        $this->assertContains('Owner/admin password login is provisioned', $this->failedCheckNames($payload));
        $this->assertContains('Owner dashboard loads', $this->failedCheckNames($payload));
        $this->assertContains('Owner reports dashboard loads', $this->failedCheckNames($payload));
    }

    public function test_handoff_check_fails_when_staff_pin_is_not_hashed(): void
    {
        $shop = $this->createReadyShop(['slug' => 'raw-pin-restaurant']);
        User::where('shop_id', $shop->id)->update(['pin_code' => '2468']);
        $logPath = $this->writeLog('[2026-06-25 12:00:00] local.INFO: Handoff drill clean.');
        $this->fakeHealthyHttpFor($shop);

        $exitCode = Artisan::call('bite:handoff-check', [
            'shop' => $shop->slug,
            '--skip-production-check' => true,
            '--log-path' => [$logPath],
            '--json' => true,
        ]);
        $payload = json_decode(Artisan::output(), true);

        $this->assertSame(1, $exitCode);
        $this->assertContains('POS PIN user exists', $this->failedCheckNames($payload));
        $this->assertContains('KDS PIN user exists', $this->failedCheckNames($payload));
    }

    public function test_handoff_check_fails_when_orderable_product_has_no_menu_photo(): void
    {
        $shop = $this->createReadyShop(['slug' => 'missing-photo-restaurant']);
        Product::where('shop_id', $shop->id)->update(['image_url' => null]);
        $logPath = $this->writeLog('[2026-06-25 12:00:00] local.INFO: Handoff drill clean.');
        $this->fakeHealthyHttpFor($shop);

        $exitCode = Artisan::call('bite:handoff-check', [
            'shop' => $shop->slug,
            '--skip-production-check' => true,
            '--log-path' => [$logPath],
            '--json' => true,
        ]);
        $payload = json_decode(Artisan::output(), true);

        $this->assertSame(1, $exitCode);
        $this->assertContains('Orderable product exists', $this->passedCheckNames($payload));
        $this->assertContains('Orderable products have menu photos', $this->failedCheckNames($payload));
    }

    public function test_handoff_check_fails_when_orderable_product_photo_is_remote(): void
    {
        $shop = $this->createReadyShop(['slug' => 'remote-photo-restaurant']);
        Product::where('shop_id', $shop->id)->update([
            'image_url' => 'https://images.pexels.com/photos/675951/pexels-photo-675951.jpeg?auto=compress&cs=tinysrgb&w=800',
        ]);
        $logPath = $this->writeLog('[2026-06-25 12:00:00] local.INFO: Handoff drill clean.');
        $this->fakeHealthyHttpFor($shop);

        $exitCode = Artisan::call('bite:handoff-check', [
            'shop' => $shop->slug,
            '--skip-production-check' => true,
            '--log-path' => [$logPath],
            '--json' => true,
        ]);
        $payload = json_decode(Artisan::output(), true);

        $this->assertSame(1, $exitCode);
        $this->assertContains('Orderable products have menu photos', $this->passedCheckNames($payload));
        $this->assertContains('Orderable menu photos are locally hosted', $this->failedCheckNames($payload));
        $this->assertContains('Orderable menu photo files exist', $this->failedCheckNames($payload));
    }

    public function test_handoff_check_fails_when_orderable_product_card_photo_file_is_missing(): void
    {
        $shop = $this->createReadyShop(['slug' => 'missing-card-photo-restaurant']);
        Storage::disk('public')->delete('products/handoff-ready-card.webp');
        $logPath = $this->writeLog('[2026-06-25 12:00:00] local.INFO: Handoff drill clean.');
        $this->fakeHealthyHttpFor($shop);

        $exitCode = Artisan::call('bite:handoff-check', [
            'shop' => $shop->slug,
            '--skip-production-check' => true,
            '--log-path' => [$logPath],
            '--json' => true,
        ]);
        $payload = json_decode(Artisan::output(), true);

        $this->assertSame(1, $exitCode);
        $this->assertContains('Orderable products have menu photos', $this->passedCheckNames($payload));
        $this->assertContains('Orderable menu photos are locally hosted', $this->passedCheckNames($payload));
        $this->assertContains('Orderable menu photo files exist', $this->failedCheckNames($payload));
        $this->assertContains('Orderable menu photo variants exist', $this->failedCheckNames($payload));
    }

    public function test_handoff_check_fails_when_orderable_product_thumb_photo_file_is_missing(): void
    {
        $shop = $this->createReadyShop(['slug' => 'missing-thumb-photo-restaurant']);
        Storage::disk('public')->delete('products/handoff-ready-thumb.webp');
        $logPath = $this->writeLog('[2026-06-25 12:00:00] local.INFO: Handoff drill clean.');
        $this->fakeHealthyHttpFor($shop);

        $exitCode = Artisan::call('bite:handoff-check', [
            'shop' => $shop->slug,
            '--skip-production-check' => true,
            '--log-path' => [$logPath],
            '--json' => true,
        ]);
        $payload = json_decode(Artisan::output(), true);

        $this->assertSame(1, $exitCode);
        $this->assertContains('Orderable menu photo files exist', $this->passedCheckNames($payload));
        $this->assertContains('Orderable menu photo variants exist', $this->failedCheckNames($payload));
    }

    public function test_handoff_check_fails_when_whatsapp_alerts_are_enabled_without_usable_number(): void
    {
        $shop = $this->createReadyShop([
            'slug' => 'broken-whatsapp-alerts',
            'branding' => [
                'onboarding_completed' => true,
                'whatsapp_notifications_enabled' => true,
                'whatsapp_number' => '++--()',
            ],
        ]);
        $logPath = $this->writeLog('[2026-06-25 12:00:00] local.INFO: Handoff drill clean.');
        $this->fakeHealthyHttpFor($shop);

        $exitCode = Artisan::call('bite:handoff-check', [
            'shop' => $shop->slug,
            '--skip-production-check' => true,
            '--log-path' => [$logPath],
            '--json' => true,
        ]);
        $payload = json_decode(Artisan::output(), true);

        $this->assertSame(1, $exitCode);
        $this->assertContains('WhatsApp alerts are disabled or usable', $this->failedCheckNames($payload));
    }

    public function test_handoff_check_passes_when_whatsapp_alerts_are_enabled_with_usable_number(): void
    {
        $shop = $this->createReadyShop([
            'slug' => 'ready-whatsapp-alerts',
            'branding' => [
                'onboarding_completed' => true,
                'whatsapp_notifications_enabled' => true,
                'whatsapp_number' => '+968 99 123 456',
            ],
        ]);
        $logPath = $this->writeLog('[2026-06-25 12:00:00] local.INFO: Handoff drill clean.');
        $this->fakeHealthyHttpFor($shop);

        $exitCode = Artisan::call('bite:handoff-check', [
            'shop' => $shop->slug,
            '--skip-production-check' => true,
            '--log-path' => [$logPath],
            '--json' => true,
        ]);
        $payload = json_decode(Artisan::output(), true);

        $this->assertSame(0, $exitCode, 'Failed checks: '.implode(', ', $this->failedCheckNames($payload)));
        $this->assertContains('WhatsApp alerts are disabled or usable', $this->passedCheckNames($payload));
    }

    public function test_handoff_check_surfaces_production_gate_failures_by_default(): void
    {
        $shop = $this->createReadyShop(['slug' => 'local-env-shop']);
        $logPath = $this->writeLog('[2026-06-25 12:00:00] local.INFO: Handoff drill clean.');
        $this->fakeHealthyHttpFor($shop);

        $this->artisan('bite:handoff-check', [
            'shop' => $shop->slug,
            '--allow-non-production' => true,
            '--log-path' => [$logPath],
        ])->assertFailed()
            ->expectsOutputToContain('FAIL [server] Production configuration gate')
            ->expectsOutputToContain('handoff readiness check(s) failed.');
    }

    public function test_handoff_check_fails_for_unknown_shop_slug(): void
    {
        $logPath = $this->writeLog('[2026-06-25 12:00:00] local.INFO: Handoff drill clean.');

        $this->artisan('bite:handoff-check', [
            'shop' => 'missing-shop',
            '--skip-production-check' => true,
            '--log-path' => [$logPath],
        ])->assertFailed()
            ->expectsOutputToContain('FAIL [restaurant] Shop exists');
    }

    public function test_handoff_check_fails_when_live_guest_menu_http_check_fails(): void
    {
        $shop = $this->createReadyShop(['slug' => 'broken-live-menu']);
        $logPath = $this->writeLog('[2026-06-25 12:00:00] local.INFO: Handoff drill clean.');
        $this->fakeHealthyHttpFor($shop, [
            route('guest.menu', $shop) => Http::response('server error', 500),
        ]);

        $exitCode = Artisan::call('bite:handoff-check', [
            'shop' => $shop->slug,
            '--skip-production-check' => true,
            '--log-path' => [$logPath],
            '--json' => true,
        ]);
        $payload = json_decode(Artisan::output(), true);

        $this->assertSame(1, $exitCode);
        $this->assertContains('Live guest menu returns 200', $this->failedCheckNames($payload));
    }

    public function test_handoff_check_fails_when_live_guest_menu_omits_product_images(): void
    {
        $shop = $this->createReadyShop(['slug' => 'missing-live-menu-images']);
        $logPath = $this->writeLog('[2026-06-25 12:00:00] local.INFO: Handoff drill clean.');
        $this->fakeHealthyHttpFor($shop, [
            route('guest.menu', $shop) => Http::response('<html><body>menu without product images</body></html>', 200),
        ]);

        $exitCode = Artisan::call('bite:handoff-check', [
            'shop' => $shop->slug,
            '--skip-production-check' => true,
            '--log-path' => [$logPath],
            '--json' => true,
        ]);
        $payload = json_decode(Artisan::output(), true);

        $this->assertSame(1, $exitCode);
        $this->assertContains('Live guest menu returns 200', $this->passedCheckNames($payload));
        $this->assertContains('Live guest menu product images return images', $this->failedCheckNames($payload));
    }

    public function test_handoff_check_fails_when_live_guest_menu_product_image_is_not_loadable(): void
    {
        $shop = $this->createReadyShop(['slug' => 'broken-live-menu-image']);
        $logPath = $this->writeLog('[2026-06-25 12:00:00] local.INFO: Handoff drill clean.');
        $this->fakeHealthyHttpFor($shop, [
            '*storage/products/*' => Http::response('<html>not found</html>', 404, [
                'Content-Type' => 'text/html; charset=UTF-8',
            ]),
        ]);

        $exitCode = Artisan::call('bite:handoff-check', [
            'shop' => $shop->slug,
            '--skip-production-check' => true,
            '--log-path' => [$logPath],
            '--json' => true,
        ]);
        $payload = json_decode(Artisan::output(), true);

        $this->assertSame(1, $exitCode);
        $this->assertContains('Live guest menu returns 200', $this->passedCheckNames($payload));
        $this->assertContains('Live guest menu product images return images', $this->failedCheckNames($payload));
    }

    public function test_handoff_check_fails_when_duplicate_named_product_image_is_missing_from_live_menu(): void
    {
        $shop = $this->createReadyShop(['slug' => 'duplicate-live-menu-image']);
        $category = $shop->categories()->firstOrFail();
        $existingProductName = Product::where('shop_id', $shop->id)->firstOrFail()->name_en;
        Product::factory()->create([
            'shop_id' => $shop->id,
            'category_id' => $category->id,
            'name_en' => $existingProductName,
            'is_visible' => true,
            'is_available' => true,
            'image_url' => 'products/handoff-ready-alt-full.webp',
        ]);
        Storage::disk('public')->put('products/handoff-ready-alt-thumb.webp', 'fake-alt-thumb-image');
        Storage::disk('public')->put('products/handoff-ready-alt-full.webp', 'fake-alt-full-image');
        Storage::disk('public')->put('products/handoff-ready-alt-card.webp', 'fake-alt-card-image');
        $logPath = $this->writeLog('[2026-06-25 12:00:00] local.INFO: Handoff drill clean.');
        $this->fakeHealthyHttpFor($shop, [
            route('guest.menu', $shop) => Http::response(
                '<html><body><img src="/storage/products/handoff-ready-alt-card.webp" alt="Only duplicate image rendered"></body></html>',
                200,
            ),
        ]);

        $exitCode = Artisan::call('bite:handoff-check', [
            'shop' => $shop->slug,
            '--skip-production-check' => true,
            '--log-path' => [$logPath],
            '--json' => true,
        ]);
        $payload = json_decode(Artisan::output(), true);

        $this->assertSame(1, $exitCode);
        $this->assertContains('Live guest menu product images return images', $this->failedCheckNames($payload));
    }

    public function test_handoff_check_fails_when_shared_product_image_is_not_rendered_for_each_product(): void
    {
        $shop = $this->createReadyShop(['slug' => 'shared-live-menu-image']);
        $category = $shop->categories()->firstOrFail();
        Product::factory()->create([
            'shop_id' => $shop->id,
            'category_id' => $category->id,
            'name_en' => 'Shared Image Item',
            'is_visible' => true,
            'is_available' => true,
            'image_url' => 'products/handoff-ready-full.webp',
        ]);
        $logPath = $this->writeLog('[2026-06-25 12:00:00] local.INFO: Handoff drill clean.');
        $this->fakeHealthyHttpFor($shop, [
            route('guest.menu', $shop) => Http::response(
                '<html><body><img src="/storage/products/handoff-ready-card.webp" alt="Only one shared image rendered"></body></html>',
                200,
            ),
        ]);

        $exitCode = Artisan::call('bite:handoff-check', [
            'shop' => $shop->slug,
            '--skip-production-check' => true,
            '--log-path' => [$logPath],
            '--json' => true,
        ]);
        $payload = json_decode(Artisan::output(), true);

        $this->assertSame(1, $exitCode);
        $this->assertContains('Live guest menu product images return images', $this->failedCheckNames($payload));
    }

    public function test_handoff_check_fails_when_live_guest_qr_is_not_svg(): void
    {
        $shop = $this->createReadyShop(['slug' => 'html-qr-restaurant']);
        $logPath = $this->writeLog('[2026-06-25 12:00:00] local.INFO: Handoff drill clean.');
        $this->fakeHealthyHttpFor($shop, [
            route('guest.menu.qr', $shop) => Http::response(
                '<html>not a qr</html>',
                200,
                [
                    'Content-Type' => 'text/html; charset=UTF-8',
                    'X-Bite-QR-Target' => route('guest.menu', $shop),
                ],
            ),
        ]);

        $exitCode = Artisan::call('bite:handoff-check', [
            'shop' => $shop->slug,
            '--skip-production-check' => true,
            '--log-path' => [$logPath],
            '--json' => true,
        ]);
        $payload = json_decode(Artisan::output(), true);

        $this->assertSame(1, $exitCode);
        $this->assertContains('Live guest QR returns SVG for guest menu', $this->failedCheckNames($payload));
    }

    public function test_handoff_check_fails_when_live_guest_qr_targets_wrong_menu(): void
    {
        $shop = $this->createReadyShop(['slug' => 'wrong-qr-target-restaurant']);
        $logPath = $this->writeLog('[2026-06-25 12:00:00] local.INFO: Handoff drill clean.');
        $this->fakeHealthyHttpFor($shop, [
            route('guest.menu.qr', $shop) => Http::response(
                '<svg xmlns="http://www.w3.org/2000/svg"></svg>',
                200,
                [
                    'Content-Type' => 'image/svg+xml; charset=UTF-8',
                    'X-Bite-QR-Target' => 'https://wrong.example.test/menu/other-restaurant',
                ],
            ),
        ]);

        $exitCode = Artisan::call('bite:handoff-check', [
            'shop' => $shop->slug,
            '--skip-production-check' => true,
            '--log-path' => [$logPath],
            '--json' => true,
        ]);
        $payload = json_decode(Artisan::output(), true);

        $this->assertSame(1, $exitCode);
        $this->assertContains('Live guest QR returns SVG for guest menu', $this->failedCheckNames($payload));
    }

    private function createReadyShop(array $overrides = []): Shop
    {
        $shop = Shop::factory()->create(array_merge([
            'name' => 'Ready Restaurant',
            'slug' => 'ready-restaurant',
            'status' => 'active',
            'branding' => ['onboarding_completed' => true],
            'tax_rate' => 5,
            'currency_code' => 'OMR',
            'currency_symbol' => 'OMR',
            'currency_decimals' => 3,
            'trial_ends_at' => now()->addDays(14),
        ], $overrides));

        User::factory()->create([
            'shop_id' => $shop->id,
            'role' => 'admin',
            'password' => Hash::make('owner-handoff-password'),
            'pin_code' => Hash::make('1111'),
        ]);
        User::factory()->create([
            'shop_id' => $shop->id,
            'role' => 'server',
            'pin_code' => Hash::make('2222'),
        ]);
        User::factory()->create([
            'shop_id' => $shop->id,
            'role' => 'kitchen',
            'pin_code' => Hash::make('3333'),
        ]);

        $category = Category::factory()->create([
            'shop_id' => $shop->id,
            'is_active' => true,
        ]);
        Product::factory()->create([
            'shop_id' => $shop->id,
            'category_id' => $category->id,
            'is_visible' => true,
            'is_available' => true,
            'image_url' => 'products/handoff-ready-full.webp',
        ]);
        Storage::disk('public')->put('products/handoff-ready-thumb.webp', 'fake-thumb-image');
        Storage::disk('public')->put('products/handoff-ready-full.webp', 'fake-full-image');
        Storage::disk('public')->put('products/handoff-ready-card.webp', 'fake-card-image');

        return $shop;
    }

    private function writeLog(string $contents): string
    {
        $path = $this->logDirectory.'/laravel.log';
        File::put($path, trim($contents).PHP_EOL);

        return $path;
    }

    /**
     * @param  array<string, \Illuminate\Http\Client\Response>  $overrides
     */
    private function fakeHealthyHttpFor(Shop $shop, array $overrides = []): void
    {
        Http::preventStrayRequests();
        Http::fake(array_merge([
            route('health') => Http::response(['status' => 'healthy'], 200),
            route('guest.menu', $shop) => Http::response('<html><body><img src="/storage/products/handoff-ready-card.webp" alt="Ready item"></body></html>', 200),
            route('guest.menu.qr', $shop) => Http::response('<svg></svg>', 200, [
                'Content-Type' => 'image/svg+xml',
                'X-Bite-QR-Target' => route('guest.menu', $shop),
            ]),
            route('pos.pin', $shop) => Http::response('<html>pin</html>', 200),
            '*storage/products/*' => Http::response('fake-card-image', 200, [
                'Content-Type' => 'image/webp',
            ]),
        ], $overrides));
    }

    /**
     * @return list<string>
     */
    private function failedCheckNames(array $payload): array
    {
        return collect($payload['checks'] ?? [])
            ->filter(fn (array $check) => $check['ok'] === false)
            ->pluck('name')
            ->values()
            ->all();
    }

    /**
     * @return list<string>
     */
    private function passedCheckNames(array $payload): array
    {
        return collect($payload['checks'] ?? [])
            ->filter(fn (array $check) => $check['ok'] === true)
            ->pluck('name')
            ->values()
            ->all();
    }
}
