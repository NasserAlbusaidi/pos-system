<?php

namespace Tests\Browser\Pages;

use Laravel\Dusk\Page;

class GuestMenuPage extends Page
{
    protected string $slug;

    public function __construct(string $slug = 'demo')
    {
        $this->slug = $slug;
    }

    public function url(): string
    {
        return '/menu/'.$this->slug;
    }

    public function assert($browser): void
    {
        $browser->assertPathIs($this->url());
    }

    public function elements(): array
    {
        return [
            '@add-to-cart' => '[wire\\:click*="addToCart"]',
            '@review-btn' => '[wire\\:click*="toggleReview"]',
            '@submit-order' => '[wire\\:click*="submitOrder"]',
            '@lang-en' => '[wire\\:click*="switchLanguage"][wire\\:click*="en"]',
            '@lang-ar' => '[wire\\:click*="switchLanguage"][wire\\:click*="ar"]',
        ];
    }
}
