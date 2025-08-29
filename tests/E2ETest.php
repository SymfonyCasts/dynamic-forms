<?php

/*
 * This file is part of the SymfonyCasts DynamicForms package.
 * Copyright (c) SymfonyCasts <https://symfonycasts.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfonycasts\DynamicForms\Tests;

use Symfony\Component\Panther\PantherTestCase;
use Symfonycasts\DynamicForms\Tests\fixtures\DynamicFormsTestKernel;
use Zenstruck\Browser\Test\HasBrowser;

class E2ETest extends PantherTestCase
{
    use HasBrowser;

    public function testRecursiveDynamicFields()
    {
        $browser = $this->pantherBrowser();
        $browser->visit('/form-pizza-selected')
            // check for the hidden field
            ->waitUntilSeeIn('//html', 'Is Form Valid: no')
            ->assertSeeElement('#test_dynamic_form___dynamic_error')
            ->assertSee('Pizza 🍕')
            ->assertNotContains('<option value="bacon">')
            ->assertContains('<option value="pizza" selected="selected">')
            ->assertContains('What size pizza?')
        ;

        // now change the meal to breakfast
        $browser->selectFieldOption('Meal', 'Breakfast')
            ->click('Submit Form')
            // form is not valid: the mainFood submitted an invalid value
            ->waitUntilSeeIn('//html', 'Is Form Valid: no')
            ->assertContains('<option value="bacon">')
            ->assertNotContains('<option value="pizza"')
            ->assertNotContains('What size pizza?')
        ;

        // select a valid food for breakfast
        $browser->selectFieldOption('Main food', 'Bacon')
            ->click('Submit Form')
            // form is valid again
            ->waitUntilSeeIn('//html', 'Is Form Valid: yes')
        ;

        // change the meal again
        $browser->selectFieldOption('Meal', 'Lunch')
            ->click('Submit Form')
            // form is not valid: the mainFood=bacon is invalid for lunch
            ->waitUntilSeeIn('//html', 'Is Form Valid: no')
        ;
    }

    protected static function getKernelClass(): string
    {
        return DynamicFormsTestKernel::class;
    }
}
