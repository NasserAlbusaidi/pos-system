<?php

namespace Tests\Browser\Pages;

use Laravel\Dusk\Page;

class PinLoginPage extends Page
{
    protected string $slug;

    public function __construct(string $slug = 'demo')
    {
        $this->slug = $slug;
    }

    public function url(): string
    {
        return '/pos/pin/' . $this->slug;
    }

    public function assert($browser): void
    {
        $browser->assertPathIs($this->url());
    }

    public function elements(): array
    {
        return [
            '@pin-input' => 'input[type="password"]',
            '@unlock-btn' => 'button[type="submit"]',
        ];
    }
}
