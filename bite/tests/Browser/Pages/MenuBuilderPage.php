<?php

namespace Tests\Browser\Pages;

use Laravel\Dusk\Page;

class MenuBuilderPage extends Page
{
    public function url(): string
    {
        return '/menu-builder';
    }

    public function assert($browser): void
    {
        $browser->assertPathIs($this->url());
    }

    public function elements(): array
    {
        return [
            '@search' => '[wire\\:model\\.live="search"]',
            '@add-category' => '[wire\\:click*="createCategory"]',
            '@toggle-visibility' => '[wire\\:click*="toggleVisibility"]',
        ];
    }
}
