<?php

namespace Tests\Feature\Livewire;

use App\Livewire\GuestMenu;
use App\Models\Shop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class GuestMenuLanguageGateTest extends TestCase
{
    use RefreshDatabase;

    public function test_gate_shows_when_no_language_chosen_this_session(): void
    {
        $shop = Shop::create(['name' => 'Sourdough', 'slug' => 'sourdough']);

        Livewire::test(GuestMenu::class, ['shop' => $shop])
            ->assertSet('showLanguageGate', true)
            ->assertSeeHtml('guest-gate screen web-screen language-screen')
            ->assertSee('Sourdough');
    }

    public function test_gate_hidden_when_language_already_chosen(): void
    {
        $shop = Shop::create(['name' => 'Sourdough', 'slug' => 'sourdough']);

        session(['guest_locale' => 'ar']);

        Livewire::test(GuestMenu::class, ['shop' => $shop])
            ->assertSet('showLanguageGate', false)
            ->assertSet('locale', 'ar');
    }

    public function test_hero_language_switch_labels_the_target_language(): void
    {
        $shop = Shop::create(['name' => 'Sourdough', 'slug' => 'sourdough']);

        session(['guest_locale' => 'ar']);
        $arabicHtml = Livewire::test(GuestMenu::class, ['shop' => $shop])
            ->assertSet('locale', 'ar')
            ->html();

        $this->assertStringContainsString("switchLanguage('en')", $arabicHtml);
        $this->assertStringContainsString('>EN</button>', $arabicHtml);
        $this->assertStringNotContainsString('>AR</button>', $arabicHtml);

        session(['guest_locale' => 'en']);
        $englishHtml = Livewire::test(GuestMenu::class, ['shop' => $shop])
            ->assertSet('locale', 'en')
            ->html();

        $this->assertStringContainsString("switchLanguage('ar')", $englishHtml);
        $this->assertStringContainsString('>AR</button>', $englishHtml);
    }

    public function test_choose_language_dismisses_gate_and_persists_locale(): void
    {
        $shop = Shop::create(['name' => 'Sourdough', 'slug' => 'sourdough']);

        Livewire::test(GuestMenu::class, ['shop' => $shop])
            ->assertSet('showLanguageGate', true)
            ->call('chooseLanguage', 'ar')
            ->assertSet('showLanguageGate', false)
            ->assertSet('locale', 'ar');

        $this->assertSame('ar', session('guest_locale'));
    }

    public function test_choose_language_rejects_invalid_locale(): void
    {
        $shop = Shop::create(['name' => 'Sourdough', 'slug' => 'sourdough']);

        Livewire::test(GuestMenu::class, ['shop' => $shop])
            ->call('chooseLanguage', 'fr')
            ->assertSet('locale', 'en')
            ->assertSet('showLanguageGate', false);
    }
}
