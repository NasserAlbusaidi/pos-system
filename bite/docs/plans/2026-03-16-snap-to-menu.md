# Snap-to-Menu Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Let restaurant owners upload photos of their paper menu and have AI extract all items, prices, categories, and bilingual names — generating a complete digital menu in under 30 seconds.

**Architecture:** A new `MenuExtractionService` calls Google Gemini Flash 2.0 with uploaded images and a structured extraction prompt. The service returns normalized JSON. The `OnboardingWizard` Livewire component gains a dual-mode Step 3: upload mode (photo → AI extraction → editable review table) and the existing manual mode. Both modes share the same save logic that bulk-creates Category + Product records.

**Tech Stack:** Laravel 12, Livewire 3 (with `WithFileUploads`), Google Gemini Flash 2.0 REST API, vanilla CSS.

---

### Task 1: Add Gemini Config + Environment

**Files:**
- Modify: `config/services.php:37`
- Modify: `.env.example:94`

**Step 1: Add Gemini config block to `config/services.php`**

After the `slack` entry (line 37), add:

```php
'gemini' => [
    'api_key' => env('GEMINI_API_KEY'),
    'model' => env('GEMINI_MODEL', 'gemini-2.0-flash'),
],
```

**Step 2: Add env variable to `.env.example`**

At the end of the file, add:

```env
# Google Gemini — AI menu extraction (Snap-to-Menu)
GEMINI_API_KEY=
```

**Step 3: Commit**

```bash
git add config/services.php .env.example
git commit -m "chore: add Gemini Flash config for Snap-to-Menu"
```

---

### Task 2: Create `MenuExtractionService` with Tests (TDD)

**Files:**
- Create: `tests/Unit/Services/MenuExtractionServiceTest.php`
- Create: `app/Services/MenuExtractionService.php`

**Step 1: Write the failing test**

```php
<?php

namespace Tests\Unit\Services;

use App\Services\MenuExtractionService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MenuExtractionServiceTest extends TestCase
{
    private MenuExtractionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.gemini.api_key' => 'test-key']);
        config(['services.gemini.model' => 'gemini-2.0-flash']);
        $this->service = new MenuExtractionService;
    }

    public function test_extract_returns_structured_menu_items(): void
    {
        $geminiResponse = [
            'candidates' => [[
                'content' => [
                    'parts' => [[
                        'text' => json_encode([
                            [
                                'category_en' => 'Hot Drinks',
                                'category_ar' => 'مشروبات ساخنة',
                                'name_en' => 'Karak Tea',
                                'name_ar' => 'شاي كرك',
                                'description_en' => 'Traditional spiced tea',
                                'description_ar' => 'شاي بالتوابل التقليدي',
                                'price' => 0.500,
                            ],
                            [
                                'category_en' => 'Hot Drinks',
                                'category_ar' => 'مشروبات ساخنة',
                                'name_en' => 'Turkish Coffee',
                                'name_ar' => 'قهوة تركية',
                                'description_en' => '',
                                'description_ar' => '',
                                'price' => 0.800,
                            ],
                        ]),
                    ]],
                ],
            ]],
        ];

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response($geminiResponse),
        ]);

        $imageData = [
            ['mime_type' => 'image/jpeg', 'data' => base64_encode('fake-image-data')],
        ];

        $result = $this->service->extract($imageData);

        $this->assertCount(2, $result);
        $this->assertEquals('Hot Drinks', $result[0]['category_en']);
        $this->assertEquals('شاي كرك', $result[0]['name_ar']);
        $this->assertEquals(0.500, $result[0]['price']);

        Http::assertSent(function (Request $request) {
            return str_contains($request->url(), 'gemini-2.0-flash')
                && str_contains($request->url(), 'key=test-key');
        });
    }

    public function test_extract_throws_on_api_failure(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([], 500),
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Menu extraction failed');

        $this->service->extract([
            ['mime_type' => 'image/jpeg', 'data' => base64_encode('fake')],
        ]);
    }

    public function test_extract_throws_on_invalid_json_response(): void
    {
        $geminiResponse = [
            'candidates' => [[
                'content' => [
                    'parts' => [[
                        'text' => 'This is not valid JSON',
                    ]],
                ],
            ]],
        ];

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response($geminiResponse),
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Could not parse');

        $this->service->extract([
            ['mime_type' => 'image/jpeg', 'data' => base64_encode('fake')],
        ]);
    }

    public function test_extract_handles_json_wrapped_in_markdown_code_block(): void
    {
        $json = json_encode([
            [
                'category_en' => 'Drinks',
                'category_ar' => 'مشروبات',
                'name_en' => 'Water',
                'name_ar' => 'ماء',
                'description_en' => '',
                'description_ar' => '',
                'price' => 0.200,
            ],
        ]);

        $geminiResponse = [
            'candidates' => [[
                'content' => [
                    'parts' => [[
                        'text' => "```json\n{$json}\n```",
                    ]],
                ],
            ]],
        ];

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response($geminiResponse),
        ]);

        $result = $this->service->extract([
            ['mime_type' => 'image/jpeg', 'data' => base64_encode('fake')],
        ]);

        $this->assertCount(1, $result);
        $this->assertEquals('Water', $result[0]['name_en']);
    }

    public function test_extract_filters_items_with_missing_name(): void
    {
        $geminiResponse = [
            'candidates' => [[
                'content' => [
                    'parts' => [[
                        'text' => json_encode([
                            [
                                'category_en' => 'Food',
                                'category_ar' => 'طعام',
                                'name_en' => 'Shawarma',
                                'name_ar' => 'شاورما',
                                'description_en' => '',
                                'description_ar' => '',
                                'price' => 1.500,
                            ],
                            [
                                'category_en' => 'Food',
                                'category_ar' => 'طعام',
                                'name_en' => '',
                                'name_ar' => '',
                                'description_en' => '',
                                'description_ar' => '',
                                'price' => 0,
                            ],
                        ]),
                    ]],
                ],
            ]],
        ];

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response($geminiResponse),
        ]);

        $result = $this->service->extract([
            ['mime_type' => 'image/jpeg', 'data' => base64_encode('fake')],
        ]);

        $this->assertCount(1, $result);
        $this->assertEquals('Shawarma', $result[0]['name_en']);
    }

    public function test_extract_sends_multiple_images(): void
    {
        $geminiResponse = [
            'candidates' => [[
                'content' => [
                    'parts' => [[
                        'text' => json_encode([
                            [
                                'category_en' => 'Food',
                                'category_ar' => 'طعام',
                                'name_en' => 'Burger',
                                'name_ar' => 'برجر',
                                'description_en' => '',
                                'description_ar' => '',
                                'price' => 2.500,
                            ],
                        ]),
                    ]],
                ],
            ]],
        ];

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response($geminiResponse),
        ]);

        $imageData = [
            ['mime_type' => 'image/jpeg', 'data' => base64_encode('page-1')],
            ['mime_type' => 'image/png', 'data' => base64_encode('page-2')],
        ];

        $this->service->extract($imageData);

        Http::assertSent(function (Request $request) {
            $body = json_decode($request->body(), true);
            $parts = $body['contents'][0]['parts'] ?? [];
            // Should have: text prompt + 2 images
            $imageParts = array_filter($parts, fn ($p) => isset($p['inline_data']));

            return count($imageParts) === 2;
        });
    }

    public function test_extract_throws_when_no_api_key_configured(): void
    {
        config(['services.gemini.api_key' => null]);
        $service = new MenuExtractionService;

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Gemini API key not configured');

        $service->extract([
            ['mime_type' => 'image/jpeg', 'data' => base64_encode('fake')],
        ]);
    }
}
```

**Step 2: Run test to verify it fails**

```bash
php artisan test --filter=MenuExtractionServiceTest
```

Expected: FAIL — class `MenuExtractionService` does not exist.

**Step 3: Write the implementation**

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class MenuExtractionService
{
    /**
     * Extract menu items from one or more images using Gemini Flash.
     *
     * @param  array<array{mime_type: string, data: string}>  $images  Base64-encoded image data
     * @return array<array{category_en: string, category_ar: string, name_en: string, name_ar: string, description_en: string, description_ar: string, price: float}>
     *
     * @throws \RuntimeException
     */
    public function extract(array $images): array
    {
        $apiKey = config('services.gemini.api_key');

        if (empty($apiKey)) {
            throw new \RuntimeException('Gemini API key not configured. Set GEMINI_API_KEY in your .env file.');
        }

        $model = config('services.gemini.model', 'gemini-2.0-flash');

        $parts = [];

        // Add each image as an inline_data part
        foreach ($images as $image) {
            $parts[] = [
                'inline_data' => [
                    'mime_type' => $image['mime_type'],
                    'data' => $image['data'],
                ],
            ];
        }

        // Add the extraction prompt
        $parts[] = [
            'text' => $this->buildPrompt(),
        ];

        $response = Http::timeout(30)->post(
            "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}",
            [
                'contents' => [
                    ['parts' => $parts],
                ],
                'generationConfig' => [
                    'temperature' => 0.1,
                    'responseMimeType' => 'application/json',
                ],
            ]
        );

        if ($response->failed()) {
            throw new \RuntimeException(
                'Menu extraction failed: ' . ($response->json('error.message') ?? 'API returned ' . $response->status())
            );
        }

        $text = $response->json('candidates.0.content.parts.0.text', '');

        return $this->parseResponse($text);
    }

    private function buildPrompt(): string
    {
        return <<<'PROMPT'
You are a restaurant menu extraction assistant. Analyze the menu image(s) and extract every menu item.

Return a JSON array where each element has exactly these fields:
- "category_en": Category name in English
- "category_ar": Category name in Arabic
- "name_en": Item name in English
- "name_ar": Item name in Arabic
- "description_en": Brief description in English (empty string if none)
- "description_ar": Brief description in Arabic (empty string if none)
- "price": Numeric price as a decimal number (e.g. 1.500)

Rules:
1. Extract ALL items visible in the menu — do not skip any.
2. Preserve the original category groupings from the menu. If no categories are visible, use "Menu" / "القائمة".
3. If the menu is only in Arabic, generate accurate English translations. If only in English, generate accurate Arabic translations.
4. Prices must be numeric. Remove currency symbols. If a price range is shown (e.g. "1.500 - 2.500"), use the lower price.
5. If an item has no visible price, set price to 0.
6. Do NOT include section headers, decorative text, or restaurant info — only food/drink items.
7. Return ONLY the JSON array, no other text.
PROMPT;
    }

    private function parseResponse(string $text): array
    {
        // Strip markdown code fences if present
        $text = trim($text);
        if (str_starts_with($text, '```')) {
            $text = preg_replace('/^```(?:json)?\s*/i', '', $text);
            $text = preg_replace('/\s*```$/', '', $text);
        }

        $items = json_decode($text, true);

        if (! is_array($items)) {
            throw new \RuntimeException('Could not parse menu extraction response as JSON.');
        }

        // Normalize and filter
        return collect($items)
            ->filter(fn ($item) => ! empty(trim($item['name_en'] ?? '')) || ! empty(trim($item['name_ar'] ?? '')))
            ->map(fn ($item) => [
                'category_en' => trim($item['category_en'] ?? 'Menu'),
                'category_ar' => trim($item['category_ar'] ?? 'القائمة'),
                'name_en' => trim($item['name_en'] ?? ''),
                'name_ar' => trim($item['name_ar'] ?? ''),
                'description_en' => trim($item['description_en'] ?? ''),
                'description_ar' => trim($item['description_ar'] ?? ''),
                'price' => max(0, (float) ($item['price'] ?? 0)),
            ])
            ->values()
            ->all();
    }
}
```

**Step 4: Run test to verify it passes**

```bash
php artisan test --filter=MenuExtractionServiceTest
```

Expected: All 6 tests PASS.

**Step 5: Commit**

```bash
git add app/Services/MenuExtractionService.php tests/Unit/Services/MenuExtractionServiceTest.php
git commit -m "feat: add MenuExtractionService for AI menu extraction via Gemini Flash"
```

---

### Task 3: Add Translation Strings (en/ar)

**Files:**
- Modify: `lang/en/admin.php`
- Modify: `lang/ar/admin.php`

**Step 1: Add English translation strings**

After the existing `onboarding_powered_by` key (line 356 in `lang/en/admin.php`), add:

```php
// Snap-to-Menu
'snap_upload_title' => 'Upload your menu',
'snap_upload_desc' => 'Take a photo of your paper menu and we\'ll extract everything automatically — items, prices, categories, in both English and Arabic.',
'snap_upload_hint' => 'Supports JPG, PNG, and PDF. Upload up to 4 images.',
'snap_upload_button' => 'Upload Menu Photos',
'snap_or_manual' => 'or add items manually',
'snap_extracting' => 'Extracting your menu...',
'snap_extracting_desc' => 'AI is reading your menu. This takes about 10 seconds.',
'snap_review_title' => 'Review Extracted Menu',
'snap_review_desc' => 'Edit any items below, then save to create your digital menu.',
'snap_category' => 'Category',
'snap_name_en' => 'Name (English)',
'snap_name_ar' => 'Name (Arabic)',
'snap_description' => 'Description',
'snap_price' => 'Price',
'snap_remove_item' => 'Remove',
'snap_add_item' => 'Add Item',
'snap_save_menu' => 'Save Menu',
'snap_try_again' => 'Upload Different Photos',
'snap_error' => 'Could not extract menu items. Please try a clearer photo or add items manually.',
'snap_no_items' => 'No items were found in the photo. Try a different image or add items manually.',
'snap_items_found' => ':count items found across :categories categories',
```

**Step 2: Add Arabic translation strings**

After the existing `onboarding_powered_by` key (line 356 in `lang/ar/admin.php`), add:

```php
// Snap-to-Menu
'snap_upload_title' => 'ارفع قائمتك',
'snap_upload_desc' => 'التقط صورة لقائمة الطعام الورقية وسنستخرج كل شيء تلقائياً — العناصر والأسعار والتصنيفات بالعربية والإنجليزية.',
'snap_upload_hint' => 'يدعم JPG و PNG و PDF. يمكنك رفع حتى 4 صور.',
'snap_upload_button' => 'ارفع صور القائمة',
'snap_or_manual' => 'أو أضف العناصر يدوياً',
'snap_extracting' => 'جارٍ استخراج القائمة...',
'snap_extracting_desc' => 'الذكاء الاصطناعي يقرأ قائمتك. يستغرق حوالي 10 ثوانٍ.',
'snap_review_title' => 'مراجعة القائمة المستخرجة',
'snap_review_desc' => 'عدّل أي عناصر أدناه، ثم احفظ لإنشاء قائمتك الرقمية.',
'snap_category' => 'التصنيف',
'snap_name_en' => 'الاسم (إنجليزي)',
'snap_name_ar' => 'الاسم (عربي)',
'snap_description' => 'الوصف',
'snap_price' => 'السعر',
'snap_remove_item' => 'حذف',
'snap_add_item' => 'إضافة عنصر',
'snap_save_menu' => 'حفظ القائمة',
'snap_try_again' => 'ارفع صور مختلفة',
'snap_error' => 'لم نتمكن من استخراج العناصر. حاول بصورة أوضح أو أضف العناصر يدوياً.',
'snap_no_items' => 'لم يتم العثور على عناصر في الصورة. جرّب صورة مختلفة أو أضف يدوياً.',
'snap_items_found' => 'تم العثور على :count عنصر في :categories تصنيف',
```

**Step 3: Commit**

```bash
git add lang/en/admin.php lang/ar/admin.php
git commit -m "feat: add Snap-to-Menu translation strings (en/ar)"
```

---

### Task 4: Update OnboardingWizard Livewire Component

**Files:**
- Modify: `app/Livewire/OnboardingWizard.php`

**Step 1: Add `WithFileUploads` trait and new state properties**

Add to imports (after line 11):

```php
use Livewire\WithFileUploads;
use App\Services\MenuExtractionService;
```

Add trait inside class (after line 14, the class declaration):

```php
use WithFileUploads;
```

Add new properties after line 40 (`public array $menuItems = [];`):

```php
// ── Step 3: Snap-to-Menu ──────────────────────────────
public array $menuPhotos = [];
public array $extractedItems = [];
public string $menuMode = 'choose'; // choose | extracting | review | manual
public string $extractionError = '';
```

**Step 2: Add the `extractMenu()` method**

Add after the `removeMenuItem()` method (after line 189):

```php
public function updatedMenuPhotos(): void
{
    $this->validate([
        'menuPhotos' => 'required|array|min:1|max:4',
        'menuPhotos.*' => 'file|mimes:jpg,jpeg,png,pdf|max:10240', // 10MB max per file
    ]);
}

public function extractMenu(): void
{
    $user = $this->onboardingUser();

    $this->validate([
        'menuPhotos' => 'required|array|min:1|max:4',
        'menuPhotos.*' => 'file|mimes:jpg,jpeg,png,pdf|max:10240',
    ]);

    $this->menuMode = 'extracting';
    $this->extractionError = '';

    try {
        // Prepare image data for Gemini
        $imageData = [];
        foreach ($this->menuPhotos as $photo) {
            $imageData[] = [
                'mime_type' => $photo->getMimeType(),
                'data' => base64_encode(file_get_contents($photo->getRealPath())),
            ];
        }

        $service = new MenuExtractionService;
        $items = $service->extract($imageData);

        if (empty($items)) {
            $this->menuMode = 'choose';
            $this->extractionError = 'no_items';
            return;
        }

        $this->extractedItems = $items;
        $this->menuMode = 'review';

    } catch (\Exception $e) {
        report($e);
        $this->menuMode = 'choose';
        $this->extractionError = 'failed';
    }
}

public function addExtractedItem(): void
{
    $this->onboardingUser();

    $this->extractedItems[] = [
        'category_en' => '',
        'category_ar' => '',
        'name_en' => '',
        'name_ar' => '',
        'description_en' => '',
        'description_ar' => '',
        'price' => 0,
    ];
}

public function removeExtractedItem(int $index): void
{
    $this->onboardingUser();

    if (count($this->extractedItems) > 1) {
        array_splice($this->extractedItems, $index, 1);
        $this->extractedItems = array_values($this->extractedItems);
    }
}

public function resetExtraction(): void
{
    $this->onboardingUser();

    $this->menuPhotos = [];
    $this->extractedItems = [];
    $this->extractionError = '';
    $this->menuMode = 'choose';
}

public function showManualEntry(): void
{
    $this->onboardingUser();
    $this->menuMode = 'manual';
}

public function saveExtractedMenu(): void
{
    $user = $this->onboardingUser();

    $items = collect($this->extractedItems)
        ->filter(fn ($item) => ! empty(trim($item['name_en'] ?? '')) || ! empty(trim($item['name_ar'] ?? '')));

    if ($items->isEmpty()) {
        $this->nextStep();
        return;
    }

    $this->validate([
        'extractedItems.*.name_en' => 'nullable|string|max:255',
        'extractedItems.*.name_ar' => 'nullable|string|max:255',
        'extractedItems.*.category_en' => 'nullable|string|max:255',
        'extractedItems.*.price' => 'nullable|numeric|min:0',
    ]);

    $shop = $user->shop;

    // Group by category and create
    $grouped = $items->groupBy(fn ($item) => $item['category_en'] ?: 'Menu');

    $catOrder = Category::where('shop_id', $shop->id)->max('sort_order') ?? 0;

    foreach ($grouped as $categoryName => $categoryItems) {
        $categoryNameAr = $categoryItems->first()['category_ar'] ?: 'القائمة';

        $category = Category::firstOrCreate(
            ['shop_id' => $shop->id, 'name_en' => $categoryName],
            ['name_ar' => $categoryNameAr, 'sort_order' => ++$catOrder]
        );

        $productOrder = Product::where('shop_id', $shop->id)
            ->where('category_id', $category->id)
            ->max('sort_order') ?? 0;

        foreach ($categoryItems as $item) {
            $nameEn = trim($item['name_en'] ?? '');
            $nameAr = trim($item['name_ar'] ?? '');

            if (empty($nameEn) && empty($nameAr)) {
                continue;
            }

            Product::forceCreate([
                'shop_id' => $shop->id,
                'category_id' => $category->id,
                'name_en' => $nameEn ?: $nameAr,
                'name_ar' => $nameAr ?: $nameEn,
                'description_en' => trim($item['description_en'] ?? ''),
                'description_ar' => trim($item['description_ar'] ?? ''),
                'price' => max(0, (float) ($item['price'] ?? 0)),
                'sort_order' => ++$productOrder,
            ]);
        }
    }

    $this->dispatch('toast', message: 'Menu items saved.', variant: 'success');
    $this->nextStep();
}
```

**Step 3: Commit**

```bash
git add app/Livewire/OnboardingWizard.php
git commit -m "feat: add Snap-to-Menu extraction flow to OnboardingWizard"
```

---

### Task 5: Update Step 3 Blade View

**Files:**
- Modify: `resources/views/livewire/onboarding-wizard.blade.php:181-261`

**Step 1: Replace the entire Step 3 block (lines 181-261)**

Replace the `@if ($step === 3)` block with the new dual-mode UI. The new Step 3 has four modes:

1. **choose** — Upload dropzone + "or add manually" link
2. **extracting** — Loading spinner
3. **review** — Editable table of extracted items
4. **manual** — Original manual entry form

```blade
@if ($step === 3)
    <div class="border-b border-line bg-muted/30 px-6 py-5">
        <h2 class="font-display text-2xl font-extrabold leading-none text-ink">
            {{ __('admin.onboarding_menu_items') }}
        </h2>
        <p class="mt-1 font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">
            {{ __('admin.onboarding_menu_items_desc') }}
        </p>
    </div>

    <div class="p-6 space-y-4">

        {{-- ── Mode: Choose (Upload or Manual) ── --}}
        @if ($menuMode === 'choose')
            <div class="space-y-6">
                {{-- Error messages --}}
                @if ($extractionError === 'failed')
                    <div class="rounded-lg border border-alert/30 bg-alert/5 p-4 text-sm text-alert">
                        {{ __('admin.snap_error') }}
                    </div>
                @elseif ($extractionError === 'no_items')
                    <div class="rounded-lg border border-alert/30 bg-alert/5 p-4 text-sm text-alert">
                        {{ __('admin.snap_no_items') }}
                    </div>
                @endif

                {{-- Upload area --}}
                <div
                    x-data="{ dragging: false }"
                    x-on:dragover.prevent="dragging = true"
                    x-on:dragleave="dragging = false"
                    x-on:drop.prevent="dragging = false; $refs.photoInput.files = $event.dataTransfer.files; $refs.photoInput.dispatchEvent(new Event('change'))"
                    class="relative rounded-xl border-2 border-dashed p-8 text-center transition-colors"
                    :class="dragging ? 'border-accent bg-accent/5' : 'border-line hover:border-ink/30'"
                >
                    <input
                        type="file"
                        wire:model="menuPhotos"
                        x-ref="photoInput"
                        accept="image/jpeg,image/png,application/pdf"
                        multiple
                        class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                    >

                    <div class="space-y-3">
                        <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-panel-muted">
                            <svg class="w-7 h-7 text-ink-soft" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-ink">{{ __('admin.snap_upload_title') }}</p>
                            <p class="mt-1 text-xs text-ink-soft leading-relaxed max-w-sm mx-auto">
                                {{ __('admin.snap_upload_desc') }}
                            </p>
                        </div>
                        <p class="font-mono text-[10px] text-ink-soft/70">
                            {{ __('admin.snap_upload_hint') }}
                        </p>
                    </div>
                </div>

                {{-- Upload button (visible after files selected) --}}
                @if (count($menuPhotos) > 0)
                    <div class="flex items-center justify-between rounded-lg border border-line bg-panel-muted/30 p-4">
                        <div class="flex items-center gap-3">
                            <div class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-signal/10 text-signal">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <p class="text-sm text-ink font-medium">
                                {{ count($menuPhotos) }} {{ count($menuPhotos) === 1 ? 'photo' : 'photos' }} selected
                            </p>
                        </div>
                        <button wire:click="extractMenu" class="btn-primary">
                            <span wire:loading.remove wire:target="extractMenu">
                                {{ __('admin.snap_upload_button') }}
                            </span>
                            <span wire:loading wire:target="extractMenu" class="inline-flex items-center gap-2">
                                <span class="loading-spinner"></span>
                                {{ __('admin.snap_extracting') }}
                            </span>
                        </button>
                    </div>
                @endif

                @error('menuPhotos') <p class="text-alert text-xs">{{ $message }}</p> @enderror
                @error('menuPhotos.*') <p class="text-alert text-xs">{{ $message }}</p> @enderror

                {{-- Divider + manual entry link --}}
                <div class="flex items-center gap-4">
                    <div class="flex-1 h-px bg-line"></div>
                    <button wire:click="showManualEntry" class="font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft hover:text-ink transition-colors">
                        {{ __('admin.snap_or_manual') }}
                    </button>
                    <div class="flex-1 h-px bg-line"></div>
                </div>
            </div>

            {{-- Navigation --}}
            <div class="flex items-center justify-between pt-4">
                <button type="button" wire:click="previousStep" class="btn-secondary">
                    {{ __('admin.onboarding_back') }}
                </button>
                <button type="button" wire:click="nextStep" class="btn-secondary">
                    {{ __('admin.onboarding_skip') }}
                </button>
            </div>

        {{-- ── Mode: Extracting ── --}}
        @elseif ($menuMode === 'extracting')
            <div class="py-12 text-center space-y-4">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-panel-muted">
                    <span class="loading-spinner" style="width: 2rem; height: 2rem;"></span>
                </div>
                <div>
                    <p class="text-sm font-medium text-ink">{{ __('admin.snap_extracting') }}</p>
                    <p class="mt-1 text-xs text-ink-soft">{{ __('admin.snap_extracting_desc') }}</p>
                </div>
            </div>

        {{-- ── Mode: Review Extracted Items ── --}}
        @elseif ($menuMode === 'review')
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-ink">{{ __('admin.snap_review_title') }}</p>
                        <p class="text-xs text-ink-soft mt-0.5">
                            {{ __('admin.snap_items_found', ['count' => count($extractedItems), 'categories' => count(collect($extractedItems)->pluck('category_en')->unique())]) }}
                        </p>
                    </div>
                    <button wire:click="resetExtraction" class="font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft hover:text-ink transition-colors">
                        {{ __('admin.snap_try_again') }}
                    </button>
                </div>

                <p class="text-xs text-ink-soft">{{ __('admin.snap_review_desc') }}</p>

                {{-- Extracted items table --}}
                <div class="space-y-3 max-h-[50vh] overflow-y-auto pr-1">
                    @foreach ($extractedItems as $index => $item)
                        <div class="rounded-lg border border-line bg-panel-muted/20 p-4 space-y-3" wire:key="extracted-{{ $index }}">
                            <div class="flex items-start justify-between gap-2">
                                {{-- Category --}}
                                <div class="flex-1 min-w-0">
                                    <label class="font-mono text-[9px] font-bold uppercase tracking-[0.12em] text-ink-soft">{{ __('admin.snap_category') }}</label>
                                    <input
                                        type="text"
                                        wire:model="extractedItems.{{ $index }}.category_en"
                                        class="field mt-1 text-xs"
                                        placeholder="Category"
                                    >
                                </div>
                                {{-- Remove --}}
                                @if (count($extractedItems) > 1)
                                    <button
                                        type="button"
                                        wire:click="removeExtractedItem({{ $index }})"
                                        class="mt-4 text-ink-soft hover:text-alert transition-colors"
                                        title="{{ __('admin.snap_remove_item') }}"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                    </button>
                                @endif
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="font-mono text-[9px] font-bold uppercase tracking-[0.12em] text-ink-soft">{{ __('admin.snap_name_en') }}</label>
                                    <input
                                        type="text"
                                        wire:model="extractedItems.{{ $index }}.name_en"
                                        class="field mt-1 text-xs"
                                    >
                                </div>
                                <div>
                                    <label class="font-mono text-[9px] font-bold uppercase tracking-[0.12em] text-ink-soft">{{ __('admin.snap_name_ar') }}</label>
                                    <input
                                        type="text"
                                        wire:model="extractedItems.{{ $index }}.name_ar"
                                        class="field mt-1 text-xs"
                                        dir="rtl"
                                    >
                                </div>
                            </div>

                            <div class="flex items-end gap-3">
                                <div class="flex-1">
                                    <label class="font-mono text-[9px] font-bold uppercase tracking-[0.12em] text-ink-soft">{{ __('admin.snap_description') }}</label>
                                    <input
                                        type="text"
                                        wire:model="extractedItems.{{ $index }}.description_en"
                                        class="field mt-1 text-xs"
                                        placeholder="Optional"
                                    >
                                </div>
                                <div class="w-28">
                                    <label class="font-mono text-[9px] font-bold uppercase tracking-[0.12em] text-ink-soft">{{ __('admin.snap_price') }}</label>
                                    <div class="relative mt-1">
                                        <input
                                            type="number"
                                            step="0.001"
                                            min="0"
                                            wire:model="extractedItems.{{ $index }}.price"
                                            class="field text-xs font-mono pr-12"
                                            placeholder="0.000"
                                        >
                                        <span class="absolute right-3 top-1/2 -translate-y-1/2 font-mono text-[10px] font-bold uppercase text-ink-soft">{{ $currency_code }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Add item button --}}
                <button
                    type="button"
                    wire:click="addExtractedItem"
                    class="inline-flex items-center gap-1.5 font-mono text-[10px] font-bold uppercase tracking-[0.16em] text-ink-soft hover:text-ink transition-colors"
                >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                    {{ __('admin.snap_add_item') }}
                </button>

                {{-- Actions --}}
                <div class="flex items-center justify-between pt-4">
                    <button type="button" wire:click="previousStep" class="btn-secondary">
                        {{ __('admin.onboarding_back') }}
                    </button>
                    <div class="flex items-center gap-3">
                        <button type="button" wire:click="nextStep" class="btn-secondary">
                            {{ __('admin.onboarding_skip') }}
                        </button>
                        <button type="button" wire:click="saveExtractedMenu" class="btn-primary">
                            <span wire:loading.remove wire:target="saveExtractedMenu">
                                {{ __('admin.snap_save_menu') }}
                            </span>
                            <span wire:loading wire:target="saveExtractedMenu" class="inline-flex items-center gap-2">
                                <span class="loading-spinner"></span>
                                {{ __('admin.onboarding_saving') }}
                            </span>
                        </button>
                    </div>
                </div>
            </div>

        {{-- ── Mode: Manual Entry (original form) ── --}}
        @elseif ($menuMode === 'manual')
            <form wire:submit.prevent="saveMenuItems" class="space-y-4">
                <div class="flex items-center justify-between">
                    <p class="text-sm text-ink-soft">
                        {{ __('admin.onboarding_menu_items_hint') }}
                    </p>
                    <button type="button" wire:click="resetExtraction" class="font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft hover:text-ink transition-colors">
                        {{ __('admin.snap_upload_button') }}
                    </button>
                </div>

                <div class="space-y-3">
                    @foreach ($menuItems as $index => $item)
                        <div class="flex items-start gap-3" wire:key="menu-item-{{ $index }}">
                            <div class="flex-1 space-y-1.5">
                                <input
                                    type="text"
                                    wire:model="menuItems.{{ $index }}.name"
                                    class="field"
                                    placeholder="{{ __('admin.onboarding_item_name_placeholder') }}"
                                >
                                @error("menuItems.{$index}.name") <p class="text-alert text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div class="w-32 space-y-1.5">
                                <div class="relative">
                                    <input
                                        type="number"
                                        step="0.001"
                                        min="0"
                                        wire:model="menuItems.{{ $index }}.price"
                                        class="field font-mono pr-12"
                                        placeholder="0.000"
                                    >
                                    <span class="absolute right-3 top-1/2 -translate-y-1/2 font-mono text-[10px] font-bold uppercase text-ink-soft">{{ $currency_code }}</span>
                                </div>
                                @error("menuItems.{$index}.price") <p class="text-alert text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            @if (count($menuItems) > 1)
                                <button
                                    type="button"
                                    wire:click="removeMenuItem({{ $index }})"
                                    class="mt-2.5 text-ink-soft hover:text-alert transition-colors"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                </button>
                            @endif
                        </div>
                    @endforeach
                </div>

                @if (count($menuItems) < 10)
                    <button
                        type="button"
                        wire:click="addMenuItem"
                        class="inline-flex items-center gap-1.5 font-mono text-[10px] font-bold uppercase tracking-[0.16em] text-ink-soft hover:text-ink transition-colors"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                        {{ __('admin.onboarding_add_another') }}
                    </button>
                @endif

                <div class="flex items-center justify-between pt-4">
                    <button type="button" wire:click="previousStep" class="btn-secondary">
                        {{ __('admin.onboarding_back') }}
                    </button>
                    <div class="flex items-center gap-3">
                        <button type="button" wire:click="nextStep" class="btn-secondary">
                            {{ __('admin.onboarding_skip') }}
                        </button>
                        <button type="submit" class="btn-primary">
                            <span wire:loading.remove wire:target="saveMenuItems">{{ __('admin.onboarding_save_continue') }}</span>
                            <span wire:loading wire:target="saveMenuItems" class="inline-flex items-center gap-2">
                                <span class="loading-spinner"></span>
                                {{ __('admin.onboarding_saving') }}
                            </span>
                        </button>
                    </div>
                </div>
            </form>
        @endif
    </div>
@endif
```

**Step 2: Commit**

```bash
git add resources/views/livewire/onboarding-wizard.blade.php
git commit -m "feat: add Snap-to-Menu UI to onboarding Step 3 with upload, review, and manual modes"
```

---

### Task 6: Write Livewire Component Tests

**Files:**
- Create: `tests/Feature/Livewire/OnboardingSnapMenuTest.php`

**Step 1: Write tests for the extraction flow**

```php
<?php

namespace Tests\Feature\Livewire;

use App\Livewire\OnboardingWizard;
use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class OnboardingSnapMenuTest extends TestCase
{
    private Shop $shop;
    private User $admin;

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
            ->call('resetExtraction')
            ->assertSet('menuMode', 'choose')
            ->assertSet('extractedItems', []);
    }

    public function test_extract_menu_calls_gemini_and_shows_review(): void
    {
        Storage::fake('local');

        $geminiResponse = [
            'candidates' => [[
                'content' => [
                    'parts' => [[
                        'text' => json_encode([
                            [
                                'category_en' => 'Hot Drinks',
                                'category_ar' => 'مشروبات ساخنة',
                                'name_en' => 'Karak Tea',
                                'name_ar' => 'شاي كرك',
                                'description_en' => 'Spiced tea',
                                'description_ar' => 'شاي بالتوابل',
                                'price' => 0.500,
                            ],
                        ]),
                    ]],
                ],
            ]],
        ];

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response($geminiResponse),
        ]);

        $photo = UploadedFile::fake()->image('menu.jpg', 800, 600);

        Livewire::actingAs($this->admin)
            ->test(OnboardingWizard::class)
            ->set('step', 3)
            ->set('menuPhotos', [$photo])
            ->call('extractMenu')
            ->assertSet('menuMode', 'review')
            ->assertCount('extractedItems', 1);
    }

    public function test_extract_menu_handles_api_failure(): void
    {
        Storage::fake('local');

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([], 500),
        ]);

        $photo = UploadedFile::fake()->image('menu.jpg');

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
                    'category_en' => 'Hot Drinks',
                    'category_ar' => 'مشروبات ساخنة',
                    'name_en' => 'Karak Tea',
                    'name_ar' => 'شاي كرك',
                    'description_en' => 'Spiced tea',
                    'description_ar' => 'شاي بالتوابل',
                    'price' => 0.500,
                ],
                [
                    'category_en' => 'Cold Drinks',
                    'category_ar' => 'مشروبات باردة',
                    'name_en' => 'Iced Latte',
                    'name_ar' => 'لاتيه مثلج',
                    'description_en' => '',
                    'description_ar' => '',
                    'price' => 1.200,
                ],
            ])
            ->call('saveExtractedMenu')
            ->assertSet('step', 4);

        // Verify categories created
        $this->assertDatabaseHas('categories', [
            'shop_id' => $this->shop->id,
            'name_en' => 'Hot Drinks',
            'name_ar' => 'مشروبات ساخنة',
        ]);
        $this->assertDatabaseHas('categories', [
            'shop_id' => $this->shop->id,
            'name_en' => 'Cold Drinks',
            'name_ar' => 'مشروبات باردة',
        ]);

        // Verify products created
        $this->assertDatabaseHas('products', [
            'shop_id' => $this->shop->id,
            'name_en' => 'Karak Tea',
            'name_ar' => 'شاي كرك',
        ]);
        $this->assertDatabaseHas('products', [
            'shop_id' => $this->shop->id,
            'name_en' => 'Iced Latte',
            'name_ar' => 'لاتيه مثلج',
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
                    'category_en' => 'Food',
                    'category_ar' => 'طعام',
                    'name_en' => 'Shawarma',
                    'name_ar' => 'شاورما',
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
            ->call('saveExtractedMenu');

        $this->assertEquals(1, Product::where('shop_id', $this->shop->id)->count());
    }

    public function test_can_add_and_remove_extracted_items(): void
    {
        Livewire::actingAs($this->admin)
            ->test(OnboardingWizard::class)
            ->set('step', 3)
            ->set('menuMode', 'review')
            ->set('extractedItems', [
                [
                    'category_en' => 'Food',
                    'category_ar' => 'طعام',
                    'name_en' => 'Item 1',
                    'name_ar' => '',
                    'description_en' => '',
                    'description_ar' => '',
                    'price' => 1,
                ],
            ])
            ->call('addExtractedItem')
            ->assertCount('extractedItems', 2)
            ->call('removeExtractedItem', 0)
            ->assertCount('extractedItems', 1);
    }

    public function test_manual_mode_still_works(): void
    {
        Livewire::actingAs($this->admin)
            ->test(OnboardingWizard::class)
            ->set('step', 3)
            ->call('showManualEntry')
            ->set('menuItems', [
                ['name' => 'Test Item', 'price' => '1.500'],
            ])
            ->call('saveMenuItems')
            ->assertSet('step', 4);

        $this->assertDatabaseHas('products', [
            'shop_id' => $this->shop->id,
            'name_en' => 'Test Item',
        ]);
    }

    public function test_validates_photo_upload(): void
    {
        Storage::fake('local');

        $badFile = UploadedFile::fake()->create('menu.exe', 100, 'application/x-msdownload');

        Livewire::actingAs($this->admin)
            ->test(OnboardingWizard::class)
            ->set('step', 3)
            ->set('menuPhotos', [$badFile])
            ->call('extractMenu')
            ->assertHasErrors('menuPhotos.*');
    }
}
```

**Step 2: Run tests**

```bash
php artisan test --filter=OnboardingSnapMenuTest
```

Expected: All tests PASS.

**Step 3: Commit**

```bash
git add tests/Feature/Livewire/OnboardingSnapMenuTest.php
git commit -m "test: add Snap-to-Menu feature tests for onboarding extraction flow"
```

---

### Task 7: Run Full Test Suite + Lint

**Step 1: Run Pint (code style)**

```bash
./vendor/bin/pint
```

Fix any issues.

**Step 2: Run full test suite**

```bash
composer test
```

Expected: All tests pass (existing + new).

**Step 3: Commit any lint fixes**

```bash
git add -A
git commit -m "chore: apply Pint code style fixes"
```

---

### Task 8: Update Notion Roadmap

After committing, update the Bite-POS Notion page (ID: `31f499aa-39c9-8154-90f0-ddad78ba4dfe`):

1. Update the **Feature Proposal: Snap-to-Menu** section status from "Proposal" to "Shipped"
2. Add to the **Completed** features table
3. Update test count in **At a Glance** section

---

### Summary of All Files

| File | Action |
|------|--------|
| `config/services.php` | Modify — add `gemini` config |
| `.env.example` | Modify — add `GEMINI_API_KEY` |
| `app/Services/MenuExtractionService.php` | **Create** — Gemini API integration |
| `tests/Unit/Services/MenuExtractionServiceTest.php` | **Create** — 6 unit tests |
| `lang/en/admin.php` | Modify — add 18 translation keys |
| `lang/ar/admin.php` | Modify — add 18 translation keys |
| `app/Livewire/OnboardingWizard.php` | Modify — add file upload + extraction flow |
| `resources/views/livewire/onboarding-wizard.blade.php` | Modify — Step 3 UI overhaul |
| `tests/Feature/Livewire/OnboardingSnapMenuTest.php` | **Create** — 9 feature tests |
