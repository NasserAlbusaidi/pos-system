<?php

namespace Tests\Browser\Pages;

use Laravel\Dusk\Page;

class SuperAdminPage extends Page
{
    public function url(): string
    {
        return '/admin';
    }

    public function assert($browser): void
    {
        $browser->assertPathIs($this->url());
    }

    public function elements(): array
    {
        return [
            '@shop-list' => '[wire\\:click*="toggleStatus"]',
            '@impersonate' => 'a[href*="impersonate"]',
        ];
    }
}
