<?php

namespace Tests\Browser\Pages;

use Laravel\Dusk\Page;

class PosPage extends Page
{
    public function url(): string
    {
        return '/pos';
    }

    public function assert($browser): void
    {
        $browser->assertPathIs($this->url());
    }

    public function elements(): array
    {
        return [
            '@order-card' => '.surface-card',
            '@pay-cash' => '[wire\\:click*="markAsPaid"][wire\\:click*="cash"]',
            '@pay-card' => '[wire\\:click*="markAsPaid"][wire\\:click*="card"]',
            '@split-btn' => '[wire\\:click*="openSplit"]',
        ];
    }
}
