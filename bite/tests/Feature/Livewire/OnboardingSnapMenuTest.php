<?php

namespace Tests\Feature\Livewire;

use App\Livewire\OnboardingWizard;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class OnboardingSnapMenuTest extends TestCase
{
    use RefreshDatabase;

    protected Shop $shop;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->shop = Shop::factory()->create([
            'branding' => ['accent' => '#cc5500', 'paper' => '#fdfcf8', 'ink' => '#1a1918'],
        ]);

        $this->admin = User::factory()->create([
            'shop_id' => $this->shop->id,
            'role' => 'admin',
        ]);

        config(['services.gemini.api_key' => 'test-key']);
        config(['services.gemini.model' => 'gemini-2.0-flash']);
    }

    public function test_step3_starts_in_choose_mode(): void
    {
        Livewire::actingAs($this->admin)
            ->test(OnboardingWizard::class)
            ->set('step', 3)
            ->assertSet('menuMode', 'choose');
    }

    public function test_can_switch_to_manual_mode(): void
    {
        Livewire::actingAs($this->admin)
            ->test(OnboardingWizard::class)
            ->set('step', 3)
            ->call('showManualEntry')
            ->assertSet('menuMode', 'manual');
    }

    public function test_can_reset_back_to_choose_mode(): void
    {
        Livewire::actingAs($this->admin)
            ->test(OnboardingWizard::class)
            ->set('step', 3)
            ->set('menuMode', 'review')
            ->set('extractedItems', [
                ['category_en' => 'Drinks', 'name_en' => 'Coffee', 'price' => 1.5],
            ])
            ->call('resetExtraction')
            ->assertSet('menuMode', 'choose')
            ->assertSet('extractedItems', []);
    }

    public function test_extract_menu_calls_gemini_and_shows_review(): void
    {
        Storage::fake('local');

        $geminiResponse = [
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            [
                                'text' => json_encode([
                                    [
                                        'category_en' => 'Beverages',
                                        'category_ar' => 'مشروبات',
                                        'name_en' => 'Latte',
                                        'name_ar' => 'لاتيه',
                                        'description_en' => 'Creamy espresso drink',
                                        'description_ar' => 'مشروب اسبريسو كريمي',
                                        'price' => 1.500,
                                    ],
                                    [
                                        'category_en' => 'Food',
                                        'category_ar' => 'طعام',
                                        'name_en' => 'Croissant',
                                        'name_ar' => 'كرواسون',
                                        'description_en' => 'Buttery pastry',
                                        'description_ar' => 'معجنات بالزبدة',
                                        'price' => 0.800,
                                    ],
                                ]),
                            ],
                        ],
                    ],
                ],
            ],
        ];

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response($geminiResponse, 200),
        ]);

        $photo = UploadedFile::fake()->image('menu.jpg', 800, 600);

        Livewire::actingAs($this->admin)
            ->test(OnboardingWizard::class)
            ->set('step', 3)
            ->set('menuPhotos', [$photo])
            ->call('extractMenu')
            ->assertSet('menuMode', 'review')
            ->assertCount('extractedItems', 2);

        Http::assertSentCount(1);
    }

    public function test_extract_menu_handles_api_failure(): void
    {
        Storage::fake('local');

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response('Server Error', 500),
        ]);

        $photo = UploadedFile::fake()->image('menu.jpg', 800, 600);

        Livewire::actingAs($this->admin)
            ->test(OnboardingWizard::class)
            ->set('step', 3)
            ->set('menuPhotos', [$photo])
            ->call('extractMenu')
            ->assertSet('menuMode', 'choose')
            ->assertSet('extractionError', 'failed');
    }

    public function test_save_extracted_menu_creates_categories_and_products(): void
    {
        Livewire::actingAs($this->admin)
            ->test(OnboardingWizard::class)
            ->set('step', 3)
            ->set('menuMode', 'review')
            ->set('extractedItems', [
                [
                    'category_en' => 'Beverages',
                    'category_ar' => 'مشروبات',
                    'name_en' => 'Latte',
                    'name_ar' => 'لاتيه',
                    'description_en' => 'Creamy espresso',
                    'description_ar' => 'اسبريسو كريمي',
                    'price' => 1.500,
                ],
                [
                    'category_en' => 'Food',
                    'category_ar' => 'طعام',
                    'name_en' => 'Croissant',
                    'name_ar' => 'كرواسون',
                    'description_en' => 'Buttery pastry',
                    'description_ar' => 'معجنات بالزبدة',
                    'price' => 0.800,
                ],
            ])
            ->call('saveExtractedMenu')
            ->assertSet('step', 4);

        $this->assertDatabaseHas('categories', [
            'shop_id' => $this->shop->id,
            'name_en' => 'Beverages',
            'name_ar' => 'مشروبات',
        ]);

        $this->assertDatabaseHas('categories', [
            'shop_id' => $this->shop->id,
            'name_en' => 'Food',
            'name_ar' => 'طعام',
        ]);

        $this->assertDatabaseHas('products', [
            'shop_id' => $this->shop->id,
            'name_en' => 'Latte',
            'name_ar' => 'لاتيه',
        ]);

        $this->assertDatabaseHas('products', [
            'shop_id' => $this->shop->id,
            'name_en' => 'Croissant',
            'name_ar' => 'كرواسون',
        ]);
    }

    public function test_save_extracted_menu_skips_empty_items(): void
    {
        Livewire::actingAs($this->admin)
            ->test(OnboardingWizard::class)
            ->set('step', 3)
            ->set('menuMode', 'review')
            ->set('extractedItems', [
                [
                    'category_en' => 'Beverages',
                    'category_ar' => 'مشروبات',
                    'name_en' => 'Latte',
                    'name_ar' => 'لاتيه',
                    'description_en' => '',
                    'description_ar' => '',
                    'price' => 1.500,
                ],
                [
                    'category_en' => '',
                    'category_ar' => '',
                    'name_en' => '',
                    'name_ar' => '',
                    'description_en' => '',
                    'description_ar' => '',
                    'price' => 0,
                ],
            ])
            ->call('saveExtractedMenu')
            ->assertSet('step', 4);

        $this->assertDatabaseHas('products', [
            'shop_id' => $this->shop->id,
            'name_en' => 'Latte',
        ]);

        $this->assertEquals(
            1,
            \App\Models\Product::where('shop_id', $this->shop->id)->count()
        );
    }

    public function test_can_add_and_remove_extracted_items(): void
    {
        $component = Livewire::actingAs($this->admin)
            ->test(OnboardingWizard::class)
            ->set('step', 3)
            ->set('menuMode', 'review')
            ->set('extractedItems', [
                [
                    'category_en' => 'Beverages',
                    'category_ar' => 'مشروبات',
                    'name_en' => 'Latte',
                    'name_ar' => 'لاتيه',
                    'description_en' => '',
                    'description_ar' => '',
                    'price' => 1.500,
                ],
            ]);

        // Add an item
        $component->call('addExtractedItem')
            ->assertCount('extractedItems', 2);

        // Add another item
        $component->call('addExtractedItem')
            ->assertCount('extractedItems', 3);

        // Remove the middle item (index 1)
        $component->call('removeExtractedItem', 1)
            ->assertCount('extractedItems', 2);
    }

    public function test_manual_mode_still_works(): void
    {
        Livewire::actingAs($this->admin)
            ->test(OnboardingWizard::class)
            ->set('step', 3)
            ->call('showManualEntry')
            ->assertSet('menuMode', 'manual')
            ->set('menuItems', [
                ['name' => 'Americano', 'price' => '0.900'],
            ])
            ->call('saveMenuItems')
            ->assertSet('step', 4);

        $this->assertDatabaseHas('products', [
            'shop_id' => $this->shop->id,
            'name_en' => 'Americano',
        ]);
    }
}
