<?php

namespace Tests\Browser\Pages;

use Laravel\Dusk\Page;

class KdsPage extends Page
{
    public function url(): string
    {
        return '/kds';
    }

    public function assert($browser): void
    {
        $browser->assertPathIs($this->url());
    }

    public function elements(): array
    {
        return [
            '@kds-card' => '.kds-card',
            '@start-preparing' => '[wire\\:click*="updateStatus"][wire\\:click*="preparing"]',
            '@mark-ready' => '[wire\\:click*="updateStatus"][wire\\:click*="ready"]',
        ];
    }
}
