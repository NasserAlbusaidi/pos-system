<?php

namespace Tests\Browser\Pages;

use Laravel\Dusk\Page;

class SettingsPage extends Page
{
    public function url(): string
    {
        return '/settings';
    }

    public function assert($browser): void
    {
        $browser->assertPathIs($this->url());
    }

    public function elements(): array
    {
        return [
            '@shop-name' => '[wire\\:model="name"]',
            '@tax-rate' => '[wire\\:model="tax_rate"]',
            '@save-btn' => '[wire\\:submit\\.prevent="save"]',
            '@add-staff' => '[wire\\:click*="addStaff"]',
        ];
    }
}
