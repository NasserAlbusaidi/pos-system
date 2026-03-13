<?php

namespace Tests\Browser\Pages;

use Laravel\Dusk\Page;

class ReportsPage extends Page
{
    public function url(): string
    {
        return '/reports';
    }

    public function assert($browser): void
    {
        $browser->assertPathIs($this->url());
    }

    public function elements(): array
    {
        return [
            '@date-range' => '[wire\\:model="rangeDays"]',
        ];
    }
}
