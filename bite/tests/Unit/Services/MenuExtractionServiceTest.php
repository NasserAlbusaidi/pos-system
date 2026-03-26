<?php

namespace Tests\Unit\Services;

use App\Exceptions\MenuExtractionException;
use App\Services\MenuExtractionService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MenuExtractionServiceTest extends TestCase
{
    private MenuExtractionService $service;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.gemini.api_key' => 'test-key']);
        config(['services.gemini.model' => 'gemini-2.5-flash']);

        $this->service = new MenuExtractionService;
    }

    public function test_extract_returns_structured_menu_items(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => json_encode([
                                        [
                                            'category_en' => 'Beverages',
                                            'category_ar' => 'مشروبات',
                                            'name_en' => 'Karak Tea',
                                            'name_ar' => 'شاي كرك',
                                            'description_en' => 'Traditional spiced tea',
                                            'description_ar' => 'شاي بالتوابل التقليدي',
                                            'price' => 0.500,
                                        ],
                                        [
                                            'category_en' => 'Beverages',
                                            'category_ar' => 'مشروبات',
                                            'name_en' => 'Turkish Coffee',
                                            'name_ar' => 'قهوة تركية',
                                            'description_en' => 'Strong brewed coffee',
                                            'description_ar' => 'قهوة مخمرة قوية',
                                            'price' => 0.750,
                                        ],
                                    ]),
                                ],
                            ],
                        ],
                    ],
                ],
            ]),
        ]);

        $images = [
            ['mime_type' => 'image/jpeg', 'data' => base64_encode('fake-image-data')],
        ];

        $result = $this->service->extract($images);

        $this->assertCount(2, $result);
        $this->assertSame('Karak Tea', $result[0]['name_en']);
        $this->assertSame('شاي كرك', $result[0]['name_ar']);
        $this->assertSame('Beverages', $result[0]['category_en']);
        $this->assertSame(0.500, $result[0]['price']);
        $this->assertSame('Turkish Coffee', $result[1]['name_en']);
        $this->assertSame(0.750, $result[1]['price']);
    }

    public function test_extract_throws_api_error_on_500_failure(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response('Server Error', 500),
        ]);

        $images = [
            ['mime_type' => 'image/jpeg', 'data' => base64_encode('fake-image-data')],
        ];

        $this->expectException(MenuExtractionException::class);

        try {
            $this->service->extract($images);
        } catch (MenuExtractionException $e) {
            $this->assertSame('api_error', $e->reason);
            throw $e;
        }
    }

    public function test_extract_throws_rate_limit_on_429(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response('Too Many Requests', 429),
        ]);

        $images = [
            ['mime_type' => 'image/jpeg', 'data' => base64_encode('fake-image-data')],
        ];

        try {
            $this->service->extract($images);
            $this->fail('Expected MenuExtractionException was not thrown');
        } catch (MenuExtractionException $e) {
            $this->assertSame('rate_limit', $e->reason);
        }
    }

    public function test_extract_throws_api_key_on_401(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response('Unauthorized', 401),
        ]);

        $images = [
            ['mime_type' => 'image/jpeg', 'data' => base64_encode('fake-image-data')],
        ];

        try {
            $this->service->extract($images);
            $this->fail('Expected MenuExtractionException was not thrown');
        } catch (MenuExtractionException $e) {
            $this->assertSame('api_key', $e->reason);
        }
    }

    public function test_extract_throws_invalid_image_on_400(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response('Bad Request', 400),
        ]);

        $images = [
            ['mime_type' => 'image/jpeg', 'data' => base64_encode('fake-image-data')],
        ];

        try {
            $this->service->extract($images);
            $this->fail('Expected MenuExtractionException was not thrown');
        } catch (MenuExtractionException $e) {
            $this->assertSame('invalid_image', $e->reason);
        }
    }

    public function test_extract_throws_parse_error_on_invalid_json_response(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => 'This is not valid JSON at all'],
                            ],
                        ],
                    ],
                ],
            ]),
        ]);

        $images = [
            ['mime_type' => 'image/jpeg', 'data' => base64_encode('fake-image-data')],
        ];

        try {
            $this->service->extract($images);
            $this->fail('Expected MenuExtractionException was not thrown');
        } catch (MenuExtractionException $e) {
            $this->assertSame('parse_error', $e->reason);
        }
    }

    public function test_extract_throws_parse_error_on_unexpected_response_structure(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [],
            ]),
        ]);

        $images = [
            ['mime_type' => 'image/jpeg', 'data' => base64_encode('fake-image-data')],
        ];

        try {
            $this->service->extract($images);
            $this->fail('Expected MenuExtractionException was not thrown');
        } catch (MenuExtractionException $e) {
            $this->assertSame('parse_error', $e->reason);
        }
    }

    public function test_extract_handles_json_wrapped_in_markdown_code_block(): void
    {
        $jsonPayload = json_encode([
            [
                'category_en' => 'Main',
                'category_ar' => 'رئيسي',
                'name_en' => 'Shawarma',
                'name_ar' => 'شاورما',
                'description_en' => 'Grilled meat wrap',
                'description_ar' => 'لفائف اللحم المشوي',
                'price' => 1.200,
            ],
        ]);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => "```json\n{$jsonPayload}\n```"],
                            ],
                        ],
                    ],
                ],
            ]),
        ]);

        $images = [
            ['mime_type' => 'image/jpeg', 'data' => base64_encode('fake-image-data')],
        ];

        $result = $this->service->extract($images);

        $this->assertCount(1, $result);
        $this->assertSame('Shawarma', $result[0]['name_en']);
    }

    public function test_extract_filters_items_with_missing_name(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
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
                                            'description_en' => 'Milk coffee',
                                            'description_ar' => 'قهوة بالحليب',
                                            'price' => 1.000,
                                        ],
                                        [
                                            'category_en' => 'Header',
                                            'category_ar' => '',
                                            'name_en' => '',
                                            'name_ar' => '',
                                            'description_en' => '',
                                            'description_ar' => '',
                                            'price' => 0,
                                        ],
                                        [
                                            'category_en' => 'Food',
                                            'category_ar' => 'طعام',
                                            'name_en' => '',
                                            'name_ar' => 'فلافل',
                                            'description_en' => '',
                                            'description_ar' => 'فلافل مقرمشة',
                                            'price' => 0.800,
                                        ],
                                    ]),
                                ],
                            ],
                        ],
                    ],
                ],
            ]),
        ]);

        $images = [
            ['mime_type' => 'image/jpeg', 'data' => base64_encode('fake-image-data')],
        ];

        $result = $this->service->extract($images);

        // Item with both empty names removed, item with at least one name kept
        $this->assertCount(2, $result);
        $this->assertSame('Latte', $result[0]['name_en']);
        $this->assertSame('فلافل', $result[1]['name_ar']);
    }

    public function test_extract_sends_multiple_images(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => json_encode([
                                        [
                                            'category_en' => 'Menu',
                                            'category_ar' => 'القائمة',
                                            'name_en' => 'Hummus',
                                            'name_ar' => 'حمص',
                                            'description_en' => 'Chickpea dip',
                                            'description_ar' => 'غموس الحمص',
                                            'price' => 0.900,
                                        ],
                                    ]),
                                ],
                            ],
                        ],
                    ],
                ],
            ]),
        ]);

        $images = [
            ['mime_type' => 'image/jpeg', 'data' => base64_encode('fake-image-1')],
            ['mime_type' => 'image/png', 'data' => base64_encode('fake-image-2')],
            ['mime_type' => 'image/jpeg', 'data' => base64_encode('fake-image-3')],
        ];

        $this->service->extract($images);

        Http::assertSent(function ($request) {
            $body = $request->data();
            $parts = $body['contents'][0]['parts'];

            // Should have text prompt + 3 image parts
            $imageParts = array_filter($parts, fn ($part) => isset($part['inline_data']));

            return count($imageParts) === 3;
        });
    }

    public function test_extract_throws_when_no_api_key_configured(): void
    {
        config(['services.gemini.api_key' => null]);

        $service = new MenuExtractionService;

        $images = [
            ['mime_type' => 'image/jpeg', 'data' => base64_encode('fake-image-data')],
        ];

        try {
            $service->extract($images);
            $this->fail('Expected MenuExtractionException was not thrown');
        } catch (MenuExtractionException $e) {
            $this->assertSame('api_key', $e->reason);
            $this->assertStringContainsString('Gemini API key not configured', $e->getMessage());
        }
    }

    public function test_extract_throws_timeout_on_connection_exception(): void
    {
        Http::fake(function () {
            throw new ConnectionException('cURL error 28: Operation timed out');
        });

        $images = [
            ['mime_type' => 'image/jpeg', 'data' => base64_encode('fake-image-data')],
        ];

        try {
            $this->service->extract($images);
            $this->fail('Expected MenuExtractionException was not thrown');
        } catch (MenuExtractionException $e) {
            $this->assertSame('timeout', $e->reason);
        }
    }
}
