<?php

namespace Tests\Browser\Pages;

use Laravel\Dusk\Page;

class BillingPage extends Page
{
    public function url(): string
    {
        return '/billing';
    }

    public function assert($browser): void
    {
        $browser->assertPathIs($this->url());
    }

    public function elements(): array
    {
        return [];
    }
}
