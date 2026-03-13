<?php

namespace Tests\Browser\Pages;

use Laravel\Dusk\Page;

class LoginPage extends Page
{
    public function url(): string
    {
        return '/login';
    }

    public function assert($browser): void
    {
        $browser->assertPathIs($this->url());
    }

    public function elements(): array
    {
        return [
            '@email' => 'input[name="email"]',
            '@password' => 'input[name="password"]',
            '@submit' => 'button[type="submit"]',
        ];
    }
}
