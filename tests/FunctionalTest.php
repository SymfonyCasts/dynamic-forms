<?php

/*
 * This file is part of the SymfonyCasts DynamicForms package.
 * Copyright (c) SymfonyCasts <https://symfonycasts.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfonycasts\DynamicForms\Tests;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfonycasts\DynamicForms\Tests\fixtures\DynamicFormsTestKernel;
use Zenstruck\Browser\Test\HasBrowser;

class FunctionalTest extends KernelTestCase
{
    use HasBrowser;

    public function testDynamicFields()
    {
        $browser = $this->browser();
        $browser->visit('/form')
            // check for the hidden field
            ->assertSeeElement('#test_dynamic_form___dynamic_error')
            ->assertSee('Is Form Valid: no')
            // Breakfast is the pre-selected meal
            ->assertSee('What is for Breakfast?')
            ->assertContains('<option value="bacon">')
            ->assertNotContains('<option value="pizza">')
            ->assertNotContains('What size pizza?')
        ;

        // change the meal to dinner
        $browser->selectFieldOption('Meal', 'Dinner')
            ->click('Submit Form')
            // changing the field doesn't cause any issues
            ->assertSee('Is Form Valid: yes')
            ->assertNotContains('<option value="bacon">')
            ->assertContains('<option value="pizza">')
            ->assertNotContains('What size pizza?')
        ;

        // now select Pizza!
        $browser->selectFieldOption('Main food', 'Pizza')
            ->click('Submit Form')
            ->assertSee('Is Form Valid: yes')
            ->assertContains('<option value="pizza" selected="selected">')
            ->assertContains('What size pizza?')
        ;

        // select the size
        $browser->selectFieldOption('Pizza size', '14 inch')
            ->click('Submit Form')
            ->assertSee('Is Form Valid: yes')
            ->assertContains('<option value="pizza" selected="selected">')
            ->assertContains('<option value="14" selected="selected">')
        ;

        // now change the meal to breakfast
        $browser->selectFieldOption('Meal', 'Breakfast')
            ->click('Submit Form')
            // form is not valid: the mainFood submitted an invalid value
            ->assertSee('Is Form Valid: no')
            ->assertContains('<option value="bacon">')
            ->assertNotContains('<option value="pizza"')
            ->assertNotContains('What size pizza?')
        ;

        // select a valid food for breakfast
        $browser->selectFieldOption('Main food', 'Bacon')
            ->click('Submit Form')
            // form is valid again
            ->assertSee('Is Form Valid: yes')
        ;

        // change the meal again
        $browser->selectFieldOption('Meal', 'Lunch')
            ->click('Submit Form')
            // form is not valid: the mainFood=bacon is invalid for lunch
            ->assertSee('Is Form Valid: no')
        ;
    }

    public function testRecursiveDynamicFields()
    {
        $browser = $this->pantherBrowser();
        $browser->visit('/form-pizza-selected')
            // check for the hidden field
            ->assertSeeElement('#test_dynamic_form___dynamic_error')
            ->assertSee('Is Form Valid: no')
            ->assertSee('Pizza 🍕')
            ->assertNotContains('<option value="bacon">')
            ->assertContains('<option value="pizza" selected="selected">')
            ->assertContains('What size pizza?')
        ;

        // now change the meal to breakfast
        $browser->selectFieldOption('Meal', 'Breakfast')
            ->click('Submit Form')
            // form is not valid: the mainFood submitted an invalid value
            ->assertSee('Is Form Valid: no')
            ->assertContains('<option value="bacon">')
            ->dump('form')
            ->assertNotContains('<option value="pizza"')
            ->assertNotContains('What size pizza?')
        ;

        // select a valid food for breakfast
        $browser->selectFieldOption('Main food', 'Bacon')
            ->click('Submit Form')
            // form is valid again
            ->assertSee('Is Form Valid: yes')
        ;

        // change the meal again
        $browser->selectFieldOption('Meal', 'Lunch')
            ->click('Submit Form')
            // form is not valid: the mainFood=bacon is invalid for lunch
            ->assertSee('Is Form Valid: no')
        ;
    }

    protected static function getKernelClass(): string
    {
        return DynamicFormsTestKernel::class;
    }
}
