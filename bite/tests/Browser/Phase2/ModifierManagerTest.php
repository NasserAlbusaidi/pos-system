<?php

namespace Tests\Browser\Phase2;

use Laravel\Dusk\Browser;
use Tests\Browser\Traits\SeedsTestData;
use Tests\DuskTestCase;

class ModifierManagerTest extends DuskTestCase
{
    use SeedsTestData;

    public function test_can_create_modifier_group_and_add_options(): void
    {
        [$shop, $admin] = $this->createShopWithAdmin();

        $this->browse(function (Browser $browser) use ($admin) {
            // Login and navigate to modifier management
            $browser->loginAs($admin)
                ->visit('/modifiers')
                // The h2 heading "Create New Group" has no uppercase CSS class
                ->waitForText('Create New Group');

            // Fill in the group form
            $browser->type('input[wire\\:model="name_en"]', 'Milk Choice')
                ->type('input[wire\\:model="name_ar"]', 'نوع الحليب')
                ->clear('input[wire\\:model="min_selection"]')
                ->type('input[wire\\:model="min_selection"]', '0')
                ->clear('input[wire\\:model="max_selection"]')
                ->type('input[wire\\:model="max_selection"]', '3')
                // btn-primary has CSS text-transform:uppercase, so press() must use the uppercase text
                ->press('SAVE GROUP');

            // Group names are uppercased via CSS text-transform in the list
            $browser->waitForText('MILK CHOICE')
                ->assertSee('MILK CHOICE')
                ->assertSee('Rule: Select 0-3');

            // Click the group row to select it — this reveals the "Add Option" form
            $browser->click('div[wire\\:click*="selectedGroupId"]')
                // The h2 "Add Option" has no uppercase CSS class
                ->waitForText('Add Option');

            // Fill in the option form
            $browser->type('input[wire\\:model="optionNameEn"]', 'Oat Milk')
                ->type('input[wire\\:model="optionNameAr"]', 'حليب الشوفان')
                ->clear('input[wire\\:model="optionPrice"]')
                ->type('input[wire\\:model="optionPrice"]', '0.500')
                // btn-primary has CSS text-transform:uppercase, so press() must use the uppercase text
                ->press('ADD OPTION');

            // Option names are uppercased via CSS text-transform in the option list
            $browser->waitForText('OAT MILK')
                ->assertSee('OAT MILK')
                ->assertSee('+OMR 0.500');
        });
    }
}
