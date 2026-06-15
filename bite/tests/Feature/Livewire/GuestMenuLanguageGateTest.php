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
            ->assertSeeHtml('class="guest-gate"')
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
