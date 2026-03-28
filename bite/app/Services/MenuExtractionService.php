<?php

namespace App\Services;

use App\Exceptions\MenuExtractionException;
use Illuminate\Support\Facades\Http;

class MenuExtractionService
{
    /**
     * Extract menu items from base64-encoded images using Gemini Flash 2.0.
     *
     * @param  array<int, array{mime_type: string, data: string}>  $images
     * @return array<int, array{category_en: string, category_ar: string, name_en: string, name_ar: string, description_en: string, description_ar: string, price: float}>
     *
     * @throws MenuExtractionException
     */
    public function extract(array $images): array
    {
        $apiKey = config('services.gemini.api_key');

        if (! $apiKey) {
            throw new MenuExtractionException('api_key', 'Gemini API key not configured');
        }

        $model = config('services.gemini.model', 'gemini-2.5-flash');
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

        $parts = $this->buildRequestParts($images);

        try {
            $response = Http::timeout(60)->connectTimeout(15)->post($url, [
                'contents' => [
                    [
                        'parts' => $parts,
                    ],
                ],
                'generationConfig' => [
                    'temperature' => 0.1,
                    'responseMimeType' => 'application/json',
                ],
            ]);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            throw new MenuExtractionException('timeout', 'Request timed out or could not connect', $e);
        }

        if ($response->failed()) {
            $status = $response->status();
            $reason = match (true) {
                $status === 429 => 'rate_limit',
                $status === 400 => 'invalid_image',
                $status === 401 || $status === 403 => 'api_key',
                default => 'api_error',
            };

            error_log("Snap-to-Menu Gemini error: HTTP {$status} — ".$response->body());
            throw new MenuExtractionException($reason, "Gemini API returned HTTP {$status}");
        }

        $text = $this->extractTextFromResponse($response->json());
        $items = $this->parseJsonResponse($text);

        return $this->normalizeItems($items);
    }

    /**
     * Build the multimodal request parts: prompt text + inline image data.
     *
     * @param  array<int, array{mime_type: string, data: string}>  $images
     * @return array<int, array<string, mixed>>
     */
    private function buildRequestParts(array $images): array
    {
        $parts = [
            ['text' => $this->getExtractionPrompt()],
        ];

        foreach ($images as $image) {
            $parts[] = [
                'inline_data' => [
                    'mime_type' => $image['mime_type'],
                    'data' => $image['data'],
                ],
            ];
        }

        return $parts;
    }

    /**
     * Get the structured extraction prompt for Gemini.
     */
    private function getExtractionPrompt(): string
    {
        return <<<'PROMPT'
Extract ALL food and drink menu items from the provided menu image(s).

Return a JSON array where each element has these exact fields:
- "category_en": English category name (use "Menu" if not visible)
- "category_ar": Arabic category name (use "القائمة" if not visible)
- "name_en": English item name
- "name_ar": Arabic item name
- "description_en": English description (empty string if not visible)
- "description_ar": Arabic description (empty string if not visible)
- "price": Numeric price as a decimal number

Rules:
- If the menu is Arabic-only, generate English translations for names and descriptions
- If the menu is English-only, generate Arabic translations for names and descriptions
- Use the lower price if a price range is shown
- Use 0 if no price is visible for an item
- Only extract food and drink items — skip headers, decorative text, logos, and non-menu content
- Prices must be numeric only (no currency symbols)
- Return an empty array if no menu items are found
PROMPT;
    }

    /**
     * Extract the text content from the Gemini API response structure.
     *
     * @throws MenuExtractionException
     */
    private function extractTextFromResponse(?array $response): string
    {
        $text = $response['candidates'][0]['content']['parts'][0]['text'] ?? null;

        if ($text === null) {
            throw new MenuExtractionException('parse_error', 'Unexpected Gemini response structure — no text candidate returned');
        }

        return $text;
    }

    /**
     * Parse JSON from the response text, handling markdown code fence wrapping.
     *
     * @return array<int, array<string, mixed>>
     *
     * @throws MenuExtractionException
     */
    private function parseJsonResponse(string $text): array
    {
        $cleaned = $this->stripMarkdownCodeFence($text);

        $decoded = json_decode($cleaned, true);

        if (! is_array($decoded)) {
            throw new MenuExtractionException('parse_error', 'Gemini response was not valid JSON');
        }

        return $decoded;
    }

    /**
     * Strip markdown code fence wrapping (```json ... ```) if present.
     */
    private function stripMarkdownCodeFence(string $text): string
    {
        $trimmed = trim($text);

        if (preg_match('/^```(?:json)?\s*\n?(.*?)\n?\s*```$/s', $trimmed, $matches)) {
            return trim($matches[1]);
        }

        return $trimmed;
    }

    /**
     * Normalize and filter extracted menu items.
     *
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array{category_en: string, category_ar: string, name_en: string, name_ar: string, description_en: string, description_ar: string, price: float}>
     */
    private function normalizeItems(array $items): array
    {
        $normalized = [];

        foreach ($items as $item) {
            $nameEn = trim((string) ($item['name_en'] ?? ''));
            $nameAr = trim((string) ($item['name_ar'] ?? ''));

            // Skip items where both names are empty
            if ($nameEn === '' && $nameAr === '') {
                continue;
            }

            $normalized[] = [
                'category_en' => trim((string) ($item['category_en'] ?? 'Menu')),
                'category_ar' => trim((string) ($item['category_ar'] ?? 'القائمة')),
                'name_en' => $nameEn,
                'name_ar' => $nameAr,
                'description_en' => trim((string) ($item['description_en'] ?? '')),
                'description_ar' => trim((string) ($item['description_ar'] ?? '')),
                'price' => max(0.0, (float) ($item['price'] ?? 0)),
            ];
        }

        return array_values($normalized);
    }
}
