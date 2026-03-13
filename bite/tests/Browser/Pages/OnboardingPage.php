<?php

namespace Tests\Browser\Pages;

use Laravel\Dusk\Page;

class OnboardingPage extends Page
{
    public function url(): string
    {
        return '/onboarding';
    }

    public function assert($browser): void
    {
        $browser->assertPathIs($this->url());
    }

    public function elements(): array
    {
        return [
            '@next-step' => '[wire\\:click*="nextStep"]',
            '@prev-step' => '[wire\\:click*="previousStep"]',
            '@complete' => '[wire\\:click*="completeOnboarding"]',
        ];
    }
}
