<?php

namespace Tests\Browser\Pages;

use Laravel\Dusk\Page;

class DashboardPage extends Page
{
    public function url(): string
    {
        return '/dashboard';
    }

    public function assert($browser): void
    {
        $browser->assertPathIs($this->url());
    }

    public function elements(): array
    {
        return [
            '@daily-revenue' => '.metric-value',
            '@notification-bell' => '[wire\\:click*="toggleNotifications"]',
        ];
    }
}
